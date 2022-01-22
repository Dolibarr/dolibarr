<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Andreu Bisquerra	 <jove@bisquerra.com>
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
 *	\file       htdocs/compta/cashcontrol/report.php
 *	\ingroup    cashdesk|takepos
 *	\brief      List of bank transactions
 */

if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1'); // Disable browser notification

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';

$langs->loadLangs(array("bills", "banks"));

$id = GETPOST('id', 'int');

$_GET['optioncss'] = "print";

$cashcontrol = new CashControl($db);
$cashcontrol->fetch($id);

//$limit = GETPOST('limit')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortorder = 'ASC';
$sortfield = 'b.datev,b.dateo,b.rowid';

$arrayfields = array(
	'b.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'b.dateo'=>array('label'=>$langs->trans("DateOperationShort"), 'checked'=>1),
	'b.num_chq'=>array('label'=>$langs->trans("Number"), 'checked'=>1),
	'ba.ref'=>array('label'=>$langs->trans("BankAccount"), 'checked'=>1),
	'cp.code'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>1),
	'b.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1, 'position'=>600),
	'b.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1, 'position'=>605),
);

$syear  = $cashcontrol->year_close;
$smonth = $cashcontrol->month_close;
$sday   = $cashcontrol->day_close;

$posmodule = $cashcontrol->posmodule;
$terminalid = $cashcontrol->posnumber;


/*
 * View
 */

$param = '';

llxHeader('', $langs->trans("CashControl"), '', '', 0, 0, array(), array(), $param);

