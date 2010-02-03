<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/compta/tva/clients.php
 *      \ingroup    tax
 *		\brief      Page des societes
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/tax.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");

// Date range
$year=$_REQUEST["year"];
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start=dol_mktime($_REQUEST["date_starthour"],$_REQUEST["date_startmin"],$_REQUEST["date_startsec"],$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"],1);
$date_end=dol_mktime($_REQUEST["date_endhour"],$_REQUEST["date_endmin"],$_REQUEST["date_endsec"],$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"],1);
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=(! empty($_REQUEST["q"]))?$_REQUEST["q"]:0;
	if ($q==0)
	{
		if (isset($_REQUEST["month"])) { $date_start=dol_get_first_day($year_start,$_REQUEST["month"]); $date_end=dol_get_last_day($year_start,$_REQUEST["month"]); }
		else $q=1;
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1); $date_end=dol_get_last_day($year_start,3); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4); $date_end=dol_get_last_day($year_start,6); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7); $date_end=dol_get_last_day($year_start,9); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10); $date_end=dol_get_last_day($year_start,12); }
}
else
{
	// TODO We define q

}

$min = $_REQUEST["min"];
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_REQUEST["modetax"])) $modetax=$_REQUEST["modetax"];

// Security check
$socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');



/*
 * View
 */

$html=new Form($db);

llxHeader();

$company_static=new Societe($db);


$fsearch.='<br>';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  <input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch.='  '.$langs->trans("SalesTurnover").' '.$langs->trans("Minimum").': ';
$fsearch.='  <input type="text" name="min" value="'.$min.'">';

// Affiche en-tete du rapport
if ($modetax==1)	// Calculate on invoice for goods and services
{
    $nom=$langs->trans("VATReportByCustomersInDueDebtMode");
    //$nom.='<br>('.$langs->trans("SeeVATReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modetax=0">','</a>').')';
    $period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATDue");
    //if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $description.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
	$builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
if ($modetax==0) 	// Invoice for goods, payment for services
{
    $nom=$langs->trans("VATReportByCustomersInInputOutputMode");
    //$nom.='<br>('.$langs->trans("SeeVATReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year_start.'&modetax=1">','</a>').')';
    $period=$html->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    //$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesVATIn");
    if ($conf->global->MAIN_MODULE_COMPTABILITE || $conf->global->MAIN_MODULE_ACCOUNTING) $description.='<br>'.img_warning().' '.$langs->trans('OptionVatInfoModuleComptabilite');
	$description.=$fsearch;
    $description.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';
	$builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	if ($mysoc->tva_assuj) $vatcust.=' ('.$langs->trans("ToPay").')';
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
	if ($mysoc->tva_assuj) $vatsup.=' ('.$langs->trans("ToGetBack").')';
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

$vatcust=$langs->trans("VATReceived");
$vatsup=$langs->trans("VATPaid");


// VAT Received

//print "<br>";
//print_titre($vatcust);

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Customer")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("SalesTurnover")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$vatcust."</td>";
print "</tr>\n";

$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'sell');
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

//print '</table>';


// VAT Paid

//print "<br>";
//print_titre($vatsup);

//print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Supplier")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("Outcome")." ".$langs->trans("HT")."</td>";
print "<td align=\"right\">".$vatsup."</td>";
print "</tr>\n";

$company_static=new Societe($db);

$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'buy');
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
