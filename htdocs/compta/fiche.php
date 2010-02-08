<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *   	\file       htdocs/compta/fiche.php
 *		\ingroup    compta
 *		\brief      Page de fiche compta
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

$langs->load("companies");
if ($conf->facture->enabled) $langs->load("bills");
if ($conf->projet->enabled)  $langs->load("projects");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 * Action
 */

if ($mode == 'search')
{
	if ($mode-search == 'soc')
	{
		$sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s ";
		$sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
	}

	if ( $db->query($sql) )
	{
		if ( $db->num_rows() == 1)
		{
			$obj = $db->fetch_object();
			$socid = $obj->rowid;
		}
		$db->free();
	}

	if ($user->societe_id > 0)
	{
		$socid = $user->societe_id;
	}

}

if ($_POST['action'] == 'setcustomeraccountancycode')
{
	$societe = new Societe($db);
	$result=$societe->fetch($_POST['socid']);
	$societe->code_compta=$_POST["customeraccountancycode"];
	$result=$societe->update($societe->id,$user,1,1,0);
	if ($result < 0)
	{
		$mesg=join(',',$societe->errors);
	}
	$POST["action"]="";
	$socid=$_POST["socid"];
}

if ($_POST['action'] == 'setsupplieraccountancycode')
{
	$societe = new Societe($db);
	$result=$societe->fetch($_POST['socid']);
	$societe->code_compta_fournisseur=$_POST["supplieraccountancycode"];
	$result=$societe->update($societe->id,$user,1,0,1);
	if ($result < 0)
	{
		$mesg=join(',',$societe->errors);
	}
	$POST["action"]="";
	$socid=$_POST["socid"];
}




/*
 * View
 */

llxHeader();

$facturestatic=new Facture($db);
$contactstatic = new Contact($db);
$form = new Form($db);

