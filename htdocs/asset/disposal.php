<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *  \file       htdocs/asset/disposal.php
 *  \ingroup    asset
 *  \brief      Card with disposal info on Asset
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT.'/asset/class/asset.class.php';

// Load translation files required by the page
$langs->loadLangs(array("assets", "companies"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Asset($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->asset->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('assetdisposal', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->asset->multidir_output[$object->entity]."/".$object->id;
}

$permissionnote = $user->rights->asset->write; // Used by the include of actions_setnotes.inc.php
$permissiontoadd = $user->rights->asset->write; // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
if ($user->socid > 0) accessforbidden();
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->asset->enabled)) accessforbidden();
if (!isset($object->disposal_date) || $object->disposal_date === "") accessforbidden();


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans('Asset'), $help_url);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = assetPrepareHead($object);

	print dol_get_fiche_head($head, 'disposal', $langs->trans("Asset"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$show_fields = array('disposal_date', 'disposal_amount_ht', 'fk_disposal_type', 'disposal_depreciated', 'disposal_subject_to_vat');
	foreach ($object->fields as $field_key => $field_info) {
		$object->fields[$field_key]['visible'] = in_array($field_key, $show_fields) ? 1 : 0;
	}
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
