<?php
/* Copyright (C) 2011-2014	Juanjo Menent 		<jmenent@2byte.es>
 * Copyright (C) 2014	    Ferran Marcet       <fmarcet@2byte.es>
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
 *	    \file       htdocs/compta/localtax/clients.php
 *      \ingroup    tax
 *		\brief      Third parties localtax report
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

$langs->load("bills");
$langs->load("compta");
$langs->load("companies");
$langs->load("products");

$local=GETPOST('localTaxType', 'int');

// Date range
$year=GETPOST("year");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);
$date_end=dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
    $q=GETPOST("q");
    if (empty($q))
	{
		if (isset($_REQUEST["month"])) { $date_start=dol_get_first_day($year_start,$_REQUEST["month"],false); $date_end=dol_get_last_day($year_start,$_REQUEST["month"],false); }
		else
		{
		    $month_current = strftime("%m",dol_now());
		    if ($month_current >= 10) $q=4;
            elseif ($month_current >= 7) $q=3;
            elseif ($month_current >= 4) $q=2;
            else $q=1;
		}
	}
	if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
	if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
	if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
	if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
}

$min = GETPOST("min");
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit
$modetax = $conf->global->TAX_MODE;
if (isset($_REQUEST["modetax"])) $modetax=$_REQUEST["modetax"];

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

/*
 * View
 */

$form=new Form($db);
$company_static=new Societe($db);

$morequerystring='';
$listofparams=array('date_startmonth','date_startyear','date_startday','date_endmonth','date_endyear','date_endday');
foreach($listofparams as $param)
{
	if (GETPOST($param)!='') $morequerystring.=($morequerystring?'&':'').$param.'='.GETPOST($param);
}

llxHeader('','','','',0,0,'','',$morequerystring);

$fsearch.='<br>';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  <input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
$fsearch.='  <input type="text" name="min" id="min" value="'.$min.'" size="6">';

$calc=$conf->global->MAIN_INFO_LOCALTAX_CALC.$local;
// Affiche en-tete du rapport
if ($calc==0 || $calc==1)	// Calculate on invoice for goods and services
{
    $nom=$langs->transcountry($local==1?"LT1ReportByCustomersInInputOutputMode":"LT2ReportByCustomersInInputOutputMode",$mysoc->country_code);
    $calcmode=$calc==0?$langs->trans("CalcModeLT".$local):$langs->trans("CalcModeLT".$local."Rec");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    $description.=$fsearch;
    $description.='<br>('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')';
	$builddate=dol_now();

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
}
if ($calc==2) 	// Invoice for goods, payment for services
{
    $nom=$langs->transcountry($local==1?"LT1ReportByCustomersInInputOutputMode":"LT2ReportByCustomersInInputOutputMode",$mysoc->country_code);
    $calcmode=$langs->trans("CalcModeLT2Debt");
    $calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')';
    $period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
    if (! empty($conf->global->MAIN_MODULE_COMPTABILITE)) $description.='<br>'.$langs->trans("WarningDepositsNotIncluded");
    $description.=$fsearch;
    $description.='<br>('.$langs->trans("TaxModuleSetupToModifyRulesLT",DOL_URL_ROOT.'/admin/company.php').')';
    $builddate=dol_now();

	$elementcust=$langs->trans("CustomersInvoices");
	$productcust=$langs->trans("Description");
	$amountcust=$langs->trans("AmountHT");
	$elementsup=$langs->trans("SuppliersInvoices");
	$productsup=$langs->trans("Description");
	$amountsup=$langs->trans("AmountHT");
}
report_header($name,'',$period,$periodlink,$description,$builddate,$exportlink,array(),$calcmode);


$vatcust=$langs->transcountry($local==1?"LT1":"LT2",$mysoc->country_code);
$vatsup=$langs->transcountry($local==1?"LT1":"LT2",$mysoc->country_code);

