<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/commande/contact.php
 *     \ingroup    commande
 *     \brief      Onglet de gestion des contacts de commande
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $id,'');

$object = new Commande($db);

/*
 * Ajout d'un nouveau contact
 */

if ($action == 'addcontact' && $user->rights->commande->creer)
{
	$result = $object->fetch($id);

    if ($result > 0 && $id > 0)
    {
  		$result = $object->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->commande->creer)
{
	if ($object->fetch($id))
	{
	    $result=$object->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
else if ($action == 'deletecontact' && $user->rights->commande->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}

else if ($action == 'setaddress' && $user->rights->commande->creer)
{
	$object->fetch($id);
	$object->setDeliveryAddress($_POST['fk_address']);
}

/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

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
dol_htmloutput_mesg($mesg);

if ($id > 0 || ! empty($ref))
{
	$langs->trans("OrderCard");
	
	if ($object->fetch($id, $ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);


		$head = commande_prepare_head($object);
		dol_fiche_head($head, 'contact', $langs->trans("CustomerOrder"), 0, 'order');


	   /*
		*   Facture synthese pour rappel
		*/
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object,'ref','',1,'ref','ref');
		print "</td></tr>";

		// Ref commande client
		print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
        print '</td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
		print $object->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		if (is_null($object->client))	$object->fetch_thirdparty();

		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$object->client->getNomUrl(1).'</td></tr>';
		
		// Delivery address
		if ($conf->global->SOCIETE_ADDRESSES_MANAGEMENT)
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
				$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,GETPOST('socid','int'),'fk_address','commande',$object->id);
			}
			else
			{
				$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,GETPOST('socid','int'),'none','commande',$object->id);
			}
			print '</td></tr>';
		}
		
		print "</table>";

		print '</div>';

		print '<br>';
		
		// Contacts lines
		include(DOL_DOCUMENT_ROOT.'/core/tpl/contacts.tpl.php');

	}
	else
	{
		// Contrat non trouve
		print "ErrorRecordNotFound";
	}
}


$db->close();
llxFooter();
?>