if ($socid > 0)
{
	$societe = new Societe($db);
	$societe->fetch($socid);
	if ($societe->id <= 0)
	{
		dol_print_error($db,$societe->error);
	}

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'compta', $langs->trans("ThirdParty"),0,'company');	// Add a div

	if ($mesg) print $mesg;

	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" width="50%" class="notopnoleft">';

	print '<table class="border" width="100%">';

	print '<tr><td width="100">'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';

	// Prefix
	print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
	print ($societe->prefix_comm?$societe->prefix_comm:'&nbsp;');
	print '</td></tr>';

	if ($societe->client)
	{
		print '<tr>';
		print '<td nowrap>'.$langs->trans("CustomerCode").'</td><td colspan="3">';
		print $societe->code_client;
		if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		// TODO Use a html->editfield function
		print '<td><table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
		print $langs->trans("CustomerAccountancyCode");
		print '</td>';
		if ($_GET['action'] != 'editcustomeraccountancycode' && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editcustomeraccountancycode&amp;socid='.$societe->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($_GET['action'] == 'editcustomeraccountancycode')
		{
			print "\n".'<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="action" value="setcustomeraccountancycode">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="socid" value="'.$societe->id.'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print '<input type="text" name="customeraccountancycode" value="'.$societe->code_compta.'">';
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>'."\n";
		}
		else print $societe->code_compta;
		print '</td>';
		// End editfield
		print '</tr>';

	}

	if ($societe->fournisseur)
	{
		print '<tr>';
		print '<td nowrap>'.$langs->trans("SupplierCode"). '</td><td colspan="3">';
		print $societe->code_fournisseur;
		if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		// TODO Use a html->editfield function
		print '<td><table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
		print $langs->trans("SupplierAccountancyCode");
		print '</td>';
		if ($_GET['action'] != 'editsupplieraccountancycode' && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editsupplieraccountancycode&amp;socid='.$societe->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($_GET['action'] == 'editsupplieraccountancycode')
		{
			print "\n".'<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="action" value="setsupplieraccountancycode">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="socid" value="'.$societe->id.'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print '<input type="text" name="supplieraccountancycode" value="'.$societe->code_compta_fournisseur.'">';
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>'."\n";
		}
		else print $societe->code_compta_fournisseur;
		print '</td>';
		// End editfield
		print '</tr>';
	}

	print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";

	print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$societe->cp.'</td>';
	print '<td>'.$langs->trans('Town').'</td><td>'.$societe->ville.'</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
	if ($societe->isInEEC()) print $form->textwithpicto($societe->pays,$langs->trans("CountryIsInEEC"),1,0);
	else print $societe->pays;
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dol_print_phone($societe->tel,$societe->pays_code,0,$societe->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($societe->fax,$societe->pays_code,0,$societe->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($societe->email,0,$societe->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($societe->url,'_blank').'</td></tr>';

	// Assujeti a TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($societe->tva_assuj);
	print '</td>';
	print '</tr>';

	// Local Taxes
	if ($obj->code=='ES' && $conf->global->MAIN_FEATURES_LEVEL >= 1)
	{
		print '<tr>';
		print '<td nowrap="nowrap">'.$langs->trans('LocalTax1IsUsedES').'</td><td colspan="3">';
		print yn($societe->localtax1_assuj);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td nowrap="nowrap">'.$langs->trans('LocalTax2IsUsedES').'</td><td colspan="3">';
		print yn($societe->localtax2_assuj);
		print '</td>';
		print '</tr>';
	}

	// TVA Intra
	print '<tr><td nowrap>'.$langs->trans('VATIntraVeryShort').'</td><td colspan="3">';
	print $societe->tva_intra;
	print '</td></tr>';

	if ($societe->client == 1)
	{
		// Remise permanente
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("CustomerRelativeDiscountShort");
		print '<td><td align="right">';
		if (!$user->societe_id > 0)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.($societe->remise_client?price2num($societe->remise_client,'MT').'%':$langs->trans("DiscountNone")).'</td>';
		print '</tr>';

		// Reductions (Discounts-Drawbacks-Rebates)
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding">';
		print '<tr><td nowrap>';
		print $langs->trans("CustomerAbsoluteDiscountShort");
		print '<td><td align="right">';
		if (!$user->societe_id > 0)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td>';
		print '<td colspan="3">';
		$amount_discount=$societe->getAvailableDiscounts();
		if ($amount_discount < 0) dol_print_error($db,$societe->error);
		if ($amount_discount > 0) print price($amount_discount).'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
		else print $langs->trans("DiscountNone");
		print '</td>';
		print '</tr>';
	}

	print "</table>";

	print "</td>\n";


	print '<td valign="top" width="50%" class="notopnoleftnoright">';

	// Nbre max d'elements des petites listes
	$MAXLIST=5;
	$tableaushown=1;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$societe->id.'">'.$langs->trans("ShowAccountancyPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	/*
	 *   Last invoices
	 */
	if ($conf->facture->enabled && $user->rights->facture->lire)
	{
		$facturestatic = new Facture($db);

		print '<table class="noborder" width="100%">';

		$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' '.$db->pdate("f.datef").' as df, '.$db->pdate("f.datec").' as dc, f.paye as paye, f.fk_statut as statut,';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' sum(pf.amount) as am';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$societe->id;
		$sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.total_ttc, f.datef, f.datec, f.paye, f.fk_statut, s.nom, s.rowid';
		$sql.= " ORDER BY f.datef DESC, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
				$tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$societe->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}

			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td>';
				$facturestatic->id=$objp->facid;
				$facturestatic->ref=$objp->facnumber;
				$facturestatic->type=$objp->type;
				print $facturestatic->getNomUrl(1);
				print '</td>';
				if ($objp->df > 0)
				{
					print "<td align=\"right\">".dol_print_date($objp->df)."</td>\n";
				}
				else
				{
					print "<td align=\"right\"><b>!!!</b></td>\n";
				}
				print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

				print '<td align="right" nowrap="nowrap">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last project
	 */
	if ($conf->projet->enabled && $user->rights->projet->lire)
	{
		print '<table class="noborder" width="100%">';

		$sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " WHERE p.fk_soc = $societe->id";
		$sql .= " ORDER by p.dateo";

		if ( $db->query($sql) )
		{
			$var=true;
			$i = 0 ;
			$num = $db->num_rows();
			if ($num > 0)
			{
				$tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="2"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/index.php?socid='.$societe->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
			}
			while ($i < $num && $i < $MAXLIST)
			{
				$obj = $db->fetch_object();
				$var = !$var;
				print "<tr $bc[$var]>";
				print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

				print "<td align=\"right\">".dol_print_date($obj->do,"day") ."</td></tr>";
				$i++;
			}
			$db->free();
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
	}

	print "</td></tr>";
	print "</table>";

	print "\n</div>\n";


	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($user->societe_id == 0)
	{
		// Si societe cliente ou prospect, on affiche bouton "Creer facture client"
		if ($conf->facture->enabled)
		{
			if ($user->rights->facture->creer)
			{
				$langs->load("bills");
				if ($societe->client != 0) print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/facture.php?action=create&socid=$societe->id\">".$langs->trans("AddBill")."</a>";
				else print "<a class=\"butActionRefused\" title=\"".dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer"))."\" href=\"#\">".$langs->trans("AddBill")."</a>";
			}
			else
			{
				print "<a class=\"butActionRefused\" title=\"".dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer"))."\" href=\"#\">".$langs->trans("AddBill")."</a>";
			}
		}

		if ($conf->deplacement->enabled)
		{
			$langs->load("trips");
			print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/deplacement/fiche.php?socid=$societe->id&amp;action=create\">".$langs->trans("AddTrip")."</a>";
		}
	}

	if ($conf->agenda->enabled)
	{
		if ($user->rights->agenda->myactions->create)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
		}
		else
		{
			print '<a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
		}
	}

	if ($user->rights->societe->contact->creer)
	{
		print "<a class=\"butAction\" href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a>";
	}

	print '</div>';
	print "<br>\n";


	/*
	 * Liste des contacts
	 */
	show_contacts($conf,$langs,$db,$societe);

	/*
	 *      Listes des actions a faire
	 */
	show_actions_todo($conf,$langs,$db,$societe);

	/*
	 *      Listes des actions effectuees
	 */
	show_actions_done($conf,$langs,$db,$societe);
}
else
{
	dol_print_error($db,'Bad value for socid parameter');
}
$db->close();


llxFooter('$Date$ - $Revision$');
?>
