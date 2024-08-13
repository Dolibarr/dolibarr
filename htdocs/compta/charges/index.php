<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2022  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/compta/charges/index.php
 *      \ingroup    compta
 *		\brief      Page to list payments of special expenses
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('specialexpensesindex'));

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax|salaries', '', '', 'charges|');

$mode = GETPOST("mode", 'alpha');
$year = GETPOSTINT("year");
$filtre = GETPOST("filtre", 'alpha');
if (!$year) {
	$year = date("Y", time());
}
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "cs.date_ech";
}
if (!$sortorder) {
	$sortorder = "DESC";
}


/*
 * View
 */

$tva_static = new Tva($db);
$ptva_static = new PaymentVAT($db);
$socialcontrib = new ChargeSociales($db);
$payment_sc_static = new PaymentSocialContribution($db);
$sal_static = new Salary($db);
$accountstatic = new Account($db);

llxHeader('', $langs->trans("SpecialExpensesArea"));

$title = $langs->trans("SpecialExpensesArea");

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.$contextpage;
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.$limit;
}
if ($sortfield) {
	$param .= '&sortfield='.$sortfield;
}
if ($sortorder) {
	$param .= '&sortorder='.$sortorder;
}

$totalnboflines = '';
$num = 0;

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$nav = ($year ? '<a href="index.php?year='.($year - 1).$param.'">'.img_previous($langs->trans("Previous"), 'class="valignbottom"')."</a> ".$langs->trans("Year").' '.$year.' <a href="index.php?year='.($year + 1).$param.'">'.img_next($langs->trans("Next"), 'class="valignbottom"')."</a>" : "");

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'object_payment', 0, $nav, '', $limit, 1);

if ($year) {
	$param .= '&year='.$year;
}

print '<span class="opacitymedium">'.$langs->trans("DescTaxAndDividendsArea").'</span><br>';
print "<br>";

