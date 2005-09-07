<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/contact/exportimport.php
        \ingroup    societe
        \brief      Onglet exports-imports d'un contact
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$langs->load("companies");


/*
 *
 *
 */

llxHeader();

$form = new Form($db);

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


$h=0;

$head[$h][0] = DOL_URL_ROOT.'/contact/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("General");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("PersonalInformations");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/contact/exportimport.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("ExportImport");
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Contact").": ".$contact->firstname.' '.$contact->name);


/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

if ($contact->socid > 0)
{
    $objsoc = new Societe($db);
    $objsoc->fetch($contact->socid);

    print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->nom_url.'</td></tr>';
}
else
{
    print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
    print $langs->trans("ContactNotLinkedToCompany");
    print '</td></tr>';
}

print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $contact->civilite_id;
print '</td></tr>';

print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td width="35%">'.$contact->name.'</td>';
print '<td width="15%">'.$langs->trans("Firstname").'</td><td width="35%">'.$contact->firstname.'</td></tr>';

print '</table>';

print '</div>';

print '<br>';

print $langs->trans("ExportCardToFormat").': ';
print '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$_GET["id"].'">';
print img_file($langs->trans("VCard")).' ';
print $langs->trans("VCard");
print '</a>';




$db->close();

llxFooter('$Date$ - $Revision$');
?>
