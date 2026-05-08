<?php
$res = 0;
if (!$res && file_exists("../../main.inc.php"))    { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

if (!isModEnabled('accounting')) accessforbidden();
if (!$user->hasRight('accounting', 'comptarapport', 'lire')) accessforbidden();

$langs->loadLangs(array("accountancy", "compta"));
$form = new Form($db);

// ── Date default: as-of today ──────────────────────────────────────────────
// Balance Sheet is a point-in-time report — we use a single "as of" date.
// We sum ALL entries from the beginning of time up to that date.
$asofmonth = ((int) GETPOST('asofmonth', 'int')) ? ((int) GETPOST('asofmonth', 'int')) : (int)date('m');
$asofday   = ((int) GETPOST('asofday',   'int')) ? ((int) GETPOST('asofday',   'int')) : (int)date('d');
$asofyear  = ((int) GETPOST('asofyear',  'int')) ? ((int) GETPOST('asofyear',  'int')) : (int)date('Y');

$date_asof = dol_mktime(23, 59, 59, $asofmonth, $asofday, $asofyear);

// ── Query function: cumulative from beginning of records to $date_asof ─────
// Balance sheet accounts are PERMANENT (cumulative), unlike P&L accounts
// which reset each fiscal year. So we do NOT filter by a start date.
function getBSRows($db, $conf, $date_asof, $pcg_types) {
    $escaped = array();
    foreach ($pcg_types as $t) {
        $escaped[] = "'".$db->escape($t)."'";
    }
    $type_list = implode(',', $escaped);
    $sql  = "SELECT bk.numero_compte, aa.label AS account_label, aa.pcg_type,";
    $sql .= " SUM(bk.debit) AS total_debit, SUM(bk.credit) AS total_credit,";
    $sql .= " (SUM(bk.debit) - SUM(bk.credit)) AS net_balance";
    $sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping AS bk";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account AS aa";
    $sql .= "   ON aa.account_number = bk.numero_compte";
    $sql .= "   AND aa.entity = ".((int)$conf->entity);
    $sql .= "   AND aa.active = 1";
    $sql .= " WHERE bk.entity = ".((int)$conf->entity);
    $sql .= " AND bk.doc_date <= '".$db->idate($date_asof)."'";
    $sql .= " AND bk.numero_compte != '5820'";   // ← ADD THIS LINE
    $sql .= " AND aa.pcg_type IN (".$type_list.")";
    $sql .= " GROUP BY bk.numero_compte, aa.label, aa.pcg_type";
    $sql .= " HAVING (SUM(bk.debit) <> 0 OR SUM(bk.credit) <> 0)";
    $sql .= " ORDER BY bk.numero_compte ASC";
    $res = $db->query($sql);
    if (!$res) dol_print_error($db);
    return $res;
}

// ── Accumulate rows ────────────────────────────────────────────────────────
function accumRows($db, $resql, &$rows, $debit_normal = true) {
    $total = 0.0;
    while ($obj = $db->fetch_object($resql)) {
        $rows[] = $obj;
        // Debit-normal accounts (assets): balance = debit - credit
        // Credit-normal accounts (liabilities, equity): balance = credit - debit
        $total += $debit_normal
            ? ((float)$obj->total_debit  - (float)$obj->total_credit)
            : ((float)$obj->total_credit - (float)$obj->total_debit);
    }
    return $total;
}

// ── Run queries ────────────────────────────────────────────────────────────
// ASSETS:      debit-normal  (1xxx)
// LIABILITIES: credit-normal (2xxx)
// EQUITY:      credit-normal (3xxx)
// CAPITAL:     credit-normal (5820 Transfer/Clearing) — shown separately
//
// NOTE: Net Income from the P&L flows into Retained Earnings on the Balance
// Sheet. We compute it here by summing INCOME/OTHER_REVENUE minus
// COGS/EXPENSE/OTHER_EXPENSES for the current fiscal year.

$resql_assets  = getBSRows($db, $conf, $date_asof, array('ASSETS'));
$resql_liab    = getBSRows($db, $conf, $date_asof, array('LIABILITIES'));
$resql_equity  = getBSRows($db, $conf, $date_asof, array('EQUITY'));
$resql_capital = getBSRows($db, $conf, $date_asof, array('CAPITAL'));

// Current period Net Income (P&L result that feeds retained earnings)
// Use fiscal year start as the beginning of the income period
$fiscalmonth   = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
$fiscal_year   = (date('m') >= $fiscalmonth) ? date('Y') : date('Y') - 1;
$date_fy_start = dol_mktime(0, 0, 0, $fiscalmonth, 1, $fiscal_year);

$sql_ni  = "SELECT";
$sql_ni .= " SUM(CASE WHEN aa.pcg_type IN ('INCOME','OTHER_REVENUE')";
$sql_ni .= "   THEN (bk.credit - bk.debit) ELSE 0 END) AS total_revenue,";
$sql_ni .= " SUM(CASE WHEN aa.pcg_type IN ('COGS','EXPENSE','XXXXXX','OTHER_EXPENSES')";
$sql_ni .= "   THEN (bk.debit - bk.credit) ELSE 0 END) AS total_expenses";
$sql_ni .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping AS bk";
$sql_ni .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account AS aa";
$sql_ni .= "   ON aa.account_number = bk.numero_compte";
$sql_ni .= "   AND aa.entity = ".((int)$conf->entity);
$sql_ni .= "   AND aa.active = 1";
$sql_ni .= " WHERE bk.entity = ".((int)$conf->entity);
$sql_ni .= " AND bk.doc_date >= '".$db->idate($date_fy_start)."'";
$sql_ni .= " AND bk.doc_date <= '".$db->idate($date_asof)."'";

$resql_ni  = $db->query($sql_ni);
$ni_obj    = $db->fetch_object($resql_ni);
$net_income = $ni_obj ? ((float)$ni_obj->total_revenue - (float)$ni_obj->total_expenses) : 0.0;

// ── Accumulate section totals ──────────────────────────────────────────────
$asset_rows   = array();
$liab_rows    = array();
$equity_rows  = array();
$capital_rows = array();

$total_assets   = accumRows($db, $resql_assets,  $asset_rows,   true);
$total_liab     = accumRows($db, $resql_liab,     $liab_rows,    false);
$total_equity   = accumRows($db, $resql_equity,   $equity_rows,  false);
$total_capital  = accumRows($db, $resql_capital,  $capital_rows, false);

// Total equity including current period net income
$total_equity_with_ni = $total_equity + $net_income;

// Accounting equation check: Assets = Liabilities + Equity
$total_liab_equity = $total_liab + $total_equity_with_ni;
$difference        = $total_assets - $total_liab_equity;

// ── HTML helpers ───────────────────────────────────────────────────────────
function bsSectionHeader($title) {
    print '<tr style="background:#1a3a5c">';
    print '<td colspan="3" style="color:#fff;padding:6px 8px"><b>'.htmlspecialchars($title).'</b></td>';
    print '</tr>';
}

function bsSubHeader($title, $amount) {
    print '<tr style="background:#e8f0fe">';
    print '<td style="padding-left:8px"><b>'.htmlspecialchars($title).'</b></td>';
    print '<td></td>';
    print '<td class="right"><b>'.price($amount).'</b></td>';
    print '</tr>';
}

function bsDetailRows($rows, $debit_normal = true) {
    foreach ($rows as $r) {
        $net = $debit_normal
            ? ((float)$r->total_debit  - (float)$r->total_credit)
            : ((float)$r->total_credit - (float)$r->total_debit);
        $style = $net < 0 ? ' style="color:red"' : '';
        print '<tr class="oddeven">';
        print '<td style="padding-left:24px">'.htmlspecialchars($r->numero_compte).'</td>';
        print '<td>'.htmlspecialchars($r->account_label ? $r->account_label : '(unmapped)').'</td>';
        print '<td class="right"'.$style.'>'.price($net).'</td>';
        print '</tr>';
    }
}

function bsTotalRow($label, $amount, $top_border = false) {
    $color  = $amount < 0 ? 'color:red;' : '';
    $border = $top_border ? 'border-top:2px solid #555;' : '';
    print '<tr style="background:#d0d8e8;'.$border.'">';
    print '<td colspan="2" style="padding-left:8px;'.$color.'"><b>'.htmlspecialchars($label).'</b></td>';
    print '<td class="right" style="'.$color.'"><b>'.price($amount).'</b></td>';
    print '</tr>';
}

function bsSpacerRow() {
    print '<tr><td colspan="3" style="height:12px;border:none"></td></tr>';
}

// ── Page output ────────────────────────────────────────────────────────────
llxHeader('', 'Balance Sheet', '', '', 0, 0, '', '', '', 'mod-accountancy');
print load_fiche_titre('Balance Sheet', '', 'title_accountancy');

// As-of date filter form
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder" style="margin-bottom:1em"><tr>';
print '<td class="fieldrequired"><b>As of Date:</b></td><td>';
print $form->selectDate($date_asof, 'asof', 0, 0, 0, '', 1, 1);
print '</td><td><input type="submit" class="button" value="Apply"></td></tr></table>';
print '</form>';

print '<p style="color:#555;font-style:italic">Cumulative balances as of '.dol_print_date($date_asof, 'day').'</p>';

print '<table class="noborder" style="max-width:720px;width:100%">';
print '<tr class="liste_titre">';
print '<td style="width:100px"><b>Account #</b></td>';
print '<td><b>Description</b></td>';
print '<td class="right" style="width:130px"><b>Amount</b></td>';
print '</tr>';

// ══ ASSETS ════════════════════════════════════════════════════════════════
bsSectionHeader('ASSETS');
bsSubHeader('Assets', $total_assets);
bsDetailRows($asset_rows, true);
bsTotalRow('TOTAL ASSETS', $total_assets, true);
bsSpacerRow();

// ══ LIABILITIES ═══════════════════════════════════════════════════════════
bsSectionHeader('LIABILITIES');
bsSubHeader('Liabilities', $total_liab);
bsDetailRows($liab_rows, false);
bsTotalRow('TOTAL LIABILITIES', $total_liab, true);
bsSpacerRow();

// ══ EQUITY ════════════════════════════════════════════════════════════════
bsSectionHeader('EQUITY');
bsSubHeader('Equity Accounts', $total_equity);
bsDetailRows($equity_rows, false);

// Current period net income shown as a line in equity
$ni_style = $net_income < 0 ? ' style="color:red"' : '';
print '<tr class="oddeven">';
print '<td style="padding-left:24px"><i>Current Period</i></td>';
print '<td><i>Net Income (from P&amp;L)</i></td>';
print '<td class="right"'.$ni_style.'>'.price($net_income).'</td>';
print '</tr>';

bsTotalRow('TOTAL EQUITY', $total_equity_with_ni, true);
bsSpacerRow();

// ══ TOTAL LIABILITIES + EQUITY ════════════════════════════════════════════
$combined_bg    = abs($difference) < 0.01 ? '#1a7340' : '#8b0000';
$combined_color = '#ffffff';
print '<tr style="border-top:3px solid #333;background:'.$combined_bg.'">';
print '<td colspan="2" style="color:'.$combined_color.'"><b>TOTAL LIABILITIES + EQUITY</b></td>';
print '<td class="right" style="color:'.$combined_color.'"><b>'.price($total_liab_equity).'</b></td>';
print '</tr>';

print '</table>';

// ── Accounting equation check ──────────────────────────────────────────────
print '<div style="margin-top:1em;padding:0.75em;';
if (abs($difference) < 0.01) {
    print 'background:#d4edda;border:1px solid #1a7340;color:#1a7340">';
    print '<b>✔ Balanced — Assets ('.price($total_assets).') = Liabilities + Equity ('.price($total_liab_equity).')</b>';
} else {
    print 'background:#f8d7da;border:1px solid #8b0000;color:#8b0000">';
    print '<b>⚠ OUT OF BALANCE by '.price(abs($difference)).'</b><br>';
    print 'Assets: '.price($total_assets).' | Liabilities + Equity: '.price($total_liab_equity).'<br>';
    print 'Common causes: unposted journal entries, accounts mapped to wrong pcg_type, or CAPITAL clearing account needs review.';
}
print '</div>';

// ── Unmapped account warning ───────────────────────────────────────────────
$sql_um  = "SELECT DISTINCT bk.numero_compte, SUM(bk.credit) as cr, SUM(bk.debit) as dr";
$sql_um .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping bk";
$sql_um .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account aa";
$sql_um .= "   ON aa.account_number = bk.numero_compte AND aa.entity = ".((int)$conf->entity)." AND aa.active = 1";
$sql_um .= " WHERE bk.entity = ".((int)$conf->entity);
$sql_um .= " AND bk.doc_date <= '".$db->idate($date_asof)."'";
$sql_um .= " AND aa.account_number IS NULL";
$sql_um .= " GROUP BY bk.numero_compte";
$res_um = $db->query($sql_um);
if ($res_um && $db->num_rows($res_um) > 0) {
    print '<div class="warning" style="margin-top:1em;padding:0.75em">';
    print '<b>⚠ Warning: These account numbers appear in journal entries but are not mapped in your Chart of Accounts and are excluded from this report:</b><br><br>';
    print '<table class="noborder"><tr class="liste_titre"><td>Account #</td><td class="right">Debits</td><td class="right">Credits</td></tr>';
    while ($u = $db->fetch_object($res_um)) {
        print '<tr class="oddeven"><td>'.htmlspecialchars($u->numero_compte).'</td>';
        print '<td class="right">'.price($u->dr).'</td>';
        print '<td class="right">'.price($u->cr).'</td></tr>';
    }
    print '</table></div>';
}

llxFooter();
$db->close();