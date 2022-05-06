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
 *	\brief      List of sales from POS
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1'); // Disable browser notification
}

$_GET['optioncss'] = "print";

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


$langs->loadLangs(array("bills", "banks"));

$id = GETPOST('id', 'int');

$object = new CashControl($db);
$object->fetch($id);

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

$syear  = $object->year_close;
$smonth = $object->month_close;
$sday   = $object->day_close;

$posmodule = $object->posmodule;
$terminalid = $object->posnumber;

// Security check
if ($user->socid > 0) {	// Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
if (empty($user->rights->cashdesk->run) && empty($user->rights->takepos->run)) {
	accessforbidden();
}


/*
 * View
 */

$title = $langs->trans("CashControl");
$param = '';

llxHeader('', $title, '', '', 0, 0, array(), array(), $param);

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
$sql.= " AND f.module_source = '".$db->escape($object->posmodule)."'";
$sql.= " AND f.pos_source = '".$db->escape($object->posnumber)."'";
$sql.= " AND f.entity IN (".getEntity('facture').")";
// Define filter on data
if ($syear && ! $smonth)              $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
elseif ($syear && $smonth && ! $sday) $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
elseif ($syear && $smonth && $sday)   $sql.= " AND dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
else dol_print_error('', 'Year not defined');
// Define filter on bank account
$sql.=" AND (b.fk_account = ".((int) $conf->global->CASHDESK_ID_BANKACCOUNT_CASH);
$sql.=" OR b.fk_account = ".((int) $conf->global->CASHDESK_ID_BANKACCOUNT_CB);
$sql.=" OR b.fk_account = ".((int) $conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE);
$sql.=")";
*/
$sql = "SELECT f.rowid as facid, f.ref, f.datef as do, pf.amount as amount, b.fk_account as bankid, cp.code";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp, ".MAIN_DB_PREFIX."bank as b";
$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement AND p.fk_bank = b.rowid";
$sql .= " AND f.module_source = '".$db->escape($posmodule)."'";
$sql .= " AND f.pos_source = '".$db->escape($terminalid)."'";
$sql .= " AND f.paye = 1";
$sql .= " AND p.entity = ".$conf->entity; // Never share entities for features related to accountancy
/*if ($key == 'cash')       $sql.=" AND cp.code = 'LIQ'";
elseif ($key == 'cheque') $sql.=" AND cp.code = 'CHQ'";
elseif ($key == 'card')   $sql.=" AND cp.code = 'CB'";
else
{
	dol_print_error('Value for key = '.$key.' not supported');
	exit;
}*/
if ($syear && !$smonth) {
	$sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
} elseif ($syear && $smonth && !$sday) {
	$sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
} elseif ($syear && $smonth && $sday) {
	$sql .= " AND datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
} else {
	dol_print_error('', 'Year not defined');
}

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print "<!-- title of cash fence -->\n";
	print '<center>';
	print '<h2>';
	if ($object->status != $object::STATUS_DRAFT) {
		print $langs->trans("CashControl")." ".$object->id;
	} else {
		print $langs->trans("CashControl")." - ".$langs->trans("Draft");
	}
	print "</h2>";
	print $mysoc->name;
	print '<br>'.$langs->trans("DateCreationShort").": ".dol_print_date($object->date_creation, 'dayhour');
	$userauthor = $object->fk_user_valid;
	if (empty($userauthor)) {
		$userauthor = $object->fk_user_creat;
	}

	$uservalid = new User($db);
	if ($userauthor > 0) {
		$uservalid->fetch($userauthor);
		print '<br>'.$langs->trans("Author").': '.$uservalid->getFullName($langs);
	}
	print '<br>'.$langs->trans("Period").': '.$object->year_close.($object->month_close ? '-'.$object->month_close : '').($object->day_close ? '-'.$object->day_close : '');
	print '</center>';

	$invoicetmp = new Facture($db);

	print "<div style='text-align: right'><h2>";
	print $langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").' : <div class="inline-block amount width100">'.price($object->opening).'</div>';
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
	$cash = $bank = $cheque = $other = 0;

	$totalqty = 0;
	$totalvat = 0;
	$totalvatperrate = array();
	$totallocaltax1 = 0;
	$totallocaltax2 = 0;
	$cachebankaccount = array();
	$cacheinvoiceid = array();
	$transactionspertype = array();
	$amountpertype = array();

	$totalarray = array();
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		// Load bankaccount
		if (empty($cachebankaccount[$objp->bankid])) {
			$bankaccounttmp = new Account($db);
			$bankaccounttmp->fetch($objp->bankid);
			$cachebankaccount[$objp->bankid] = $bankaccounttmp;
			$bankaccount = $bankaccounttmp;
		} else {
			$bankaccount = $cachebankaccount[$objp->bankid];
		}

		$invoicetmp->fetch($objp->facid);

		if (empty($cacheinvoiceid[$objp->facid])) {
			$cacheinvoiceid[$objp->facid] = $objp->facid; // First time this invoice is found into list of invoice x payments
			foreach ($invoicetmp->lines as $line) {
				$totalqty += $line->qty;
				$totalvat += $line->total_tva;
				if ($line->tva_tx) {
					if (empty($totalvatperrate[$line->tva_tx])) {
						$totalvatperrate[$line->tva_tx] = 0;
					}
					$totalvatperrate[$line->tva_tx] += $line->total_tva;
				}
				$totallocaltax1 += $line->total_localtax1;
				$totallocaltax2 += $line->total_localtax2;
			}
		}

		print '<tr class="oddeven">';

		// Ref
		print '<td class="nowrap left">';
		print $invoicetmp->getNomUrl(1);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}

		// Date ope
		print '<td class="nowrap left">';
		print '<span id="dateoperation_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->do), "day")."</span>";
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}

		if ($object->posmodule == "takepos") {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH'.$object->posnumber;
		} else {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH';
		}

		// Bank account
		print '<td class="nowrap right">';
		print $bankaccount->getNomUrl(1);
		if ($objp->code == 'CHQ') {
			$cheque += $objp->amount;
			if (empty($transactionspertype[$objp->code])) {
				$transactionspertype[$objp->code] = 0;
			}
			$transactionspertype[$objp->code] += 1;
		} elseif ($objp->code == 'CB') {
			$bank += $objp->amount;
			if (empty($transactionspertype[$objp->code])) {
				$transactionspertype[$objp->code] = 0;
			}
			$transactionspertype[$objp->code] += 1;
		} else {
			if ($conf->global->$var1 == $bankaccount->id) {
				$cash += $objp->amount;
				// } elseif ($conf->global->$var2 == $bankaccount->id) $bank+=$objp->amount;
				//elseif ($conf->global->$var3 == $bankaccount->id) $cheque+=$objp->amount;
				if (empty($transactionspertype['CASH'])) {
					$transactionspertype['CASH'] = 0;
				}
				$transactionspertype['CASH'] += 1;
			} else {
				$other += $objp->amount;
				if (empty($transactionspertype['OTHER'])) {
					$transactionspertype['OTHER'] = 0;
				}
				$transactionspertype['OTHER'] += 1;
			}
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}

		// Type
		print '<td class="right">';
		print $objp->code;
		if (empty($amountpertype[$objp->code])) {
			$amountpertype[$objp->code] = 0;
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}

		// Debit
		print '<td class="right">';
		if ($objp->amount < 0) {
			print '<span class="amount">'.price($objp->amount * -1).'</span>';
			$totalarray['val']['totaldebfield'] += $objp->amount;
			$amountpertype[$objp->code] += $objp->amount;
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totaldebfield';
		}

		// Credit
		print '<td class="right">';
		if ($objp->amount > 0) {
			print '<span class="amount">'.price($objp->amount).'</span>';
			$totalarray['val']['totalcredfield'] += $objp->amount;
			$amountpertype[$objp->code] -= $objp->amount;
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totalcredfield';
		}

		print "</tr>";

		$i++;
	}

	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

	print "</table>";
	print "</div>";

	//$cash = $amountpertype['LIQ'] + $object->opening;
	$cash = price2num($cash + $object->opening, 'MT');

	print '<div style="text-align: right">';
	print '<h2>';

	print $langs->trans("Cash").($transactionspertype['CASH'] ? ' ('.$transactionspertype['CASH'].')' : '').' : <div class="inline-block amount width100">'.price($cash).'</div>';
	if ($object->status == $object::STATUS_VALIDATED && $cash != $object->cash) {
		print ' <> <div class="inline-block amountremaintopay fontsizeunset">'.$langs->trans("Declared").': '.price($object->cash).'</div>';
	}
	print "<br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCHQ").($transactionspertype['CHQ'] ? ' ('.$transactionspertype['CHQ'].')' : '').' : <div class="inline-block amount width100">'.price($cheque).'</div>';
	if ($object->status == $object::STATUS_VALIDATED && $cheque != $object->cheque) {
		print ' <> <div class="inline-block amountremaintopay fontsizeunset">'.$langs->trans("Declared").' : '.price($object->cheque).'</div>';
	}
	print "<br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCB").($transactionspertype['CB'] ? ' ('.$transactionspertype['CB'].')' : '').' : <div class="inline-block amount width100">'.price($bank).'</div>';
	if ($object->status == $object::STATUS_VALIDATED && $bank != $object->card) {
		print ' <> <div class="inline-block amountremaintopay fontsizeunset">'.$langs->trans("Declared").': '.price($object->card).'</div>';
	}
	print "<br>";

	// print '<br>';
	if ($other) {
		print ''.$langs->trans("Other").($transactionspertype['OTHER'] ? ' ('.$transactionspertype['OTHER'].')' : '').' : <div class="inline-block amount width100">'.price($other)."</div>";
		print '<br>';
	}

	print $langs->trans("Total").' ('.$totalqty.' '.$langs->trans("Articles").') : <div class="inline-block amount width100">'.price($cash + $cheque + $bank + $other).'</div>';

	print '<br>'.$langs->trans("TotalVAT").' : <div class="inline-block amount width100">'.price($totalvat).'</div>';

	if ($mysoc->useLocalTax(1)) {
		print '<br>'.$langs->trans("TotalLT1").' : <div class="inline-block amount width100">'.price($totallocaltax1).'</div>';
	}
	if ($mysoc->useLocalTax(1)) {
		print '<br>'.$langs->trans("TotalLT2").' : <div class="inline-block amount width100">'.price($totallocaltax2).'</div>';
	}

	if (!empty($totalvatperrate) && is_array($totalvatperrate)) {
		print '<br><br><div class="small inline-block">'.$langs->trans("VATRate").'</div>';
		foreach ($totalvatperrate as $keyrate => $valuerate) {
			print '<br><div class="small">'.$langs->trans("VATRate").' '.vatrate($keyrate, 1).' : <div class="inline-block amount width100">'.price($valuerate).'</div></div>';
		}
	}

	print '</h2>';
	print '</div>';

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();

$db->close();
