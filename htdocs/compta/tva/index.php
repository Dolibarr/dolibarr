<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2021      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021      Open-Dsi             <support@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/compta/tva/index.php
 *		\ingroup    tax
 *		\brief      Index page of VAT reports
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

// Load translation files required by the page
$langs->loadLangs(array("other", "compta", "banks", "bills", "companies", "product", "trips", "admin"));

$now = dol_now();

$refresh = GETPOSTISSET('submit') ? true : false;
$year_current = GETPOSTISSET('year') ? GETPOST('year', 'int') : dol_print_date($now, '%Y', 'tzserver');
$year_start = $year_current;
$month_current = GETPOSTISSET('month') ? GETPOST('month', 'int') : dol_print_date($now, '%m', 'tzserver');
$month_start = $month_current;

$refresh = true;

include DOL_DOCUMENT_ROOT.'/compta/tva/initdatesforvat.inc.php';

// Define modetax (0 or 1)
// 0=normal, 1=option vat for services is on debit, 2=option on payments for products
$modetax = getDolGlobalString('TAX_MODE');
if (GETPOSTISSET("modetax")) {
	$modetax = GETPOST("modetax", 'int');
}
if (empty($modetax)) {
	$modetax = 0;
}

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'tva', 'charges');


/**
 * print function
 *
 * @param		DoliDB	$db		Database handler
 * @param		string	$sql	SQL Request
 * @param		string	$date	Date
 * @return		void
 */
function pt($db, $sql, $date)
{
	global $conf, $bc, $langs, $form;

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		$total = 0;
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<td class="nowrap">'.$date.'</td>';
		print '<td class="right">'.$langs->trans("ClaimedForThisPeriod").'</td>';
		print '<td class="right">'.$langs->trans("PaidDuringThisPeriod").$form->textwithpicto('', $langs->trans('PaidDuringThisPeriodDesc'), 1).'</td>';
		print "</tr>\n";

		$totalclaimed = 0;
		$totalpaid = 0;
		$amountclaimed = 0;
		$amountpaid = 0;
		$previousmonth = '';
		$previousmode = '';
		$mode = '';

		while ($i < $num) {
			$obj = $db->fetch_object($result);
			$mode = $obj->mode;

			//print $obj->dm.' '.$obj->mode.' '.$previousmonth.' '.$previousmode;
			if ($obj->mode == 'claimed' && !empty($previousmode)) {
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$previousmonth."</td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($amountclaimed)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($amountpaid)."</span></td>\n";
				print "</tr>\n";

				$amountclaimed = 0;
				$amountpaid = 0;
			}

			if ($obj->mode == 'claimed') {
				$amountclaimed = $obj->mm;
				$totalclaimed = $totalclaimed + $amountclaimed;
			}
			if ($obj->mode == 'paid') {
				$amountpaid = $obj->mm;
				$totalpaid = $totalpaid + $amountpaid;
			}

			if ($obj->mode == 'paid') {
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$obj->dm."</td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($amountclaimed)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($amountpaid)."</span></td>\n";
				print "</tr>\n";
				$amountclaimed = 0;
				$amountpaid = 0;
				$previousmode = '';
				$previousmonth = '';
			} else {
				$previousmode = $obj->mode;
				$previousmonth = $obj->dm;
			}

			$i++;
		}

		if ($mode == 'claimed' && !empty($previousmode)) {
			print '<tr class="oddeven">';
			print '<td class="nowrap">'.$previousmonth."</td>\n";
			print '<td class="nowrap right">'.price($amountclaimed)."</td>\n";
			print '<td class="nowrap right">'.price($amountpaid)."</td>\n";
			print "</tr>\n";

			$amountclaimed = 0;
			$amountpaid = 0;
		}

		print '<tr class="liste_total">';
		print '<td class="right">'.$langs->trans("Total").'</td>';
		print '<td class="nowrap right">'.price($totalclaimed).'</td>';
		print '<td class="nowrap right">'.price($totalpaid).'</td>';
		print "</tr>";

		print "</table>";

		$db->free($result);
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);
$company_static = new Societe($db);
$tva = new Tva($db);

$fsearch = '<!-- hidden fields for form -->';
$fsearch .= '<input type="hidden" name="token" value="'.newToken().'">';
$fsearch .= '<input type="hidden" name="modetax" value="'.$modetax.'">';

$description = $fsearch;

