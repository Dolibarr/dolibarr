<?php
/* Copyright (C) 2005     	Patrick Rouillon    <patrick@rouillon.net>
 * Copyright (C) 2005-2011	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2015	Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2014		Charles-Fr Benke	<charles.fr@benke.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
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
 *     \file       htdocs/societe/societecontact.php
 *     \ingroup    societe
 *     \brief      Onglet de gestion des contacts additionnel d'une société
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("orders");
$langs->load("companies");

$id=GETPOST('id','int')?GETPOST('id','int'):GETPOST('socid','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $id,'');

$object = new Societe($db);


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->societe->creer)
{
	$result = $object->fetch($id);

    if ($result > 0 && $id > 0)
    {
    	$contactid = (GETPOST('userid','int') ? GETPOST('userid','int') : GETPOST('contactid','int'));
  		$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
    }

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
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
else if ($action == 'swapstatut' && $user->rights->societe->creer)
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
else if ($action == 'deletecontact' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result = $object->delete_contact($_GET["lineid"]);

	if ($result >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else {
		dol_print_error($db);
	}
}
/*
else if ($action == 'setaddress' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->setDeliveryAddress($_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}*/


/*
 * View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);


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
	if ($object->fetch($id, $ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$head = societe_prepare_head($object);
		dol_fiche_head($head, 'contact', $langs->trans("ThirdParty"), -1, 'company');

		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    	print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

    	// Prospect/Customer
    	/*print '<tr><td class="titlefield">'.$langs->trans('ProspectCustomer').'</td><td>';
    	print $object->getLibCustProspStatut();
    	print '</td></tr>';

    	// Supplier
    	print '<tr><td>'.$langs->trans('Supplier').'</td><td>';
    	print yn($object->fournisseur);
    	print '</td></tr>';*/

		if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
		{
		    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
		}

		if ($object->client)
		{
		    print '<tr><td class="titlefield">';
		    print $langs->trans('CustomerCode').'</td><td colspan="3">';
		    print $object->code_client;
		    if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		    print '</td></tr>';
		}

		if ($object->fournisseur)
		{
		    print '<tr><td class="titlefield">';
		    print $langs->trans('SupplierCode').'</td><td colspan="3">';
		    print $object->code_fournisseur;
		    if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		    print '</td></tr>';
		}
		print '</table>';

		print '</div>';

		print '</form>';
		print '<br>';

		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
		foreach($dirtpls as $reldir)
		{
			$res=@include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) break;
		}

		// additionnal list with adherents of company
		if (! empty($conf->adherent->enabled) && $user->rights->adherent->lire)
		{
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

			$membertypestatic=new AdherentType($db);
			$memberstatic=new Adherent($db);

			$langs->load("members");
			$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
			$sql.= " d.datefin,";
			$sql.= " d.email, d.fk_adherent_type as type_id, d.morphy, d.statut,";
			$sql.= " t.libelle as type, t.subscription";
			$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d";
			$sql.= ", ".MAIN_DB_PREFIX."adherent_type as t";
			$sql.= " WHERE d.fk_soc = ".$id;
			$sql.= " AND d.fk_adherent_type = t.rowid";

			dol_syslog("get list sql=".$sql);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);

				if ($num  > 0 )
				{
					$titre=$langs->trans("MembersListOfTiers");
					print '<br>';

					print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'');

					print "<table class=\"noborder\" width=\"100%\">";
					print '<tr class="liste_titre">';
					print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"d.rowid",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre( $langs->trans("Name")." / ".$langs->trans("Company"),$_SERVER["PHP_SELF"],"d.lastname",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("Login",$_SERVER["PHP_SELF"],"d.login",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("Type",$_SERVER["PHP_SELF"],"t.libelle",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("Person",$_SERVER["PHP_SELF"],"d.morphy",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("EMail",$_SERVER["PHP_SELF"],"d.email",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("Status",$_SERVER["PHP_SELF"],"d.statut,d.datefin",$param,"","",$sortfield,$sortorder);
					print_liste_field_titre("EndSubscription",$_SERVER["PHP_SELF"],"d.datefin",$param,"",'align="center"',$sortfield,$sortorder);
					print "</tr>\n";

					$var=True;
					$i=0;
					while ($i < $num && $i < $conf->liste_limit)
					{
						$objp = $db->fetch_object($resql);

						$datefin=$db->jdate($objp->datefin);
						$memberstatic->id=$objp->rowid;
						$memberstatic->ref=$objp->rowid;
						$memberstatic->lastname=$objp->lastname;
						$memberstatic->firstname=$objp->firstname;
						$memberstatic->statut=$objp->statut;
						$memberstatic->datefin=$db->jdate($objp->datefin);

						$companyname=$objp->company;


						print '<tr class="oddeven">';

						// Ref
						print "<td>";
						print $memberstatic->getNomUrl(1);
						print "</td>\n";

						// Lastname
						print "<td><a href=\"card.php?rowid=$objp->rowid\">";
						print ((! empty($objp->lastname) || ! empty($objp->firstname)) ? dol_trunc($memberstatic->getFullName($langs)) : '');
						print (((! empty($objp->lastname) || ! empty($objp->firstname)) && ! empty($companyname)) ? ' / ' : '');
						print (! empty($companyname) ? dol_trunc($companyname, 32) : '');
						print "</a></td>\n";

						// Login
						print "<td>".$objp->login."</td>\n";

						// Type
						$membertypestatic->id=$objp->type_id;
						$membertypestatic->libelle=$objp->type;
						print '<td class="nowrap">';
						print $membertypestatic->getNomUrl(1,32);
						print '</td>';

						// Moral/Physique
						print "<td>".$memberstatic->getmorphylib($objp->morphy)."</td>\n";

						// EMail
						print "<td>".dol_print_email($objp->email,0,0,1)."</td>\n";

						// Statut
						print '<td class="nowrap">';
						print $memberstatic->LibStatut($objp->statut,$objp->subscription,$datefin,2);
						print "</td>";

						// End of subscription date
						if ($datefin)
						{
							print '<td align="center" class="nowrap">';
							print dol_print_date($datefin,'day');
							if ($memberstatic->hasDelay()) {
								print " ".img_warning($langs->trans("SubscriptionLate"));
							}
							print '</td>';
						}
						else
						{
							print '<td align="left" class="nowrap">';
							if ($objp->subscription == 'yes')
							{
								print $langs->trans("SubscriptionNotReceived");
								if ($objp->statut > 0) print " ".img_warning();
							}
							else
							{
								print '&nbsp;';
							}
							print '</td>';
						}

						print "</tr>\n";
						$i++;
					}
					print "</table>\n";
				}
			}
		}
	}
	else
	{
		// Contrat non trouve
		print "ErrorRecordNotFound";
	}
}

llxFooter();
$db->close();
