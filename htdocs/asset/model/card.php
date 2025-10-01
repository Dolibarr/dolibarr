<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/asset/model/card.php
 *  \ingroup    asset
 *  \brief      Page to create/edit/view asset Model
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT . '/asset/class/assetmodel.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';
require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("assets", "other"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'assetmodelcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize a technical objects
$object = new AssetModel($db);
$assetdepreciationoptions = new AssetDepreciationOptions($db);
$assetaccountancycodes = new AssetAccountancyCodes($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('assetmodelcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$permissiontoread = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'read')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'read')));
$permissiontoadd = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'write')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'write'))); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'delete')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'delete'))) || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $permissiontoadd; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $permissiontoadd; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->asset->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check (enable the most restrictive one)
if ($user->socid > 0) {
	accessforbidden();
}
if ($user->socid > 0) {
	$socid = $user->socid;
}
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
restrictedArea($user, 'asset', $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled('asset')) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}

// Model depreciation options and accountancy codes
$object->asset_depreciation_options = &$assetdepreciationoptions;
$object->asset_accountancy_codes = &$assetaccountancycodes;
if (!empty($id)) {
	$depreciationoptionserrors = $assetdepreciationoptions->fetchDeprecationOptions(0, $object->id);
	$accountancycodeserrors = $assetaccountancycodes->fetchAccountancyCodes(0, $object->id);

	if ($depreciationoptionserrors < 0) {
		setEventMessages($assetdepreciationoptions->error, $assetdepreciationoptions->errors, 'errors');
	}
	if ($accountancycodeserrors < 0) {
		setEventMessages($assetaccountancycodes->error, $assetaccountancycodes->errors, 'errors');
	}
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

	$backurlforlist = DOL_URL_ROOT . '/asset/model/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT . '/asset/model/card.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'ASSETMODEL_MODIFY'; // Name of trigger action code to execute when we modify record

	if (($action == 'edit' && !($permissiontoadd && $object->status == $object::STATUS_DRAFT)) ||
		($action == 'confirm_setdraft' && !($permissiontoadd && $object->status != $object::STATUS_DRAFT)) ||
		($action == 'confirm_validate' && !($permissiontoadd && $object->status != $object::STATUS_VALIDATED)) ||
		($action == 'confirm_close' && !($permissiontoadd && $object->status != $object::STATUS_CANCELED))
	) {
		$action = "";
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';
}


/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("AssetModel") . ' - ' . $langs->trans("Card");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-model-card');

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("AssetModel")), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
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
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	// Depreciation options
	include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_edit.tpl.php';

	// Accountancy codes
	print '<div class="clearboth"></div>';
	print '<hr>';
	include DOL_DOCUMENT_ROOT . '/asset/tpl/accountancy_codes_edit.tpl.php';

	print '</table>' . "\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("AssetModel"), '', 'object_' . $object->picto);

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
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	// Depreciation options
	include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_edit.tpl.php';

	// Accountancy codes
	print '<div class="clearboth"></div>';
	print '<hr>';
	include DOL_DOCUMENT_ROOT . '/asset/tpl/accountancy_codes_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = assetModelPrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("AssetModel"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteAssetModel'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	} elseif ($action == 'clone') {
		// Clone confirmation
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
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
	$linkback = '<a href="' . DOL_URL_ROOT . '/asset/model/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	// Common attributes
	$object->fields = dol_sort_array($object->fields, 'position');

	foreach ($object->fields as $key => $val) {
		if (!empty($keyforbreak) && $key == $keyforbreak) {
			break; // key used for break on second column
		}

		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) {
			continue;
		}

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) {
			continue; // We don't want this field
		}
		if (in_array($key, array('ref', 'status'))) {
			continue; // Ref and status are already in dol_banner
		}

		$value = $object->$key;

		print '<tr class="field_'.$key.'"><td';
		print ' class="'.(empty($val['tdcss']) ? 'titlefield' : $val['tdcss']).' fieldname_'.$key;
		//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
		if ($val['type'] == 'text' || $val['type'] == 'html') {
			print ' tdtop';
		}
		print '">';

		$labeltoshow = '';
		if (!empty($val['help'])) {
			$labeltoshow .= $form->textwithpicto($langs->trans($val['label']), $langs->trans($val['help']));
		} else {
			if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 1) {
				$labeltoshow .= showValueWithClipboardCPButton($value, 0, $langs->transnoentitiesnoconv($val['label']));
			} else {
				$labeltoshow .= $langs->trans($val['label']);
			}
		}
		if (empty($val['alwayseditable'])) {
			print $labeltoshow;
		} else {
			print $form->editfieldkey($labeltoshow, $key, $value, $object, 1, $val['type']);
		}

		print '</td>';
		print '<td class="valuefield fieldname_'.$key;
		if ($val['type'] == 'text') {
			print ' wordbreak';
		}
		if (!empty($val['cssview'])) {
			print ' '.$val['cssview'];
		}
		print '">';
		if (empty($val['alwayseditable'])) {
			if (preg_match('/^(text|html)/', $val['type'])) {
				print '<div class="longmessagecut">';
			}
			if ($key == 'lang') {
				$langs->load("languages");
				$labellang = ($value ? $langs->trans('Language_'.$value) : '');
				print picto_from_langcode($value, 'class="paddingrightonly saturatemedium opacitylow"');
				print $labellang;
			} else {
				if (isset($val['copytoclipboard']) && $val['copytoclipboard'] == 2) {
					$out = $object->showOutputField($val, $key, $value, '', '', '', 0);
					print showValueWithClipboardCPButton($out, 0, $out);
				} else {
					print $object->showOutputField($val, $key, $value, '', '', '', 0);
				}
			}
			//print dol_escape_htmltag($object->$key, 1, 1);
			if (preg_match('/^(text|html)/', $val['type'])) {
				print '</div>';
			}
		} else {
			print $form->editfieldval($labeltoshow, $key, $value, $object, 1, $val['type']);
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
	print '</table>';

	// Depreciation options attributes
	print '<div class="fichehalfleft">';
	print '<table class="border centpercent tableforfield">';
	include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_view.tpl.php';
	print '</table>';
	print '</div>';

	// Accountancy codes attributes
	print '<div class="fichehalfright">';
	print '<table class="border centpercent tableforfield">';
	include DOL_DOCUMENT_ROOT . '/asset/tpl/accountancy_codes_view.tpl.php';
	print '</table>';
	print '</div>';

	print '</div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	// Buttons for actions
	if ($action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			if ($object->status == $object::STATUS_DRAFT) {
				print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
			}

			// Back to draft
			if ($object->status != $object::STATUS_DRAFT) {
				print dolGetButtonAction($langs->trans('SetToDraft'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_setdraft&confirm=yes&token=' . newToken(), '', $permissiontoadd);
			}

			if ($object->status != $object::STATUS_VALIDATED) {
				print dolGetButtonAction($langs->trans('Enable'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_validate&confirm=yes&token=' . newToken(), '', $permissiontoadd);
			}

			if ($object->status != $object::STATUS_CANCELED) {
				print dolGetButtonAction($langs->trans('Disable'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=confirm_close&confirm=yes&token='.newToken(), '', $permissiontoadd);
			}

			// Clone
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . (!empty($socid) ? '&socid=' . $socid : '') . '&action=clone&token=' . newToken(), '', $permissiontoadd);

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>' . "\n";
	}

	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	print '</div><div class="fichehalfright">';

	//  $MAXEVENT = 10;
	//
	//  $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT . '/asset/model/agenda.php?id=' . $object->id);
	//
	//  // List of actions on element
	//  include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	//  $formactions = new FormActions($db);
	//  $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, 0, 1, '', $MAXEVENT, '', $morehtmlright);

	print '</div></div>';
}

// End of page
llxFooter();
$db->close();
