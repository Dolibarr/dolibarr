<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
 * Copyright (C) 2014	   Ferran Marcet        <fmarcet@2byte.es>
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
 *		\file       htdocs/compta/tva/clients.php
 *		\ingroup    tax
 *		\brief      Page of sales taxes
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';

$langs->loadLangs(array("other","compta","banks","bills","companies","product","trips","admin"));

// Date range
$year=GETPOST("year","int");
if (empty($year))
{
	$year_current = strftime("%Y",dol_now());
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start=dol_mktime(0,0,0,GETPOST("date_startmonth"),GETPOST("date_startday"),GETPOST("date_startyear"));
$date_end=dol_mktime(23,59,59,GETPOST("date_endmonth"),GETPOST("date_endday"),GETPOST("date_endyear"));
// Quarter
if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$q=GETPOST("q");
	if (empty($q))
	{
		if (GETPOST("month")) { $date_start=dol_get_first_day($year_start,GETPOST("month"),false); $date_end=dol_get_last_day($year_start,GETPOST("month"),false); }
		else
		{
			$date_start=dol_get_first_day($year_start,empty($conf->global->SOCIETE_FISCAL_MONTH_START)?1:$conf->global->SOCIETE_FISCAL_MONTH_START,false);
			if (empty($conf->global->MAIN_INFO_VAT_RETURN) || $conf->global->MAIN_INFO_VAT_RETURN == 2) $date_end=dol_time_plus_duree($date_start, 3, 'm') - 1;
			else if ($conf->global->MAIN_INFO_VAT_RETURN == 3) $date_end=dol_time_plus_duree($date_start, 1, 'y') - 1;
			else if ($conf->global->MAIN_INFO_VAT_RETURN == 1) $date_end=dol_time_plus_duree($date_start, 1, 'm') - 1;
		}
	}
	else
	{
		if ($q==1) { $date_start=dol_get_first_day($year_start,1,false); $date_end=dol_get_last_day($year_start,3,false); }
		if ($q==2) { $date_start=dol_get_first_day($year_start,4,false); $date_end=dol_get_last_day($year_start,6,false); }
		if ($q==3) { $date_start=dol_get_first_day($year_start,7,false); $date_end=dol_get_last_day($year_start,9,false); }
		if ($q==4) { $date_start=dol_get_first_day($year_start,10,false); $date_end=dol_get_last_day($year_start,12,false); }
	}
}

$min = price2num(GETPOST("min","alpha"));
if (empty($min)) $min = 0;

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = $conf->global->TAX_MODE;
if (GETPOSTISSET("modetax")) $modetax=GETPOST("modetax",'int');
if (empty($modetax)) $modetax=0;

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->global->ACCOUNTING_MODE;
if (GETPOST("modecompta")) $modecompta=GETPOST("modecompta");



/*
 * View
 */

$form=new Form($db);
$company_static=new Societe($db);

$morequerystring='';
$listofparams=array('date_startmonth','date_startyear','date_startday','date_endmonth','date_endyear','date_endday');
foreach($listofparams as $param) {
	if (GETPOST($param)!='') {
		$morequerystring.=($morequerystring?'&':'').$param.'='.GETPOST($param);
	}
}

$special_report = false;
if (isset($_REQUEST['extra_report']) && $_REQUEST['extra_report'] == 1) {
	$special_report = true;
}

llxHeader('',$langs->trans("VATReport"),'','',0,0,'','',$morequerystring);

$fsearch.='<br>';
$fsearch.='  <input type="hidden" name="year" value="'.$year.'">';
$fsearch.='  <input type="hidden" name="modetax" value="'.$modetax.'">';
$fsearch.='  '.$langs->trans("SalesTurnoverMinimum").': ';
$fsearch.='  <input type="text" name="min" id="min" value="'.$min.'" size="6">';

$description='';

// Show report header
$name=$langs->trans("VATReportByCustomers");
$calcmode='';
if ($modetax == 0) $calcmode=$langs->trans('OptionVATDefault');
if ($modetax == 1) $calcmode=$langs->trans('OptionVATDebitOption');
if ($modetax == 2) $calcmode=$langs->trans('OptionPaymentForProductAndServices');
$calcmode.='<br>('.$langs->trans("TaxModuleSetupToModifyRules",DOL_URL_ROOT.'/admin/taxes.php').')';

