<?php
/* Copyright (C) 2005      Patrick Rouillon     <patrick@rouillon.net>
 * Copyright (C) 2005-2009 Destailleur Laurent  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/comm/propal/contact.php
 *       \ingroup    propal
 *       \brief      Onglet de gestion des contacts de propal
 *       \version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');

$langs->load("facture");
$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");

$id = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');


/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->propale->creer)
{

	$result = 0;
	$propal = new Propal($db);
	$result = $propal->fetch($_GET["id"]);

    if ($result > 0 && $_GET["id"] > 0)
    {
  		$result = $propal->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$propal->id);
		exit;
	}
	else
	{
		if ($propal->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$propal->error.'</div>';
		}
	}
}
// modification d'un contact. On enregistre le type
if ($_POST["action"] == 'updateligne' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	if ($propal->fetch($_GET["id"]))
	{
		$contact = $propal->detail_contact($_POST["elrowid"]);
		$type = $_POST["type"];
		$statut = $contact->statut;

		$result = $propal->update_contact($_POST["elrowid"], $statut, $type);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dol_print_error($db, "result=$result");
			$db->rollback();
		}
	} else
	{
		dol_print_error($db);
	}
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	if ($propal->fetch($_GET["id"]))
	{
		$contact = $propal->detail_contact($_GET["ligne"]);
		$id_type_contact = $contact->fk_c_type_contact;
		$statut = ($contact->statut == 4) ? 5 : 4;

		$result = $propal->update_contact($_GET["ligne"], $statut, $id_type_contact);
		if ($result >= 0)
		{
			$db->commit();
		} else
		{
			dol_print_error($db, "result=$result");
			$db->rollback();
		}
	} else
	{
		dol_print_error($db);
	}
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET["id"]);
	$result = $propal->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$propal->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Proposal"), "Propal");

$html = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if (isset($mesg)) print $mesg;

$id = $_GET["id"];
$ref= $_GET["ref"];
if ($id > 0 || ! empty($ref))
{
	$propal = New Propal($db);
	if ( $propal->fetch($id,$ref) > 0)
	{
		$soc = new Societe($db, $propal->socid);
		$soc->fetch($propal->socid);


		$head = propal_prepare_head($propal);
		dol_fiche_head($head, 'contact', $langs->trans("Proposal"), 0, 'propal');


		/*
		*   Propal synthese pour rappel
		*/
		print '<table class="border" width="100%">';

		$linkback="<a href=\"".$_SERVER["PHP_SELF"]."?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

		// Ref
		print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $html->showrefnav($propal,'ref',$linkback,1,'ref','ref','');
		print '</td></tr>';

		// Ref client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		print $propal->ref_client;
		print '</td>';
		print '</tr>';

		// Customer
		if ( is_null($propal->client) )
			$propal->fetch_client();
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$propal->client->getNomUrl(1).'</td></tr>';

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
		if ($_GET["action"] != 'editline' && $user->rights->propale->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';

			// Ligne ajout pour contact interne
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','user').' '.$langs->trans("Users");
			print '</td>';

			print '<td colspan="1">';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			// On recupere les id des users deja selectionnes
			//$userAlreadySelected = $propal->getListContactId('internal');	// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
			$html->select_users($user->id,'contactid',0,$userAlreadySelected);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($propal, '', 'type','internal');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

			print '</form>';

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="external">';

			// Ligne ajout pour contact externe
			$var=!$var;
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
			print '</td>';

			print '<td colspan="1">';
			$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$propal->client->id;
			$selectedCompany = $formcompany->selectCompaniesForNewContact($propal, 'id', $selectedCompany, 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			//$contactAlreadySelected = $propal->getListContactId('external');		// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
			$nbofcontacts=$html->select_contacts($selectedCompany, '', 'contactid', 0, $contactAlreadySelected);
			if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($propal, '', 'type','external');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
			if (! $nbofcontacts) print ' disabled="true"';
			print '></td>';
			print '</tr>';

			print "</form>";

			print '<tr><td colspan="6">&nbsp;</td></tr>';
		}

		// Liste des contacts lies
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("Company").'</td>';
		print '<td>'.$langs->trans("Contacts").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td align="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$companystatic = new Societe($db);
		$var = true;

		foreach(array('internal','external') as $source)
		{
			$tab = $propal->liste_contact(-1,$source);
			$num=sizeof($tab);

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
					print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowUser"),"user").' '.$tab[$i]['nom'].'</a>';
				}
				if ($tab[$i]['source']=='external')
				{
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$tab[$i]['id'].'">';
					print img_object($langs->trans("ShowContact"),"contact").' '.$tab[$i]['nom'].'</a>';
				}
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td align="center">';
				// Activation desativation du contact
				if ($propal->statut >= 0) print '<a href="contact.php?id='.$propal->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($propal->statut >= 0) print '</a>';
				print '</td>';

				// Icon update et delete
				print '<td align="center" nowrap>';
				if ($user->rights->propale->creer)
				{
					print '&nbsp;';
					print '<a href="contact.php?id='.$propal->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
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
		print "ErrorRecordNotFound";
	}
}

$db->close();

llxFooter('$Date$');
?>