<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
 * Copyright (C) 2023-2024  Frédéric France     <frederic.france@free.fr>
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
 *    \file       htdocs/hrm/skill_card.php
 *    \ingroup    hrm
 *    \brief      Page to create/edit/view skills
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/skill.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_skill.lib.php';


// Load translation files required by the page
$langs->loadLangs(array('hrm', 'other', 'products'));  // why products?

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'skillcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');

// Initialize a technical objects
$object = new Skill($db);
$extrafields = new ExtraFields($db);
//$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('skillcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');


// Initialize array of search criteria
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) {
		$search[$key] = GETPOST('search_' . $key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Permissions
$permissiontoread   = $user->hasRight('hrm', 'all', 'read');
$permissiontoadd    = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('hrm', 'all', 'delete');

$upload_dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1] . '/skill';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->hrm->enabled)) {
	accessforbidden();
}
if (!$permissiontoread || ($action === 'create' && !$permissiontoadd)) {
	accessforbidden();
}

$MaxNumberSkill = getDolGlobalInt('HRM_MAXRANK', Skill::DEFAULT_MAX_RANK_PER_SKILL);


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
				$backtopage = DOL_URL_ROOT.'/hrm/skill_card.php?id=' . ($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'HRM_SKILL_MODIFY'; // Name of trigger action code to execute when we modify record


	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	$noback = 1;
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// action update on Skilldet
	$skilldetArray = GETPOST("descriptionline", "array:alphanohtml");

	if (!$error) {
		if (is_array($skilldetArray) && count($skilldetArray) > 0) {
			if ($action == 'add' && $permissiontoadd) {
				$arraySkill = $object->fetchLines();
				$index = 0;
				foreach ($arraySkill as $skilldet) {
					if (isset($skilldetArray[$index])) {
						$SkValueToUpdate = $skilldetArray[$index];
						$skilldet->description = $SkValueToUpdate;
						$resupd = $skilldet->update($user);
						if ($resupd <= 0) {
							setEventMessage($langs->trans('errorUpdateSkilldet'), 'errors');
						}
					}
					$index++;
				}
			}
			if ($action == 'update' && $permissiontoadd) {
				foreach ($skilldetArray as $key => $SkValueToUpdate) {
					$skilldetObj = new Skilldet($object->db);
					$res = $skilldetObj->fetch($key);
					if ($res > 0) {
						$skilldetObj->description = $SkValueToUpdate;
						$resupd = $skilldetObj->update($user);
						if ($resupd <= 0) {
							setEventMessage($langs->trans('errorUpdateSkilldet'), 'errors');
						}
					}
				}
			}
		}
	}




	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'HRM_SKILL_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SKILL_TO';
	$trackid = 'skill' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

	if ($action == 'confirm_clone' && $confirm != 'yes') {
		$action = '';
	}

	if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
		$id = $result->id;
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Skill");
$help_url = '';
llxHeader('', $title, $help_url);


// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewSkill"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	$backtopage .= (strpos($backtopage, '?') > 0 ? '&' : '?') ."objecttype=job";
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';


	// SKILLDET ADD
	//@todo je stop ici ... à continuer  (affichage des 5 skilled input pour create action
	//print $object->showInputField($val, $key, $value, '', '['']', '', 0);

	print '</table>' . "\n";
	print '<hr>';

	print '<table class="border centpercent =">' . "\n";
	for ($i = 1; $i <= $MaxNumberSkill; $i++) {
		print '<tr><td class="titlefieldcreate tdtop">'. $langs->trans('Description') . ' ' . $langs->trans('rank') . ' ' . $i . '</td>';
		print '<td class="valuefieldcreate"><textarea name="descriptionline[]" rows="5"  class="flat minwidth100" style="margin-top: 5px; width: 90%"></textarea></td>';
	}
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button button-cancel" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="history.go(-1)"') . '>';
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
// and skilldet edition
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Skill"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print '<hr>';

	// SKILLDET
	$SkilldetRecords = $object->fetchLines();

	if (is_array($SkilldetRecords) && count($SkilldetRecords) == 0) {
		$object->createSkills(1);
	}

	if (is_array($SkilldetRecords) && count($SkilldetRecords) > 0) {
		print '<table>';
		foreach ($SkilldetRecords as $sk) {
			if ($sk->rankorder > $MaxNumberSkill) {
				continue;
			}

			print '<table class="border centpercent =">' . "\n";
			$sk->fields = dol_sort_array($sk->fields, 'position');
			foreach ($sk->fields as $key => $val) {
				if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4) {
					continue;
				}

				if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
					continue; // We don't want this field
				}

				print '<tr class="field_' . $key . '"><td';
				print ' class="titlefieldcreate';
				if (isset($val['notnull']) && $val['notnull'] > 0) {
					print ' fieldrequired';
				}
				if (preg_match('/^(text|html)/', $val['type'])) {
					print ' tdtop';
				}
				print '">';
				//              if (!empty($val['help'])) {
				//                  print $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
				//              } else {
				print $langs->trans($val['label']).'&nbsp;'.$langs->trans('rank').'&nbsp;'.$sk->rankorder;
				//              }
				print '</td>';
				print '<td class="valuefieldcreate">';
				//              if (!empty($val['picto'])) {
				//                  print img_picto('', $val['picto'], '', false, 0, 0, '', 'pictofixedwidth');
				//              }
				//              if (in_array($val['type'], array('int', 'integer'))) {
				//                  $value = GETPOSTISSET($key) ? GETPOST($key, 'int') : $sk->$key;
				//              } elseif ($val['type'] == 'double') {
				//                  $value = GETPOSTISSET($key) ? price2num(GETPOST($key, 'alphanohtml')) : $sk->$key;
				//              } elseif (preg_match('/^(text|html)/', $val['type'])) {
				//                  $tmparray = explode(':', $val['type']);
				if (!empty($tmparray[1])) {
					$check = $tmparray[1];
				} else {
					$check = 'restricthtml';
				}

				$skilldetArray = GETPOST("descriptionline", "array");
				if (empty($skilldetArray)) {
					$value = GETPOSTISSET($key) ? GETPOST($key, $check) : $sk->$key;
				} else {
					$value=$skilldetArray[$sk->id];
				}
				//
				//              } elseif ($val['type'] == 'price') {
				//                  $value = GETPOSTISSET($key) ? price2num(GETPOST($key)) : price2num($sk->$key);
				//              } else {
				//                  $value = GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $sk->$key;
				//              }
				//var_dump($val.' '.$key.' '.$value);
				if (!empty($val['noteditable'])) {
					print $sk->showOutputField($val, $key, $value, '', '', '', 0);
				} else {
					/** @var Skilldet $sk */
					print $sk->showInputField($val, $key, $value, "", "line[" . $sk->id . "]", "", "");
				}
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';
	}


	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = skillPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteSkill'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Confirmation clone
	if ($action === 'clone') {
		$formquestion = array(
			array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->label), 'confirm_clone', $formquestion, 'yes', 1, 280);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
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
	$linkback = '<a href="' . DOL_URL_ROOT.'/hrm/skill_list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref = '<div class="refid">';
	$morehtmlref.= $object->label;
	$morehtmlref .= '</div>';
	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	$object->fields['label']['visible']=0; // Already in banner
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';


	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	//dol_include_once('/hrm/tpl/hrm_skillde.fiche.tpl.php');

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Back to draft
			if ($object->status == $object::STATUS_VALIDATED) {
				print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_setdraft&confirm=yes&token=' . newToken(), '', $permissiontoadd);
			}

			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);

			// Clone
			if ($permissiontoadd) {
				print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER["PHP_SELF"].'?action=clone&token='.newToken().'&id='.$object->id, '');
			}
			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete);
		}
		print '</div>' . "\n";
	}
}


