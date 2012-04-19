<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2012-2012  Vinicius Nogueira       <viniciusvgn@gmail.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
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
 *		\file       htdocs/fourn/facture/impayees.php
 *		\ingroup    facture
 *		\brief      Page to list all unpaid invoices
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

if (! $user->rights->facture->lire) accessforbidden();

$langs->load("companies");
$langs->load("bills");


$socid=GETPOST('socid','int');
$option = GETPOST('option');

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * View
 */

$now=dol_now();

llxHeader('',$langs->trans("BillsSuppliersUnpaid"));

$title=$langs->trans("BillsSuppliersUnpaid");

$facturestatic=new FactureFournisseur($db);
$companystatic=new Societe($db);


/***************************************************************************
*                                                                         *
*                      Mode Liste                                         *
*                                                                         *
***************************************************************************/

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');

$search_ref = GETPOST('search_ref','alpha');
$search_ref_supplier = GETPOST('search_ref_supplier','alpha');
$search_societe = GETPOST('search_societe','alpha');
$search_montant_ht = GETPOST('search_montant_ht','int');
$search_montant_ttc = GETPOST('search_montant_ttc','int');


$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

if ($user->rights->fournisseur->facture->lire)
{
	$sql = "SELECT s.rowid as socid, s.nom,";
	$sql.= " f.rowid as ref, f.facnumber, f.total_ht, f.total_ttc,";
	$sql.= " f.datef as df, f.date_lim_reglement as datelimite, ";
	$sql.= " f.paye as paye, f.rowid as facid, f.fk_statut";
	$sql.= " ,sum(pf.amount) as am";
	if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ",".MAIN_DB_PREFIX."facture_fourn as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid=pf.fk_facturefourn ";
	$sql.= " WHERE f.fk_soc = s.rowid";
	$sql.= " AND f.paye = 0 AND f.fk_statut = 1";
	if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
	if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;

	if ($_GET["filtre"])
	{
		$filtrearr = explode(",", $_GET["filtre"]);
		foreach ($filtrearr as $fil)
		{
			$filt = explode(":", $fil);
			$sql .= " AND " . $filt[0] . " = " . $filt[1];
		}
	}

	if ($search_ref)
	{
		$sql .= " AND f.rowid like '%".$search_ref."%'";
	}
	if ($search_ref_supplier)
	{
		$sql .= " AND f.facnumber like '%".$search_ref_supplier."%'";
	}

	if ($search_societe)
	{
		$sql .= " AND s.nom like '%".$search_societe."%'";
	}

	if ($search_montant_ht)
	{
		$sql .= " AND f.total_ht = '".$search_montant_ht."'";
	}

	if ($search_montant_ttc)
	{
		$sql .= " AND f.total_ttc = '".$search_montant_ttc."'";
	}

	if (dol_strlen($_POST["sf_ref"]) > 0)
	{
		$sql .= " AND f.facnumber like '%".$_POST["sf_ref"]."%'";
	}
	$sql.= " GROUP BY f.facnumber, f.rowid, f.total_ht, f.total_ttc, f.datef, f.date_lim_reglement, f.paye, f.fk_statut, s.rowid, s.nom";

	$sql.= " ORDER BY ";
	$listfield=explode(',',$sortfield);
	foreach ($listfield as $key => $value) $sql.=$listfield[$key]." ".$sortorder.",";
	$sql.= " f.facnumber DESC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}

		$param ='';
		if ($socid) $param.="&socid=".$socid;
		
		if ($search_ref)         	$param.='&amp;search_ref='.urlencode($search_ref);
		if ($search_ref_supplier)	$param.='&amp;search_ref_supplier='.urlencode($search_ref_supplier);
		if ($search_societe)     	$param.='&amp;search_societe='.urlencode($search_societe);
		if ($search_montant_ht)  	$param.='&amp;search_montant_ht='.urlencode($search_montant_ht);
		if ($search_montant_ttc) 	$param.='&amp;search_montant_ttc='.urlencode($search_montant_ttc);
		
		$param.=($option?"&option=".$option:"");
		if ($late) $param.='&late='.urlencode($late);
		$urlsource.=str_replace('&amp;','&',$param);

		$titre=($socid?$langs->trans("BillsSuppliersUnpaidForCompany",$soc->nom):$langs->trans("BillsSuppliersUnpaid"));

		if ($option == 'late') $titre.=' ('.$langs->trans("Late").')';
	    else $titre.=' ('.$langs->trans("All").')';

		$link='';
		if (empty($option)) $link='<a href="'.$_SERVER["PHP_SELF"].'?option=late'.($socid?'&socid='.$socid:'').'">'.$langs->trans("ShowUnpaidLateOnly").'</a>';
		elseif ($option == 'late') $link='<a href="'.$_SERVER["PHP_SELF"].'?'.($socid?'&socid='.$socid:'').'">'.$langs->trans("ShowUnpaidAll").'</a>';
		print_fiche_titre($titre,$link);

		print_barre_liste('','',$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',0);	// We don't want pagination on this page
		$i = 0;
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.rowid","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("RefSupplier"),$_SERVER["PHP_SELF"],"f.facnumber","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total_ht","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AlreadyPaid"),$_SERVER["PHP_SELF"],"am","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye,am","",$param,'align="right"',$sortfield,$sortorder);
		print "</tr>\n";

		// Lines with filter fields
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		print '<input class="flat" size="8" type="text" name="search_ref" value="'.$search_ref.'"></td>';
		print '<td class="liste_titre">';
		print '<input class="flat" size="8" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'"></td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="6" name="search_societe" value="'.$search_societe.'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="8" name="search_montant_ht" value="'.$search_montant_ht.'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="8" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
		print '</td><td class="liste_titre" colspan="2" align="right">';
		print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '</td>';
		print "</tr>\n";

		if ($num > 0)
		{
			$var=True;
			$total_ht=0;
			$total_ttc=0;
			$total_paid=0;

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$var=!$var;

				print "<tr ".$bc[$var].">";
				$classname = "impayee";

				print '<td nowrap>';
				$facturestatic->id=$objp->facid;
				$facturestatic->ref=$objp->ref;
				print $facturestatic->getNomUrl(1);
				print "</td>\n";

				print "<td nowrap>".dol_trunc($objp->facnumber,12)."</td>\n";

				print "<td nowrap align=\"center\">".dol_print_date($db->jdate($objp->df),'day')."</td>\n";
				print "<td nowrap align=\"center\">".dol_print_date($db->jdate($objp->datelimite),'day');
				if ($objp->datelimite && $db->jdate($objp->datelimite) < ($now - $conf->facture->fournisseur->warning_delay) && ! $objp->paye && $objp->fk_statut == 1) print img_warning($langs->trans("Late"));
				print "</td>\n";

				print '<td>';
				$companystatic->id=$objp->socid;
				$companystatic->nom=$objp->nom;
				print $companystatic->getNomUrl(1,'supplier',32);
				print '</td>';

				print "<td align=\"right\">".price($objp->total_ht)."</td>";
				print "<td align=\"right\">".price($objp->total_ttc)."</td>";
				print "<td align=\"right\">".price($objp->am)."</td>";

				// Affiche statut de la facture
				print '<td align="right" nowrap="nowrap">';
				print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);
				print '</td>';

				print "</tr>\n";
				$total_ht+=$objp->total_ht;
				$total_ttc+=$objp->total_ttc;
				$total_paid+=$objp->am;

				$i++;
			}

			print '<tr class="liste_total">';
			print "<td colspan=\"5\" align=\"left\">".$langs->trans("Total").": </td>";
			print "<td align=\"right\"><b>".price($total_ht)."</b></td>";
			print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
			print "<td align=\"right\"><b>".price($total_paid)."</b></td>";
			print '<td align="center">&nbsp;</td>';
			print "</tr>\n";
		}

		print "</table>";

		print '</form>';

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

}

// End of page
$db->close();
llxFooter();
?>
