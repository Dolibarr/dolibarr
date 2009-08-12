<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/contact/perso.php
        \ingroup    societe
        \brief      Onglet informations personnelles d'un contact
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");

$langs->load("companies");
$langs->load("other");

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, 'socpeople');

/*
 * Action
 */

if ($user->rights->societe->contact->creer)
{
	if ($_POST["action"] == 'update' && ! $_POST["cancel"])
	{
		$contact = new Contact($db);
		$contact->fetch($_POST["contactid"]);

		// Note: Correct date should be completed with location to have exact GM time of birth.
		$contact->birthday = dol_mktime(0,0,0,$_POST["birthdaymonth"],$_POST["birthdayday"],$_POST["birthdayyear"]);
		$contact->birthday_alert = $_POST["birthday_alert"];

		$result = $contact->update_perso($_POST["contactid"], $user);

		if ($result > 0)
		{
			$contact->old_name='';
			$contact->old_firstname='';
		}
		else
		{
			$error = $contact->error;
		}
	}
}


/*
*	View
*/

llxHeader('',$langs->trans("Contacts"),'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');

$form = new Form($db);

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);

$head = contact_prepare_head($contact);

dol_fiche_head($head, 'perso', $langs->trans("Contact"), 0, 'contact');



if ($_GET["action"] == 'edit')
{
    /*
     * Fiche en mode edition
     */

    print '<table class="border" width="100%">';

    print '<form name="perso" method="post" action="perso.php?id='.$_GET["id"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="contactid" value="'.$contact->id.'">';

	// Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $contact->id;
    print '</td></tr>';

	// Name
    print '<tr><td>'.$langs->trans("Lastname").'</td><td>'.$contact->nom.'</td>';
    print '<td>'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->prenom.'</td>';

    // Company
	if ($contact->socid > 0)
    {
        $objsoc = new Societe($db);
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }

	// Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $contact->getCivilityLabel();
    print '</td></tr>';

	// Date To Birth
    print '<tr><td>'.$langs->trans("DateToBirth").'</td><td>';
    $html=new Form($db);
    if ($contact->birthday)
    {
        print $html->select_date($contact->birthday,'birthday',0,0,0,"perso");
    }
    else
    {
        print $html->select_date('','birthday',0,0,1,"perso");
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

	// Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($contact,'id');
    print '</td></tr>';

	// Name
    print '<tr><td>'.$langs->trans("Lastname").'</td><td>'.$contact->name.'</td>';
    print '<td>'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->firstname.'</td></tr>';

    // Company
	if ($contact->socid > 0)
    {
        $objsoc = new Societe($db);
        $objsoc->fetch($contact->socid);

        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
    }

    else
    {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
        print $langs->trans("ContactNotLinkedToCompany");
        print '</td></tr>';
    }

	// Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $contact->getCivilityLabel();
    print '</td></tr>';

	// Date To Birth
    if ($contact->birthday != '')
    {
        print '<tr><td>'.$langs->trans("DateToBirth").'</td><td colspan="3">'.dol_print_date($contact->birthday,"day");
		print ' (';
        if ($contact->birthday_alert) print $langs->trans("BirthdayAlertOn");
        else print $langs->trans("BirthdayAlertOff");
        print ')</td>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("DateToBirth").'</td><td colspan="3">'.$langs->trans("Unknown")."</td>";
    }
    print "</tr>";

    print "</table>";

    print "</div>";


    // Barre d'actions
    if ($user->societe_id == 0)
    {
        print '<div class="tabsAction">';

				if ($user->rights->societe->contact->creer)
    		{
        	print '<a class="butAction" href="perso.php?id='.$_GET["id"].'&amp;action=edit">'.$langs->trans('Modify').'</a>';
        }

        print "</div>";
    }

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
