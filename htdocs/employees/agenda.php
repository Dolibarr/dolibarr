<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		  <patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>  
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
 *  \file       htdocs/employees/agenda.php
 *  \ingroup    employee
 *  \brief      Page of employees events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';

$langs->load("companies");
$langs->load("employees");

$id = GETPOST('id','int');

// Security check
$result=restrictedArea($user,'employee',$id);

$object = new Employee($db);
$result=$object->fetch($id);
if ($result > 0)
{
    $adht = new EmployeeType($db);
    $result=$empt->fetch($object->typeid);
}

/*
 *	Actions
 */

// None

/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($object->id > 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	llxHeader("",$langs->trans("Agenda"),'');

	if (! empty($conf->notification->enabled)) $langs->load("mails");
	$head = employee_prepare_head($object);

	dol_fiche_head($head, 'agenda', $langs->trans("Employee"),0,'user');

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/employees/liste.php">'.$langs->trans("BackToList").'</a>';

	// Reference
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'id', $linkback);
	print '</td>';
	print '</tr>';

	// Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
	print '</tr>';

	// Lastname
	print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="3">'.$object->lastname.'&nbsp;</td>';
	print '</tr>';

	// Firstname
	print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="3">'.$object->firstname.'&nbsp;</td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$object->getLibStatut(4).'</td></tr>';

	print '</table>';

	print '</div>';


    /*
     * Barre d'action
     */

    print '<div class="tabsAction">';

    if (! empty($conf->agenda->enabled))
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create">'.$langs->trans("AddAction").'</a></div>';
    }

    print '</div>';

    print load_fiche_titre($langs->trans("ActionsOnEmployee"),'','');

    // List of todo actions
    show_actions_todo($conf,$langs,$db,$object);

    // List of done actions
    show_actions_done($conf,$langs,$db,$object);
}



llxFooter();

$db->close();
?>
