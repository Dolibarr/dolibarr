<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/hrm/evaluation_card.php
 *    \ingroup    hrm
 *    \brief      Page to create/edit/view evaluation
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluation.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/skillrank.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_evaluation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/lib/hrm_skillrank.lib.php';


// Load translation files required by the page
$langs->loadLangs(array('hrm', 'other', 'products'));  // why products?

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'evaluationcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');

// Initialize a technical objects
$object = new Evaluation($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('evaluationcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Permissions
$permissiontoread = $user->hasRight('hrm', 'evaluation', 'read');
$permissiontoadd = $user->hasRight('hrm', 'evaluation', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontovalidate = (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('hrm', 'evaluation_advance', 'validate')) || (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $permissiontoadd);
$permissiontoClose = $user->hasRight('hrm', 'evaluation', 'write');
$permissiontodelete = $user->hasRight('hrm', 'evaluation', 'delete')/* || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT)*/;
$permissiondellink = $user->hasRight('hrm', 'evaluation', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1].'/evaluation';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("hrm")) {
	accessforbidden();
}
if (!$permissiontoread || ($action === 'create' && !$permissiontoadd)) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/hrm/evaluation_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/hrm/evaluation_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'HRM_EVALUATION_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';


	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'HRM_EVALUATION_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_EVALUATION_TO';
	$trackid = 'evaluation'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	if ($action == 'saveSkill') {
		$TNote = GETPOST('TNote', 'array');
		if (!empty($TNote)) {
			foreach ($object->lines as $line) {
				$line->rankorder = $TNote[$line->fk_skill];
				$line->update($user);
			}
			setEventMessage($langs->trans("SaveLevelSkill"));
		}
	}

	if ($action == 'close') {
		// save evaldet lines to user;
		$sk = new SkillRank($db);
		$SkillrecordsForActiveUser = $sk->fetchAll('ASC', 'fk_skill', 0, 0, "(fk_object:=:".((int) $object->fk_user).") AND (objecttype:=:'".$db->escape(SkillRank::SKILLRANK_TYPE_USER)."')", 'AND');

		$errors = 0;
		// we go through the evaldets of the eval
		foreach ($object->lines as $key => $line) {
			// no reference .. we add the line to use it
			if (count($SkillrecordsForActiveUser) == 0) {
				$newSkill = new SkillRank($db);
				$resCreate = $newSkill->cloneFromCurrentSkill($line, $object->fk_user);

				if ($resCreate <= 0) {
					$errors++;
					setEventMessage($langs->trans('ErrorCreateUserSkill'), $line->fk_skill);
				}
			} else {
				//check if the skill is present to use it
				$find = false;
				$keyFind = 0;
				foreach ($SkillrecordsForActiveUser as $k => $sr) {
					if ($sr->fk_skill == $line->fk_skill) {
						$keyFind = $k;
						$find = true;
						break;
					}
				}
				//we update the skill user
				if ($find) {
					$updSkill = $SkillrecordsForActiveUser[$k];

					$updSkill->rankorder = $line->rankorder;
					$updSkill->update($user);
				} else { // sinon on ajoute la skill
					$newSkill = new SkillRank($db);
					$resCreate = $newSkill->cloneFromCurrentSkill($line, $object->fk_user);
				}
			}
		}
		if (empty($errors)) {
			$object->setStatut(Evaluation::STATUS_CLOSED);
			setEventMessage('EmployeeSkillsUpdated');
		}
	}

	if ($action == 'reopen') {
		// no update here we just change the evaluation status
		$object->setStatut(Evaluation::STATUS_VALIDATED);
	}

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// action to remove file
	if ($action == 'remove_file_comfirm') {
		// Delete file in doc form
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$upload_dir = $conf->hrm->dir_output;
		$file = $upload_dir.'/'.GETPOST('file');
		$ret = dol_delete_file($file, 0, 0, 0, $object);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}
	}
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Evaluation");
$help_url = '';
$css = array();
$css[] = '/hrm/css/style.css';
llxHeader('', $title, $help_url, '', 0, 0, '', $css);

print '<script type="text/javascript" language="javascript">
	$(document).ready(function() {
	  $("#btn_valid").click(function(){
		 var form = $("#form_save_rank");

		 $.ajax({

			 type: "POST",
			 url: form.attr("action"),
			 data: form.serialize(),
			 dataType: "json"
		 }).always(function() {
             window.location.href = "'.dol_buildpath('/hrm/evaluation_card.php', 1).'?id='.$id.'&action=validate&token='.newToken().'";
             return false;
		 });

	   });
	});
</script>';

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewEval"), '', 'object_' . $object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create", "Cancel");

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Evaluation"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = evaluationPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	if ($action == 'validate' && $permissiontovalidate) {
		// Confirm validate proposal
		$error = 0;

		// We verify whether the object is provisionally numbering
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$numref = $object->getNextNumRef();
			if (empty($numref)) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateEvaluation', $numref);
		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('HRM_EVALUATION_VALIDATE', 0, $object);
		}

		if (!$error) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateEvaluation'), $text, 'confirm_validate', '', 0, 1);
		}
	}

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteEvaluation'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
	if ($action == 'xxx') {
		$text = $langs->trans('ConfirmActionMyObject', $object->ref);

		$formquestion = array();

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/hrm/evaluation_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $langs->trans('Label').' : '.$object->label;
	$u_position = new User(($db));
	$u_position->fetch($object->fk_user);
	$morehtmlref .= '<br>'.$u_position->getNomUrl(1);
	$job = new Job($db);
	$job->fetch($object->fk_job);
	$morehtmlref .= '<br>'.$langs->trans('JobProfile').' : '.$job->getNomUrl(1);
	$morehtmlref .= '</div>';



	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	$object->fields['label']['visible']=0; // Already in banner
	$object->fields['fk_user']['visible']=0; // Already in banner
	$object->fields['fk_job']['visible']=0; // Already in banner
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	// Lines when evaluation is in edit mode

	if (!empty($object->table_element_line) && $object->status == Evaluation::STATUS_DRAFT) {
		// Show object lines
		$result = $object->getLinesArray();
		if ($result < 0) {
			dol_print_error($db, $object->error, $object->errors);
		}

		print '<br>';

		print '	<form name="form_save_rank" id="form_save_rank" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline') ? '' : '#line_'.GETPOSTINT('lineid')).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="saveSkill">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';

		if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		$conf->modules_parts['tpl']['hrm']='/hrm/core/tpl/'; // Pour utilisation du tpl hrm sur cet écran

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder noshadow centpercent">';
		}

		// Lines of evaluated skills
		// $object is Evaluation
		$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1);

		if (empty($object->lines)) {
			print '<tr><td colspan="4"><span class="opacitymedium">'.img_warning().' '.$langs->trans("TheJobProfileHasNoSkillsDefinedFixBefore").'</td></tr>';
		}

		// Form to add new line
		/*
		if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
			if ($action != 'editline') {
				// Add products/services form

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				if (empty($reshook))
					$object->formAddObjectLine(1, $mysoc, $soc);
			}
		}
		*/

		if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";

		print "<br>";
	}

	// Lines when evaluation is validated

	if ($object->status != Evaluation::STATUS_DRAFT) {
		// Recovery of skills related to this evaluation

		$sql = 'select';
		$sql .= '  e.ref,';
		$sql .= '  e.date_creation,';
		$sql .= '  e.fk_job,';
		$sql .= '  j.label as "refjob",';
		$sql .= '  ed.fk_skill,';

		$sql .= '  sk.label as "skilllabel",';
		$sql .= '  sk.skill_type,';
		$sql .= '  sk.description,';
		$sql .= '  ed.rankorder,';
		$sql .= '  ed.required_rank,';
		$sql .= '  ed.rankorder as "userRankForSkill",';
		$sql .= '  skdet_user.description as "userRankForSkillDesc",';
		$sql .= '  skdet_required.description as "required_rank_desc"';

		$sql .= '  FROM ' . MAIN_DB_PREFIX . 'hrm_evaluation as e';
		$sql .= '  LEFT JOIN ' . MAIN_DB_PREFIX . 'hrm_evaluationdet as ed ON  e.rowid = ed.fk_evaluation';
		$sql .= '  LEFT JOIN ' . MAIN_DB_PREFIX . 'hrm_job as j ON e.fk_job = j.rowid';
		$sql .= '  LEFT JOIN ' . MAIN_DB_PREFIX . 'hrm_skill as sk ON ed.fk_skill = sk.rowid';
		$sql .= '  INNER JOIN ' . MAIN_DB_PREFIX . 'hrm_skilldet as skdet_user ON (skdet_user.fk_skill = sk.rowid AND skdet_user.rankorder = ed.rankorder)';
		//$sql .= "  LEFT JOIN " . MAIN_DB_PREFIX . "hrm_skillrank as skr ON (j.rowid = skr.fk_object AND skr.fk_skill = ed.fk_skill AND skr.objecttype = 'job')";
		$sql .= '  LEFT JOIN ' . MAIN_DB_PREFIX . 'hrm_skilldet as skdet_required ON (skdet_required.fk_skill = sk.rowid AND skdet_required.rankorder = ed.required_rank)';
		$sql .= " WHERE e.rowid =" . ((int) $object->id);

		//      echo $sql;

		$resql = $db->query($sql);
		$Tab = array();

		if ($resql) {
			$num = 0;
			while ($obj = $db->fetch_object($resql)) {
				$Tab[$num] = new stdClass();
				$class = '';
				$Tab[$num]->skill_type = $obj->skill_type;
				$Tab[$num]->skill_id = $obj->fk_skill;
				$Tab[$num]->skilllabel = $obj->skilllabel;
				$Tab[$num]->description = $obj->description;
				$Tab[$num]->userRankForSkill = '<span title="'.$obj->userRankForSkillDesc.'" class="radio_js_bloc_number TNote_1">' . $obj->userRankForSkill . '</span>';
				$Tab[$num]->required_rank = '<span title="'.$obj->required_rank_desc.'" class="radio_js_bloc_number TNote_1">' . $obj->required_rank . '</span>';

				if ($obj->userRankForSkill > $obj->required_rank) {
					$title=$langs->trans('MaxlevelGreaterThanShort');
					$class .= 'veryhappy diffnote';
				} elseif ($obj->userRankForSkill == $obj->required_rank) {
					$title=$langs->trans('MaxLevelEqualToShort');
					$class .= 'happy diffnote';
				} elseif ($obj->userRankForSkill < $obj->required_rank) {
					$title=$langs->trans('MaxLevelLowerThanShort');
					$class .= 'sad';
				}

				$Tab[$num]->result = '<span title="'.$title.'" class="classfortooltip ' . $class . ' note">&nbsp;</span>';

				$num++;
			}

			print '<br>';

			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder noshadow centpercent">';

			print '<tr class="liste_titre">';
			print '<th style="width:auto;text-align:auto" class="liste_titre">' . $langs->trans("TypeSkill") . ' </th>';
			print '<th style="width:auto;text-align:auto" class="liste_titre">' . $langs->trans("Label") . '</th>';
			print '<th style="width:auto;text-align:auto" class="liste_titre">' . $langs->trans("Description") . '</th>';
			print '<th style="width:auto;text-align:center" class="liste_titre">' . $langs->trans("EmployeeRank") . '</th>';
			print '<th style="width:auto;text-align:center" class="liste_titre">' . $langs->trans("RequiredRank") . '</th>';
			print '<th style="width:auto;text-align:auto" class="liste_titre">' . $langs->trans("Result") . ' ' .$form->textwithpicto('', GetLegendSkills(), 1) .'</th>';
			print '</tr>';

			$sk = new Skill($db);
			foreach ($Tab as $t) {
				$sk->fetch($t->skill_id);

				print '<tr>';
				print ' <td>' . Skill::typeCodeToLabel($t->skill_type) . '</td>';
				print ' <td>' . $sk->getNomUrl(1) . '</td>';
				print ' <td>' . $t->description . '</td>';
				print ' <td align="center">' . $t->userRankForSkill . '</td>';
				print ' <td align="center">' . $t->required_rank . '</td>';
				print ' <td>' . $t->result . '</td>';
				print '</tr>';
			}

			print '</table>';
			print '</div>'; ?>

			<script>

				$(document).ready(function() {
					$(".radio_js_bloc_number").tooltip();
				});

			</script>

			<?php
		}
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			}

			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_setdraft&confirm=yes&token='.newToken(), '', $permissiontoadd);
				print dolGetButtonAction('', $langs->trans('Close'), 'close', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_CLOSED && $permissiontoclose));
			} elseif ($object->status != $object::STATUS_CLOSED) {
				print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			}

			if ($object->status == $object::STATUS_CLOSED) {
				print dolGetButtonAction($langs->trans('ReOpen'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
			}


			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
					print dolGetButtonAction($langs->trans('Save').'&nbsp;'.$langs->trans('and').'&nbsp;'.$langs->trans('Valid'), '', 'default', '#', 'btn_valid', $permissiontovalidate);
				} else {
					$langs->load("errors");
					print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
				}
			}


			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete);
		}


		print '</div>'."\n";
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($user->hasRight('hrm', 'evaluation', 'read')) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->hrm->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight('hrm', 'evaluation', 'read'); // If you can read, you can build the PDF to read content
			$delallowed = $user->hasRight('hrm', 'evaluation', 'write'); // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('hrm:Evaluation', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '', $object, 0, 'remove_file_comfirm');
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('evaluation'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/hrm/evaluation_agenda.php?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	/*if (GETPOST('modelselected')) {
		// $action = 'presend';
	}*/ // To delete.

	// Presend form
	$modelmail = 'evaluation';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->hrm->dir_output;
	$trackid = 'evaluation'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
