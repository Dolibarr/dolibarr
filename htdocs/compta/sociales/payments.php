<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2022  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
if (isModEnabled('accounting')) {
	include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('specialexpensesindex'));

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'hrm'));

$year = GETPOST("year", 'int');
$search_sc_type = GETPOST('search_sc_type', 'int');
$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0) {
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

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'chargesociales', 'charges');


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_sc_type = '';
	//$toselect = array();
	//$search_array_options = array();
}


/*
 * View
 */

$tva_static = new Tva($db);
$socialcontrib = new ChargeSociales($db);
$payment_sc_static = new PaymentSocialContribution($db);
$userstatic = new User($db);
$sal_static = new Salary($db);
$accountstatic = new Account($db);
$accountlinestatic = new AccountLine($db);
$formsocialcontrib = new FormSocialContrib($db);

$title = $langs->trans("SocialContributionsPayments");
$help_url = '';

llxHeader('', $title, $help_url);


$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($sortfield) {
	$param .= '&sortfield='.urlencode($sortfield);
}
if ($sortorder) {
	$param .= '&sortorder='.urlencode($sortorder);
}
if ($year) {
	$param .= '&year='.urlencode($year);
}
if ($search_sc_type) {
	$param .= '&search_sc_type='.urlencode($search_sc_type);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
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

$sql = "SELECT c.id, c.libelle as type_label,";
$sql .= " cs.rowid, cs.libelle as label_sc, cs.fk_type as type, cs.periode, cs.date_ech, cs.amount as total, cs.paye,";
$sql .= " pc.rowid as pid, pc.datep, pc.amount as totalpaid, pc.num_paiement as num_payment, pc.fk_bank,";
$sql .= " pct.code as payment_code,";
$sql .= " u.rowid as uid, u.lastname, u.firstname, u.email, u.login, u.admin, u.statut,";
$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel, ba.iban_prefix as iban, ba.bic, ba.currency_code, ba.clos,";
$sql .= " aj.label as account_journal";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
$sql .= " ".MAIN_DB_PREFIX."chargesociales as cs";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pct ON pc.fk_typepaiement = pct.id";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON pc.fk_bank = b.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_journal as aj ON ba.fk_accountancy_journal = aj.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = cs.fk_user";
$sql .= " WHERE cs.fk_type = c.id";
$sql .= " AND cs.entity IN (".getEntity("tax").")";
if ($search_sc_type > 0) {
	$sql .= " AND cs.fk_type = ".((int) $search_sc_type);
}
if ($year > 0) {
	$sql .= " AND (";
	// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
	// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
	$sql .= "   (cs.periode IS NOT NULL AND cs.periode between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
	$sql .= " OR (cs.periode IS NULL AND cs.date_ech between '".$db->idate(dol_get_first_day($year))."' AND '".$db->idate(dol_get_last_day($year))."')";
	$sql .= ")";
}
if (preg_match('/^cs\./', $sortfield)
	|| preg_match('/^c\./', $sortfield)
	|| preg_match('/^pc\./', $sortfield)
	|| preg_match('/^pct\./', $sortfield)
	|| preg_match('/^u\./', $sortfield)
	|| preg_match('/^ba\./', $sortfield)) {
		$sql .= $db->order($sortfield, $sortorder);
}

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
	$num = $nbtotalofrecords;
} else {
	if ($limit) {
		$sql .= $db->plimit($limit + 1, $offset);
	}

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}
//$sql.= $db->plimit($limit+1,$offset);
//print $sql;

