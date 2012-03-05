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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/contrat/contact.php
 *      \ingroup    contrat
 *      \brief      Onglet de gestion des contacts des contrats
 */

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');


$langs->load("contracts");
$langs->load("companies");

$contratid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid);


/*
 * Ajout d'un nouveau contact
 */

if ($_POST["action"] == 'addcontact' && $user->rights->contrat->creer)
{
	$result = 0;
	$contrat = new Contrat($db);
	$result = $contrat->fetch($_GET["id"]);

    if ($result > 0 && $_POST["id"] > 0)
    {
  		$result = $contrat->add_contact($_POST["contactid"], $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$contrat->id);
		exit;
	}
	else
	{
		if ($contrat->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		{
			$langs->load("errors");
			$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
		}
		else
		{
			$mesg = '<div class="error">'.$contrat->error.'</div>';
		}
	}
}

// bascule du statut d'un contact
if ($_GET["action"] == 'swapstatut' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	if ($contrat->fetch(GETPOST('id','int')))
	{
	    $result=$contrat->swapContactStatus(GETPOST('ligne'));
	}
	else
	{
		dol_print_error($db,$contrat->error);
	}
}

// Efface un contact
if ($_GET["action"] == 'deleteline' && $user->rights->contrat->creer)
{
	$contrat = new Contrat($db);
	$contrat->fetch($_GET["id"]);
	$result = $contrat->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		Header("Location: contact.php?id=".$contrat->id);
		exit;
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("ContractCard"), "Contrat");

$form = new Form($db);
$formcompany= new FormCompany($db);
$contactstatic=new Contact($db);
$userstatic=new User($db);

dol_htmloutput_mesg($mesg);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
$id = $_GET["id"];
if ($id > 0)
{
	$contrat = New Contrat($db);
	if ($contrat->fetch($id) > 0)
	{
		if ($mesg) print $mesg;

		$soc = new Societe($db);
		$soc->fetch($contrat->socid);

	    $head = contract_prepare_head($contrat);

		$hselected=1;

		dol_fiche_head($head, $hselected, $langs->trans("Contract"), 0, 'contract');

		/*
		 *   Contrat
		 */
		print '<table class="border" width="100%">';

		// Reference du contrat
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $contrat->ref;
		print "</td></tr>";

		// Customer
		print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

		// Ligne info remises tiers
	    print '<tr><td>'.$langs->trans('Discount').'</td><td>';
		if ($contrat->societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$contrat->societe->remise_client);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$contrat->societe->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';

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
		if ($_GET["action"] != 'editline' && $user->rights->contrat->creer)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Source").'</td>';
			print '<td>'.$langs->trans("Company").'</td>';
			print '<td>'.$langs->trans("Contacts").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			$var = false;

			print '<form action="contact.php?id='.$id.'" method="post">';
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
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td colspan="1">';
			//$userAlreadySelected = $contrat->getListContactId('internal'); 	// On ne doit pas desactiver un contact deja selectionner car on doit pouvoir le seclectionner une deuxieme fois pour un autre type
			$form->select_users($user->id,'contactid',0,$userAlreadySelected);
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($contrat, '', 'type','internal');
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
			print "<tr $bc[$var]>";

			print '<td nowrap="nowrap">';
			print img_object('','contact').' '.$langs->trans("ThirdPartyContacts");
			print '</td>';

			print '<td colspan="1">';
			$selectedCompany = isset($_GET["newcompany"])?$_GET["newcompany"]:$contrat->societe->id;
			$selectedCompany = $formcompany->selectCompaniesForNewContact($contrat, 'id', $selectedCompany, 'newcompany');
			print '</td>';

			print '<td colspan="1">';
			$nbofcontacts=$form->select_contacts($selectedCompany, '', 'contactid');
			if ($nbofcontacts == 0) print $langs->trans("NoContactDefined");
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($contrat, '', 'type','external');
			print '</td>';
			print '<td align="right" colspan="3" ><input type="submit" class="button" value="'.$langs->trans("Add").'"';
			if (! $nbofcontacts) print ' disabled="disabled"';
			print '></td>';
			print '</tr>';

			print "</form>";

		}

        print '<tr><td colspan="6">&nbsp;</td></tr>';

		// Liste des contacts li�s
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
    		$tab = $contrat->liste_contact(-1,$source);
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
				if ($contrat->statut >= 0) print '<a href="'.DOL_URL_ROOT.'/contrat/contact.php?id='.$contrat->id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
				print $contactstatic->LibStatut($tab[$i]['status'],3);
				if ($contrat->statut >= 0) print '</a>';
				print '</td>';

				// Icon delete
				print '<td align="center" nowrap>';
				if ($user->rights->contrat->creer)
				{
					print '&nbsp;';
					print '<a href="contact.php?id='.$contrat->id.'&amp;action=deleteline&amp;lineid='.$tab[$i]['rowid'].'">';
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

llxFooter();
?>
