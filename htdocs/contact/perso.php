<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/contact/perso.php
        \ingroup    societe
        \brief      Onglet informations personnelles d'un contact
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

$langs->load("companies");


if ($_POST["action"] == 'update')
{
    $contact = new Contact($db);
    $contact->id = $_POST["contactid"];

    if ($_POST["birthdayyear"])
    {
        if ($_POST["birthdayyear"]<=1970 && $_SERVER["WINDIR"])
        {
        // windows mktime does not support negative date timestamp so birthday is not supported for old persons
        $contact->birthday = $_POST["birthdayyear"].'-'.$_POST["birthdaymonth"].'-'.$_POST["birthdayday"];
        // array_push($error,"Windows ne sachant pas gérer des dates avant 1970, les dates de naissance avant cette date ne seront pas sauvegardées");
        } else {
            $contact->birthday     = mktime(0,0,0,$_POST["birthdaymonth"],$_POST["birthdayday"],$_POST["birthdayyear"]);
        }
    }

    $contact->birthday_alert = $_POST["birthday_alert"];

    $result = $contact->update_perso($_POST["contactid"], $user);
}


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
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/contact/exportimport.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("ExportImport");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Contact").": ".$contact->firstname.' '.$contact->name);


if ($_GET["action"] == 'edit')
{
    /*
     * Fiche en mode edition
     */

    print '<table class="border" width="100%">';

    print '<form method="post" action="perso.php?id='.$_GET["id"].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="contactid" value="'.$contact->id.'">';

    if ($contact->socid > 0)
    {
        $objsoc = new Societe($db);
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->nom_url.'</td>';
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
    
    print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td width="35%">'.$contact->nom.'</td>';
    print '<td width="15%">'.$langs->trans("Firstname").'</td><td width="35%">'.$contact->prenom.'</td>';
    

    print '<tr><td>'.$langs->trans("Birthday").'</td><td>';
    $html=new Form($db);
    if ($contact->birthday && $contact->birthday > 0)
    {
        print $html->select_date($contact->birthday,'birthday',0,0,0);
    } else {
        print $html->select_date(0,'birthday',0,0,1);
    }
    print '</td>';

    print '<td colspan="2">'.$langs->trans("Alert").': ';
    if ($contact->birthday_alert)
    {
        print '<input type="checkbox" name="birthday_alert" checked></td>';
    }
    else
    {
        print '<input type="checkbox" name="birthday_alert"></td>';
    }
    print '</tr>';

    print '<tr><td colspan="4" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'"></td></tr>';
    print "</table>";

    print "</form>";
}
else
{
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

    if ($contact->birthday && $contact->birthday > 0) {
        print '<tr><td>'.$langs->trans("Birthdate").'</td><td colspan="3">'.dolibarr_print_date($contact->birthday);

        if ($contact->birthday_alert)
        print ' (alerte anniversaire active)</td>';
        else
        print ' (alerte anniversaire inactive)</td>';
    }
    else {
        print '<tr><td>'.$langs->trans("Birthday").'</td><td colspan="3">'.$langs->trans("Unknown")."</td>";
    }
    print "</tr>";

    print "</table>";

    print "</div>";


    // Barre d'actions
    if ($user->societe_id == 0)
    {
        print '<div class="tabsAction">';

        print '<a class="butAction" href="perso.php?id='.$_GET["id"].'&amp;action=edit">'.$langs->trans('Edit').'</a>';

        print "</div>";
    }

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