if ($conf->global->TAX_MODE_SELL_PRODUCT == 'invoice') $description.=$langs->trans("RulesVATDueProducts");
if ($conf->global->TAX_MODE_SELL_PRODUCT == 'payment') $description.=$langs->trans("RulesVATInProducts");
if ($conf->global->TAX_MODE_SELL_SERVICE == 'invoice') $description.='<br>'.$langs->trans("RulesVATDueServices");
if ($conf->global->TAX_MODE_SELL_SERVICE == 'payment') $description.='<br>'.$langs->trans("RulesVATInServices");
if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	$description.='<br>'.$langs->trans("DepositsAreNotIncluded");
}
if (! empty($conf->global->MAIN_MODULE_ACCOUNTING)) $description.='<br>'.$langs->trans("ThisIsAnEstimatedValue");

$period=$form->select_date($date_start,'date_start',0,0,0,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,0,'',1,0,1);
//$periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year=".($year_start-1)."&modetax=".$modetax."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year=".($year_start+1)."&modetax=".$modetax."'>".img_next()."</a>":"");
$description.=$fsearch;
$description.='<br>'
	. '<input type="radio" name="extra_report" value="0" '.($special_report?'':'checked="checked"').'> '
	. $langs->trans('SimpleReport')
	. '</input>'
	. '<br>'
	. '<input type="radio" name="extra_report" value="1" '.($special_report?'checked="checked"':'').'> '
	. $langs->trans('AddExtraReport')
	. '</input>'
	. '<br>';
$builddate=dol_now();
//$exportlink=$langs->trans("NotYetAvailable");

$elementcust=$langs->trans("CustomersInvoices");
$productcust=$langs->trans("Description");
$amountcust=$langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
	$vatcust.=' ('.$langs->trans("ToPay").')';
}
$elementsup=$langs->trans("SuppliersInvoices");
$productsup=$langs->trans("Description");
$amountsup=$langs->trans("AmountHT");
if ($mysoc->tva_assuj) {
	$vatsup.=' ('.$langs->trans("ToGetBack").')';
}
report_header($name,'',$period,$periodlink,$description,$builddate,$exportlink,array(),$calcmode);

$vatcust=$langs->trans("VATReceived");
$vatsup=$langs->trans("VATPaid");


// VAT Received

//print "<br>";
//print load_fiche_titre($vatcust);

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Customer")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("AmountHTVATRealReceived")."</td>";
print "<td align=\"right\">".$vatcust."</td>";
print "</tr>\n";

$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'sell');

$action = "tvaclient";
$object = &$coll_list;
$parameters["mode"] = $modetax;
$parameters["start"] = $date_start;
$parameters["end"] = $date_end;
$parameters["direction"] = 'sell';
$parameters["type"] = 'vat';

// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('externalbalance'));
$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if (is_array($coll_list)) {
	$var=true;
	$total = 0;  $totalamount = 0;
	$i = 1;
	foreach ($coll_list as $coll) {
		if ($min == 0 or ($min > 0 && $coll->amount > $min)) {

			$intra = str_replace($find,$replace,$coll->tva_intra);
			if(empty($intra)) {
				if($coll->assuj == '1') {
					$intra = $langs->trans('Unknown');
				} else {
					//$intra = $langs->trans('NotRegistered');
					$intra = '';
				}
			}
			print '<tr class="oddeven">';
			print '<td class="nowrap">'.$i."</td>";
			$company_static->id=$coll->socid;
			$company_static->name=$coll->name;
			$company_static->client=1;
			print '<td class="nowrap">'.$company_static->getNomUrl(1,'customer').'</td>';
			$find = array(' ','.');
			$replace = array('','');
			print '<td class="nowrap">'.$intra."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($coll->amount)."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($coll->tva)."</td>";
			$totalamount = $totalamount + $coll->amount;
			$total = $total + $coll->tva;
			print "</tr>\n";
			$i++;
		}
	}
	$x_coll_sum = $total;

	print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("Total").':</td>';
	print '<td class="nowrap" align="right">'.price($totalamount).'</td>';
	print '<td class="nowrap" align="right">'.price($total).'</td>';
	print '</tr>';
} else {
	$langs->load("errors");
	if ($coll_list == -1) {
		if ($modecompta == 'CREANCES-DETTES')
		{
			print '<tr><td colspan="5">' . $langs->trans("ErrorNoAccountancyModuleLoaded") . '</td></tr>';
		}
		else
		{
			print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
		}
	} else if ($coll_list == -2) {
		print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
	} else {
		print '<tr><td colspan="5">' . $langs->trans("Error") . '</td></tr>';
	}
}

//print '</table>';


// VAT Paid

//print "<br>";
//print load_fiche_titre($vatsup);

