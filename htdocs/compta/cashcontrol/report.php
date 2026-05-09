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
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';

$langs->loadLangs(array("bills", "banks", "cashdesk", "blockedlog"));

$id = GETPOSTINT('id');
$summaryonly = GETPOSTINT('summaryonly');		// May be used for ticket Z

$object = new CashControl($db);
$object->fetch($id);

//$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortorder = 'ASC';
$sortfield = 'b.datev,b.dateo,b.rowid';

$arrayfields = array(
	'b.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1),
	'b.dateo' => array('label' => $langs->trans("DateOperationShort"), 'checked' => 1),
	'b.num_chq' => array('label' => $langs->trans("Number"), 'checked' => 1),
	'ba.ref' => array('label' => $langs->trans("BankAccount"), 'checked' => 1),
	'cp.code' => array('label' => $langs->trans("PaymentMode"), 'checked' => 1),
	'b.debit' => array('label' => $langs->trans("Debit"), 'checked' => 1, 'position' => 600),
	'b.credit' => array('label' => $langs->trans("Credit"), 'checked' => 1, 'position' => 605),
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
if (!$user->hasRight('cashdesk', 'run') && !$user->hasRight('takepos', 'run')) {
	accessforbidden();
}


/*
 * View
 */

$title = $langs->trans("CashControl");
$param = '';

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

llxHeader('', $title, '', '', 0, 0, array(), array(), $param);

print '<!-- Begin div id-container --><div id="id-container" class="id-container centpercent">';

$dates = $datee = 0;

if ($syear && !$smonth) {
	$dates = dol_get_first_day($syear, 1); $datee = dol_get_last_day($syear, 12);
} elseif ($syear && $smonth && !$sday) {
	$dates = dol_get_first_day($syear, $smonth); $datee = dol_get_last_day($syear, $smonth);
} elseif ($syear && $smonth && $sday) {
	$dates = dol_mktime(0, 0, 0, $smonth, $sday, $syear); $datee = dol_mktime(23, 59, 59, $smonth, $sday, $syear);
} else {
	dol_print_error(null, 'Year not defined');
}
$datefilter = 'p.datep';
$modulesourcefilter = 'f.module_source';
$amountfield = 'pf.amount';
$joinleft = 'LEFT ';
if (isALNERunningVersion() && $mysoc->country_code == 'FR') {
	$datefilter = 'bl.date_creation';	// By using this as a filter, it is like the LEFT JOIN is an INNER JOIN
	$modulesourcefilter = 'bl.module_source';
	$amountfield = 'bl.amounts';
	$joinleft = '';
}

// NOTE: This request must use similar fields and filters to the one into cashcontrol_card to count and sum amount
$sql = "SELECT p.rowid, p.datep as datep, cp.code,";
$sql .= " f.rowid as facid, f.ref, f.datef as datef, ".$db->sanitize($amountfield)." as amount,";
$sql .= " b.fk_account as bankid,";
$sql .= " bl.signature";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f,";
$sql .= " ".MAIN_DB_PREFIX."paiement as p";
//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."blockedlog as bl ON bl.ref_object = p.ref AND bl.entity = ".((int) $conf->entity).",";
$sql .= " ".$db->sanitize($joinleft)." JOIN ".MAIN_DB_PREFIX."blockedlog as bl ON bl.action = 'PAYMENT_CUSTOMER_CREATE'";
$sql .= " AND bl.element = 'payment' AND bl.fk_object = p.rowid AND bl.entity = ".((int) $conf->entity);
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid,";
$sql .= " ".MAIN_DB_PREFIX."c_paiement as cp";
$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
$sql .= " AND ".$db->sanitize($modulesourcefilter)." = '".$db->escape($posmodule)."'";
$sql .= " AND f.pos_source = '".$db->escape($terminalid)."'";
$sql .= " AND p.entity = ".((int) $conf->entity); // Never share entities for features related to accountancy
$sql .= " AND ".$db->sanitize($datefilter)." BETWEEN '".$db->idate($dates)."' AND '".$db->idate($datee)."'";
$sql .= " ORDER BY ".$db->sanitize($datefilter)." ASC, rowid ASC"; // Required so later we can use the parameter $previoushash of checkSignature()

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print "<!-- title of cash control -->\n";
	print '<!-- We will use this request to find payments: '.dolPrintHTML($sql).' -->';
	print '<center>';
	print '<h2>';

	$nameterminal = getDolGlobalString("TAKEPOS_TERMINAL_NAME_".$object->posnumber);
	print $langs->trans("CashControl")." #".$object->id.(($nameterminal != "TAKEPOS_TERMINAL_NAME_".$object->posnumber) ? '<br>'.$nameterminal : '');
	if ($object->status == $object::STATUS_DRAFT) {
		print '<br><span class="opacitymedium small">('.$langs->trans("Draft")." - ".$langs->trans("TheoricalView").")</span>";
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
		print ' - '.$langs->trans("Author").': '.$uservalid->getFullName($langs);
	}
	print '<br>'.$langs->trans("Period").': '.$object->year_close.($object->month_close ? '-'.sprintf("%02d", $object->month_close) : '').($object->day_close ? '-'.sprintf("%02d", $object->day_close) : '');
	print '</center>';

	$invoicetmp = new Facture($db);

	if (!$summaryonly) {
		print "<div style='text-align: right'><h2>";
		print $langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").' : <div class="inline-block amount width100">'.price($object->opening).'</div>';
		print "</h2></div>";
	} else {
		print '<br>';
	}

	$param = '';

	if (!$summaryonly) {
		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste">'."\n";

		// Fields title
		print '<tr class="liste_titre">';
		print_liste_field_titre($arrayfields['b.rowid']['label'], $_SERVER['PHP_SELF'], 'b.rowid', '', $param, '', $sortfield, $sortorder);
		print_liste_field_titre($arrayfields['b.dateo']['label'], $_SERVER['PHP_SELF'], 'b.dateo', '', $param, '"', $sortfield, $sortorder, 'center ');
		print_liste_field_titre($arrayfields['ba.ref']['label'], $_SERVER['PHP_SELF'], 'ba.ref', '', $param, '', $sortfield, $sortorder, '');
		print_liste_field_titre($arrayfields['cp.code']['label'], $_SERVER['PHP_SELF'], 'cp.code', '', $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre($arrayfields['b.debit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre($arrayfields['b.credit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, 'right ');
		print "</tr>\n";
	}

	// Loop on each record
	$cash = $bank = $cheque = $other = 0;

	$totalqty = 0;
	$totalvat = 0;
	$totalvatperrate = array();
	$totalhtperrate = array();
	$totallocaltax1 = 0;
	$totallocaltax2 = 0;
	$cachebankaccount = array();
	$cacheinvoiceid = array();
	$transactionspertype = array();
	$amountpertype = array();

	$totalarray = array('nbfield' => 0, 'pos' => array(), 'val' => array('totaldebfield' => 0, 'totalcredfield' => 0));
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
						$totalhtperrate[$line->tva_tx] = 0;
					}
					$totalvatperrate[$line->tva_tx] += $line->total_tva;
					$totalhtperrate[$line->tva_tx] += $line->total_ht;
				}
				$totallocaltax1 += $line->total_localtax1;
				$totallocaltax2 += $line->total_localtax2;
			}
		}

		if ($object->posmodule == "takepos") {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH'.$object->posnumber;
		} else {
			$var1 = 'CASHDESK_ID_BANKACCOUNT_CASH';
		}

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
		} elseif ($objp->code == 'LIQ') {
			$cash += $objp->amount;
			// } elseif (getDolGlobalString($var2) == $bankaccount->id) $bank+=$objp->amount;
			//elseif (getDolGlobalString($var3) == $bankaccount->id) $cheque+=$objp->amount;
			if (empty($transactionspertype['CASH'])) {
				$transactionspertype['CASH'] = 0;
			}
			$transactionspertype['CASH'] += 1;
		} else {
			if (getDolGlobalString($var1) == $bankaccount->id) {
				$cash += $objp->amount;
				// } elseif (getDolGlobalString($var2) == $bankaccount->id) $bank+=$objp->amount;
				//elseif (getDolGlobalString($var3) == $bankaccount->id) $cheque+=$objp->amount;
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

		if (empty($amountpertype[$objp->code])) {
			$amountpertype[$objp->code] = 0;
		}

		if ($objp->amount < 0) {
			$amountpertype[$objp->code] += $objp->amount;
		}
		if ($objp->amount > 0) {
			$amountpertype[$objp->code] -= $objp->amount;
		}

		// List of all invoices
		if (!$summaryonly) {
			print '<tr class="oddeven">';

			// Ref
			print '<td class="nowrap left smallheight">';
			print $invoicetmp->getNomUrl(1);
			print '<br><span class="small opacitymedium" title="'.$langs->trans("FingerprintInBlockedLog").': '.$objp->signature.'">'.dol_trunc($objp->signature, 16).'</span>';
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}

			// Date ope
			print '<td class="nowrap center">';
			print '<span id="dateoperation_'.$objp->facid.'">'.dol_print_date($db->jdate($objp->datep), "day")."</span>";
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}

			// Bank account
			print '<td class="nowrap left">';
			print $bankaccount->getNomUrl(1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}

			// Type
			print '<td class="right">';
			print $objp->code;
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}

			// Debit
			print '<td class="right">';
			if ($objp->amount < 0) {
				print '<span class="amount">'.price($objp->amount * -1).'</span>';
				$totalarray['val']['totaldebfield'] += $objp->amount;
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
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'totalcredfield';
			}

			print "</tr>";
		}

		$i++;
	}

	if (!$summaryonly) {
		// Show total line
		$moreinfoontotal = ' ('.$num.' '.$langs->trans($num > 1 ? "Invoices" : "Invoice").')';		// Used in the .tpl
		include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

		print "</table>";
		print "</div>";
	}

	//$cash = $amountpertype['LIQ'] + $object->opening;
	$newcash = price2num($cash + (float) $object->opening, 'MT');

	print '<div style="text-align: right">';
	print '<h2>';

	print $langs->trans("Cash").(!empty($transactionspertype['CASH']) ? ' ('.$transactionspertype['CASH'].' '.$langs->trans("Payments").')' : '').' : ';
	if (!$summaryonly) {
		print '<div class="inline-block amount width100">'.($cash >= 0 ? '+' : '').price($cash).'</div>';
		print '<div class="inline-block amount width100">'.price($newcash).'</div>';
	} else {
		print '<div class="inline-block amount width100"></div>';
		print '<div class="inline-block amount width100">'.price($cash).'</div>';
	}
	if (!$summaryonly && $object->status == $object::STATUS_CLOSED && price2num($newcash) != price2num((float) $object->cash_declared)) {
		//$s = '<div class="inline-block amountremaintopay fontsizeunset small">';
		$s = $langs->trans("Declared").': '.price($object->cash_declared);
		print img_picto($s, 'warning');
	}
	print "<br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCHQ").(!empty($transactionspertype['CHQ']) ? ' ('.$transactionspertype['CHQ'].' '.$langs->trans("Payments").')' : '').' : ';
	print '<div class="inline-block amount width100"></div>';
	print '<div class="inline-block amount width100">'.price($cheque).'</div>';
	if (!$summaryonly && $object->status == $object::STATUS_CLOSED && price2num($cheque) != price2num((float) $object->cheque_declared)) {
		//print ' <div class="inline-block amountremaintopay fontsizeunset small"><> '.$langs->trans("Declared").' : '.price($object->cheque_declared).'</div>';
		$s = $langs->trans("Declared").': '.price($object->cheque_declared);
	}
	print "<br>";

	//print '<br>';
	print $langs->trans("PaymentTypeCB").(!empty($transactionspertype['CB']) ? ' ('.$transactionspertype['CB'].' '.$langs->trans("Payments").')' : '').' : ';
	print '<div class="inline-block amount width100"></div>';
	print '<div class="inline-block amount width100">'.price($bank).'</div>';
	if (!$summaryonly && $object->status == $object::STATUS_CLOSED && price2num($bank) != price2num((float) $object->card_declared)) {
		//print ' <div class="inline-block amountremaintopay fontsizeunset small"><> '.$langs->trans("Declared").': '.price($object->card_declared).'</div>';
		$s = $langs->trans("Declared").': '.price($object->card_declared);
	}
	print "<br>";

	// print '<br>';
	if ($other) {
		print ''.$langs->trans("Other").(!empty($transactionspertype['OTHER']) ? ' ('.$transactionspertype['OTHER'].' '.$langs->trans("Payments").')' : '').' : ';
		print '<div class="inline-block amount width100"></div>';
		print '<div class="inline-block amount width100">'.price($other)."</div>";
		print '<br>';
	}


	print "<br>";

	print $langs->trans("Total").' ('.$totalqty.' '.$langs->trans("Articles").') : <div class="inline-block amount width100"></div><div class="inline-block amount width100">'.price((float) $cash + (float) $cheque + (float) $bank + (float) $other).'</div>';

	print '<br>'.$langs->trans("TotalVAT").' : <div class="inline-block amount width100"></div><div class="inline-block amount width100">'.price($totalvat).'</div>';

	if ($mysoc->useLocalTax(1)) {
		print '<br>'.$langs->trans("TotalLT1").' : <div class="inline-block amount width100"></div><div class="inline-block amount width100">'.price($totallocaltax1).'</div>';
	}
	if ($mysoc->useLocalTax(1)) {
		print '<br>'.$langs->trans("TotalLT2").' : <div class="inline-block amount width100"></div><div class="inline-block amount width100">'.price($totallocaltax2).'</div>';
	}

	if (!empty($totalvatperrate) && is_array($totalvatperrate)) {
		print '<br><br><div class="small inline-block width100">'.$langs->trans("TotalHT").'</div><div class="small inline-block width100">'.$langs->trans("TotalVAT").'</div>';
		if (getDolGlobalInt('TAKEPOS_CASHCONTROL_REPORT_SHOW_TOTAL_INCLUDING_TAXES_COLUMN', 0) != 0) {
			print '<div class="small inline-block width100">'.$langs->trans("TotalTTC").'</div>';
		}
		foreach ($totalvatperrate as $keyrate => $valuerate) {
			print '<br><div class="small">'.$langs->trans("VATRate").' '.vatrate($keyrate, true).' : <div class="inline-block amount width100">'.price($totalhtperrate[$keyrate] ?? 0).'</div><div class="inline-block amount width100">'.price($valuerate).'</div>';
			if (getDolGlobalInt('TAKEPOS_CASHCONTROL_REPORT_SHOW_TOTAL_INCLUDING_TAXES_COLUMN', 0) != 0) {
				print '<div class="inline-block amount width100">'.price(($totalhtperrate[$keyrate] ?? 0) + $valuerate).'</div>';
			}
			print '</div>';
		}
	}

	print '</h2>';
	print '</div>';

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

print '</div>';

llxFooter();

$db->close();
