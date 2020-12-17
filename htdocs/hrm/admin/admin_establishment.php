<?php
/* Copyright (C) 2015 		Alexandre Spangaro <aspangaro@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/hrm/admin/admin_establishment.php
 * \ingroup HRM
 * \brief 	HRM Establishment module setup page
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

if (!$user->admin)
	accessforbidden();

$error = 0;


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$establishmenttmp = new Establishment($db);

llxHeader('', $langs->trans("Establishments"));

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortorder     = GETPOST("sortorder", 'alpha');
$sortfield     = GETPOST("sortfield", 'alpha');
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "e.rowid";

if (empty($page) || $page == -1) {
	$page = 0;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("HRMSetup"), $linkback);

// Configuration header
$head = hrm_admin_prepare_head();
print dol_get_fiche_head($head, 'establishments', $langs->trans("HRM"), -1, "user");

$sql = "SELECT e.rowid, e.label, e.address, e.zip, e.town, e.status";
$sql .= " FROM ".MAIN_DB_PREFIX."establishment as e";
$sql .= " WHERE e.entity IN (".getEntity('establishment').')';
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	// Load attribute_label
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "e.ref", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "e.label", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Address", $_SERVER["PHP_SELF"], "e.address", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Zip", $_SERVER["PHP_SELF"], "e.zip", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Town", $_SERVER["PHP_SELF"], "e.town", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "e.status", "", "", '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

	if ($num > 0)
	{
		$establishmentstatic = new Establishment($db);

		while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($result);

			$establishmentstatic->id = $obj->rowid;
			$establishmentstatic->ref = $obj->ref;
			$establishmentstatic->label = $obj->label;
			$establishmentstatic->status = $obj->status;


			print '<tr class="oddeven">';
			print '<td>'.$establishmentstatic->getNomUrl(1).'</td>';
			print '<td>'.$obj->label.'</td>';
			print '<td class="left">'.$obj->address.'</td>';
			print '<td class="left">'.$obj->zip.'</td>';
			print '<td class="left">'.$obj->town.'</td>';
			print '<td class="right">';
			print $establishmentstatic->getLibStatut(5);
			print '</td>';
			print "</tr>\n";

			$i++;
		}
	} else {
		print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}

	print '</table>';
} else {
	dol_print_error($db);
}

print dol_get_fiche_end();

// Buttons
print '<div class="tabsAction">';
print '<a class="butAction" href="'.DOL_URL_ROOT.'/hrm/establishment/card.php?action=create">'.$langs->trans("NewEstablishment").'</a>';
print '</div>';

// End of page
llxFooter();
$db->close();
