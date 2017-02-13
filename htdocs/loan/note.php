<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
 * Copyright (C) 2016       Alexandre Spangaro      <aspangaro@zendsi.com>
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
 *   \ingroup    loan
 *   \brief      Tab for notes on loan
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

$title = $langs->trans("Loan") . ' - ' . $langs->trans("Notes");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$title,$help_url);

if ($id > 0)
{
    /*
     * Affichage onglets
     */
	$totalpaid=$object->getSumPayment();

    $head = loan_prepare_head($object);

    dol_fiche_head($head, 'note', $langs->trans("Loan"), 0, 'bill');

	$morehtmlref='<div class="refidno">';
	// Ref loan
	$morehtmlref.=$form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', null, null, '', 1);
	$morehtmlref.='</div>';

	$linkback = '<a href="' . DOL_URL_ROOT . '/loan/index.php">' . $langs->trans("BackToList") . '</a>';

	$object->totalpaid = $totalpaid;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

    $cssclass='titlefield';
    $permission = $user->rights->loan->write;  // Used by the include of notes.tpl.php
    include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    dol_fiche_end();
}

llxFooter();
$db->close();

