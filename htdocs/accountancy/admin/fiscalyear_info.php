<?php
/* Copyright (C) 2014-2024	Alexandre Spangaro	<aspangaro@easya.solutions>
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
 * \file	    htdocs/accountancy/admin/fiscalyear_info.php
 * \ingroup     Accountancy (Double entries)
 * \brief	    Page to show info of a fiscal year
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "compta"));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {
	accessforbidden();
}

$id = GETPOSTINT('id');


// View

$title = $langs->trans("Fiscalyear")." - ".$langs->trans("Info");

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin_fiscalyear_info');

if ($id) {
	$object = new Fiscalyear($db);
	$object->fetch($id);
	$object->info($id);

	$head = fiscalyear_prepare_head($object);

	print dol_get_fiche_head($head, 'info', $langs->trans("Fiscalyear"), -1, $object->picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
