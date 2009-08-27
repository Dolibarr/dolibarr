<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 *       \file       htdocs/comm/fiche.php
 *       \ingroup    commercial
 *       \brief      Onglet client de la fiche societe
 *       \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
if (!empty($conf->global->MAIN_MODULE_CHRONODOCS)) require_once(DOL_DOCUMENT_ROOT."/chronodocs/chronodocs_entries.class.php");

$langs->load("companies");
$langs->load("orders");
$langs->load("bills");
$langs->load("contracts");
if ($conf->ficheinter->enabled) $langs->load("interventions");
if (!empty($conf->global->MAIN_MODULE_CHRONODOCS)) $langs->load("chronodocs");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


/*
 * Actions
 */

if ($_GET["action"] == 'attribute_prefix' && $user->rights->societe->creer)
{
	$societe = new Societe($db, $_GET["socid"]);
	$societe->attribute_prefix($db, $_GET["socid"]);
}
// conditions de reglement
if ($_POST["action"] == 'setconditions' && $user->rights->societe->creer)
{

	$societe = new Societe($db, $_GET["socid"]);
	$societe->cond_reglement=$_POST['cond_reglement_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET cond_reglement='".$_POST['cond_reglement_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}
// mode de reglement
if ($_POST["action"] == 'setmode' && $user->rights->societe->creer)
{
	$societe = new Societe($db, $_GET["socid"]);
	$societe->mode_reglement=$_POST['mode_reglement_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET mode_reglement='".$_POST['mode_reglement_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}
// assujetissement a la TVA
if ($_POST["action"] == 'setassujtva' && $user->rights->societe->creer)
{
	$societe = new Societe($db, $_GET["socid"]);
	$societe->tva_assuj=$_POST['assujtva_value'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET tva_assuj='".$_POST['assujtva_value']."' WHERE rowid='".$socid."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}



/*
 * View
 */

llxHeader('',$langs->trans('CustomerCard'));


$userstatic=new User($db);

$form = new Form($db);


if ($mode == 'search')
{
	if ($mode-search == 'soc')
	{
		$sql = "SELECT s.rowid";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
		if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
}


if ($socid > 0)
{
	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$socid;
	$objsoc->fetch($socid,$to);

	if ($errmesg)
	{
		print "<b>$errmesg</b><br>";
	}

	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($objsoc);

	dol_fiche_head($head, 'customer', $langs->trans("ThirdParty"),0,'company');


	/*
	 *
	 */
	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" class="notopnoleft">';

	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("Name").'</td><td width="70%" colspan="3">';
	print $objsoc->nom;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$objsoc->prefix_comm.'</td></tr>';

	if ($objsoc->client)
	{
		print '<tr><td nowrap>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $objsoc->code_client;
		if ($objsoc->check_codeclient() <> 0) print '  <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($objsoc->adresse)."</td></tr>";

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$objsoc->cp."</td>";
	print '<td>'.$langs->trans('Town').'</td><td>'.$objsoc->ville."</td></tr>";

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	if ($objsoc->isInEEC()) print $form->textwithpicto($objsoc->pays,$langs->trans("CountryIsInEEC"),1,0);
	else print $objsoc->pays;
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($objsoc->tel,$objsoc->pays_code,0,$objsoc->id,'AC_TEL').'</td>';

	// Fax
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($objsoc->fax,$objsoc->pays_code,0,$objsoc->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($objsoc->email,0,$objsoc->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">'.dol_print_url($objsoc->url,'_blank').'</td></tr>';

	// Assujeti TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($objsoc->tva_assuj);
	print '</td>';
	print '</tr>';

	// Conditions de reglement par defaut
	$langs->load('bills');
	$html = new Form($db);
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($_GET['action'] != 'editconditions') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'cond_reglement_id',-1,1);
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Mode de reglement
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($_GET['action'] != 'editmode') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Reductions relative (Discounts-Drawbacks-Rebates)
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans("CustomerRelativeDiscountShort");
	print '<td><td align="right">';
	if ($user->rights->societe->creer)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td><td colspan="3">'.($objsoc->remise_client?$objsoc->remise_client.'%':$langs->trans("DiscountNone")).'</td>';
	print '</tr>';

	// Reductions absolues (Discounts-Drawbacks-Rebates)
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding">';
	print '<tr><td nowrap>';
	print $langs->trans("CustomerAbsoluteDiscountShort");
	print '<td><td align="right">';
	if ($user->rights->societe->creer)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
	}
	print '</td></tr></table>';
	print '</td>';
	print '<td colspan="3">';
	$amount_discount=$objsoc->getAvailableDiscounts();
	if ($amount_discount < 0) dol_print_error($db,$societe->error);
	if ($amount_discount > 0) print price($amount_discount).'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
	else print $langs->trans("DiscountNone");
	print '</td>';
	print '</tr>';

	// Multiprice level
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("PriceLevel");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.$objsoc->price_level."</td>";
		print '</tr>';
	}

	// Adresse de livraison
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("DeliveriesAddress");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">';

		$sql = "SELECT count(rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_adresse_livraison";
		$sql.= " WHERE fk_societe =".$objsoc->id;

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$objal = $db->fetch_object($resql);
			print $objal->nb?($objal->nb):$langs->trans("NoOtherDeliveryAddress");
		}
		else
		{
			dol_print_error($db);
		}

		print '</td>';
		print '</tr>';
	}

	print "</table>";

	print "</td>\n";


	print '<td valign="top" width="50%" class="notopnoleftnoright">';

	// Nbre max d'elements des petites listes
	$MAXLIST=4;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/recap-client.php?socid='.$objsoc->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	$now=gmmktime();

	/*
	 * Last proposals
	 */
	if ($conf->propal->enabled && $user->rights->propale->lire)
	{
		$propal_static=new Propal($db);

		print '<table class="noborder" width="100%">';

		$sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql.= " ".$db->pdate("p.datep")." as dp, ".$db->pdate("p.fin_validite")." as datelimite";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
		$sql .= " AND s.rowid = ".$objsoc->id;
		$sql .= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$objsoc->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
				$var=!$var;
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print "<td nowrap><a href=\"propal.php?propalid=$objp->propalid\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
				if ( ($objp->dp < $now - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1 )
				{
					print " ".img_warning();
				}
				print '</td><td align="right" width="80">'.dol_print_date($objp->dp,'day')."</td>\n";
				print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
				print '<td align="right" nowrap="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
				$var=!$var;
				$i++;
			}
			$db->free($resql);
		}
		else {
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last orders
	 */
	if ($conf->commande->enabled && $user->rights->commande->lire)
	{
		$commande_static=new Commande($db);

		print '<table class="noborder" width="100%">';

		$sql = "SELECT s.nom, s.rowid,";
		$sql.= " c.rowid as cid, c.total_ht, c.ref, c.fk_statut, c.facture,";
		$sql.= " ".$db->pdate("c.date_commande")." as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.fk_soc = s.rowid ";
		$sql.= " AND s.rowid = ".$objsoc->id;
		$sql.= " ORDER BY c.date_commande DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num >0 )
			{
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
				print '</td><td align="right" width="80">'.dol_print_date($objp->dc,'day')."</td>\n";
				print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
				print '<td align="right" width="100">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
				$i++;
			}
			$db->free($resql);
		}
		else {
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last linked contracts
	 */
	if ($conf->contrat->enabled && $user->rights->contrat->lire)
	{
		$contratstatic=new Contrat($db);

		print '<table class="noborder" width="100%">';

		$sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, ".$db->pdate("c.datec")." as dc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
		$sql .= " WHERE c.fk_soc = s.rowid ";
		$sql .= " AND s.rowid = ".$objsoc->id;
		$sql .= " ORDER BY c.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num >0 )
			{
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastContracts",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
				print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllContracts").' ('.$num.')</a></td></tr></table></td>';
				print '</tr>';
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$contrat=new Contrat($db);

				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap">';
				$contrat->id=$objp->id;
				$contrat->ref=$objp->ref?$objp->ref:$objp->id;
				print $contrat->getNomUrl(1,12);
				print "</td>\n";
				print '<td align="right" width="80">'.dol_print_date($objp->dc,'day')."</td>\n";
				print '<td width="20">&nbsp;</td>';
				print '<td align="right" nowrap="nowrap">';
				$contrat->fetch_lignes();
				print $contrat->getLibStatut(4);
				print "</td>\n";
				print '</tr>';
				$i++;
			}
			$db->free($resql);
		}
		else {
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last interventions
	 */
	if ($conf->ficheinter->enabled && $user->rights->ficheinter->lire)
	{
		print '<table class="noborder" width="100%">';

		$sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, ".$db->pdate("f.datei")." as di";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND s.rowid = ".$objsoc->id;
		$sql .= " ORDER BY f.tms DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			if ($num >0 )
			{
				print '<tr class="liste_titre">';
				print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastInterventions",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/fichinter/index.php?socid='.$objsoc->id.'">'.$langs->trans("AllInterventions").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
				$var=!$var;
			}
			$i = 0;
			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td nowrap><a href="'.DOL_URL_ROOT."/fichinter/fiche.php?id=".$objp->id."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
				print "</td><td align=\"right\">".dol_print_date($objp->di,'day')."</td>\n";
				print '</tr>';
				$var=!$var;
				$i++;
			}
			$db->free($resql);
		}
		else {
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last linked projects
	 */
	if ($conf->projet->enabled && $user->rights->projet->lire)
	{
		print '<table class="noborder" width=100%>';

		$sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " WHERE p.fk_soc = $objsoc->id";
		$sql .= " ORDER BY p.dateo DESC";

		$result=$db->query($sql);
		if ($result) {
			$var=true;
			$i = 0 ;
			$num = $db->num_rows($result);
			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<td colspan="2"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
			}
			while ($i < $num && $i < $MAXLIST) {
				$obj = $db->fetch_object($result);
				$var = !$var;
				print "<tr $bc[$var]>";
				print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

				print "<td align=\"right\">".$obj->ref ."</td></tr>";
				$i++;
			}
			$db->free($result);
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
	}

	/*
	 * Last linked chronodocs
	 */
	if(!empty($conf->global->MAIN_MODULE_CHRONODOCS) && $user->rights->chronodocs->entries->read)
	{
		print '<table class="noborder" width=100%>';
		$chronodocs_static=new Chronodocs_entries($db);
		$result=$chronodocs_static->get_list($MAXLIST,0,"f.date_c","DESC",$objsoc->id);
		if (is_array($result))
		{
			$var=true;
			$i = 0 ;
			//$num = sizeOf($result);
			$num=$chronodocs_static->get_nb_chronodocs($objsoc->id);

			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<td colspan="3"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastChronodocs",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/chronodocs/index.php?socid='.$objsoc->id.'">'.$langs->trans("AllChronodocs").' ('.$num.')</td></tr></table></td>';
				print '</tr>';
			}
			while ($i < $num && $i < $MAXLIST) {
				$obj = array_shift($result);
				$var = !$var;
				print "<tr $bc[$var]>";
				print '<td><a href="'.DOL_URL_ROOT.'/chronodocs/fiche.php?id='.$obj->fichid.'">'.img_object($langs->trans("ShowChronodocs"),"generic")." ".$obj->ref.'</a></td>';

				print "<td align=\"left\">".dol_trunc($obj->title,30) ."</td>";
				print "<td align=\"right\">".dol_print_date($obj->dp,'day')."</td>\n";
				print "</tr>";

				$i++;
			}
		}

		print "</table>";
	}

	print "</td></tr>";
	print "</table></div>\n";


	/*
	 * Barre d'action
	 *
	 */
	print '<div class="tabsAction">';

	if ($conf->propal->enabled && $user->rights->propale->creer)
	{
		$langs->load("propal");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
	}

	if ($conf->commande->enabled && $user->rights->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->contrat->creer)
	{
		$langs->load("contracts");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContract").'</a>';
	}

	if ($conf->ficheinter->enabled && $user->rights->ficheinter->creer)
	{
		$langs->load("fichinter");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a>';
	}

	if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$objsoc->id.'">'.$langs->trans("AddAction").'</a>';
	}

	if ($user->rights->societe->contact->creer)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';
	}

	if(!empty($conf->global->MAIN_MODULE_CHRONODOCS) && $user->rights->chronodocs->entries->write)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/chronodocs/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddChronodoc").'</a>';
	}

	print '</div>';
	print '<br>';

	/*
	 * Liste des contacts
	 */
	show_contacts($conf,$langs,$db,$objsoc);

	/*
	 *      Listes des actions a faire
	 */
	show_actions_todo($conf,$langs,$db,$objsoc);

	/*
	 *      Listes des actions effectuees
	 */
	show_actions_done($conf,$langs,$db,$objsoc);
}
else
{
	dol_print_error($db,'Bad value for socid parameter');
}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
