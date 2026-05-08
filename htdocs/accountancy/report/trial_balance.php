<?php
/* ============================================================
 * Trial Balance Report - US-GAAP-BASIC / Accrual Accounting
 * Place at: htdocs/accountancy/report/trial_balance.php
 * ============================================================ */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../../main.inc.php"))        { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php"))     { $res = @include "../../../main.inc.php"; }
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Security
if (!isModEnabled('accounting')) accessforbidden();
if (!$user->hasRight('accounting', 'comptarapport', 'lire')) accessforbidden();

$langs->loadLangs(array("accountancy", "compta", "admin"));
$form = new Form($db);

// ── Date filter defaults: current fiscal year ──────────────────────────────
$now        = dol_now();
$fiscalmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
$year_start  = (date('m') >= $fiscalmonth) ? date('Y') : date('Y') - 1;

$date_startmonth = GETPOSTINT('date_startmonth') ?: $fiscalmonth;
$date_startday   = GETPOSTINT('date_startday')   ?: 1;
$date_startyear  = GETPOSTINT('date_startyear')  ?: $year_start;
$date_endmonth   = GETPOSTINT('date_endmonth')   ?: date('m');
$date_endday     = GETPOSTINT('date_endday')     ?: date('d');
$date_endyear    = GETPOSTINT('date_endyear')    ?: date('Y');

$date_start = dol_mktime(0,  0,  0,  $date_startmonth, $date_startday, $date_startyear);
$date_end   = dol_mktime(23, 59, 59, $date_endmonth,   $date_endday,   $date_endyear);

// ── Query: accrual — join on account for pcg_type, filter by date ──────────
// For accrual accounting we use doc_date (the transaction/invoice date),
// NOT the payment date. llx_accounting_bookkeeping records entries when
// the invoice/expense is journalized, which is the accrual event.
$sql  = "SELECT";
$sql .= "  bk.numero_compte,";
$sql .= "  aa.label AS account_label,";
$sql .= "  aa.pcg_type,";
$sql .= "  SUM(bk.debit)  AS total_debit,";
$sql .= "  SUM(bk.credit) AS total_credit,";
$sql .= "  (SUM(bk.debit) - SUM(bk.credit)) AS balance";
$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping AS bk";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account AS aa";
$sql .= "   ON aa.account_number = bk.numero_compte";
$sql .= "   AND aa.entity = ".((int)$conf->entity);
$sql .= "   AND aa.active = 1";
$sql .= " WHERE bk.entity = ".((int)$conf->entity);
$sql .= " AND bk.doc_date >= '".$db->idate($date_start)."'";
$sql .= " AND bk.doc_date <= '".$db->idate($date_end)."'";
$sql .= " GROUP BY bk.numero_compte, aa.label, aa.pcg_type";
$sql .= " HAVING (SUM(bk.debit) <> 0 OR SUM(bk.credit) <> 0)";
$sql .= " ORDER BY bk.numero_compte ASC";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);

// ── pcg_type display order for US-GAAP-BASIC USING US-BASE TOO ────────────────────────
$type_order = array(
    'ASSETS'            => 'Assets',
    'LIABILITIES'       => 'Liabilities',
    'EQUITY'            => 'Equity',
    'CAPITAL'           => 'Capital / Clearing',
    'INCOME'            => 'Revenue',
    'COGS'              => 'Cost of Goods Sold',
    'EXPENSE'           => 'Operating Expenses',
    'XXXXXX'            => 'Sales Taxes Paid',
    'OTHER_REVENUE'     => 'Other Revenue',
    'OTHER_EXPENSES'    => 'Other Expenses',
);

// Bucket results by pcg_type
$rows_by_type = array();
$grand_debit = $grand_credit = 0.0;

while ($obj = $db->fetch_object($resql)) {
    $t = $obj->pcg_type ?: 'UNCATEGORIZED';
    $rows_by_type[$t][] = $obj;
    $grand_debit  += (float)$obj->total_debit;
    $grand_credit += (float)$obj->total_credit;
}

// ── HTML Output ────────────────────────────────────────────────────────────
llxHeader('', 'Trial Balance', '', '', 0, 0, '', '', '', 'mod-accountancy page-trialbalance');
print load_fiche_titre('Trial Balance', '', 'title_accountancy');

// Date filter form
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder" style="margin-bottom:1em">';
print '<tr><td class="fieldrequired">Period Start:</td><td>';
print $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 1);
print '</td><td class="fieldrequired">Period End:</td><td>';
print $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 1);
print '</td><td><input type="submit" class="button" value="Apply"></td></tr>';
print '</table>';
print '</form>';

// Report table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td style="width:120px"><b>Account #</b></td>';
print '<td><b>Account Name</b></td>';
print '<td><b>Type</b></td>';
print '<td class="right"><b>Debit</b></td>';
print '<td class="right"><b>Credit</b></td>';
print '<td class="right"><b>Net Balance</b></td>';
print '</tr>';

// Render in logical US order
$type_sequence = array_keys($type_order);
foreach ($type_sequence as $ptype) {
    if (empty($rows_by_type[$ptype])) continue;

    $section_label = $type_order[$ptype];
    $sec_debit = $sec_credit = 0.0;

    // Section header row
    print '<tr style="background:#e8f0fe">';
    print '<td colspan="3"><b>'.$section_label.'</b></td>';

    // Compute section totals first for the header
    foreach ($rows_by_type[$ptype] as $r) {
        $sec_debit  += (float)$r->total_debit;
        $sec_credit += (float)$r->total_credit;
    }
    print '<td class="right"><b>'.price($sec_debit).'</b></td>';
    print '<td class="right"><b>'.price($sec_credit).'</b></td>';
    print '<td class="right"><b>'.price($sec_debit - $sec_credit).'</b></td>';
    print '</tr>';

    // Detail rows
    foreach ($rows_by_type[$ptype] as $r) {
        $balance = (float)$r->total_debit - (float)$r->total_credit;
        $bal_style = $balance < 0 ? ' style="color:red"' : '';
        print '<tr class="oddeven">';
        print '<td>'.htmlentities($r->numero_compte).'</td>';
        print '<td>'.htmlentities($r->account_label ?: '(unmapped)').'</td>';
        print '<td><span style="font-size:0.85em;color:#666">'.htmlentities($r->pcg_type).'</span></td>';
        print '<td class="right">'.price($r->total_debit).'</td>';
        print '<td class="right">'.price($r->total_credit).'</td>';
        print '<td class="right"'.$bal_style.'>'.price($balance).'</td>';
        print '</tr>';
    }
}

// Grand total footer
print '<tr class="liste_total">';
print '<td colspan="3"><b>GRAND TOTAL</b></td>';
print '<td class="right"><b>'.price($grand_debit).'</b></td>';
print '<td class="right"><b>'.price($grand_credit).'</b></td>';
$grand_net = $grand_debit - $grand_credit;
$gstyle = $grand_net != 0 ? ' style="color:red;font-weight:bold"' : '';
print '<td class="right"'.$gstyle.'>'.price($grand_net).'</td>';
print '</tr>';
print '</table>';

// Balanced indicator
if (abs($grand_net) < 0.01) {
    print '<div class="ok" style="margin-top:1em;padding:0.5em">✔ Ledger is balanced (debits = credits)</div>';
} else {
    print '<div class="error" style="margin-top:1em;padding:0.5em">⚠ OUT OF BALANCE by '.price(abs($grand_net)).' — investigate journal entries</div>';
}

llxFooter();
$db->close();