// Show report header
$name = $langs->trans("VATReportByMonth");
$calcmode = '';
if ($modetax == 0) {
	$calcmode = $langs->trans('OptionVATDefault');
}
if ($modetax == 1) {
	$calcmode = $langs->trans('OptionVATDebitOption');
}
if ($modetax == 2) {
	$calcmode = $langs->trans('OptionPaymentForProductAndServices');
}
$calcmode .= ' <span class="opacitymedium">('.$langs->trans("TaxModuleSetupToModifyRules", DOL_URL_ROOT.'/admin/taxes.php').')</span>';

$description .= $langs->trans("VATSummary").'<br>';
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice') {
	$description .= $langs->trans("RulesVATDueProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment') {
	$description .= $langs->trans("RulesVATInProducts");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice') {
	$description .= '<br>'.$langs->trans("RulesVATDueServices");
}
if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
	$description .= '<br>'.$langs->trans("RulesVATInServices");
}
if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= '<br>'.$langs->trans("DepositsAreNotIncluded");
}
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
	$description .= $langs->trans("SupplierDepositsAreNotIncluded");
}
if (isModEnabled('accounting')) {
	$description .= '<br>'.$langs->trans("ThisIsAnEstimatedValue");
}

$period = $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 0);

$builddate = dol_now();


llxHeader('', $name);

//$textprevyear="<a href=\"index.php?year=" . ($year_current-1) . "\">".img_previous($langs->trans("Previous"), 'class="valignbottom"')."</a>";
//$textnextyear=" <a href=\"index.php?year=" . ($year_current+1) . "\">".img_next($langs->trans("Next"), 'class="valignbottom"')."</a>";
//print load_fiche_titre($langs->transcountry("VAT", $mysoc->country_code), $textprevyear." ".$langs->trans("Year")." ".$year_start." ".$textnextyear, 'bill');

$periodlink = '';
$exportlink = '';

report_header($name, '', $period, $periodlink, $description, $builddate, $exportlink, array(), $calcmode);
//report_header($name,'',$textprevyear.$langs->trans("Year")." ".$year_start.$textnextyear,'',$description,$builddate,$exportlink,array(),$calcmode);


print '<br>';

