<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/contrat/note.php
 *      \ingroup    contrat
 *      \brief      Fiche de notes sur un contrat
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

$langs->load("companies");
$langs->load("contracts");

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$socid=GETPOST('socid','int');
$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$id);

$object = new Contrat($db);
$object->fetch($id,$ref);

$permissionnote=$user->rights->contrat->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
    $object->fetch_thirdparty();

    $head = contract_prepare_head($object);

    $hselected = 2;

    dol_fiche_head($head, 'note', $langs->trans("Contract"), 0, 'contract');


    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

    // Reference
	print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="5">'.$form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '').'</td></tr>';

    // Societe
    print '<tr><td>'.$langs->trans("Customer").'</td>';
    print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

	// Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discount').'</td><td>';
	if ($object->thirdparty->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$object->thirdparty->remise_percent);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$object->thirdparty->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->currency));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	print "</table>";

	print '<br>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();

}


llxFooter();
$db->close();
