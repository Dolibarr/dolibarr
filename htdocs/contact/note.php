<?php
/* Copyright (C) 2001-2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013           Florian Henry		 <florian.henry@open-concept.pro>
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
 *   \file       htdocs/contact/note.php
 *   \brief      Tab for notes on contact
 *   \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$action = GETPOST('action');

$langs->load("companies");

// Security check
$id = GETPOST('id','int');
if ($user->societe_id) $id=$user->societe_id;
$result = restrictedArea($user, 'societe', $id, '&societe');

$object = new Contact($db);
if ($id > 0) $object->fetch($id);

/*
 * Actions
 */

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/
if ($action == 'setnote_public' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) setEventMessage($object->error,'errors');
}

else if ($action == 'setnote_private' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) setEventMessage($object->error,'errors');
}

/*
 *	View
 */

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

$form = new Form($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

if ($id > 0)
{
    /*
     * Affichage onglets
     */
    if (! empty($conf->notification->enabled)) $langs->load("mails");

    $head = contact_prepare_head($object);

    dol_fiche_head($head, 'note', $title,0,'contact');


    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';
    
    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $form->showrefnav($object, 'id', $linkback);
    print '</td></tr>';
    
    // Name
    print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%">'.$object->lastname.'</td>';
    print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td></tr>';
    
    // Company
    if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
    {
    	if ($object->socid > 0)
    	{
    		$objsoc = new Societe($db);
    		$objsoc->fetch($object->socid);
    
    		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
    	}
    
    	else
    	{
    		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
    		print $langs->trans("ContactNotLinkedToCompany");
    		print '</td></tr>';
    	}
    }
    
    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $object->getCivilityLabel();
    print '</td></tr>';
    
    // Date To Birth
    print '<tr>';
    if (! empty($object->birthday))
    {
    	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
    
    	print '<td>'.$langs->trans("DateToBirth").'</td><td colspan="3">'.dol_print_date($object->birthday,"day");
    
    	print ' &nbsp; ';
    	//var_dump($birthdatearray);
    	$ageyear=convertSecondToTime($now-$object->birthday,'year')-1970;
    	$agemonth=convertSecondToTime($now-$object->birthday,'month')-1;
    	if ($ageyear >= 2) print '('.$ageyear.' '.$langs->trans("DurationYears").')';
    	else if ($agemonth >= 2) print '('.$agemonth.' '.$langs->trans("DurationMonths").')';
    	else print '('.$agemonth.' '.$langs->trans("DurationMonth").')';
    
    
    	print ' &nbsp; - &nbsp; ';
    	if ($object->birthday_alert) print $langs->trans("BirthdayAlertOn");
    	else print $langs->trans("BirthdayAlertOff");
    	print '</td>';
    }
    else
    {
    	print '<td>'.$langs->trans("DateToBirth").'</td><td colspan="3">'.$langs->trans("Unknown")."</td>";
    }
    print "</tr>";
    
    print "</table>";

    print '<br>';

    $colwidth='20';
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';


    dol_fiche_end();
}

llxFooter();
$db->close();
?>