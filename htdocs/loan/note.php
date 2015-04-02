<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 *   \file       htdocs/loan/note.php
 *   \brief      Tab for notes on loan
 *   \ingroup    loan
 */

require '../main.inc.php';
require_once(DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';

$action = GETPOST('action');

$langs->load('loan');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'loan', $id, '&loan');

$object = new Loan($db);
if ($id > 0) $object->fetch($id);

$permissionnote=$user->rights->loan->write;  // Used by the include of actions_setnotes.inc.php


/*
 *  Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once


/*
 *  View
 */

$form = new Form($db);

llxHeader('',$langs->trans("LoanArea").' - '.$langs->trans("Notes"),'');

if ($id > 0)
{
    /*
     * Affichage onglets
     */

    $head = loan_prepare_head($object);

    dol_fiche_head($head, 'note', $langs->trans("Loan"),0,'loan');


    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
    print '<td colspan="3">';
    print $form->showrefnav($object,'id','','','rowid','ref');
    print '</td></tr>';
    // Name
    print '<tr><td width="20%">'.$langs->trans("Name").'</td>';
    print '<td colspan="3">'.$object->label.'</td></tr>';

    print "</table>";

    print '<br>';

    $colwidth='25';
    $permission = $user->rights->loan->write;  // Used by the include of notes.tpl.php
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';


    dol_fiche_end();
}

llxFooter();
$db->close();

