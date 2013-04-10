<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@capnetworks.com>
* Copyright (C) 2013 	   Florian Henry        <florian.henry@open-concept.pro>
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
 *      \file       htdocs/expedition/note.php
*      \ingroup    expedition
*      \brief      Note card expedition
*/

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';

$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');
$langs->load('propal');

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
$socid='';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, $origin, $origin_id);

$object = new Expedition($db);
$object->fetch($id);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'setnote_public' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote_private' && $user->rights->facture->creer)
{
	$object->fetch($id);
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) dol_print_error($db,$object->error);
}


/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head=shipping_prepare_head($object);
    dol_fiche_head($head, 'note', $langs->trans("Sending"), 0, 'sending');

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/liste.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td></tr>';

	// Customer
	print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
	print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
	print "</tr>";

	// Linked documents
	if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
	{
		print '<tr><td>';
		$objectsrc=new Commande($db);
		$objectsrc->fetch($object->$typeobject->id);
		print $langs->trans("RefOrder").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1,'commande');
		print "</td>\n";
		print '</tr>';
	}
	if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
	{
		print '<tr><td>';
		$objectsrc=new Propal($db);
		$objectsrc->fetch($object->$typeobject->id);
		print $langs->trans("RefProposal").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1,'expedition');
		print "</td>\n";
		print '</tr>';
	}

	// Ref customer
	print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
	print '<td colspan="3">'.$object->ref_customer."</a></td>\n";
	print '</tr>';

	// Date creation
	print '<tr><td>'.$langs->trans("DateCreation").'</td>';
	print '<td colspan="3">'.dol_print_date($object->date_creation,"day")."</td>\n";
	print '</tr>';

	// Delivery date planed
	print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
	print '<td colspan="3">'.dol_print_date($object->date_delivery,"dayhourtext")."</td>\n";
	print '</tr>';

	print '</table>';

	print '<br>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}


llxFooter();

$db->close();
?>