if ($refresh === true) {
	print '<div class="fichecenter"><div class="fichethirdleft">';

	print load_fiche_titre($langs->trans("VATSummary"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="30%">' . $langs->trans("Year") . '</td>';
	print '<td class="right">' . $langs->trans("VATToPay") . '</td>';
	print '<td class="right">' . $langs->trans("VATToCollect") . '</td>';
	print '<td class="right">' . $langs->trans("Balance") . '</td>';
	print '<td>&nbsp;</td>' . "\n";
	print '</tr>' . "\n";

	$tmp = dol_getdate($date_start);
	$y = $tmp['year'];
	$m = $tmp['mon'];
	$tmp = dol_getdate($date_end);
	$yend = $tmp['year'];
	$mend = $tmp['mon'];
	//var_dump($m);
	$total = 0;
	$subtotalcoll = 0;
	$subtotalpaid = 0;
	$subtotal = 0;
	$i = 0;
	$mcursor = 0;

	while ((($y < $yend) || ($y == $yend && $m <= $mend)) && $mcursor < 1000) {    // $mcursor is to avoid too large loop
		//$m = $conf->global->SOCIETE_FISCAL_MONTH_START + ($mcursor % 12);
		if ($m == 13) {
			$y++;
		}
		if ($m > 12) {
			$m -= 12;
		}
		$mcursor++;

		$x_coll = tax_by_rate('vat', $db, $y, 0, 0, 0, $modetax, 'sell', $m);
		$x_paye = tax_by_rate('vat', $db, $y, 0, 0, 0, $modetax, 'buy', $m);

		$x_both = array();
		//now, from these two arrays, get another array with one rate per line
		foreach (array_keys($x_coll) as $my_coll_rate) {
			$x_both[$my_coll_rate]['coll']['totalht'] = $x_coll[$my_coll_rate]['totalht'];
			$x_both[$my_coll_rate]['coll']['vat'] = $x_coll[$my_coll_rate]['vat'];
			$x_both[$my_coll_rate]['paye']['totalht'] = 0;
			$x_both[$my_coll_rate]['paye']['vat'] = 0;
			$x_both[$my_coll_rate]['coll']['links'] = '';
			$x_both[$my_coll_rate]['coll']['detail'] = array();
			foreach ($x_coll[$my_coll_rate]['facid'] as $id => $dummy) {
				//$invoice_customer->id=$x_coll[$my_coll_rate]['facid'][$id];
				//$invoice_customer->ref=$x_coll[$my_coll_rate]['facnum'][$id];
				//$invoice_customer->type=$x_coll[$my_coll_rate]['type'][$id];
				//$company_static->fetch($x_coll[$my_coll_rate]['company_id'][$id]);
				$x_both[$my_coll_rate]['coll']['detail'][] = array(
					'id' => $x_coll[$my_coll_rate]['facid'][$id],
					'descr' => $x_coll[$my_coll_rate]['descr'][$id],
					'pid' => $x_coll[$my_coll_rate]['pid'][$id],
					'pref' => $x_coll[$my_coll_rate]['pref'][$id],
					'ptype' => $x_coll[$my_coll_rate]['ptype'][$id],
					'payment_id' => $x_coll[$my_coll_rate]['payment_id'][$id],
					'payment_amount' => $x_coll[$my_coll_rate]['payment_amount'][$id],
					'ftotal_ttc' => $x_coll[$my_coll_rate]['ftotal_ttc'][$id],
					'dtotal_ttc' => $x_coll[$my_coll_rate]['dtotal_ttc'][$id],
					'dtype' => $x_coll[$my_coll_rate]['dtype'][$id],
					'datef' => $x_coll[$my_coll_rate]['datef'][$id],
					'datep' => $x_coll[$my_coll_rate]['datep'][$id],
					//'company_link'=>$company_static->getNomUrl(1,'',20),
					'ddate_start' => $x_coll[$my_coll_rate]['ddate_start'][$id],
					'ddate_end' => $x_coll[$my_coll_rate]['ddate_end'][$id],
					'totalht' => $x_coll[$my_coll_rate]['totalht_list'][$id],
					'vat' => $x_coll[$my_coll_rate]['vat_list'][$id],
					//'link'      =>$invoice_customer->getNomUrl(1,'',12)
				);
			}
		}

		// tva paid
		foreach (array_keys($x_paye) as $my_paye_rate) {
			$x_both[$my_paye_rate]['paye']['totalht'] = $x_paye[$my_paye_rate]['totalht'];
			$x_both[$my_paye_rate]['paye']['vat'] = $x_paye[$my_paye_rate]['vat'];
			if (!isset($x_both[$my_paye_rate]['coll']['totalht'])) {
				$x_both[$my_paye_rate]['coll']['totalht'] = 0;
				$x_both[$my_paye_rate]['coll']['vat'] = 0;
			}
			$x_both[$my_paye_rate]['paye']['links'] = '';
			$x_both[$my_paye_rate]['paye']['detail'] = array();

			foreach ($x_paye[$my_paye_rate]['facid'] as $id => $dummy) {
				// ExpenseReport
				if ($x_paye[$my_paye_rate]['ptype'][$id] == 'ExpenseReportPayment') {
					//$expensereport->id=$x_paye[$my_paye_rate]['facid'][$id];
					//$expensereport->ref=$x_paye[$my_paye_rate]['facnum'][$id];
					//$expensereport->type=$x_paye[$my_paye_rate]['type'][$id];

					$x_both[$my_paye_rate]['paye']['detail'][] = array(
						'id' => $x_paye[$my_paye_rate]['facid'][$id],
						'descr' => $x_paye[$my_paye_rate]['descr'][$id],
						'pid' => $x_paye[$my_paye_rate]['pid'][$id],
						'pref' => $x_paye[$my_paye_rate]['pref'][$id],
						'ptype' => $x_paye[$my_paye_rate]['ptype'][$id],
						'payment_id' => $x_paye[$my_paye_rate]['payment_id'][$id],
						'payment_amount' => $x_paye[$my_paye_rate]['payment_amount'][$id],
						'ftotal_ttc' => price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
						'dtotal_ttc' => price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
						'dtype' => $x_paye[$my_paye_rate]['dtype'][$id],
						'ddate_start' => $x_paye[$my_paye_rate]['ddate_start'][$id],
						'ddate_end' => $x_paye[$my_paye_rate]['ddate_end'][$id],
						'totalht' => price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
						'vat' => $x_paye[$my_paye_rate]['vat_list'][$id],
						//'link'				=>$expensereport->getNomUrl(1)
					);
				} else {
					//$invoice_supplier->id=$x_paye[$my_paye_rate]['facid'][$id];
					//$invoice_supplier->ref=$x_paye[$my_paye_rate]['facnum'][$id];
					//$invoice_supplier->type=$x_paye[$my_paye_rate]['type'][$id];
					//$company_static->fetch($x_paye[$my_paye_rate]['company_id'][$id]);
					$x_both[$my_paye_rate]['paye']['detail'][] = array(
						'id' => $x_paye[$my_paye_rate]['facid'][$id],
						'descr' => $x_paye[$my_paye_rate]['descr'][$id],
						'pid' => $x_paye[$my_paye_rate]['pid'][$id],
						'pref' => $x_paye[$my_paye_rate]['pref'][$id],
						'ptype' => $x_paye[$my_paye_rate]['ptype'][$id],
						'payment_id' => $x_paye[$my_paye_rate]['payment_id'][$id],
						'payment_amount' => $x_paye[$my_paye_rate]['payment_amount'][$id],
						'ftotal_ttc' => price2num($x_paye[$my_paye_rate]['ftotal_ttc'][$id]),
						'dtotal_ttc' => price2num($x_paye[$my_paye_rate]['dtotal_ttc'][$id]),
						'dtype' => $x_paye[$my_paye_rate]['dtype'][$id],
						'datef' => $x_paye[$my_paye_rate]['datef'][$id],
						'datep' => $x_paye[$my_paye_rate]['datep'][$id],
						//'company_link'=>$company_static->getNomUrl(1,'',20),
						'ddate_start' => $x_paye[$my_paye_rate]['ddate_start'][$id],
						'ddate_end' => $x_paye[$my_paye_rate]['ddate_end'][$id],
						'totalht' => price2num($x_paye[$my_paye_rate]['totalht_list'][$id]),
						'vat' => $x_paye[$my_paye_rate]['vat_list'][$id],
						//'link'      =>$invoice_supplier->getNomUrl(1,'',12)
					);
				}
			}
		}
		//now we have an array (x_both) indexed by rates for coll and paye

		$action = "tva";
		$object = array(&$x_coll, &$x_paye, &$x_both);
		$parameters["mode"] = $modetax;
		$parameters["year"] = $y;
		$parameters["month"] = $m;
		$parameters["type"] = 'vat';

		// Initialize technical object to manage hooks of expenses. Note that conf->hooks_modules contains array array
		$hookmanager->initHooks(array('externalbalance'));
		$reshook = $hookmanager->executeHooks('addVatLine', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks


		print '<tr class="oddeven">';
		print '<td class="nowrap"><a href="' . DOL_URL_ROOT . '/compta/tva/quadri_detail.php?leftmenu=tax_vat&month=' . $m . '&year=' . $y . '">' . dol_print_date(dol_mktime(0, 0, 0, $m, 1, $y), "%b %Y") . '</a></td>';

		$x_coll_sum = 0;
		foreach (array_keys($x_coll) as $rate) {
			$subtot_coll_total_ht = 0;
			$subtot_coll_vat = 0;

			foreach ($x_both[$rate]['coll']['detail'] as $index => $fields) {
				// Payment
				$ratiopaymentinvoice = 1;
				if ($modetax != 1) {
					// Define type
					// We MUST use dtype (type in line). We can use something else, only if dtype is really unknown.
					$type = (isset($fields['dtype']) ? $fields['dtype'] : $fields['ptype']);
					// Try to enhance type detection using date_start and date_end for free lines where type
					// was not saved.
					if (!empty($fields['ddate_start'])) {
						$type = 1;
					}
					if (!empty($fields['ddate_end'])) {
						$type = 1;
					}

					if (($type == 0 && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
						|| ($type == 1 && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')) {
						//print $langs->trans("NA");
					} else {
						if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
							$ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
						}
					}
				}
				//var_dump('type='.$type.' '.$fields['totalht'].' '.$ratiopaymentinvoice);
				$temp_ht = $fields['totalht'] * $ratiopaymentinvoice;
				$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
				$subtot_coll_total_ht += $temp_ht;
				$subtot_coll_vat += $temp_vat;
				$x_coll_sum += $temp_vat;
			}
		}
		print '<td class="nowrap right"><span class="amount">' . price(price2num($x_coll_sum, 'MT')) . '</span></td>';

		$x_paye_sum = 0;
		foreach (array_keys($x_paye) as $rate) {
			$subtot_paye_total_ht = 0;
			$subtot_paye_vat = 0;

			foreach ($x_both[$rate]['paye']['detail'] as $index => $fields) {
				// Payment
				$ratiopaymentinvoice = 1;
				if ($modetax != 1) {
					// Define type
					// We MUST use dtype (type in line). We can use something else, only if dtype is really unknown.
					$type = (isset($fields['dtype']) ? $fields['dtype'] : $fields['ptype']);
					// Try to enhance type detection using date_start and date_end for free lines where type
					// was not saved.
					if (!empty($fields['ddate_start'])) {
						$type = 1;
					}
					if (!empty($fields['ddate_end'])) {
						$type = 1;
					}

					if (($type == 0 && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
						|| ($type == 1 && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')) {
						//print $langs->trans("NA");
					} else {
						if (isset($fields['payment_amount']) && price2num($fields['ftotal_ttc'])) {
							$ratiopaymentinvoice = ($fields['payment_amount'] / $fields['ftotal_ttc']);
						}
					}
				}
				//var_dump('type='.$type.' '.$fields['totalht'].' '.$ratiopaymentinvoice);
				$temp_ht = $fields['totalht'] * $ratiopaymentinvoice;
				$temp_vat = $fields['vat'] * $ratiopaymentinvoice;
				$subtot_paye_total_ht += $temp_ht;
				$subtot_paye_vat += $temp_vat;
				$x_paye_sum += $temp_vat;
			}
		}
		print '<td class="nowrap right"><span class="amount">' . price(price2num($x_paye_sum, 'MT')) . '</span></td>';

		$subtotalcoll = $subtotalcoll + $x_coll_sum;
		$subtotalpaid = $subtotalpaid + $x_paye_sum;

		$diff = $x_coll_sum - $x_paye_sum;
		$total = $total + $diff;
		$subtotal = price2num($subtotal + $diff, 'MT');

		print '<td class="nowrap right"><span class="amount">' . price(price2num($diff, 'MT')) . '</span></td>' . "\n";
		print "<td>&nbsp;</td>\n";
		print "</tr>\n";

		// Total
		$i++;
		$m++;
		if ($i > 2) {
			print '<tr class="liste_total">';
			print '<td class="right"><a href="quadri_detail.php?leftmenu=tax_vat&q=' . round($m / 3) . '&year=' . $y . '">' . $langs->trans("SubTotal") . '</a>:</td>';
			print '<td class="nowrap right">' . price(price2num($subtotalcoll, 'MT')) . '</td>';
			print '<td class="nowrap right">' . price(price2num($subtotalpaid, 'MT')) . '</td>';
			print '<td class="nowrap right">' . price(price2num($subtotal, 'MT')) . '</td>';
			print '<td>&nbsp;</td></tr>';
			$i = 0;
			$subtotalcoll = 0;
			$subtotalpaid = 0;
			$subtotal = 0;
		}
	}
	print '<tr class="liste_total"><td class="right" colspan="3">' . $langs->trans("TotalToPay") . ':</td><td class="nowrap right">' . price(price2num($total, 'MT')) . '</td>';
	print "<td>&nbsp;</td>\n";
	print '</tr>';

	print '</table>';


	print '</div><div class="fichetwothirdright">';


	/*
	 * Paid
	 */
	print load_fiche_titre($langs->trans("VATPaid"), '', '');

	$sql = '';

	$sql .= "SELECT SUM(amount) as mm, date_format(tva.datev,'%Y-%m') as dm, 'claimed' as mode";
	$sql .= " FROM " . MAIN_DB_PREFIX . "tva as tva";
	$sql .= " WHERE tva.entity = " . $conf->entity;
	$sql .= " AND (tva.datev >= '" . $db->idate($date_start) . "' AND tva.datev <= '" . $db->idate($date_end) . "')";
	$sql .= " GROUP BY dm";

	$sql .= " UNION ";

	$sql .= "SELECT SUM(ptva.amount) as mm, date_format(tva.datev,'%Y-%m') as dm, 'paid' as mode";
	$sql .= " FROM " . MAIN_DB_PREFIX . "tva as tva";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "payment_vat as ptva ON (tva.rowid = ptva.fk_tva)";
	$sql .= " WHERE tva.entity = " . $conf->entity;
	$sql .= " AND (tva.datev >= '" . $db->idate($date_start) . "' AND tva.datev <= '" . $db->idate($date_end) . "')";
	$sql .= " GROUP BY dm";

	$sql .= " ORDER BY dm ASC, mode ASC";
	//print $sql;

	pt($db, $sql, $langs->trans("Month"));

	print '</div>';
}

llxFooter();
$db->close();