$nav = '';
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'object_payment', 0, $nav, '', $limit, 0);

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre">';
$formsocialcontrib->select_type_socialcontrib(GETPOSTISSET("search_sc_type") ? $search_sc_type : '', 'search_sc_type', 1, 0, 0, 'minwidth200 maxwidth300');
print '</td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
if (isModEnabled('banque')) {
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
}
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pc.rowid", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("SocialContribution", $_SERVER["PHP_SELF"], "c.libelle", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("TypeContrib", $_SERVER["PHP_SELF"], "cs.fk_type", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "cs.periode", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pc.datep", "", $param, '', $sortfield, $sortorder, 'center ');
print_liste_field_titre("Employee", $_SERVER["PHP_SELF"], "u.rowid", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("PaymentMode", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Numero", $_SERVER["PHP_SELF"], "pc.num_paiement", "", $param, '', $sortfield, $sortorder, '', 'ChequeOrTransferNumber');
if (isModEnabled('banque')) {
	print_liste_field_titre("BankTransactionLine", $_SERVER["PHP_SELF"], "pc.fk_bank", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
}
print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "cs.amount", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('');
print "</tr>\n";

if (!$resql) {
	dol_print_error($db);
	exit;
}

$i = 0;
$total = 0;
$totalnb = 0;
$totalpaid = 0;

while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$payment_sc_static->id = $obj->pid;
	$payment_sc_static->ref = $obj->pid;
	$payment_sc_static->date = $db->jdate($obj->datep);

	$socialcontrib->id = $obj->rowid;
	$socialcontrib->ref = empty($obj->label_sc) ? $obj->type_label : $obj->label_sc;
	$socialcontrib->paye = $obj->paye;
	// $obj->label_sc is label of social contribution (may be empty)
	// $obj->type_label is label of type of social contribution
	$socialcontrib->label = empty($obj->label_sc) ? $obj->type_label : $obj->label_sc;
	$socialcontrib->type_label = $obj->type_label;

	print '<tr class="oddeven">';
	// Ref payment
	print '<td class="nowraponall">'.$payment_sc_static->getNomUrl(1)."</td>\n";
	// Label
	print '<td class="tdoverflowmax250">';
	print $socialcontrib->getNomUrl(1, '');
	print '</td>';
	// Type
	print '<td title="'.dol_escape_htmltag($obj->label_sc).'" class="tdoverflowmax300">'.$obj->label_sc.'</td>';
	// Date
	$date = $obj->periode;
	if (empty($date)) {
		$date = $obj->date_ech;
	}
	print '<td class="center">'.dol_print_date($date, 'day').'</td>';
	// Date payment
	print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';

	// Employee
	print "<td>";
	if (!empty($obj->uid)) {
		$userstatic->id = $obj->uid;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->admin = $obj->admin;
		$userstatic->login = $obj->login;
		$userstatic->email = $obj->email;
		$userstatic->statut = $obj->statut;
		print $userstatic->getNomUrl(1);
		print "</td>\n";
	}

	// Type payment
	$labelpayment = '';
	if ($obj->payment_code) {
		$labelpayment = $langs->trans("PaymentTypeShort".$obj->payment_code);
	}
	print '<td class="tdoverflowmax150" title="'.$labelpayment.'">';
	print $labelpayment;
	print '</td>';

	print '<td>'.$obj->num_payment.'</td>';

	// Account
	if (isModEnabled('banque')) {
		// Bank transaction
		print '<td class="nowraponall">';
		$accountlinestatic->id = $obj->fk_bank;
		print $accountlinestatic->getNomUrl(1);
		print '</td>';

		print '<td class="nowraponall">';
		if ($obj->bid > 0) {
			$accountstatic->id = $obj->bid;
			$accountstatic->ref = $obj->bref;
			$accountstatic->number = $obj->bnumber;
			$accountstatic->label = $obj->blabel;
			$accountstatic->iban = $obj->iban;
			$accountstatic->bic = $obj->bic;
			$accountstatic->currency_code = $langs->trans("Currency".$obj->currency_code);
			$accountstatic->clos = $obj->clos;

			if (isModEnabled('accounting')) {
				$accountstatic->account_number = $obj->account_number;
				$accountstatic->accountancy_journal = $obj->account_journal;
			}
			print $accountstatic->getNomUrl(1);
		} else {
			print '&nbsp;';
		}
		print '</td>';
	}

	// Expected to pay
	print '<td class="right"><span class="amount">'.price($obj->total).'</span></td>';

	// Paid
	print '<td class="right">';
	if ($obj->totalpaid) {
		print '<span class="amount">'.price($obj->totalpaid).'</span>';
	}
	print '</td>';

	print '<td></td>';

	print '</tr>';

	$total = $total + $obj->total;
	$totalpaid = $totalpaid + $obj->totalpaid;
	$i++;
}

// Total
print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
print '<td class="liste_total right"></td>'; // A total here has no sense
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
if (isModEnabled('banque')) {
	print '<td></td>';
	print '<td></td>';
}
print '<td class="liste_total right">'.price($totalpaid)."</td>";
print '<td></td>';
print "</tr>";

print '</table>';


print '</form>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardSpecialBills', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
