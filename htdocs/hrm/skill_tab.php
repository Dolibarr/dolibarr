<?php
/* Copyright (C) 2021 grégory Blémand  <contact@atm-consulting.fr>
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
 *    \file       htdocs/hrm/skill_tab.php
 *    \ingroup    hrm
 *    \brief      Page to add/delete/view skill to jobs/users
 */


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/skill.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/skillrank.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_skill.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('hrm', 'other'));

// Get Parameters
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'skillcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$id = GETPOST('id', 'int');
$TSkillsToAdd = GETPOST('fk_skill', 'array');
$objecttype = GETPOST('objecttype', 'alpha');
$TNote = GETPOST('TNote', 'array');
$lineid = GETPOST('lineid', 'int');

if (empty($objecttype)) {
	$objecttype = 'job';
}

$TAuthorizedObjects = array('job', 'user');
$skill = new SkillRank($db);

// Initialize technical objects
if (in_array($objecttype, $TAuthorizedObjects)) {
	if ($objecttype == 'job') {
		$object = new Job($db);
	} elseif ($objecttype == "user") {
		$object = new User($db);
	}
} else {
	accessforbidden('ErrorBadObjectType');
}

$hookmanager->initHooks(array('skilltab', 'globalcard')); // Note that conf->hooks_modules contains array

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Permissions
$permissiontoread = $user->hasRight('hrm', 'all', 'read');
$permissiontoadd  = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
if (empty($conf->hrm->enabled)) accessforbidden();
if (!$permissiontoread) accessforbidden();


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

	$backurlforlist = DOL_URL_ROOT.'/hrm/skill_list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/hrm/skill_list.php?id=' . ($id > 0 ? $id : '__ID__');
			}
		}
	}

	if ($action == 'addSkill') {
		$error = 0;

		if (empty($TSkillsToAdd)) {
			setEventMessage('ErrNoSkillSelected', 'errors');
			$error++;
		}

		if (!$error) {
			foreach ($TSkillsToAdd as $k=>$v) {
				$skillAdded = new SkillRank($db);
				$skillAdded->fk_skill = $v;
				$skillAdded->fk_object = $id;
				$skillAdded->objecttype = $objecttype;
				$ret = $skillAdded->create($user);
				if ($ret < 0) setEventMessages($skillAdded->error, null, 'errors');
				//else unset($TSkillsToAdd);
			}
			if ($ret > 0) setEventMessages($langs->trans("SaveAddSkill"), null);
		}
	} elseif ($action == 'saveSkill') {
		if (!empty($TNote)) {
			foreach ($TNote as $skillId => $rank) {
				$TSkills = $skill->fetchAll('ASC', 't.rowid', 0, 0, array('customsql' => 'fk_object=' . ((int) $id) . " AND objecttype='" . $db->escape($objecttype) . "' AND fk_skill = " . ((int) $skillId)));
				if (is_array($TSkills) && !empty($TSkills)) {
					foreach ($TSkills as $tmpObj) {
						$tmpObj->rankorder = $rank;
						$tmpObj->update($user);
					}
				}
			}
			setEventMessages($langs->trans("SaveLevelSkill"), null);
			header("Location: " . DOL_URL_ROOT.'/hrm/skill_tab.php?id=' . $id. '&objecttype=job');
			exit;
		}
	} elseif ($action == 'confirm_deleteskill' && $confirm == 'yes') {
		$skillToDelete = new SkillRank($db);
		$ret = $skillToDelete->fetch($lineid);
		setEventMessages($langs->trans("DeleteSkill"), null);
		if ($ret > 0) {
			$skillToDelete->delete($user);
		}
	}
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("RequiredSkills");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	// view configuration
	if ($objecttype == 'job') {
		require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_job.lib.php';
		$head = jobPrepareHead($object);
		$listLink = dol_buildpath('/hrm/job_list.php', 1);
	} elseif ($objecttype == "user") {
		require_once DOL_DOCUMENT_ROOT . "/core/lib/usergroups.lib.php";
		$object->getRights();
		$head = user_prepare_head($object);
		$listLink = dol_buildpath('/user/list.php', 1);
	}

	print dol_get_fiche_head($head, 'skill_tab', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	/*if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteSkill'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}*/
	// Confirmation to delete line
	if ($action == 'ask_deleteskill') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&objecttype=' . $objecttype . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteskill', '', 0, 1);
	}
	// Clone confirmation
	/*if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}*/

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
	if ($objecttype == 'job') {
		$linkback = '<a href="' . dol_buildpath('/hrm/job_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref = '<div class="refid">';
		$morehtmlref.= $object->label;
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);
	} else {
		$linkback = '<a href="' . $listLink . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
		$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
		$morehtmlref .= '</a>';

		$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
		$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->trans("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref, '&objecttype='.$objecttype);
	}

	// Get all available skills
	$static_skill = new Skill($db);
	$TAllSkills = $static_skill->fetchAll();

	// Array format for multiselectarray function
	$TAllSkillsFormatted=array();
	if (!empty($TAllSkills)) {
		foreach ($TAllSkills as $k=>$v) {
			$TAllSkillsFormatted[$k] = $v->label;
		}
	}

	// table of skillRank linked to current object
	$TSkillsJob = $skill->fetchAll('ASC', 't.rowid', 0, 0, array('customsql' => 'fk_object=' . ((int) $id) . " AND objecttype='" . $db->escape($objecttype) . "'"));

	$TAlreadyUsedSkill = array();
	if (is_array($TSkillsJob) && !empty($TSkillsJob)) {
		foreach ($TSkillsJob as $skillElement) {
			$TAlreadyUsedSkill[$skillElement->fk_skill] = $skillElement->fk_skill;
		}
	}

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	if ($objecttype == 'job') {
		// Common attributes
		//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
		//unset($object->fields['fk_project']);				// Hide field already shown in banner
		//unset($object->fields['fk_soc']);					// Hide field already shown in banner
		$object->fields['label']['visible']=0; // Already in banner
		include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

		// Other attributes. Fields from hook formObjectOptions and Extrafields.
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
	} else {
		// Login
		print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
		if (!empty($object->ldap_sid) && $object->statut == 0) {
			print '<td class="error">';
			print $langs->trans("LoginAccountDisableInDolibarr");
			print '</td>';
		} else {
			print '<td>';
			$addadmin = '';
			if (property_exists($object, 'admin')) {
				if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
					$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
				} elseif (!empty($object->admin)) {
					$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
				}
			}
			print showValueWithClipboardCPButton(!empty($object->login) ? $object->login : '').$addadmin;
			print '</td>';
		}
		print '</tr>'."\n";

		$object->fields['label']['visible']=0; // Already in banner
		$object->fields['firstname']['visible']=0; // Already in banner
		$object->fields['lastname']['visible']=0; // Already in banner
		//include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

		// Ref employee
		print '<tr><td class="titlefield">'.$langs->trans("RefEmployee").'</td>';
		print '<td class="error">';
		print showValueWithClipboardCPButton(!empty($object->ref_employee) ? $object->ref_employee : '');
		print '</td>';
		print '</tr>'."\n";

		// National Registration Number
		print '<tr><td class="titlefield">'.$langs->trans("NationalRegistrationNumber").'</td>';
		print '<td class="error">';
		print showValueWithClipboardCPButton(!empty($object->national_registration_number) ? $object->national_registration_number : '');
		print '</td>';
		print '</tr>'."\n";
	}

	print '</table>';

	print '</div>';
	print '</div>';


	print '<div class="clearboth"></div><br>';

	if ($objecttype != 'user' && $permissiontoadd) {
		// form pour ajouter des compétences
		print '<form name="addSkill" method="post" action="' . $_SERVER['PHP_SELF'] . '">';
		print '<input type="hidden" name="objecttype" value="' . $objecttype . '">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="action" value="addSkill">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';
		print '<tr><td style="width:90%">' . $langs->trans('AddSkill') . '</td><td style="width:10%"></td></tr>';
		print '<tr>';
		print '<td>';
		print img_picto('', 'shapes', 'class="pictofixedwidth"');
		print $form->multiselectarray('fk_skill', array_diff_key($TAllSkillsFormatted, $TAlreadyUsedSkill), array(), 0, 0, 'widthcentpercentminusx') . '</td>';
		print '<td><input class="button reposition" type="submit" value="' . $langs->trans('Add') . '"></td>';
		print '</tr>';
		print '</table>';
		print '</div>';
		print '</form>';
	}
	print '<br>';

	print '<div class="clearboth"></div>';

	if ($objecttype != 'user' && $permissiontoadd) {
		print '<form name="saveSkill" method="post" action="' . $_SERVER['PHP_SELF'] . '">';
		print '<input type="hidden" name="objecttype" value="' . $objecttype . '">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="saveSkill">';
	}
	print '<div class="div-table-responsive-no-min">';
	print '<table id="tablelines" class="noborder centpercent" width="100%">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans('SkillType').'</th>';
	print '<th>'.$langs->trans('Label').'</th>';
	print '<th>'.$langs->trans('Description').'</th>';
	print '<th>'.$langs->trans($objecttype === 'job' ? 'RequiredRank' : 'EmployeeRank').'</th>';
	if ($objecttype === 'job') {
		print '<th class="linecoledit"></th>';
		print '<th class="linecoldelete"></th>';
	}
	print '</tr>';
	if (!is_array($TSkillsJob) || empty($TSkillsJob)) {
		print '<tr><td><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
	} else {
		$sk = new Skill($db);
		foreach ($TSkillsJob as $skillElement) {
			$sk->fetch($skillElement->fk_skill);
			print '<tr>';
			print '<td>';
			print Skill::typeCodeToLabel($sk->skill_type);
			print '</td><td class="linecolfk_skill">';
			print $sk->getNomUrl(1);
			print '</td>';
			print '<td>';
			print $sk->description;
			print '</td><td class="linecolrank">';
			print displayRankInfos($skillElement->rankorder, $skillElement->fk_skill, 'TNote', $objecttype == 'job' && $permissiontoadd ? 'edit' : 'view');
			print '</td>';
			if ($objecttype != 'user' && $permissiontoadd) {
				print '<td class="linecoledit"></td>';
				print '<td class="linecoldelete">';
				print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $skillElement->fk_object . '&amp;objecttype=' . $objecttype . '&amp;action=ask_deleteskill&amp;lineid=' . $skillElement->id . '">';
				print img_delete();
				print '</a>';
			}
			print '</td>';
			print '</tr>';
		}
	}

	print '</table>';
	if ($objecttype != 'user' && $permissiontoadd) print '<td><input class="button pull-right" type="submit" value="' . $langs->trans('SaveRank') . '"></td>';
	print '</div>';
	if ($objecttype != 'user' && $permissiontoadd) print '</form>';


	// liste des compétences liées

	print dol_get_fiche_end();

	llxFooter();
}