// IRPF that the customer has retained me
if($calc ==0 || $calc == 2)
{
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print '<td align="left">'.$langs->trans("Num")."</td>";
	print '<td align="left">'.$langs->trans("Customer")."</td>";
	print "<td>".$langs->transcountry("ProfId1",$mysoc->country_code)."</td>";
	print "<td align=\"right\">".$langs->trans("TotalHT")."</td>";
	print "<td align=\"right\">".$vatcust."</td>";
	print "</tr>\n";

	$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'sell');

	$action = "tvaclient";
	$object = &$coll_list;
	$parameters["mode"] = $modetax;
	$parameters["start"] = $date_start;
	$parameters["end"] = $date_end;
	$parameters["direction"] = 'sell';
	$parameters["type"] = 'localtax'.$local;

	// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
	$hookmanager->initHooks(array('externalbalance'));
	$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

	if (is_array($coll_list))
	{
		$var=true;
		$total = 0;  $totalamount = 0;
		$i = 1;
		foreach($coll_list as $coll)
		{
			if(($min == 0 or ($min > 0 && $coll->amount > $min)) && ($local==1?$coll->localtax1:$coll->localtax2) !=0)
			{

				$intra = str_replace($find,$replace,$coll->tva_intra);
				if(empty($intra))
				{
					if($coll->assuj == '1')
					{
						$intra = $langs->trans('Unknown');
					}
					else
					{
						$intra = '';
					}
				}
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				$company_static->id=$coll->socid;
				$company_static->name=$coll->name;
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ','.');
				$replace = array('','');
				print '<td class="nowrap">'.$intra."</td>";
				print "<td class=\"nowrap\" align=\"right\">".price($coll->amount)."</td>";
				print "<td class=\"nowrap\" align=\"right\">".price($local==1?$coll->localtax1:$coll->localtax2)."</td>";
	            $totalamount = $totalamount + $coll->amount;
				$total = $total + ($local==1?$coll->localtax1:$coll->localtax2);
				print "</tr>\n";
				$i++;
			}
		}
	    $x_coll_sum = $total;

		print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").':</td>';
	    print '<td class="nowrap" align="right">'.price($totalamount).'</td>';
		print '<td class="nowrap" align="right">'.price($total).'</td>';
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
}

// IRPF I retained my supplier
if($calc ==0 || $calc == 1){
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print '<td align="left">'.$langs->trans("Num")."</td>";
	print '<td align="left">'.$langs->trans("Supplier")."</td>";
	print "<td>".$langs->transcountry("ProfId1",$mysoc->country_code)."</td>";
	print "<td align=\"right\">".$langs->trans("TotalHT")."</td>";
	print "<td align=\"right\">".$vatsup."</td>";
	print "</tr>\n";

	$company_static=new Societe($db);

	$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'buy');
	$parameters["direction"] = 'buy';
	$parameters["type"] = 'localtax'.$local;

	$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	if (is_array($coll_list))
	{
		$var=true;
		$total = 0;  $totalamount = 0;
		$i = 1;
		foreach($coll_list as $coll)
		{
			if(($min == 0 or ($min > 0 && $coll->amount > $min)) && ($local==1?$coll->localtax1:$coll->localtax2) != 0)
			{

				$intra = str_replace($find,$replace,$coll->tva_intra);
				if(empty($intra))
				{
					if($coll->assuj == '1')
					{
						$intra = $langs->trans('Unknown');
					}
					else
					{
						$intra = '';
					}
				}
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$i."</td>";
				$company_static->id=$coll->socid;
				$company_static->name=$coll->name;
				print '<td class="nowrap">'.$company_static->getNomUrl(1).'</td>';
				$find = array(' ','.');
				$replace = array('','');
				print '<td class="nowrap">'.$intra."</td>";
				print "<td class=\"nowrap\" align=\"right\">".price($coll->amount)."</td>";
				print "<td class=\"nowrap\" align=\"right\">".price($local==1?$coll->localtax1:$coll->localtax2)."</td>";
	            $totalamount = $totalamount + $coll->amount;
				$total = $total + ($local==1?$coll->localtax1:$coll->localtax2);
				print "</tr>\n";
				$i++;
			}
		}
	    $x_paye_sum = $total;

		print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").':</td>';
	    print '<td class="nowrap" align="right">'.price($totalamount).'</td>';
		print '<td class="nowrap" align="right">'.price($total).'</td>';
		print '</tr>';

		print '</table>';

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
}

if($calc ==0){
	// Total to pay
	print '<br><br>';
	print '<table class="noborder" width="100%">';
	$diff = $x_coll_sum - $x_paye_sum ;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="4">'.$langs->trans("TotalToPay").($q?', '.$langs->trans("Quadri").' '.$q:'').'</td>';
	print '<td class="liste_total nowrap" align="right"><b>'.price(price2num($diff,'MT'))."</b></td>\n";
	print "</tr>\n";

}
print '</table>';

llxFooter();
$db->close();