//print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre liste_titre_topborder\">";
print '<td align="left">'.$langs->trans("Num")."</td>";
print '<td align="left">'.$langs->trans("Supplier")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("AmountHTVATRealPaid")."</td>";
print "<td align=\"right\">".$vatsup."</td>";
print "</tr>\n";

$company_static=new Societe($db);

$coll_list = vat_by_thirdparty($db,0,$date_start,$date_end,$modetax,'buy');

$parameters["direction"] = 'buy';
$reshook=$hookmanager->executeHooks('addVatLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if (is_array($coll_list)) {
	$var=true;
	$total = 0;  $totalamount = 0;
	$i = 1;
	foreach ($coll_list as $coll) {
		if ($min == 0 or ($min > 0 && $coll->amount > $min)) {

			$intra = str_replace($find,$replace,$coll->tva_intra);
			if (empty($intra)) {
				if ($coll->assuj == '1') {
					$intra = $langs->trans('Unknown');
				} else {
					//$intra = $langs->trans('NotRegistered');
					$intra = '';
				}
			}
			print '<tr class="oddeven">';
			print '<td class="nowrap">'.$i."</td>";
			$company_static->id=$coll->socid;
			$company_static->name=$coll->name;
			$company_static->fournisseur=1;
			print '<td class="nowrap">'.$company_static->getNomUrl(1,'supplier').'</td>';
			$find = array(' ','.');
			$replace = array('','');
			print '<td class="nowrap">'.$intra."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($coll->amount)."</td>";
			print "<td class=\"nowrap\" align=\"right\">".price($coll->tva)."</td>";
			$totalamount = $totalamount + $coll->amount;
			$total = $total + $coll->tva;
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

	// Total to pay
	print '<br><br>';
	print '<table class="noborder" width="100%">';
	$diff = $x_coll_sum - $x_paye_sum;
	print '<tr class="liste_total">';
	print '<td class="liste_total" colspan="4">'.$langs->trans("TotalToPay").($q?', '.$langs->trans("Quadri").' '.$q:'').'</td>';
	print '<td class="liste_total nowrap" align="right"><b>'.price(price2num($diff,'MT'))."</b></td>\n";
	print "</tr>\n";

} else {
	$langs->load("errors");
	if ($coll_list == -1) {
		if ($modecompta == 'CREANCES-DETTES')
		{
			print '<tr><td colspan="5">' . $langs->trans("ErrorNoAccountancyModuleLoaded") . '</td></tr>';
		}
		else
		{
			print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
		}
	} else if ($coll_list == -2) {
		print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
	} else {
		print '<tr><td colspan="5">' . $langs->trans("Error") . '</td></tr>';
	}
}

print '</table>';

if ($special_report) {
	// Get country 2-letters code
	global $mysoc;
	$country_id = $mysoc->country_id;
	$country = new Ccountry($db);
	$country->fetch($country_id);

	// Print listing of other-country customers as additional report
	// This matches tax requirements to list all same-country customers (only)
	print '<h3>'.$langs->trans('OtherCountriesCustomersReport').'</h3>';
	print $langs->trans('BasedOnTwoFirstLettersOfVATNumberBeingDifferentFromYourCompanyCountry');
	$coll_list = vat_by_thirdparty($db, 0, $date_start, $date_end, $modetax, 'sell');

	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print '<td align="left">' . $langs->trans("Num") . "</td>";
	print '<td align="left">' . $langs->trans("Customer") . "</td>";
	print "<td>" . $langs->trans("VATIntra") . "</td>";
	print "<td align=\"right\">" . $langs->trans("AmountHTVATRealReceived") . "</td>";
	print "<td align=\"right\">" . $vatcust . "</td>";
	print "</tr>\n";

	if (is_array($coll_list)) {
		$var = true;
		$total = 0;
		$totalamount = 0;
		$i = 1;
		foreach ($coll_list as $coll) {
			if (substr($coll->tva_intra, 0, 2) == $country->code) {
				// Only use different-country VAT codes
				continue;
			}
			if ($min == 0 or ($min > 0 && $coll->amount > $min)) {
				$var = !$var;
				$intra = str_replace($find, $replace, $coll->tva_intra);
				if (empty($intra)) {
					if ($coll->assuj == '1') {
						$intra = $langs->trans('Unknown');
					} else {
						//$intra = $langs->trans('NotRegistered');
						$intra = '';
					}
				}
				print "<tr " . $bc[$var] . ">";
				print '<td class="nowrap">' . $i . "</td>";
				$company_static->id = $coll->socid;
				$company_static->name = $coll->name;
				$company_static->client = 1;
				print '<td class="nowrap">' . $company_static->getNomUrl(1,
						'customer') . '</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">' . $intra . "</td>";
				print "<td class=\"nowrap\" align=\"right\">" . price($coll->amount) . "</td>";
				print "<td class=\"nowrap\" align=\"right\">" . price($coll->tva) . "</td>";
				$totalamount = $totalamount + $coll->amount;
				$total = $total + $coll->tva;
				print "</tr>\n";
				$i++;
			}
		}
		$x_coll_sum = $total;

		print '<tr class="liste_total"><td align="right" colspan="3">' . $langs->trans("Total") . ':</td>';
		print '<td class="nowrap" align="right">' . price($totalamount) . '</td>';
		print '<td class="nowrap" align="right">' . price($total) . '</td>';
		print '</tr>';
	} else {
		$langs->load("errors");
		if ($coll_list == -1) {
			if ($modecompta == 'CREANCES-DETTES')
			{
				print '<tr><td colspan="5">' . $langs->trans("ErrorNoAccountancyModuleLoaded") . '</td></tr>';
			}
			else
			{
				print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
			}
		} else {
			if ($coll_list == -2) {
				print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
			} else {
				print '<tr><td colspan="5">' . $langs->trans("Error") . '</td></tr>';
			}
		}
	}
	print '</table>';

	// Print listing of same-country customers as additional report
	// This matches tax requirements to list all same-country customers (only)
	print '<h3>'.$langs->trans('SameCountryCustomersWithVAT').'</h3>';
	print $langs->trans('BasedOnTwoFirstLettersOfVATNumberBeingTheSameAsYourCompanyCountry');
	$coll_list = vat_by_thirdparty($db, 0, $date_start, $date_end, $modetax, 'sell');

	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print '<td align="left">' . $langs->trans("Num") . "</td>";
	print '<td align="left">' . $langs->trans("Customer") . "</td>";
	print "<td>" . $langs->trans("VATIntra") . "</td>";
	print "<td align=\"right\">" . $langs->trans("AmountHTVATRealReceived") . "</td>";
	print "<td align=\"right\">" . $vatcust . "</td>";
	print "</tr>\n";

	if (is_array($coll_list)) {
		$var = true;
		$total = 0;
		$totalamount = 0;
		$i = 1;
		foreach ($coll_list as $coll) {
			if (substr($coll->tva_intra, 0, 2) != $country->code) {
				// Only use same-country VAT codes
				continue;
			}
			if ($min == 0 or ($min > 0 && $coll->amount > $min)) {
				$var = !$var;
				$intra = str_replace($find, $replace, $coll->tva_intra);
				if (empty($intra)) {
					if ($coll->assuj == '1') {
						$intra = $langs->trans('Unknown');
					} else {
						//$intra = $langs->trans('NotRegistered');
						$intra = '';
					}
				}
				print "<tr " . $bc[$var] . ">";
				print '<td class="nowrap">' . $i . "</td>";
				$company_static->id = $coll->socid;
				$company_static->name = $coll->name;
				$company_static->client = 1;
				print '<td class="nowrap">' . $company_static->getNomUrl(1, 'customer') . '</td>';
				$find = array(' ', '.');
				$replace = array('', '');
				print '<td class="nowrap">' . $intra . "</td>";
				print "<td class=\"nowrap\" align=\"right\">" . price($coll->amount) . "</td>";
				print "<td class=\"nowrap\" align=\"right\">" . price($coll->tva) . "</td>";
				$totalamount = $totalamount + $coll->amount;
				$total = $total + $coll->tva;
				print "</tr>\n";
				$i++;
			}
		}
		$x_coll_sum = $total;

		print '<tr class="liste_total"><td align="right" colspan="3">' . $langs->trans("Total") . ':</td>';
		print '<td class="nowrap" align="right">' . price($totalamount) . '</td>';
		print '<td class="nowrap" align="right">' . price($total) . '</td>';
		print '</tr>';
	} else {
		$langs->load("errors");
		if ($coll_list == -1) {
			if ($modecompta == 'CREANCES-DETTES')
			{
				print '<tr><td colspan="5">' . $langs->trans("ErrorNoAccountancyModuleLoaded") . '</td></tr>';
			}
			else
			{
				print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
			}
		} else {
			if ($coll_list == -2) {
				print '<tr><td colspan="5">' . $langs->trans("FeatureNotYetAvailable") . '</td></tr>';
			} else {
				print '<tr><td colspan="5">' . $langs->trans("Error") . '</td></tr>';
			}
		}
	}
	print '</table>';
}

llxFooter();

$db->close();
