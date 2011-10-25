<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/contact/exportimport.php
 *	\ingroup    societe
 *	\brief      Onglet exports-imports d'un contact
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/contact.lib.php");

$langs->load("companies");

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, 'socpeople');


/*
 *	View
 */

llxHeader('',$langs->trans("ContactsAddresses"),'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');

$form = new Form($db);

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


$head = contact_prepare_head($contact);

dol_fiche_head($head, 'exportimport', $langs->trans("ContactsAddresses"), 0, 'contact');


/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

// Ref
print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($contact,'id');
print '</td></tr>';

// Name
print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td>'.$contact->name.'</td>';
print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->firstname.'</td></tr>';

// Company
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
{
    if ($contact->socid > 0)
    {
    	$objsoc = new Societe($db);
    	$objsoc->fetch($contact->socid);

    	print '<tr><td width="15%">'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
    }
    else
    {
    	print '<tr><td width="15%">'.$langs->trans("Company").'</td><td colspan="3">';
    	print $langs->trans("ContactNotLinkedToCompany");
    	print '</td></tr>';
    }
}

// Civility
print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $contact->getCivilityLabel();
print '</td></tr>';

print '</table>';

print '</div>';

print '<br>';

print $langs->trans("ExportCardToFormat").': ';
print '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$_GET["id"].'">';
print img_picto($langs->trans("VCard"),'vcard.png').' ';
print $langs->trans("VCard");
print '</a>';




$db->close();

llxFooter();
?>
