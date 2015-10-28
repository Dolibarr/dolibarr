<?php
/* Copyright (C) 2015 		Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/hrm/admin/admin_establishment.php
 * \ingroup HRM
 * \brief 	HRM Establishment module setup page
 */
require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';

$langs->load("admin");
$langs->load('hrm');

if (! $user->admin)
	accessforbidden();

$error=0;

$action = GETPOST('action', 'alpha');

$object = new Establishment($db);

/*
 * Actions
 */

/*
 * View
 */
$page_name = "Establishments";
llxHeader('', $langs->trans($page_name));

$form = new Form($db);

dol_htmloutput_mesg($mesg);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("HRMSetup"), $linkback);

// Configuration header
$head = hrm_admin_prepare_head();
dol_fiche_head($head, 'establishments', $langs->trans("HRM"), 0, "user");

$sql = "SELECT e.rowid, e.name, e.address, e.zip, e.town, e.status";
$sql.= " FROM ".MAIN_DB_PREFIX."establishment as e";
$sql.= " WHERE e.entity = ".$conf->entity;

$result = $db->query($sql);
if ($result)
{
	$var=false;
    $num = $db->num_rows($result);

    $i = 0;

	// Load attribute_label
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Ref").'</td>';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Address").'</td>';
	print '<td>'.$langs->trans("Zipcode").'</td>';
	print '<td>'.$langs->trans("Town").'</td>';
	print '<td align="right">'.$langs->trans("Status").'</td>';
	print '</tr>';

	if ($num)
    {
	    $establishmentstatic=new Establishment($db);

		while ($i < $num && $i < $max)
        {
            $obj = $db->fetch_object($result);
            $fiscalyearstatic->id=$obj->rowid;
            print '<tr '.$bc[$var].'>';
			print '<td><a href="admin_establishment_card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowEstablishment"),"building").' '.$obj->rowid.'</a></td>';
            print '<td align="left">'.$obj->name.'</td>';
            print '<td align="left">'.$obj->address.'</td>';
			print '<td align="left">'.$obj->zip.'</td>';
			print '<td align="left">'.$obj->town.'</td>';
            print '<td align="right">'.$establishmentstatic->LibStatut($obj->status,5).'</td>';
            print '</tr>';
            $var=!$var;
            $i++;
        }

    }
    else
    {
        print '<tr '.$bc[$var].'><td colspan="6">'.$langs->trans("None").'</td></tr>';
    }

	print '</table>';
}
else
{
	dol_print_error($db);
}

dol_fiche_end();

// Buttons
print '<div class="tabsAction">';
print '<a class="butAction" href="../establishment/card.php?action=create">'.$langs->trans("NewEstablishment").'</a>';
print '</div>';

llxFooter();
$db->close();