if (isModEnabled('tax') && $user->hasRight('tax', 'charges', 'lire')) {
	$sql = "SELECT c.id, c.libelle as label,";
	$sql .= " cs.rowid, cs.libelle, cs.fk_type as type, cs.periode as period, cs.date_ech, cs.amount as total,";
	$sql .= " pc.rowid as pid, pc.datep, pc.amount as totalpaid, pc.num_paiement as num_payment, pc.fk_bank,";
	$sql .= " pct.code as payment_code,";
	$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
	$sql .= " ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON pc.fk_typepaiement = pct.id";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON pc.fk_bank = b.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " WHERE cs.fk_type = c.id";
	$sql .= " AND cs.entity IN (".getEntity("tax").")";
	if ($year > 0) {
		$sql .= " AND (";
		// If period defined, we use it as dat criteria, if not  we use date echeance,
		// so we are compatible when period is not mandatory
		$sql .= "   (cs.periode IS NOT NULL AND cs.periode between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= " OR (cs.periode IS NULL AND cs.date_ech between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
		$sql .= ")";
	}
	if (preg_match('/^cs\./', $sortfield) || preg_match('/^c\./', $sortfield) || preg_match('/^pc\./', $sortfield) || preg_match('/^pct\./', $sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}
	//$sql.= $db->plimit($limit+1,$offset);
	//print $sql;

	dol_syslog("compta/charges/index.php: select payment", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		// Social contributions only
		//print_barre_liste($langs->trans("SocialContributions").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $num, '', 0, $nav, '', $limit, 1);
		print load_fiche_titre($langs->trans("SocialContributions").($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "cs.date_ech", "", $param, 'width="120"', $sortfield, $sortorder, 'nowraponall ');
		print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "c.libelle", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "cs.fk_type", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "cs.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pc.rowid", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pc.datep", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("PaymentMode", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
		if (isModEnabled("bank")) {
			print_liste_field_titre("BankAccount", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
		}
		print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print "</tr>\n";


		$total = 0;
		$totalpaid = 0;

		$i = 0;
		//$imaxinloop = ($limit ? min($num, $limit) : $num);
		$imaxinloop = $num;		// We want to show all (we can't use navigation when there is 2 tables shown)
		while ($i < $imaxinloop) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			// Date
			$date = $obj->period;
			if (empty($date)) {
				$date = $obj->date_ech;
			}
			print '<td>'.dol_print_date($date, 'day').'</td>';
			// Label
			print '<td>';
			$socialcontrib->id = $obj->rowid;
			$socialcontrib->ref = $obj->label;
			$socialcontrib->label = $obj->label;
			print $socialcontrib->getNomUrl(1, '20');
			print '</td>';
			// Type
			print '<td class="tdoverflowmax200"><a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?filtre=cs.fk_type:'.$obj->type.'">'.$obj->label.'</a></td>';
			// Expected to pay
			print '<td class="right"><span class="amount">'.price($obj->total).'</span></td>';
			// Ref payment
			$payment_sc_static->id = $obj->pid;
			$payment_sc_static->ref = $obj->pid;
			print '<td>'.$payment_sc_static->getNomUrl(1)."</td>\n";
			// Date payment
			print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';

			// Payment mode
			$s = '';
			if ($obj->payment_code) {
				$s .= $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
			}
			$s .= $obj->num_payment;
			print '<td class="tdoverflowmax125" title="'.dolPrintHTMLForAttribute($s).'">';
			print $s;
			print '</td>';

			// Account
			if (isModEnabled("bank")) {
				print '<td>';
				if ($obj->fk_bank > 0) {
					//$accountstatic->fetch($obj->fk_bank);
					$accountstatic->id = $obj->bid;
					$accountstatic->ref = $obj->bref;
					$accountstatic->number = $obj->bnumber;
					$accountstatic->account_number = $obj->account_number;
					$accountstatic->fk_accountancy_journal = $obj->fk_accountancy_journal;
					$accountstatic->label = $obj->blabel;

					print $accountstatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			// Paid
			print '<td class="right">';
			if ($obj->totalpaid) {
				print price($obj->totalpaid);
			}
			print '</td>';
			print '</tr>';

			$total += $obj->total;
			$totalpaid += $obj->totalpaid;
			$i++;
		}
		print '<tr class="liste_total">';

		print '<td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';

		// Total here has no sens because we can have several time the same line
		//print '<td class="liste_total right">'.price($total).'</td>';
		print '<td class="liste_total right"></td>';

		print '<td class="liste_total center">&nbsp;</td>';
		print '<td class="liste_total center">&nbsp;</td>';
		print '<td class="liste_total center">&nbsp;</td>';
		if (isModEnabled("bank")) {
			print '<td class="liste_total center"></td>';
		}
		print '<td class="liste_total right">'.price($totalpaid)."</td>";

		print "</tr>";
	} else {
		dol_print_error($db);
	}
	print '</table>';
}

// VAT
if (isModEnabled('tax') && $user->hasRight('tax', 'charges', 'lire')) {
	$sql = "SELECT ptva.rowid, pv.rowid as id_tva, pv.amount as amount_tva, ptva.amount, pv.label, pv.datev as dm, ptva.datep as date_payment, ptva.fk_bank, ptva.num_paiement as num_payment,";
	$sql .= " pct.code as payment_code,";
	$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
	$sql .= " FROM ".MAIN_DB_PREFIX."tva as pv";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_vat as ptva ON ptva.fk_tva = pv.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON (ptva.fk_bank = b.rowid)";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON ptva.fk_typepaiement = pct.id";
	$sql .= " WHERE pv.entity IN (".getEntity("tax").")";
	if ($year > 0) {
		// If period defined, we use it as dat criteria, if not  we use date echeance,
		// so we are compatible when period is not mandatory
		$sql .= " AND pv.datev between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
	}
	if (preg_match('/^pv\./', $sortfield) || preg_match('/^ptva\./', $sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		$i = 0;
		$total = 0;
		$totaltopay = 0;

		print "<br>";

		$labeltax = $langs->transcountry("VAT", $mysoc->country_code);

		print load_fiche_titre($labeltax.($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "pv.datev", "", $param, 'width="120"', $sortfield, $sortorder, 'nowraponall ');
		print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "pv.label", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "pv.amount", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "ptva.rowid", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "ptva.datep", "", $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("PaymentMode", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
		if (isModEnabled("bank")) {
			print_liste_field_titre("BankAccount", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
		}
		print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "ptva.amount", "", $param, '', $sortfield, $sortorder, 'right ');
		print "</tr>\n";

		//$imaxinloop = ($limit ? min($num, $limit) : $num);
		$imaxinloop = $num;		// We want to show all (we can't use navigation when there is 2 tables shown)

		while ($i < $imaxinloop) {
			$obj = $db->fetch_object($result);

			$totaltopay += $obj->amount_tva;
			$total += $obj->amount;

			print '<tr class="oddeven">';

			print '<td class="left">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>'."\n";

			$tva_static->id = $obj->id_tva;
			$tva_static->ref = $obj->label;
			print "<td>".$tva_static->getNomUrl(1)."</td>\n";

			print '<td class="right"><span class="amount">'.price($obj->amount_tva)."</span></td>";

			// Ref payment
			$ptva_static->id = $obj->rowid;
			$ptva_static->ref = $obj->rowid;
			print '<td>'.$ptva_static->getNomUrl(1)."</td>\n";

			// Date
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_payment), 'day')."</td>\n";

			// Payment mode
			$s = '';
			if ($obj->payment_code) {
				$s .= $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
			}
			$s .= $obj->num_payment;
			print '<td class="tdoverflowmax125" title="'.dolPrintHTMLForAttribute($s).'">';
			print $s;
			print '</td>';

			// Account
			if (isModEnabled("bank")) {
				print '<td>';
				if ($obj->fk_bank > 0) {
					//$accountstatic->fetch($obj->fk_bank);
					$accountstatic->id = $obj->bid;
					$accountstatic->ref = $obj->bref;
					$accountstatic->number = $obj->bnumber;
					$accountstatic->account_number = $obj->account_number;
					$accountstatic->fk_accountancy_journal = $obj->fk_accountancy_journal;
					$accountstatic->label = $obj->blabel;

					print $accountstatic->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}

			// Paid
			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>";
			print "</tr>\n";

			$i++;
		}


		print '<tr class="liste_total">';

		print '<td class="liste_total" colspan="2">'.$langs->trans("Total").'</td>';

		// Total here has no sens because we can have several time the same line
		//print '<td class="right">'.price($totaltopay).'</td>';
		print '<td class="liste_total">&nbsp;</td>';

		print '<td class="liste_total"></td>';
		print '<td class="liste_total"></td>';
		print '<td class="liste_total"></td>';

		if (isModEnabled("bank")) {
			print '<td class="liste_total"></td>';
		}

		print '<td class="liste_total right">'.price($total)."</td>";

		print "</tr>";

		print "</table>";

		$db->free($result);
	} else {
		dol_print_error($db);
	}
}

// Localtax
if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
	$j = 1;
	$numlt = 3;
} elseif ($mysoc->localtax1_assuj == "1") {
	$j = 1;
	$numlt = 2;
} elseif ($mysoc->localtax2_assuj == "1") {
	$j = 2;
	$numlt = 3;
} else {
	$j = 0;
	$numlt = 0;
}

while ($j < $numlt) {
	print "<br>";

	$labeltax = $langs->transcountry(($j == 1 ? "LT1" : "LT2"), $mysoc->country_code);

	print load_fiche_titre($labeltax.($year ? ' ('.$langs->trans("Year").' '.$year.')' : ''), '', '');


	$sql = "SELECT pv.rowid, pv.amount, pv.label, pv.datev as dm, pv.datep as dp";
	$sql .= " FROM ".MAIN_DB_PREFIX."localtax as pv";
	$sql .= " WHERE pv.entity = ".$conf->entity." AND localtaxtype = ".((int) $j);
	if ($year > 0) {
		// If period defined, we use it as dat criteria, if not  we use date echeance,
		// so we are compatible when period is not mandatory
		$sql .= " AND pv.datev between '".$db->idate(dol_get_first_day($year, 1, false))."' AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
	}
	if (preg_match('/^pv/', $sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
	}

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;
		$total = 0;

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "pv.datev", "", $param, 'width="120"', $sortfield, $sortorder);
		print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "pv.label", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pv.rowid", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pv.datep", "", $param, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pv.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		print "</tr>\n";

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			$total += $obj->amount;

			print '<tr class="oddeven">';
			print '<td class="left">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>'."\n";

			print "<td>".$obj->label."</td>\n";

			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>";

			// Ref payment
			$ptva_static->id = $obj->rowid;
			$ptva_static->ref = $obj->rowid;
			print '<td class="left">'.$ptva_static->getNomUrl(1)."</td>\n";

			print '<td class="center">'.dol_print_date($db->jdate($obj->dp), 'day')."</td>\n";
			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>";
			print "</tr>\n";

			$i++;
		}
		print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").'</td>';
		print '<td class="right">'.price($total)."</td>";
		print '<td align="center">&nbsp;</td>';
		print '<td align="center">&nbsp;</td>';
		print '<td class="right">'.price($total)."</td>";
		print "</tr>";

		print "</table>";
		print '</div>';

		$db->free($result);
	} else {
		dol_print_error($db);
	}

	$j++;
}

print '</form>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardSpecialBills', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