/*$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.url_id,";
$sql.= " f.module_source, f.ref as ref";
$sql.= " FROM ";
//if ($bid) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'payment'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON bu.url_id = f.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";
// Define filter on invoice
$sql.= " AND f.module_source = '".$db->escape($cashcontrol->posmodule)."'";
$sql.= " AND f.pos_source = '".$db->escape($cashcontrol->posnumber)."'";
$sql.= " AND f.entity IN (".getEntity('facture').")";
// Define filter on data
if ($syear && ! $smonth)              $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
elseif ($syear && $smonth && ! $sday) $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
elseif ($syear && $smonth && $sday)   $sql.= " AND dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
else dol_print_error('', 'Year not defined');
// Define filter on bank account
$sql.=" AND (b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
$sql.=" OR b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CB;
$sql.=" OR b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
$sql.=")";
*/
$sql = "SELECT f.rowid as facid, f.ref, f.datef as do, pf.amount as amount, b.fk_account as bankid, cp.code";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp, ".MAIN_DB_PREFIX."bank as b";
$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement AND p.fk_bank = b.rowid";
$sql .= " AND f.module_source = '".$db->escape($posmodule)."'";
$sql .= " AND f.pos_source = '".$db->escape($terminalid)."'";
$sql .= " AND f.paye = 1";
$sql .= " AND p.entity = ".$conf->entity;	// Never share entities for features related to accountancy
/*if ($key == 'cash')       $sql.=" AND cp.code = 'LIQ'";
elseif ($key == 'cheque') $sql.=" AND cp.code = 'CHQ'";
elseif ($key == 'card')   $sql.=" AND cp.code = 'CB'";
else
{
	dol_print_error('Value for key = '.$key.' not supported');
	exit;
}*/
if ($syear && !$smonth)              $sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
elseif ($syear && $smonth && !$sday) $sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
elseif ($syear && $smonth && $sday)   $sql .= " AND datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
else dol_print_error('', 'Year not defined');

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print "<!-- title of cash fence -->\n";
	print "<center><h2>";
	if ($cashcontrol->status != $cashcontrol::STATUS_DRAFT) print $langs->trans("CashControl")." ".$cashcontrol->id;
	else print $langs->trans("CashControl")." - ".$langs->trans("Draft");
	print "<br>".$langs->trans("DateCreationShort").": ".dol_print_date($cashcontrol->date_creation, 'dayhour');
	print "</h2></center>";

	$invoicetmp = new Facture($db);

	print "<div style='text-align: right'><h2>";
	print $langs->trans("InitialBankBalance").' - '.$langs->trans("Cash")." : ".price($cashcontrol->opening);
	print "</h2></div>";

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">'."\n";

	$param = '';

	// Fields title
	print '<tr class="liste_titre">';
	print_liste_field_titre($arrayfields['b.rowid']['label'], $_SERVER['PHP_SELF'], 'b.rowid', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre($arrayfields['b.dateo']['label'], $_SERVER['PHP_SELF'], 'b.dateo', '', $param, '"', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($arrayfields['ba.ref']['label'], $_SERVER['PHP_SELF'], 'ba.ref', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($arrayfields['cp.code']['label'], $_SERVER['PHP_SELF'], 'cp.code', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($arrayfields['b.debit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($arrayfields['b.credit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

	// Loop on each record
	$sign = 1;
	$cash = $bank = $cheque = $other = 0;

	$totalarray = array();
	$cachebankaccount = array();
	$amountpertype = array();
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		if (empty($cachebankaccount[$objp->bankid])) {
			$bankaccounttmp = new Account($db);
			$bankaccounttmp->fetch($objp->bankid);
			$cachebankaccount[$objp->bankid] = $bankaccounttmp;
			$bankaccount = $bankaccounttmp;
		} else {
			$bankaccount = $cachebankaccount[$objp->bankid];
		}

		$invoicetmp->fetch($objp->facid);

		/*if ($first == "yes")
		{
			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").'</td>';
			print '<td></td><td></td><td></td><td class="right">'.price($cashcontrol->opening).'</td>';
			print '</tr>';
			$first = "no";
		}*/

		print '<tr class="oddeven">';

		// Ref
		print '<td class="nowrap left">';
		print $invoicetmp->getNomUrl(1);
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		// Date ope
		print '<td class="nowrap left">';
		print '<span id="dateoperation_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->do), "day")."</span>";
		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;

		// Bank account
		print '<td class="nowrap right">';
		print $bankaccount->getNomUrl(1);
		if ($cashcontrol->posmodule == "takepos") {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH'.$cashcontrol->posnumber;
		} else {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH';
		}
		if ($objp->code == 'CHQ') {
			$cheque += $objp->amount;
		} elseif ($objp->code == 'CB') {
			$bank += $objp->amount;
		} else {
			if ($conf->global->$var1 == $bankaccount->id) $cash += $objp->amount;
			//elseif ($conf->global->$var2 == $bankaccount->id) $bank+=$objp->amount;
			//elseif ($conf->global->$var3 == $bankaccount->id) $cheque+=$objp->amount;
			else $other += $objp->amount;
		}
		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;

		// Type
		print '<td class="right">';
	   	print $objp->code;
	   	if (empty($amountpertype[$objp->code])) $amountpertype[$objp->code] = 0;
		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;

		// Debit
		print '<td class="right">';
		if ($objp->amount < 0) {
			print price($objp->amount * -1);
			$totalarray['val']['totaldebfield'] += $objp->amount;
			$amountpertype[$objp->code] += $objp->amount;
		}
		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totaldebfield';

		// Credit
		print '<td class="right">';
		if ($objp->amount > 0) {
			print price($objp->amount);
			$totalarray['val']['totalcredfield'] += $objp->amount;
			$amountpertype[$objp->code] -= $objp->amount;
		}
		print "</td>\n";
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'totalcredfield';

		print "</tr>";

		$i++;
	}

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	print "</table>";

	//$cash = $amountpertype['LIQ'] + $cashcontrol->opening;
	$cash = price2num($cash + $cashcontrol->opening, 'MT');

	print "<div style='text-align: right'><h2>";
	print $langs->trans("Cash").": ".price($cash);
	if ($cashcontrol->status == $cashcontrol::STATUS_VALIDATED && $cash != $cashcontrol->cash) {
		print ' <> <span class="amountremaintopay">'.$langs->trans("Declared").': '.price($cashcontrol->cash).'</span>';
	}
	print "<br><br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCHQ").": ".price($cheque);
	if ($cashcontrol->status == $cashcontrol::STATUS_VALIDATED && $cheque != $cashcontrol->cheque) {
		print ' <> <span class="amountremaintopay">'.$langs->trans("Declared").': '.price($cashcontrol->cheque).'</span>';
	}
	print "<br><br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCB").": ".price($bank);
	if ($cashcontrol->status == $cashcontrol::STATUS_VALIDATED && $bank != $cashcontrol->card) {
		print ' <> <span class="amountremaintopay">'.$langs->trans("Declared").': '.price($cashcontrol->card).'</span>';
	}
	print "<br><br>";

	// print '<br>';
	if ($other) {
		print '<br>'.$langs->trans("Other").": ".price($other)."<br><br>";
	}
	print "</h2></div>";

	//save totals to DB
	/*
	$sql = "UPDATE ".MAIN_DB_PREFIX."pos_cash_fence ";
	$sql .= "SET";
	$sql .= " cash='".$db->escape($cash)."'";
    $sql .= ", card='".$db->escape($bank)."'";
	$sql .= " where rowid=".$id;
	$db->query($sql);
	*/

	print "</div>";

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();

$db->close();
