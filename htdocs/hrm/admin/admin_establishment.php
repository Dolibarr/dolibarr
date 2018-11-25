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
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

if (! $user->admin)
	accessforbidden();

$error=0;

// List of statut
static $tmpstatus2label=array(
		'0'=>'OpenEtablishment',
		'1'=>'CloseEtablishment'
);
$status2label=array('');
foreach ($tmpstatus2label as $key => $val) $status2label[$key]=$langs->trans($val);

/*
 * Actions
 */

/*
 * View
 */
llxHeader('', $langs->trans("Establishments"));

$sortorder     = GETPOST("sortorder");
$sortfield     = GETPOST("sortfield");
if (!$sortorder) $sortorder="DESC";
if (!$sortfield) $sortfield="e.rowid";

if ($page == -1) {
	$page = 0 ;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;

$form = new Form($db);
$establishmenttmp=new Establishment($db);

dol_htmloutput_mesg($mesg);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("HRMSetup"), $linkback);

// Configuration header
$head = hrm_admin_prepare_head();
dol_fiche_head($head, 'establishments', $langs->trans("HRM"), -1, "user");

$sql = "SELECT e.rowid, e.name, e.address, e.zip, e.town, e.status";
$sql.= " FROM ".MAIN_DB_PREFIX."establishment as e";
$sql.= " WHERE e.entity = ".$conf->entity;
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	// Load attribute_label
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Name",$_SERVER["PHP_SELF"],"e.name","","","",$sortfield,$sortorder);
	print_liste_field_titre("Address",$_SERVER["PHP_SELF"],"e.address","","","",$sortfield,$sortorder);
	print_liste_field_titre("Zipcode",$_SERVER["PHP_SELF"],"e.zip","","","",$sortfield,$sortorder);
	print_liste_field_titre("Town",$_SERVER["PHP_SELF"],"e.town","","","",$sortfield,$sortorder);
	print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"e.status","","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	if ($num > 0)
    {
	    $establishmentstatic=new Establishment($db);

		while ($i < min($num,$limit))
		{
            $obj = $db->fetch_object($result);

			$establishmentstatic->id=$obj->rowid;
			$establishmentstatic->name=$obj->name;
			$establishmentstatic->status=$obj->status;


			print '<tr class="oddeven">';
			print '<td>'.$establishmentstatic->getNomUrl(1).'</td>';
            print '<td align="left">'.$obj->address.'</td>';
			print '<td align="left">'.$obj->zip.'</td>';
			print '<td align="left">'.$obj->town.'</td>';

            print '<td align="right">';
			print $establishmentstatic->getLibStatut(5);
			print '</td>';
            print "</tr>\n";

            $i++;
        }
    }
    else
    {
        print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
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

// End of page
llxFooter();
$db->close();
