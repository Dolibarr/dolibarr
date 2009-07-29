<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      �ric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \ingroup    tax
		\brief      Page des societes
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");

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

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_GET["modetax"])) $modetax=$_GET["modetax"];

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

llxHeader();

$company_static=new Societe($db);

//print_fiche_titre($langs->trans("VAT"),"");

$fsearch='<form method="get" action="clients.php?year='.$year.'">';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  '.$langs->trans("SalesTurnover").' '.$langs->trans("Minimum").': ';
$fsearch.='  <input type="text" name="min" value="'.$min.'">';
$fsearch.='  <input type="submit" class="button" name="submit" value="'.$langs->trans("Chercher").'">';
$fsearch.='</form>';

// Affiche en-tete du rapport
if ($modetax==1)	// Calculate on invoice for goods and services
{
    $nom=$langs->trans("VATReportByCustomersInDueDebtMode");
    $nom.='<br>('.$langs->trans("SeeVATReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modetax=0">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATDue");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
if ($modetax==0) 	// Invoice for goods, payment for services
{
    $nom=$langs->trans("VATReportByCustomersInInputOutputMode");
    $nom.='<br>('.$langs->trans("SeeVATReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modetax=1">','</a>').')';
    $period=$year_start;
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATIn");
    if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	$vatcust=$langs->trans("VATReceived");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
	$vatsup=$langs->trans("VATPayed");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


// VAT Received

print "<br>";
print_titre($vatcust);

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Customer")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("SalesTurnover")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$vatcust."</td>";
print "</tr>\n";

$coll_list = vat_by_thirdparty($db,$year_current,$modetax,'sell');
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

	print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("Total").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
	print '</tr>';
}
else
{
	$langs->load("errors");
	if ($coll_list == -1)
		print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
	else if ($coll_list == -2)
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	else
		print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
}

print '</table>';


// VAT Payed

print "<br>";
print_titre($vatsup);

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Supplier")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("Outcome")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$vatsup."</td>";
print "</tr>\n";

$company_static=new Societe($db);

$coll_list = vat_by_thirdparty($db,$year_current,$modetax,'buy');
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

	print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("Total").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
	print '</tr>';
}
else
{
	$langs->load("errors");
	if ($coll_list == -1)
		print '<tr><td colspan="5">'.$langs->trans("ErrorNoAccountancyModuleLoaded").'</td></tr>';
	else if ($coll_list == -2)
		print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	else
		print '<tr><td colspan="5">'.$langs->trans("Error").'</td></tr>';
}

print '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
