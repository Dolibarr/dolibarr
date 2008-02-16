<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
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
	    \file       htdocs/compta/tva/clients.php
        \ingroup    compta
		\brief      Page des societes
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.inc.php");
require_once(DOL_DOCUMENT_ROOT."/tva.class.php");

$langs->load("compta");
$langs->load("companies");

$year=$_GET["year"];
if ($year == 0 or $year!=intval(strval($year)))
{
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}

$min = $_GET["min"];
if($min == 0 or $min!=floatval(strval($min))){
	$min = 0.00;
}else{
	//keep min
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];



/*
 * Code
 */

llxHeader();

$company_static=new Societe($db);

print_fiche_titre($langs->trans("VAT"),"");

$fsearch='<form method="get" action="clients.php?year='.$year.'">';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  '.$langs->trans("SalesTurnover").' '.$langs->trans("Minimum").': ';
$fsearch.='  <input type="text" name="min" value="'.$min.'">';
$fsearch.='  <input type="submit" class="button" name="submit" value="'.$langs->trans("Chercher").'">';
$fsearch.='</form>';

// Affiche en-tête du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("ReportByCustomers");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("ReportByCustomers");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("VATReportDesc");
    if ($conf->global->MAIN_MODULE_COMPTABILITE) $description.='<br>'.img_warning().' '.$langs->trans('OptionModeTrueInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// VAT Received

print "<br>";
print_fiche_titre($langs->trans("VATReceived"));

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Company")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("SalesTurnover")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$langs->trans("VATReceived")."</td>";
print "</tr>\n";

$coll_list = tva_coll($db,$year_current);
if (is_array($coll_list))
{
	$var=true;
	$total = 0;  $subtotal = 0;
	$i = 1;
	foreach($coll_list as $coll)
	{
		if($min == 0 or ($min>0 and $coll[2]>$min))
		{
			$var=!$var;
			$intra = str_replace($find,$replace,$coll[1]);
			if(empty($intra))
			{
				if($coll[4] == '1')
				{
					$intra = $langs->trans('Unknown');
				}
				else
				{
					$intra = $langs->trans('NotRegistered');
				}
			}
			print "<tr $bc[$var]>";
			print "<td nowrap>".$i."</td>";
			$company_static->id=$coll[5];
			$company_static->nom=$coll[0];
			print '<td nowrap>'.$company_static->getNomUrl(1).'</td>';
			$find = array(' ','.');
			$replace = array('','');
			print "<td nowrap>".$intra."</td>";
			print "<td nowrap align=\"right\">".price($coll[2])."</td>";
			print "<td nowrap align=\"right\">".price($coll[3])."</td>";
			$total = $total + $coll[3];
			print "</tr>\n";
			$i++;
		}
	}

	print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("TotalVATReceived").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
	print '</tr>';
}
else
{
	print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
}

print '</table>';


// VAT Payed

print "<br>";
print_fiche_titre($langs->trans("VATPayed"));

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Company")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("Outcome")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$langs->trans("VATPayed")."</td>";
print "</tr>\n";

$company_static=new Societe($db);

$coll_list = tva_paye($db,$year_current);
if (is_array($coll_list))
{
	$var=true;
	$total = 0;  $subtotal = 0;
	$i = 1;
	foreach($coll_list as $coll)
	{
		if($min == 0 or ($min>0 and $coll[2]>$min))
		{
			$var=!$var;
			$intra = str_replace($find,$replace,$coll[1]);
			if(empty($intra))
			{
				if($coll[4] == '1')
				{
					$intra = $langs->trans('Unknown');
				}
				else
				{
					$intra = $langs->trans('NotRegistered');
				}
			}
			print "<tr $bc[$var]>";
			print "<td nowrap>".$i."</td>";
			$company_static->id=$coll[5];
			$company_static->nom=$coll[0];
			print '<td nowrap>'.$company_static->getNomUrl(1).'</td>';
			$find = array(' ','.');
			$replace = array('','');
			print "<td nowrap>".$intra."</td>";
			print "<td nowrap align=\"right\">".price($coll[2])."</td>";
			print "<td nowrap align=\"right\">".price($coll[3])."</td>";
			$total = $total + $coll[3];
			print "</tr>\n";
			$i++;
		}
	}

	print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("TotalVATReceived").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
	print '</tr>';
}
else
{
	print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
}

print '</table>';
	

$db->close();

llxFooter('$Date$ - $Revision$');


/**
 * 	\brief		Look for collectable VAT clients in the chosen year
 *	\param		db			Database handle
 *	\param		y			Year
 *	\return		array		Liste of third parties
 */
function tva_coll($db,$y)
{
	global $conf, $modecompta;
	
    // Define sql request
	$sql='';
	if ($modecompta == "CREANCES-DETTES")
    {
        // If vat payed on due invoices (non draft)
        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
		$sql.= " sum(f.total) as amount, sum(f.tva) as tva,";
		$sql.= " s.tva_assuj as assuj, s.rowid as socid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";	// Validated or payed (partially or completely)
        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
        $sql.= " AND s.rowid = f.fk_soc";
        $sql.= " GROUP BY s.rowid";
    }
    else
    {
        // If vat payed on payments
		if ($conf->global->MAIN_MODULE_COMPTABILITEEXPERT)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocké dans la table des payments.
	        // Seul le module compta expert peut résoudre ce problème.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
	        // Tva sur factures payés (should be on payment)
	        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
			$sql.= " sum(f.total) as amount, sum(f.tva) as tva,";
			$sql.= " s.tva_assuj as assuj, s.rowid as socid";
	        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	        $sql.= " WHERE ";
			$sql.= " f.fk_statut in (2)";	// Payed (partially or completely)
			$sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
			$sql.= " AND s.rowid = f.fk_soc";
			$sql.= " GROUP BY s.rowid";
		}
    }

	if ($sql)
	{
		dolibarr_syslog("Client::tva_coll sql=".$sql);
	    $resql = $db->query($sql);
	    if ($resql)
	    {
	    	$list = array();
	    	while($assoc = $db->fetch_array($resql))
			{
	        	$list[] = $assoc;
	    	}
			$db->free();
	    	return $list;
	    }
	    else
	    {
	        dolibarr_print_error($db);
			return -2;
	    }
	}
	else
	{
			return -1;
	}
}


