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
 *     \file       htdocs/expedition/contact.php
 *     \ingroup    expedition
 *     \brief      Onglet de gestion des contacts de expedition
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/sendings.lib.php");
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
$result = restrictedArea($user, 'expedition', $id,'');

$object = new Expedition($db);
if ($id > 0 || ! empty($ref))
{
    $object->fetch($id, $ref);

    if (!empty($object->origin))
    {
        $typeobject = $object->origin;
        $origin = $object->origin;
        $object->fetch_origin();
    }

    // Linked documents
    if ($typeobject == 'commande' && $object->$typeobject->id && $conf->commande->enabled)
    {
        $objectsrc=new Commande($db);
        $objectsrc->fetch($object->$typeobject->id);
    }
    if ($typeobject == 'propal' && $object->$typeobject->id && $conf->propal->enabled)
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
  		$result = $objectsrc->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		if ($objectsrc->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$objectsrc->error.'</div>';
		}
	}
}

// bascule du statut d'un contact
else if ($action == 'swapstatut' && $user->rights->expedition->creer)
{
    $result=$objectsrc->swapContactStatus(GETPOST('ligne'));
}

// Efface un contact
else if ($action == 'deleteline' && $user->rights->expedition->creer)
{
	$result = $objectsrc->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
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
dol_htmloutput_mesg($mesg);

if ($id > 0 || ! empty($ref))
{
	$langs->trans("OrderCard");

	$soc = new Societe($db);
	$soc->fetch($object->socid);


	$head = shipping_prepare_head($object);
	dol_fiche_head($head, 'contact', $langs->trans("Sending"), 0, 'sending');

	if (is_null($object->client))	$object->fetch_thirdparty();

   /*
	*   Facture synthese pour rappel
	*/
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object,'ref','',1,'ref','ref');
	print "</td></tr>";

	// Customer
	print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
	print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
	print "</tr>";

	// Linked documents
	if ($typeobject == 'commande' && $object->$typeobject->id && $conf->commande->enabled)
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
	if ($typeobject == 'propal' && $object->$typeobject->id && $conf->propal->enabled)
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
    print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
    print '</td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
	print $objectsrc->ref_client;
	print '</td>';
	print '</tr>';
	
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
			$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$object->socid,'fk_address','shipping',$object->id);
		}
		else
		{
			$formother->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$object->socid,'none','shipping',$object->id);
		}
		print '</td></tr>';
	}

	print "</table>";

	print '</div>';

	// Lignes de contacts
	echo '<br><table class="noborder" width="100%">';

	/*
	 * Ajouter une ligne de contact. Non affiche en mode modification de ligne
	 */
	if ($action != 'editline' && $user->rights->expedition->creer)
	{
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td>&nbsp;</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$var = false;

		print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="addcontact">';
		print '<input type="hidden" name="source" value="internal">';
		print '<input type="hidden" name="id" value="'.$id.'">';

		// Ligne ajout pour contact interne
		print '<tr '.$bc[$var].'>';

		print '<td nowrap="nowrap">';
		print img_object('','user').' '.$langs->trans("Users");
		print '</td>';

		print '<td colspan="1">';
		print $conf->global->MAIN_INFO_SOCIETE_NOM;
		print '</td>';

		print '<td colspan="1">';
		//$userAlreadySelected = $object->getListContactId('internal');	// On ne doit pas desactiver un contact deja selectionne car on doit pouvoir le selectionner une deuxieme fois pour un autre type
		$form->select_users($user->id,'contactid',0,$userAlreadySelected);
		print '</td>';
		print '<td>';
		$formcompany->selectTypeContact($objectsrc, '', 'type','internal');
		print '</td>';
		print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
		print '</tr>';

		print '</form>';

		print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="addcontact">';
		print '<input type="hidden" name="source" value="external">';
		print '<input type="hidden" name="id" value="'.$id.'">';

		// Ligne ajout pour contact externe
		$var=!$var;
		print '<tr '.$bc[$var].'>';

		print '<td nowrap="nowrap">';
		print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
		print '</td>';

		print '<td colspan="1">';
		$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$object->client->id;
		$selectedCompany = $formcompany->selectCompaniesForNewContact($objectsrc, 'id', $selectedCompany, 'newcompany', '', $object->id);
		print '</td>';

		print '<td colspan="1">';
		$nbofcontacts=$form->select_contacts($selectedCompany, '', 'contactid');
		if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
		print '</td>';
		print '<td>';

		$formcompany->selectTypeContact($objectsrc, '', 'type','external');
		print '</td>';
		print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
		if (! $nbofcontacts) print ' disabled="disabled"';
		print '></td>';
		print '</tr>';

		print "</form>";

		print '<tr><td colspan="7">&nbsp;</td></tr>';
	}

	// List of linked contacts
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Source").'</td>';
	print '<td>'.$langs->trans("Company").'</td>';
	print '<td>'.$langs->trans("Contacts").'</td>';
	print '<td>'.$langs->trans("ContactType").'</td>';
	print '<td align="center">'.$langs->trans("Status").'</td>';
	print '<td colspan="2">&nbsp;</td>';
	print "</tr>\n";

	$companystatic=new Societe($db);
	$var = true;

	foreach(array('internal','external') as $source)
	{
		$tab = $objectsrc->liste_contact(-1,$source);
		$num=count($tab);

		$i = 0;
		while ($i < $num)
		{
			$var = !$var;

			print '<tr '.$bc[$var].' valign="top">';

			// Source
			print '<td align="left">';
			if ($tab[$i]['source']=='internal') print $langs->trans("User");
			if ($tab[$i]['source']=='external') print $langs->trans("ThirdPartyContact");
			print '</td>';

			// Societe
			print '<td align="left">';
			if ($tab[$i]['socid'] > 0)
			{
				$companystatic->fetch($tab[$i]['socid']);
				print $companystatic->getNomUrl(1);
			}
			if ($tab[$i]['socid'] < 0)
			{
				print $conf->global->MAIN_INFO_SOCIETE_NOM;
			}
			if (! $tab[$i]['socid'])
			{
				print '&nbsp;';
			}
			print '</td>';

			// Contact
			print '<td>';
            if ($tab[$i]['source']=='internal')
            {
                $userstatic->id=$tab[$i]['id'];
                $userstatic->lastname=$tab[$i]['lastname'];
                $userstatic->firstname=$tab[$i]['firstname'];
                print $userstatic->getNomUrl(1);
            }
            if ($tab[$i]['source']=='external')
            {
                $contactstatic->id=$tab[$i]['id'];
                $contactstatic->lastname=$tab[$i]['lastname'];
                $contactstatic->firstname=$tab[$i]['firstname'];
                print $contactstatic->getNomUrl(1);
            }
			print '</td>';

			// Type de contact
			print '<td>'.$tab[$i]['libelle'].'</td>';

			// Statut
			print '<td align="center">';
			// Activation desativation du contact
			if ($object->statut >= 0)	print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
			print $contactstatic->LibStatut($tab[$i]['status'],3);
			if ($object->statut >= 0)	print '</a>';
			print '</td>';

			// Icon update et delete
			print '<td align="center" nowrap="nowrap" colspan="2">';
			if ($object->statut < 5 && $user->rights->expedition->creer)
			{
				print '&nbsp;';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
				print img_delete();
				print '</a>';
			}
			print '</td>';

			print "</tr>\n";

			$i ++;
		}
	}
	print "</table>";
}

llxFooter();

$db->close();
?>