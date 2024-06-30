<?php
/* Copyright (C) 2016   Marcos García   <marcosgdf@gmail.com>
 * Copyright (C) 2018   Frédéric France <frederic.france@netlogic.fr>
 * Copyright (C) 2022   Open-Dsi		<support@open-dsi.fr>
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
 * \file 	htdocs/variants/card.php
 * \ingroup variants
 * \brief 	Page to show product attribute
 */

// Load Dolibarr environment
require '../main.inc.php';
require 'class/ProductAttribute.class.php';
require 'class/ProductAttributeValue.class.php';
require 'lib/variants.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'productattribute'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid = GETPOST('lineid', 'alpha');

// Security check
if (!isModEnabled('variants')) {
	accessforbidden('Module not enabled');
}
if ($user->socid > 0) { // Protection if external user
	accessforbidden();
}
$result = restrictedArea($user, 'variants');

$object = new ProductAttribute($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productattributecard', 'globalcard'));

$permissiontoread = $user->hasRight('variants', 'read');
$permissiontoadd = $user->hasRight('variants', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontoedit = $user->hasRight('variants', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('variants', 'delete');

$error = 0;


/*
 * Actions
 */


$parameters = array();
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/variants/list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/variants/card.php', 1).'?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	// Action to move up and down lines of object
	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';
	if ($cancel) {
		if (!empty($backtopage)) {
			header("Location: " . $backtopage);
			exit;
		}
		$action = '';
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Action to move up and down lines of object
	if ($action == 'up' && $permissiontoedit) {
		$object->line_up(GETPOST('rowid'), false);

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.GETPOST('rowid'));
		exit();
	} elseif ($action == 'down' && $permissiontoedit) {
		$object->line_down(GETPOST('rowid'), false);

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.GETPOST('rowid'));
		exit();
	}

	if ($action == 'addline' && $permissiontoedit) {
		$line_ref = GETPOST('line_ref', 'alpha');
		$line_value = GETPOST('line_value', 'alpha');

		$result = $object->addLine($line_ref, $line_value);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	} elseif ($action == 'updateline' && $permissiontoedit) {
		$line_ref = GETPOST('line_ref', 'alpha');
		$line_value = GETPOST('line_value', 'alpha');

		$result = $object->updateLine($lineid, $line_ref, $line_value);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editline';
		}
	}
}


/*
 * View
 */

$title = $langs->trans('ProductAttributeName', dol_htmlentities($object->label));
$help_url = 'EN:Module_Products#Variants';
llxHeader('', $title, $help_url);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("ProductAttribute")), '', 'object_' . $object->picto);

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

	print '</table>' . "\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button button-cancel" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="history.go(-1)"') . '>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	dol_set_focus('input[name="ref"]');
} elseif (($id || $ref) && $action == 'edit') {
	// Part to edit record
	print load_fiche_titre($langs->trans("ProductAttribute"), '', 'object_' . $object->picto);

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

	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
} elseif ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	// Part to show record
	$res = $object->fetch_optionals();

	$head = productAttributePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("ProductAttribute"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteMyObject'), $langs->trans('ProductAttributeDeleteDialog'), 'confirm_delete', '', 0, 1);
	} elseif ($action == 'ask_deleteline') {
		// Confirmation to delete line
		$object_value = new ProductAttributeValue($db);
		if ($object_value->fetch($lineid) > 0) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ProductAttributeValueDeleteDialog', dol_htmlentities($object_value->value), dol_htmlentities($object_value->ref)), 'confirm_deleteline', '', 0, 1);
		}
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
	$backtolist = (GETPOST('backtolist') ? GETPOST('backtolist') : DOL_URL_ROOT . '/variants/list.php?leftmenu=?restore_lastsearch_values=1');
	$linkback = '<a href="' . dol_sanitizeUrl($backtolist) . '">' . $langs->trans("BackToList") . '</a>';

	dol_banner_tab($object, 'id', $linkback);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions
	if ($action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Modify
			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit', '', $permissiontoedit);

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete', '', $permissiontodelete);
		}
		print '</div>' . "\n";
	}

	/*
	 * Lines
	 */
	if (!empty($object->table_element_line)) {
		// Show object lines
		$result = $object->getLinesArray();

		print load_fiche_titre($langs->trans("PossibleValues") . (!empty($object->lines) ? '<span class="opacitymedium colorblack paddingleft">(' . count($object->lines) . ')</span>' : ''));

		print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '' : '#line_' . GETPOSTINT('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . newToken() . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		';
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
		}
		if ($backtopageforcancel) {
			print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
		}

		if (!empty($conf->use_javascript_ajax)) {
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		if (!empty($object->lines) || ($permissiontoedit && $action != 'selectlines' && $action != 'editline')) {
			print '<table id="tablelines" class="noborder centpercent">';
		}

		$object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1, '/variants/tpl', ($permissiontoedit ? 1 : 0));

		if (!empty($object->lines) || ($permissiontoedit && $action != 'selectlines' && $action != 'editline')) {
			print '</table>';
		}
		print '</div>';

		print "</form>\n";
	}
}

// End of page
llxFooter();
$db->close();
