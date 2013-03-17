<?php
/*
 * Copyright (C) 2013 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/admin/carrier.php
 *  \ingroup    expedition
 *  \brief      Page to setup carriers. TODO Delete this page. We mut use dictionnary instead.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

$langs->load("admin");
$langs->load("sendings");
$langs->load("deliveries");
$langs->load('other');

if (! $user->admin)
    accessforbidden();

$action=GETPOST('action','alpha');
$carrier=GETPOST('carrier','int');

$object = new Expedition($db);


/*
 * Actions
 */
//if ($action==setvalue AND $carrier)
if ($action==setvalue)
{
    // need to add check on values
    $object->update[code]=GETPOST('code','alpha');
    $object->update[libelle]=GETPOST('libelle','alpha');
    $object->update[description]=GETPOST('description','alpha');
    $object->update[tracking]=GETPOST('tracking','alpha');
    $object->update_delivery_method($carrier);
    header("Location: carrier.php");
    exit;
}

if ($action==activate_carrier AND $carrier!='')
{
    $object->activ_delivery_method($carrier);
}

if ($action==disable_carrier AND $carrier!='')
{
    $object->disable_delivery_method($carrier);
}

/*
 * View
 */

$form=new Form($db);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("SendingsSetup"),$linkback,'setup');
print '<br>';


//if ($mesg) print $mesg.'<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/confexped.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/carrier.php";
$head[$h][1] = $langs->trans("Carriers");
$hselected=$h;
$h++;

if (! empty($conf->global->MAIN_SUBMODULE_EXPEDITION))
{
    $head[$h][0] = DOL_URL_ROOT."/admin/expedition.php";
    $head[$h][1] = $langs->trans("Sending");
    $h++;
}

if (! empty($conf->global->MAIN_SUBMODULE_LIVRAISON))
{
    $head[$h][0] = DOL_URL_ROOT."/admin/livraison.php";
    $head[$h][1] = $langs->trans("Receivings");
    $h++;
}

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

/*
 * Carrier List
 */
if ($action=='edit_carrier' || $action=='setvalue')
{
    // Carrier Edit
    if ($carrier!='') $object->list_delivery_methods($carrier);
    print_titre($langs->trans("CarrierEdit"));

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?carrier='.$carrier.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setvalue">';


    print '<table class="nobordernopadding" width="100%">';

    $var=true;
    print '<tr class="liste_titre">';
    print '<td width="150">'.$langs->trans("CarrierParameter").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print "</tr>\n";

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("Code").'</td><td>';
    print '<input size="32" type="text" name="code" value="'.$object->listmeths[0][code].'">';
    print ' &nbsp; '.$langs->trans("Example").': CODE';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("Name").'</td><td>';
    print '<input size="32" type="text" name="libelle" value="'.$object->listmeths[0][libelle].'">';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("Description").'</td><td>';
    print '<input size="64" type="text" name="description" value="'.$object->listmeths[0][description].'">';
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td class="fieldrequired">';
    print $langs->trans("Tracking").'</td><td>';
    print '<input size="128" type="text" name="tracking" value="'.$object->listmeths[0][tracking].'">';
    print ' &nbsp; '.$langs->trans("Example").': http://www.website.com/dir/{TRACKID}';
    print '</td></tr>';

    if ($carrier)
    {
    print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
    }
    else
    {
    print '<tr><td colspan="2" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
    }

    print '</table>';
    print '</form>';

}
else
{
    // Display List
    $object->list_delivery_methods();
    $var=true;
    print_titre($langs->trans("CarrierList"));

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td width="80">'.$langs->trans("Code").'</td>';
    print '<td width="150">'.$langs->trans("Name").'</td>';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("TrackingUrl").'</td>';
    print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
    print '<td align="center" width="30">'.$langs->trans("Edit").'</td>';
    print "</tr>\n";
    for ($i=0; $i<sizeof($object->listmeths); $i++)
    {
        $var=!$var;
        print "<tr ".$bc[$var].">";
        print '<td>'.$object->listmeths[$i][code].'</td>';
        print '<td>'.$object->listmeths[$i][libelle].'</td>';
        print '<td>'.$object->listmeths[$i][description].'</td>';
        print '<td>'.$object->listmeths[$i][tracking].'</td>';
        print '<td align="center">';
        if($object->listmeths[$i][active] == 0)
        {
            print '<a href="carrier.php?action=activate_carrier&amp;carrier='.$object->listmeths[$i][rowid].'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
        }
        else
        {
            print '<a href="carrier.php?action=disable_carrier&amp;carrier='.$object->listmeths[$i][rowid].'">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
        }
        print '</td><td align="center">';
        print '<a href="carrier.php?action=edit_carrier&amp;carrier='.$object->listmeths[$i][rowid].'">'.img_picto($langs->trans("Edit"),'edit').'</a>';
        print '</td>';
        print "</tr>\n";
    }
            print '<tr><td align="center"><a href="carrier.php?action=edit_carrier"><br>'.$langs->trans("Add").'</a></td><tr>';

    print '</table><br>';

    print '</div>';
}

llxFooter();

$db->close();
?>