/**
 * 	Get payable VAT
 *	@param		resource	Database handle
 *	@param		int			Year
 */
function tva_paye($db, $y)
{
	global $conf, $modecompta;

    // Define sql request
   	$sql='';
	if ($modecompta == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
		$sql.= " sum(f.total_ht) as amount, sum(f.total_tva) as tva,";
		$sql.= " s.tva_assuj as assuj, s.rowid as socid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";	// Validated or payed (partially or completely)
        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
        $sql.= " AND s.rowid = f.fk_soc ";
        $sql.= " GROUP BY s.rowid";
    }
    else
    {
        // Si on paye la tva sur les payments

		if ($conf->global->MAIN_MODULE_COMPTABILITEEXPERT)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocké dans la table des payments.
	        // Seul le module compta expert peut résoudre ce problème.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
	        // Tva sur factures payés
	        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
			$sql.= " sum(f.total_ht) as amount, sum(f.total_tva) as tva,";
			$sql.= " s.tva_assuj as assuj, s.rowid as socid";
	        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
	        $sql.= " WHERE ";
	        //$sql.= " f.fk_statut in (2)";	// Payed (partially or completely)
	        $sql.= " f.paye in (1)";		// Payed (completely)
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        $sql.= " AND s.rowid = f.fk_soc ";
	        $sql.= " GROUP BY s.rowid";
		}
    }

	if ($sql)
	{
		dolibarr_syslog("Client::tva_paye sql=".$sql);
	    $resql = $db->query($sql);
	    if ($resql)
	    {
	    	$list = array();
	    	while($assoc = $db->fetch_array($resql))
			{
	        	$list[] = $assoc;
	    	}
	    	return $list;
	    }
	    else
	    {
	        dolibarr_print_error($db);
			return -2;
		}
	}
	else
	{
		return -1;
	}
}

?>
