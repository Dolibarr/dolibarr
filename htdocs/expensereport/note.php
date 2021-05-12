<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
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
 *  \file       htdocs/expensereport/note.php
 *  \ingroup    expensereport
 *  \brief      Tab for notes on expense reports
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

// Load translation files required by the page
$langs->loadLangs(array('trips', 'companies', 'bills', 'orders'));

<<<<<<< HEAD
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
=======
$id = GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$socid=GETPOST('socid', 'int');
$action=GETPOST('action', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
<<<<<<< HEAD
$result=restrictedArea($user,'expensereport',$id,'expensereport');
=======
$result=restrictedArea($user, 'expensereport', $id, 'expensereport');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


$object = new ExpenseReport($db);
if (! $object->fetch($id, $ref) > 0)
{
	dol_print_error($db);
}

$permissionnote=$user->rights->expensereport->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

<<<<<<< HEAD
include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once
=======
include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not include_once
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


/*
 * View
 */
$title=$langs->trans("ExpenseReport") . " - " . $langs->trans("Note");
$helpurl="EN:Module_Expense_Reports";
<<<<<<< HEAD
llxHeader("",$title,$helpurl);
=======
llxHeader("", $title, $helpurl);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new ExpenseReport($db);
	$object->fetch($id, $ref);
	$object->info($object->id);

	$head = expensereport_prepare_head($object);

	dol_fiche_head($head, 'note', $langs->trans("ExpenseReport"), -1, 'trip');

	$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
    $morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

	$cssclass="titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	dol_fiche_end();
}

<<<<<<< HEAD

=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