//*---------------------------------------------------------------------------

if ($action != "create" && $action != "edit") {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

	// load hrm libraries
	require_once __DIR__ . '/class/skilldet.class.php';

	// for other modules
	//dol_include_once('/othermodule/class/otherobject.class.php');

	$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
	$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
	$show_files = GETPOSTINT('show_files'); // Show files area generated by bulk actions ?
	$confirm = GETPOST('confirm', 'alpha'); // Result of a confirmation
	$cancel = GETPOST('cancel', 'alpha'); // We click on a Cancel button
	$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
	$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'skilldetlist'; // To manage different context of search
	$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
	$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

	$id = GETPOSTINT('id');

	// Load variable for pagination
	$limit = 0;
	$sortfield = GETPOST('sortfield', 'aZ09comma');
	$sortorder = GETPOST('sortorder', 'aZ09comma');
	$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
	if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		// If $page is not defined, or '' or -1 or if we click on clear filters
		$page = 0;
	}
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	// Initialize a technical objects
	$objectline = new Skilldet($db);
	//  $diroutputmassaction = $conf->hrm->dir_output . '/temp/massgeneration/' . $user->id;
	//  $hookmanager->initHooks(array('skilldetlist')); // Note that conf->hooks_modules contains array

	// Default sort order (if not yet defined by previous GETPOST)
	if (!$sortfield) {
		reset($objectline->fields);                    // Reset is required to avoid key() to return null.
		$sortfield = "t." . key($objectline->fields); // Set here default search field. By default 1st field in definition.
	}
	if (!$sortorder) {
		$sortorder = "ASC";
	}

	// Initialize array of search criteria
	$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
	$search = array();
	foreach ($objectline->fields as $key => $val) {
		if (GETPOST('search_' . $key, 'alpha') !== '') {
			$search[$key] = GETPOST('search_' . $key, 'alpha');
		}
		if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
			$search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
			$search[$key . '_dtend'] = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
		}
	}

	// List of fields to search into when doing a "search in all"
	$fieldstosearchall = array();
	foreach ($objectline->fields as $key => $val) {
		if (!empty($val['searchall'])) {
			$fieldstosearchall['t.' . $key] = $val['label'];
		}
	}

	// Definition of array of fields for columns
	$arrayfields = array();
	foreach ($objectline->fields as $key => $val) {
		// If $val['visible']==0, then we never show the field
		if (!empty($val['visible'])) {
			$visible = (int) dol_eval($val['visible'], 1, 1, '1');
			$arrayfields['t.' . $key] = array(
				'label' => $val['label'],
				'checked' => (($visible < 0) ? 0 : 1),
				'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
				'position' => $val['position'],
				'help' => isset($val['help']) ? $val['help'] : ''
			);
		}
	}

	$objectline->fields = dol_sort_array($objectline->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');


	// View

	$form = new Form($db);

	$now = dol_now();

	$help_url = '';
	$title = $langs->transnoentitiesnoconv("Skilldets");
	$morejs = array();
	$morecss = array();
	$nbtotalofrecords = '';

	// Build and execute select
	// --------------------------------------------------------------------
	$sql = 'SELECT ';
	$sql .= $objectline->getFieldList('t');
	$sql .= " FROM " . MAIN_DB_PREFIX . $objectline->table_element . " as t";
	if ($objectline->ismultientitymanaged == 1) {
		$sql .= " WHERE t.entity IN (" . getEntity($objectline->element) . ")";
	} else {
		$sql .= " WHERE 1 = 1 ";
	}
	$sql .= " AND fk_skill = ".((int) $id);

	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	$num = $db->num_rows($resql);

	print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">' . "\n";
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
	}
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="page" value="' . $page . '">';
	print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
	if (!empty($id)) {
		print '<input type="hidden" name="id" value="' . $id . '">';
	}

	$param_fk = "&fk_skill=" . $id . "&fk_user_creat=" . (!empty($user->rowid) ? $user->rowid : 0);
	$backtopage = dol_buildpath('/hrm/skill_card.php', 1) . '?id=' . $id;
	$param = "";
	$massactionbutton = "";
	//$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/hrm/skilldet_card.php', 1) . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']) . $param_fk . '&backtopage=' . $backtopage, '', $permissiontoadd);

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_' . $object->picto, 0, '', '', 0, 0, 0, 1);

	// Add code for pre mass action (confirmation or email presend form)
	$topicmail = "SendSkilldetRef";
	$modelmail = "skilldet";
	$objecttmp = new Skilldet($db);
	$trackid = 'xxxx' . $object->id;
	//include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($search_all) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . implode(', ', $fieldstosearchall) . '</div>';
	}

	$moreforfilter = '';
	/*$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
	$moreforfilter.= '</div>';*/

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $objectline); // Note that $action and $objectline may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if (!empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	//  $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	//  $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($objectline->fields as $key => $val) {
		//      $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
		//      if ($key == 'status') {
		//          $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		//      } elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
		//          $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
		//      } elseif (in_array($val['type'], array('timestamp'))) {
		//          $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
		//      } elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
		//          $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
		//      }
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, (!empty($cssforfield) ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, (!empty($cssforfield) ? $cssforfield . ' ' : '')) . "\n";
		}
	}
	//print '<td></td>';
	print '<td></td>';
	print '</tr>' . "\n";


	// Display all ranks of skill
	// --------------------------------------------------------------------

	$i = 0;
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	while ($i < ($limit ? min($num, $limit) : $num)) {
		$obj = $db->fetch_object($resql);
		if (empty($obj)) {
			break; // Should not happen
		}

		if ($obj->rankorder > $MaxNumberSkill) {
			continue;
		}

		// Store properties in $objectline
		$objectline->setVarsFromFetchObj($obj);

		// Show here line of result
		print '<tr class="oddeven">';
		foreach ($objectline->fields as $key => $val) {
			//          $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
			//          if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			//              $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
			//          } elseif ($key == 'status') {
			//              $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
			//          }
			//
			//          if (in_array($val['type'], array('timestamp'))) {
			//              $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
			//          } elseif ($key == 'ref') {
			//              $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
			//          }
			//
			//          if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status')) && empty($val['arrayofkeyval'])) {
			//              $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
			//          }
			//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

			if (!empty($arrayfields['t.' . $key]['checked'])) {
				print '<td' . (empty($cssforfield) ? '' : ' class="' . $cssforfield . '"') . '>';
				if ($key == 'status') {
					print $objectline->getLibStatut(5);
				} elseif ($key == 'rowid') {
					print $objectline->showOutputField($val, $key, $objectline->id, '');
					// ajout pencil
					print '<a class="timeline-btn" href="' . DOL_MAIN_URL_ROOT . '/comm/action/skilldet_card.php?action=edit&id=' . $objectline->id . '"><i class="fa fa-pencil" title="' . $langs->trans("Modify") . '" ></i></a>';
				} else {
					print $objectline->showOutputField($val, $key, $objectline->$key, '');
				}
				print '</td>';


				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
					if (!$i) {
						$totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
					}
					if (!isset($totalarray['val'])) {
						$totalarray['val'] = array();
					}
					if (!isset($totalarray['val']['t.' . $key])) {
						$totalarray['val']['t.' . $key] = 0;
					}
					$totalarray['val']['t.' . $key] += $objectline->$key;
				}
			}
		}


		// LINE EDITION | SUPPRESSION

		print '<td>';
		print '</td>';

		// print '<td>';
		// add pencil
		//@todo change to proper call dol_
		//print '<a class="timeline-btn" href="' . dol_buildpath("custom/hrm/skilldet_card.php?action=edit&id=" . $objectline->id, 1) . '"><i class="fa fa-pencil" title="' . $langs->trans("Modify") . '" ></i></a>';
		// add trash
		//@todo change to proper call dol_
		//print '<a class="timeline-btn" href="'.dol_buildpath("custom/hrm/skilldet_card.php?action=delete&id=".$objectline->id,1)  .'"><i class="fa fa-trash" title="'.$langs->trans("Delete").'" ></i></a>';
		// print '</td>';


		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'object' => $objectline, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objectline); // Note that $action and $objectline may have been modified by hook
		print $hookmanager->resPrint;
		/*// Action column
		print '<td class="nowrap center">';

		print '</td>';*/
		if (!$i) {
			$totalarray['nbfield']++;
		}

		print '</tr>' . "\n";

		$i++;
	}


	// If no record found

	if ($num == 0) {
		$colspan = 2;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
	}

	if (!empty($resql)) {
		$db->free($resql);
	}

	$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $objectline); // Note that $action and $objectline may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>' . "\n";
	print '</div>' . "\n";

	print '</form>' . "\n";

	//  if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	//      $hidegeneratedfilelistifempty = 1;
	//      if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
	//          $hidegeneratedfilelistifempty = 0;
	//      }
	//
	//      require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
	//      $formfile = new FormFile($db);
	//
	//      // Show list of available documents
	//      $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
	//      $urlsource .= str_replace('&amp;', '&', $param);
	//
	//      $filedir = $diroutputmassaction;
	//      $genallowed = $permissiontoread;
	//      $delallowed = $permissiontoadd;
	//
	//      print $formfile->showdocuments('massfilesarea_hrm', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
	//  }

	print '<div class="fichecenter"><div class="fichehalfleft">';

	// Show links to link elements
	$linktoelem = $form->showLinkToObjectBlock($object, null, array('skill'));
	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

	print '</div><div class="fichehalfright">';

	$MAXEVENT = 10;

	$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/hrm/skill_agenda.php?id='.$object->id);

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

	print '</div></div>';
}

// End of page
llxFooter();
$db->close();
