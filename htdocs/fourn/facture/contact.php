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
        \file       htdocs/fourn/facture/contact.php
        \ingroup    facture, fournisseur
        \brief      Onglet de gestion des contacts des factures
        \version    $Id$
*/

require ("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/html.formcompany.class.php');

$langs->load("facture");
$langs->load("companies");

$facid = isset($_GET["facid"])?$_GET["facid"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $facid, '', 'facture');


/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->fournisseur->facture->creer)
{

	$result = 0;
	$facture = new FactureFournisseur($db);
	$result = $facture->fetch($_GET["facid"]);

    if ($result > 0 && $_GET["facid"] > 0)
    {
  		$result = $facture->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?facid=".$facture->id);
		exit;
	}
	else
	{
		if ($facture->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$facture->error.'</div>';
		}
	}
}
// modification d'un contact. On enregistre le type
if ($_POST["action"] == 'updateligne' && $user->rights->fournisseur->facture->creer)
{
	$facture = new FactureFournisseur($db);
	if ($facture->fetch($_GET["facid"]))
	{
		$contact = $facture->detail_contact($_POST["elrowid"]);
		$type = $_POST["type"];
		$statut = $contact->statut;

		$result = $facture->update_contact($_POST["elrowid"], $statut, $type);
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
if ($_GET["action"] == 'swapstatut' && $user->rights->fournisseur->facture->creer)
{
	$facture = new FactureFournisseur($db);
	if ($facture->fetch($_GET["facid"]))
	{
		$contact = $facture->detail_contact($_GET["ligne"]);
		$id_type_contact = $contact->fk_c_type_contact;
		$statut = ($contact->statut == 4) ? 5 : 4;

		$result = $facture->update_contact($_GET["ligne"], $statut, $id_type_contact);
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
if ($_GET["action"] == 'deleteline' && $user->rights->fournisseur->facture->creer)
{
	$facture = new FactureFournisseur($db);
	$facture->fetch($_GET["facid"]);
	$result = $facture->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?facid=".$facture->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Bill"), "Facture");

$html = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic=new Contact($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
if (isset($mesg)) print $mesg;
$id = $_GET["facid"];
if ($id > 0)
{
	$facture = new FactureFournisseur($db);
	if ($facture->fetch($_GET['facid'], $user->societe_id) > 0)
	{
		$facture->fetch_client();

		$head = facturefourn_prepare_head($facture);

		dol_fiche_head($head, 'contact', $langs->trans('SupplierInvoice'));

		/*
		 *   Facture synthese pour rappel
		 */
		print '<table class="border" width="100%">';

		// Reference du facture
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $facture->ref;
		print "</td></tr>";

        // Ref supplier
        print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$fac->ref_supplier.'</td>';
        print "</tr>\n";

		// Third party
		print "<tr><td>".$langs->trans("Company")."</td>";
		print '<td colspan="3">'.$facture->client->getNomUrl(1,'compta').'</td></tr>';
		print "</table>";

		print '</div>';

		/*
		 * Lignes de contacts
		 */
		echo '<br><table class="noborder" width="100%">';

		/*
		 * Ajouter une ligne de contact
		 * Non affich� en mode modification de ligne
		 */
		if ($_GET["action"] != 'editline' && $user->rights->facture->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="contact.php?facid='.$id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="internal">';
			print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact interne
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','user').' '.$langs->trans("Users");
			print '</td>';

			print '<td colspan="1">';
			print $mysoc->nom;
			print '</td>';

			print '<td colspan="1">';
			//$userAlreadySelected = $facture->getListContactId('internal');	// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
			$html->select_users($user->id,'contactid',0,$userAlreadySelected);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($facture, '', 'type','internal');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>';

            print '</form>';

			print '<form action="contact.php?facid='.$id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="source" value="external">';
			print '<input type="hidden" name="id" value="'.$id.'">';

            // Ligne ajout pour contact externe
			$var=!$var;
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
			print '</td>';

			print '<td colspan="1">';
			$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$facture->client->id;
			$selectedCompany = $formcompany->selectCompaniesForNewContact($facture, 'facid', $selectedCompany, $htmlname = 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			//$contactAlreadySelected = $facture->getListContactId('external');		// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
			$nbofcontacts=$html->select_contacts($selectedCompany, '', 'contactid', 0, $contactAlreadySelected);
			if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($facture, '', 'type','external');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
			if (! $nbofcontacts) print ' disabled="true"';
			print '></td>';
			print '</tr>';

			print "</form>";

            print '<tr><td colspan="6">&nbsp;</td></tr>';
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

		$societe = new Societe($db);
    	$var = true;

		foreach(array('internal','external') as $source)
		{
			$tab = $facture->liste_contact(-1,$source);
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
					print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$tab[$i]['socid'].'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.$societe->get_nom($tab[$i]['socid']);
					print '</a>';
                }
				if ($tab[$i]['socid'] < 0)
				{
                    print $mysoc->nom;
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
				if ($facture->statut >= 0) print '<a href="contact.php?facid='.$facture->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($facture->statut >= 0) print '</a>';
				print '</td>';

				// Icon update et delete (statut contrat 0=brouillon,1=valid�,2=ferm�)
				print '<td align="center" nowrap>';
				if ($user->rights->facture->creer)
				{
					print '&nbsp;';
					print '<a href="contact.php?facid='.$facture->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
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