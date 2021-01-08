<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2016 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2011-2014 Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
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
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';


$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('specialexpensesindex'));

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax|salaries', '', '', 'charges|');

$year = GETPOST("year", 'int');
$search_sc_type = GETPOST('search_sc_type', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "cs.date_ech";
if (!$sortorder) $sortorder = "DESC";

if (empty($conf->tax->enabled) || empty($user->rights->tax->charges->lire))
{
	accessforbidden();
}


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_sc_type = '';
	//$toselect = '';
	//$search_array_options = array();
}


/*
 * View
 */

$tva_static = new Tva($db);
$socialcontrib = new ChargeSociales($db);
$payment_sc_static = new PaymentSocialContribution($db);
$sal_static = new PaymentSalary($db);
$accountstatic = new Account($db);
$formsocialcontrib = new FormSocialContrib($db);

$title = $langs->trans("SocialContributionsPayments");

llxHeader('', $title);


$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($sortfield) $param .= '&sortfield='.urlencode($sortfield);
if ($sortorder) $param .= '&sortorder='.urlencode($sortorder);
if ($year) $param .= '&year='.urlencode($year);
if ($search_sc_type) $param .= '&search_sc_type='.urlencode($search_sc_type);
$num = 0;

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

$sql = "SELECT c.id, c.libelle as label,";
$sql .= " cs.rowid, cs.libelle, cs.fk_type as type, cs.periode, cs.date_ech, cs.amount as total,";
$sql .= " pc.rowid as pid, pc.datep, pc.amount as totalpaye, pc.num_paiement as num_payment, pc.fk_bank,";
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
if (preg_match('/^cs\./', $sortfield) || preg_match('/^c\./', $sortfield) || preg_match('/^pc\./', $sortfield) || preg_match('/^pct\./', $sortfield)) {
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
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

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
if (!empty($conf->banque->enabled)) print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "cs.date_ech", "", $param, 'width="140px"', $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "c.libelle", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "cs.fk_type", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("ExpectedToPay", $_SERVER["PHP_SELF"], "cs.amount", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "pc.rowid", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "pc.datep", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "pct.code", "", $param, '', $sortfield, $sortorder);
if (!empty($conf->banque->enabled)) print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "pc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('');
print "</tr>\n";

if (!$resql)
{
	dol_print_error($db);
	exit;
}

$i = 0;
$total = 0;
$totalnb = 0;
$totalpaye = 0;

while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);
	print '<tr class="oddeven">';
	// Date
	$date = $obj->periode;
	if (empty($date)) $date = $obj->date_ech;
	print '<td>'.dol_print_date($date, 'day').'</td>';
	// Label
	print '<td>';
	$socialcontrib->id = $obj->rowid;
	$socialcontrib->ref = $obj->label;
	$socialcontrib->label = $obj->label;
	print $socialcontrib->getNomUrl(1, '20');
	print '</td>';
	// Type
	print '<td title="'.dol_escape_htmltag($obj->label).'" class="tdmaxoverflow300">'.$obj->label.'</td>';
	// Expected to pay
	print '<td class="right">'.price($obj->total).'</td>';
	// Ref payment
	$payment_sc_static->id = $obj->pid;
	$payment_sc_static->ref = $obj->pid;
	print '<td>'.$payment_sc_static->getNomUrl(1)."</td>\n";
	// Date payment
	print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day').'</td>';
	// Type payment
	print '<td>';
	if ($obj->payment_code) print $langs->trans("PaymentTypeShort".$obj->payment_code).' ';
	print $obj->num_payment.'</td>';
	// Account
	if (!empty($conf->banque->enabled))
	{
		print '<td>';
		if ($obj->fk_bank > 0)
		{
			//$accountstatic->fetch($obj->fk_bank);
			$accountstatic->id = $obj->bid;
			$accountstatic->ref = $obj->bref;
			$accountstatic->number = $obj->bnumber;
			$accountstatic->accountancy_number = $obj->account_number;
			$accountstatic->accountancy_journal = $obj->accountancy_journal;
			$accountstatic->label = $obj->blabel;
			print $accountstatic->getNomUrl(1);
		} else print '&nbsp;';
		print '</td>';
	}
	// Paid
	print '<td class="right">';
	if ($obj->totalpaye) print price($obj->totalpaye);
	print '</td>';

	print '<td></td>';

	print '</tr>';

	$total = $total + $obj->total;
	$totalnb = $totalnb + $obj->nb;
	$totalpaye = $totalpaye + $obj->totalpaye;
	$i++;
}

// Total
print '<tr class="liste_total"><td colspan="3" class="liste_total">'.$langs->trans("Total").'</td>';
print '<td class="liste_total right"></td>'; // A total here has no sense
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
print '<td align="center" class="liste_total">&nbsp;</td>';
if (!empty($conf->banque->enabled)) print '<td></td>';
print '<td class="liste_total right">'.price($totalpaye)."</td>";
print '<td></td>';
print "</tr>";

print '</table>';


print '</form>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardSpecialBills', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
