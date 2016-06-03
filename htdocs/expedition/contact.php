<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/expedition/contact.php
 *     \ingroup    expedition
 *     \brief      Onglet de gestion des contacts de expedition
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition', $id,'');

$object = new Expedition($db);
if ($id > 0 || ! empty($ref))
{
    $object->fetch($id, $ref);
    $object->fetch_thirdparty();

    if (!empty($object->origin))
    {
        $typeobject = $object->origin;
        $origin = $object->origin;
        $object->fetch_origin();
    }

    // Linked documents
    if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
    {
        $objectsrc=new Commande($db);
        $objectsrc->fetch($object->$typeobject->id);
    }
    if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
    {
        $objectsrc=new Propal($db);
        $objectsrc->fetch($object->$typeobject->id);
    }
}


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->expedition->creer)
{
    if ($result > 0 && $id > 0)
    {
  		$result = $objectsrc->add_contact(GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'), $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($objectsrc->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') 
		{
			$langs->load("errors");
			$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
		} else {
			$mesg = $objectsrc->error;
			$mesgs = $objectsrc->errors;
		}
		setEventMessages($mesg, $mesgs, 'errors');
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->expedition->creer)
{
    $result=$objectsrc->swapContactStatus(GETPOST('ligne'));
}

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->expedition->creer)
{
	$result = $objectsrc->delete_contact(GETPOST("lineid"));

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}

else if ($action == 'setaddress' && $user->rights->expedition->creer)
{
	$object->fetch($id);
	$result=$object->setDeliveryAddress($_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:expeditions_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	$langs->trans("OrderCard");

	$head = shipping_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Shipment"), 0, 'sending');


   /*
	*   Facture synthese pour rappel
	*/
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print "</td></tr>";

	// Customer
	print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
	print '<td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td>';
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

	// Ref expedition client
	print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
	print $langs->trans('RefCustomer').'</td><td align="left">';
    print '</td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
	print $objectsrc->ref_client;
	print '</td>';
	print '</tr>';

	// Delivery address
	if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DeliveryAddress');
		print '</td>';

		if ($action != 'editdelivery_address' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_address&amp;socid='.$object->socid.'&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDeliveryAddress'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';

		if ($action == 'editdelivery_address')
		{
			$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$object->socid,'fk_address','shipping',$object->id);
		}
		else
		{
			$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$object->socid,'none','shipping',$object->id);
		}
		print '</td></tr>';
	}

	print "</table>";

	dol_fiche_end();

	// Lignes de contacts
	echo '<br>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
	foreach($dirtpls as $reldir)
	{
	    $res=@include dol_buildpath($reldir.'/contacts.tpl.php');
	    if ($res) break;
	}

}

llxFooter();

$db->close();
