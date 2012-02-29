<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$comid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $comid,'');


/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->commande->creer)
{

	$result = 0;
	$commande = new Commande($db);
	$result = $commande->fetch($_GET["id"]);

    if ($result > 0 && $_GET["id"] > 0)
    {
  		$result = $commande->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$commande->id);
		exit;
	}
	else
	{
		if ($commande->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$commande->error.'</div>';
		}
	}
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	if ($commande->fetch(GETPOST('id','int')))
	{
	    $result=$commande->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET["id"]);
	$result = $commande->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$commande->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
dol_htmloutput_mesg($mesg);

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$langs->trans("OrderCard");
	$commande = new Commande($db);
	if ( $commande->fetch($_GET['id'],$_GET['ref']) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);


		$head = commande_prepare_head($commande);
		dol_fiche_head($head, 'contact', $langs->trans("CustomerOrder"), 0, 'order');


	   /*
		*   Facture synthese pour rappel
		*/
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="18%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($commande,'ref','',1,'ref','ref');
		print "</td></tr>";

		// Ref commande client
		print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
        print '</td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
		print $commande->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		if ( is_null($commande->client) )
			$commande->fetch_thirdparty();

		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$commande->client->getNomUrl(1).'</td></tr>';
		print "</table>";

		print '</div>';

		/*
		* Lignes de contacts
		*/
		echo '<br><table class="noborder" width="100%">';

		/*
		* Ajouter une ligne de contact
		* Non affiche en mode modification de ligne
		*/
		if ($_GET["action"] != 'editline' && $user->rights->commande->creer)
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

			print '<form action="contact.php?id='.$id.'" method="post">';
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
			//$userAlreadySelected = $commande->getListContactId('internal');	// On ne doit pas desactiver un contact deja selectionne car on doit pouvoir le selectionner une deuxieme fois pour un autre type
			$form->select_users($user->id,'contactid',0,$userAlreadySelected);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($commande, '', 'type','internal');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print '</form>';

			print '<form action="contact.php?id='.$id.'" method="post">';
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
			$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$commande->client->id;
			$selectedCompany = $formcompany->selectCompaniesForNewContact($commande, 'id', $selectedCompany, 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			$nbofcontacts=$form->select_contacts($selectedCompany, '', 'contactid');
			if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($commande, '', 'type','external');
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
			$tab = $commande->liste_contact(-1,$source);
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
				if ($commande->statut >= 0)	print '<a href="contact.php?id='.$commande->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($commande->statut >= 0)	print '</a>';
				print '</td>';

				// Icon update et delete
				print '<td align="center" nowrap="nowrap" colspan="2">';
				if ($commande->statut < 5 && $user->rights->commande->creer)
				{
					print '&nbsp;';
					print '<a href="contact.php?id='.$commande->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
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
	else
	{
		// Contrat non trouve
		print "ErrorRecordNotFound";
	}
}

$db->close();

llxFooter();
?>