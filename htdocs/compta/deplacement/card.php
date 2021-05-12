<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
=======
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
 *  \file       	htdocs/compta/deplacement/card.php
 *  \brief      	Page to show a trip card
=======
 *  \file       htdocs/compta/deplacement/card.php
 *  \brief      Page to show a trip card
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/trip.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (! empty($conf->projet->enabled))
{
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->load("trips");


// Security check
<<<<<<< HEAD
$id = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id,'');

$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');
=======
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id, '');

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$object = new Deplacement($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('tripsandexpensescard','globalcard'));

$permissionnote=$user->rights->deplacement->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once

if ($action == 'validate' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    if ($object->statut == 0)
    {
        $result = $object->setStatut(1);
        if ($result > 0)
        {
            header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
	        setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

<<<<<<< HEAD
else if ($action == 'classifyrefunded' && $user->rights->deplacement->creer)
=======
elseif ($action == 'classifyrefunded' && $user->rights->deplacement->creer)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $object->fetch($id);
    if ($object->statut == 1)
    {
        $result = $object->setStatut(2);
        if ($result > 0)
        {
            header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
	        setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

<<<<<<< HEAD
else if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->deplacement->supprimer)
=======
elseif ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->deplacement->supprimer)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $result=$object->delete($id);
    if ($result >= 0)
    {
        header("Location: index.php");
        exit;
    }
    else
    {
	    setEventMessages($object->error, $object->errors, 'errors');
    }
}

<<<<<<< HEAD
else if ($action == 'add' && $user->rights->deplacement->creer)
{
    if (! GETPOST('cancel','alpha'))
    {
        $error=0;

        $object->date			= dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));
        $object->km				= price2num(GETPOST('km','alpha'), 'MU'); // Not 'int', it may be a formated amount
        $object->type			= GETPOST('type','alpha');
        $object->socid			= GETPOST('socid','int');
        $object->fk_user		= GETPOST('fk_user','int');
        $object->note_private	= GETPOST('note_private','alpha');
        $object->note_public	= GETPOST('note_public','alpha');
=======
elseif ($action == 'add' && $user->rights->deplacement->creer)
{
    if (! GETPOST('cancel', 'alpha'))
    {
        $error=0;

        $object->date			= dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
        $object->km				= price2num(GETPOST('km', 'alpha'), 'MU'); // Not 'int', it may be a formated amount
        $object->type			= GETPOST('type', 'alpha');
        $object->socid			= GETPOST('socid', 'int');
        $object->fk_user		= GETPOST('fk_user', 'int');
        $object->note_private	= GETPOST('note_private', 'alpha');
        $object->note_public	= GETPOST('note_public', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $object->statut     	= 0;

        if (! $object->date)
        {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
            $error++;
        }
        if ($object->type == '-1')
        {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
            $error++;
        }
        if (! ($object->fk_user > 0))
        {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Person")), null, 'errors');
            $error++;
        }

        if (! $error)
        {
            $id = $object->create($user);

            if ($id > 0)
            {
                header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
                exit;
            }
            else
            {
	            setEventMessages($object->error, $object->errors, 'errors');
                $action='create';
            }
        }
        else
        {
            $action='create';
        }
    }
    else
    {
        header("Location: index.php");
        exit;
    }
}

// Update record
<<<<<<< HEAD
else if ($action == 'update' && $user->rights->deplacement->creer)
{
    if (! GETPOST('cancel','alpha'))
    {
        $result = $object->fetch($id);

        $object->date			= dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));
        $object->km				= price2num(GETPOST('km','alpha'), 'MU'); // Not 'int', it may be a formated amount
        $object->type			= GETPOST('type','alpha');
        $object->socid			= GETPOST('socid','int');
        $object->fk_user		= GETPOST('fk_user','int');
        $object->note_private	= GETPOST('note_private','alpha');
        $object->note_public	= GETPOST('note_public','alpha');
=======
elseif ($action == 'update' && $user->rights->deplacement->creer)
{
    if (! GETPOST('cancel', 'alpha'))
    {
        $result = $object->fetch($id);

        $object->date			= dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
        $object->km				= price2num(GETPOST('km', 'alpha'), 'MU'); // Not 'int', it may be a formated amount
        $object->type			= GETPOST('type', 'alpha');
        $object->socid			= GETPOST('socid', 'int');
        $object->fk_user		= GETPOST('fk_user', 'int');
        $object->note_private	= GETPOST('note_private', 'alpha');
        $object->note_public	= GETPOST('note_public', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $result = $object->update($user);

        if ($result > 0)
        {
            header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
	        setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    else
    {
        header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
        exit;
    }
}

// Set into a project
<<<<<<< HEAD
else if ($action == 'classin' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setProject(GETPOST('projectid','int'));
=======
elseif ($action == 'classin' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setProject(GETPOST('projectid', 'int'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    if ($result < 0) dol_print_error($db, $object->error);
}

// Set fields
<<<<<<< HEAD
else if ($action == 'setdated' && $user->rights->deplacement->creer)
{
    $dated=dol_mktime(GETPOST('datedhour','int'), GETPOST('datedmin','int'), GETPOST('datedsec','int'), GETPOST('datedmonth','int'), GETPOST('datedday','int'), GETPOST('datedyear','int'));
=======
elseif ($action == 'setdated' && $user->rights->deplacement->creer)
{
    $dated=dol_mktime(GETPOST('datedhour', 'int'), GETPOST('datedmin', 'int'), GETPOST('datedsec', 'int'), GETPOST('datedmonth', 'int'), GETPOST('datedday', 'int'), GETPOST('datedyear', 'int'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $object->fetch($id);
    $result=$object->setValueFrom('dated', $dated, '', '', 'date', '', $user, 'DEPLACEMENT_MODIFY');
    if ($result < 0) dol_print_error($db, $object->error);
}
<<<<<<< HEAD
else if ($action == 'setkm' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setValueFrom('km', GETPOST('km','int'), '', null, 'text', '', $user, 'DEPLACEMENT_MODIFY');
=======
elseif ($action == 'setkm' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setValueFrom('km', GETPOST('km', 'int'), '', null, 'text', '', $user, 'DEPLACEMENT_MODIFY');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    if ($result < 0) dol_print_error($db, $object->error);
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

/*
 * Action create
*/
if ($action == 'create')
{
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

    print load_fiche_titre($langs->trans("NewTrip"));

<<<<<<< HEAD
    $datec = dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));
=======
    $datec = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
<<<<<<< HEAD
    $form->select_type_fees(GETPOST('type','int'),'type',1);
=======
    $form->select_type_fees(GETPOST('type', 'int'), 'type', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</td></tr>';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
<<<<<<< HEAD
    print $form->select_dolusers(GETPOST('fk_user','int'), 'fk_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
=======
    print $form->select_dolusers(GETPOST('fk_user', 'int'), 'fk_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</td></tr>';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
<<<<<<< HEAD
    print $form->select_date($datec?$datec:-1,'','','','','add',1,1,1);
=======
    print $form->selectDate($datec?$datec:-1, '', '', '', '', 'add', 1, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</td></tr>';

    // Km
    print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" size="10" value="' . GETPOST("km") . '"></td></tr>';

    // Company
    print "<tr>";
    print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
<<<<<<< HEAD
    print $form->select_company(GETPOST('socid','int'),'socid','',1);
=======
    print $form->select_company(GETPOST('socid', 'int'), 'socid', '', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</td></tr>';

    // Public note
    print '<tr>';
    print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
    print '<td>';

<<<<<<< HEAD
    $doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8,'90%');
=======
    $doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, '90%');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print $doleditor->Create(1);

    print '</td></tr>';

    // Private note
    if (empty($user->societe_id))
    {
        print '<tr>';
        print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
        print '<td>';

        $doleditor = new DolEditor('note_private', GETPOST('note_private', 'alpha'), '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, '90%');
        print $doleditor->Create(1);

        print '</td></tr>';
    }

    // Other attributes
    $parameters=array();
<<<<<<< HEAD
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
=======
    $reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print $hookmanager->resPrint;

    print '</table>';

    print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

    print '</form>';
}
<<<<<<< HEAD
else if ($id)
=======
elseif ($id)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    $result = $object->fetch($id);
    if ($result > 0)
    {
        $head = trip_prepare_head($object);

        dol_fiche_head($head, 'card', $langs->trans("TripCard"), 0, 'trip');

        if ($action == 'edit' && $user->rights->deplacement->creer)
        {
            //WYSIWYG Editor
            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

            $soc = new Societe($db);
            if ($object->socid)
            {
                $soc->fetch($object->socid);
            }

            print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            print '<table class="border" width="100%">';

            // Ref
            print "<tr>";
            print '<td class="titlefield">'.$langs->trans("Ref").'</td><td>';
            print $object->ref;
            print '</td></tr>';

            // Type
            print "<tr>";
            print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
<<<<<<< HEAD
            $form->select_type_fees(GETPOST('type','int')?GETPOST('type','int'):$object->type,'type',0);
=======
            $form->select_type_fees(GETPOST('type', 'int')?GETPOST('type', 'int'):$object->type, 'type', 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Who
            print "<tr>";
            print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
<<<<<<< HEAD
            print $form->select_dolusers(GETPOST('fk_user','int')?GETPOST('fk_user','int'):$object->fk_user, 'fk_user', 0, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
=======
            print $form->select_dolusers(GETPOST('fk_user', 'int')?GETPOST('fk_user', 'int'):$object->fk_user, 'fk_user', 0, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Date
            print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
<<<<<<< HEAD
            print $form->select_date($object->date,'',0,0,0,'update',1,0,1);
=======
            print $form->selectDate($object->date, '', 0, 0, 0, 'update', 1, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Km
            print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td>';
            print '<input name="km" class="flat" size="10" value="'.$object->km.'">';
            print '</td></tr>';

            // Where
            print "<tr>";
            print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
<<<<<<< HEAD
            print $form->select_company($soc->id,'socid','',1);
=======
            print $form->select_company($soc->id, 'socid', '', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Public note
            print '<tr><td class="tdtop">'.$langs->trans("NotePublic").'</td>';
            print '<td>';

            $doleditor = new DolEditor('note_public', $object->note_public, '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, '90%');
            print $doleditor->Create(1);

            print "</td></tr>";

            // Private note
            if (empty($user->societe_id))
            {
                print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td>';
                print '<td>';

                $doleditor = new DolEditor('note_private', $object->note_private, '', 200, 'dolibarr_notes', 'In', false, true, true, ROWS_8, '90%');
                print $doleditor->Create(1);

                print "</td></tr>";
            }

            // Other attributes
            $parameters=array();
<<<<<<< HEAD
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
=======
            $reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print $hookmanager->resPrint;

            print '</table>';

            print '<br><div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
            print '</div>';

            print '</form>';

            print '</div>';
        }
        else
        {
           /*
            * Confirm delete trip
            */
            if ($action == 'delete')
            {
<<<<<<< HEAD
                print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");

=======
                print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("DeleteTrip"), $langs->trans("ConfirmDeleteTrip"), "confirm_delete");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            }

            $soc = new Societe($db);
            if ($object->socid) $soc->fetch($object->socid);

            print '<table class="border" width="100%">';

            $linkback = '<a href="'.DOL_URL_ROOT.'/compta/deplacement/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

            // Ref
            print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
            print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
            print '</td></tr>';

	        $form->load_cache_types_fees();

	        // Type
            print '<tr><td>';
<<<<<<< HEAD
            print $form->editfieldkey("Type",'type',$langs->trans($object->type),$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'select:types_fees');
            print '</td><td>';
            print $form->editfieldval("Type",'type',$form->cache_types_fees[$object->type],$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'select:types_fees');
=======
            print $form->editfieldkey("Type", 'type', $langs->trans($object->type), $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'select:types_fees');
            print '</td><td>';
            print $form->editfieldval("Type", 'type', $form->cache_types_fees[$object->type], $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'select:types_fees');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Who
            print '<tr><td>'.$langs->trans("Person").'</td><td>';
            $userfee=new User($db);
            $userfee->fetch($object->fk_user);
            print $userfee->getNomUrl(1);
            print '</td></tr>';

            // Date
            print '<tr><td>';
<<<<<<< HEAD
            print $form->editfieldkey("Date",'dated',$object->date,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'datepicker');
            print '</td><td>';
            print $form->editfieldval("Date",'dated',$object->date,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'datepicker');
=======
            print $form->editfieldkey("Date", 'dated', $object->date, $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'datepicker');
            print '</td><td>';
            print $form->editfieldval("Date", 'dated', $object->date, $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'datepicker');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print '</td></tr>';

            // Km/Price
            print '<tr><td class="tdtop">';
<<<<<<< HEAD
            print $form->editfieldkey("FeesKilometersOrAmout",'km',$object->km,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'numeric:6');
            print '</td><td>';
            print $form->editfieldval("FeesKilometersOrAmout",'km',$object->km,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'numeric:6');
=======
            print $form->editfieldkey("FeesKilometersOrAmout", 'km', $object->km, $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'numeric:6');
            print '</td><td>';
            print $form->editfieldval("FeesKilometersOrAmout", 'km', $object->km, $object, $conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer, 'numeric:6');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print "</td></tr>";

            // Where
            print '<tr><td>'.$langs->trans("CompanyVisited").'</td>';
            print '<td>';
            if ($soc->id) print $soc->getNomUrl(1);
            print '</td></tr>';

            // Project
            if (! empty($conf->projet->enabled))
            {
                $langs->load('projects');
                print '<tr>';
                print '<td>';

                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('Project');
                print '</td>';
                if ($action != 'classify' && $user->rights->deplacement->creer)
                {
<<<<<<< HEAD
                    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
                    print img_edit($langs->trans('SetProject'),1);
=======
                    print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
                    print img_edit($langs->trans('SetProject'), 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                    print '</a></td>';
                }
                print '</tr></table>';
                print '</td><td colspan="3">';
                if ($action == 'classify')
                {
<<<<<<< HEAD
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'projectid', 0, 0, 1);
                }
                else
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'none', 0, 0);
=======
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1);
                }
                else
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                }
                print '</td>';
                print '</tr>';
            }

            // Statut
            print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

        	// Other attributes
        	$parameters=array('socid'=>$object->id);
        	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print "</table><br>";

            // Notes
            $blocname = 'notes';
            $title = $langs->trans('Notes');
            include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';

            print '</div>';

            /*
             * Barre d'actions
             */

            print '<div class="tabsAction">';

            if ($object->statut < 2) 	// if not refunded
            {
	            if ($user->rights->deplacement->creer)
	            {
	                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	            }
	            else
	            {
<<<<<<< HEAD
	                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
=======
	                print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	            }
            }

            if ($object->statut == 0) 	// if draft
            {
                if ($user->rights->deplacement->creer)
                {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&id='.$id.'">'.$langs->trans('Validate').'</a>';
                }
                else
                {
<<<<<<< HEAD
                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Validate').'</a>';
=======
                    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Validate').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                }
            }

            if ($object->statut == 1) 	// if validated
            {
                if ($user->rights->deplacement->creer)
                {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=classifyrefunded&id='.$id.'">'.$langs->trans('ClassifyRefunded').'</a>';
                }
                else
                {
<<<<<<< HEAD
                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('ClassifyRefunded').'</a>';
=======
                    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('ClassifyRefunded').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                }
            }

            if ($user->rights->deplacement->supprimer)
            {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
            }
            else
            {
<<<<<<< HEAD
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
=======
                print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            }

            print '</div>';
        }
    }
    else
    {
        dol_print_error($db);
    }
}

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
