<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/contact/perso.php
 *       \ingroup    societe
 *       \brief      Onglet informations personnelles d'un contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';

$langs->load("companies");
$langs->load("other");

$id		= GETPOST('id','int');
$action	= GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');
$object = new Contact($db);

/*
 * Action
 */

if ($action == 'update' && ! $_POST["cancel"] && $user->rights->societe->contact->creer)
{
	$ret = $object->fetch($id);

	// Note: Correct date should be completed with location to have exact GM time of birth.
	$object->birthday = dol_mktime(0,0,0,$_POST["birthdaymonth"],$_POST["birthdayday"],$_POST["birthdayyear"]);
	$object->birthday_alert = $_POST["birthday_alert"];

	$result = $object->update_perso($id, $user);
	if ($result > 0)
	{
		$object->old_name='';
		$object->old_firstname='';
	}
	else
	{
		$error = $object->error;
	}
}


/*
 *	View
 */

$now=dol_now();

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contactnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->lastname) $title=$object->lastname;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $helpurl);

$form = new Form($db);

$object->fetch($id, $user);

$head = contact_prepare_head($object);

dol_fiche_head($head, 'perso', $title, 0, 'contact');

if ($action == 'edit')
{
	/*
	 * Fiche en mode edition
	 */

    print '<form name="perso" method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $object->id;
    print '</td></tr>';

    // Name
    print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%">'.$object->lastname.'</td>';
    print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td>';

    // Company
    if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
    {
        if ($object->socid > 0)
        {
            $objsoc = new Societe($db);
            $objsoc->fetch($object->socid);

            print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td>';
        }
        else
        {
            print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
            print $langs->trans("ContactNotLinkedToCompany");
            print '</td></tr>';
        }
    }

    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
    print $object->getCivilityLabel();
    print '</td></tr>';

    // Date To Birth
    print '<tr><td>'.$langs->trans("DateToBirth").'</td><td>';
    $form=new Form($db);
    print $form->select_date($object->birthday,'birthday',0,0,1,"perso", 1,0,1);
    print '</td>';

    print '<td colspan="2">'.$langs->trans("Alert").': ';
    if (! empty($object->birthday_alert))
    {
        print '<input type="checkbox" name="birthday_alert" checked></td>';
    }
    else
    {
        print '<input type="checkbox" name="birthday_alert"></td>';
    }
    print '</tr>';

    print "</table><br>";

    print '<div class="center">';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print "</form>";
}
else
{
    // View mode
    
    $linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';
    
    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');
    
    
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent">';

    // Company
    if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
    {
        if ($object->socid > 0)
        {
            $objsoc = new Societe($db);
            $objsoc->fetch($object->socid);

            print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
        }

        else
        {
            print '<tr><td>'.$langs->trans("ThirdParty").'</td><td colspan="3">';
            print $langs->trans("ContactNotLinkedToCompany");
            print '</td></tr>';
        }
    }

    // Civility
    print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td colspan="3">';
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

    print '</div>';
}

dol_fiche_end();

if ($action != 'edit')
{
    // Barre d'actions
    if ($user->societe_id == 0)
    {
        print '<div class="tabsAction">';

        if ($user->rights->societe->contact->creer)
        {
            print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
        }

        print "</div>";
    }
}


llxFooter();

$db->close();
