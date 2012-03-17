<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/compta/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');

$langs->load("companies");
$langs->load("bills");

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'facture',$id,'');

$object = new Facture($db);
$object->fetch($id);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'setnote_public' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note'), ENT_QUOTES));
	if ($result < 0) dol_print_error($db,$object->error);
}


/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new Facture($db);
	$object->fetch($id,$ref);

	$soc = new Societe($db);
    $soc->fetch($object->socid);

    $head = facture_prepare_head($object);
    dol_fiche_head($head, 'note', $langs->trans("InvoiceCustomer"), 0, 'bill');


    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%">'.$langs->trans('Ref').'</td>';
	print '<td colspan="3">';
	$morehtmlref='';
	$discount=new DiscountAbsolute($db);
	$result=$discount->fetch(0,$object->id);
	if ($result > 0)
	{
		$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
	}
	if ($result < 0)
	{
		dol_print_error('',$discount->error);
	}
	print $form->showrefnav($object,'ref','',1,'facnumber','ref',$morehtmlref);
	print '</td></tr>';

    // Company
    print '<tr><td>'.$langs->trans("Company").'</td>';
    print '<td colspan="3">'.$soc->getNomUrl(1,'compta').'</td>';

    print "</table>";

    print '<br>';

	include(DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php');

	dol_fiche_end();
}


llxFooter();

$db->close();
?>
