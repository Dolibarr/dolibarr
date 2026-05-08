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

$fiscalmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
$year_start  = (date('m') >= $fiscalmonth) ? date('Y') : date('Y') - 1;

$date_startmonth = ((int) GETPOST('date_startmonth', 'int')) ? ((int) GETPOST('date_startmonth', 'int')) : $fiscalmonth;
$date_startday   = ((int) GETPOST('date_startday',   'int')) ? ((int) GETPOST('date_startday',   'int')) : 1;
$date_startyear  = ((int) GETPOST('date_startyear',  'int')) ? ((int) GETPOST('date_startyear',  'int')) : $year_start;
$date_endmonth   = ((int) GETPOST('date_endmonth',   'int')) ? ((int) GETPOST('date_endmonth',   'int')) : (int)date('m');
$date_endday     = ((int) GETPOST('date_endday',     'int')) ? ((int) GETPOST('date_endday',     'int')) : (int)date('d');
$date_endyear    = ((int) GETPOST('date_endyear',    'int')) ? ((int) GETPOST('date_endyear',    'int')) : (int)date('Y');

$date_start = dol_mktime(0,  0,  0,  $date_startmonth, $date_startday, $date_startyear);
$date_end   = dol_mktime(23, 59, 59, $date_endmonth,   $date_endday,   $date_endyear);

function getAccountRows($db, $conf, $date_start, $date_end, $pcg_types) {
    $escaped = array();
    foreach ($pcg_types as $t) {
        $escaped[] = "'".$db->escape($t)."'";
    }
    $type_list = implode(',', $escaped);
    $sql  = "SELECT bk.numero_compte, aa.label AS account_label, aa.pcg_type,";
    $sql .= " SUM(bk.debit) AS total_debit, SUM(bk.credit) AS total_credit";
    $sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping AS bk";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account AS aa";
    $sql .= "   ON aa.account_number = bk.numero_compte";
    $sql .= "   AND aa.entity = ".((int)$conf->entity);
    $sql .= "   AND aa.active = 1";
    $sql .= " WHERE bk.entity = ".((int)$conf->entity);
    $sql .= " AND bk.doc_date >= '".$db->idate($date_start)."'";
    $sql .= " AND bk.doc_date <= '".$db->idate($date_end)."'";
    $sql .= " AND aa.pcg_type IN (".$type_list.")";
    $sql .= " GROUP BY bk.numero_compte, aa.label, aa.pcg_type";
    $sql .= " HAVING (SUM(bk.debit) <> 0 OR SUM(bk.credit) <> 0)";
    $sql .= " ORDER BY bk.numero_compte ASC";
    $res = $db->query($sql);
    if (!$res) dol_print_error($db);
    return $res;
}

function sumSection($db, $resql, &$rows, $credit_normal = false) {
    $net = 0.0;
    while ($obj = $db->fetch_object($resql)) {
        $rows[] = $obj;
        $net += $credit_normal
            ? ((float)$obj->total_credit - (float)$obj->total_debit)
            : ((float)$obj->total_debit  - (float)$obj->total_credit);
    }
    return $net;
}

function renderSection($title, $rows, $section_total, $credit_normal = false) {
    print '<tr style="background:#e8f0fe">';
    print '<td style="padding-left:8px"><b>'.htmlspecialchars($title).'</b></td>';
    print '<td></td>';
    print '<td class="right"><b>'.price($section_total).'</b></td>';
    print '</tr>';
    foreach ($rows as $r) {
        $net = $credit_normal
            ? ((float)$r->total_credit - (float)$r->total_debit)
            : ((float)$r->total_debit  - (float)$r->total_credit);
        $style = $net < 0 ? ' style="color:red"' : '';
        print '<tr class="oddeven">';
        print '<td style="padding-left:24px">'.htmlspecialchars($r->numero_compte).'</td>';
        print '<td>'.htmlspecialchars($r->account_label ? $r->account_label : '(unmapped)').'</td>';
        print '<td class="right"'.$style.'>'.price($net).'</td>';
        print '</tr>';
    }
}

function subtotalRow($label, $amount, $strong = true) {
    $color = $amount < 0 ? 'color:red;' : '';
    $bg    = $strong ? 'background:#d0d8e8;' : 'background:#f0f0f0;';
    $b     = $strong ? '<b>' : '';
    $be    = $strong ? '</b>' : '';
    print '<tr style="border-top:1px solid #aaa;'.$bg.'">';
    print '<td colspan="2" style="padding-left:8px;'.$color.'">'.$b.htmlspecialchars($label).$be.'</td>';
    print '<td class="right" style="'.$color.'">'.$b.price($amount).$be.'</td>';
    print '</tr>';
}

function spacerRow() {
    print '<tr><td colspan="3" style="height:10px;border:none"></td></tr>';
}

$resql_rev       = getAccountRows($db, $conf, $date_start, $date_end, array('INCOME'));
$resql_other_rev = getAccountRows($db, $conf, $date_start, $date_end, array('OTHER_REVENUE'));
$resql_cogs      = getAccountRows($db, $conf, $date_start, $date_end, array('COGS'));
$resql_opex      = getAccountRows($db, $conf, $date_start, $date_end, array('EXPENSE', 'XXXXXX'));
$resql_other_exp = getAccountRows($db, $conf, $date_start, $date_end, array('OTHER_EXPENSES'));

$rev_rows = array();
$other_rev_rows = array();
$cogs_rows = array();
$opex_rows = array();
$other_exp_rows = array();

$total_revenue   = sumSection($db, $resql_rev,       $rev_rows,       true);
$total_other_rev = sumSection($db, $resql_other_rev, $other_rev_rows, true);
$total_cogs      = sumSection($db, $resql_cogs,      $cogs_rows,      false);
$total_opex      = sumSection($db, $resql_opex,      $opex_rows,      false);
$total_other_exp = sumSection($db, $resql_other_exp, $other_exp_rows, false);

$gross_profit     = $total_revenue - $total_cogs;
$operating_income = $gross_profit - $total_opex;
$net_income       = $operating_income + $total_other_rev - $total_other_exp;

llxHeader('', 'Income Statement', '', '', 0, 0, '', '', '', 'mod-accountancy');
print load_fiche_titre('Income Statement (Profit &amp; Loss)', '', 'title_accountancy');

print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'">';
print '<table class="noborder" style="margin-bottom:1em"><tr>';
print '<td class="fieldrequired">Period&nbsp;Start:</td><td>';
print $form->selectDate($date_start, 'date_start', 0, 0, 0, '', 1, 1);
print '</td><td class="fieldrequired">Period&nbsp;End:</td><td>';
print $form->selectDate($date_end, 'date_end', 0, 0, 0, '', 1, 1);
print '</td><td><input type="submit" class="button" value="Apply"></td></tr></table>';
print '</form>';

print '<table class="noborder" style="max-width:720px;width:100%">';
print '<tr class="liste_titre">';
print '<td style="width:100px"><b>Account #</b></td>';
print '<td><b>Description</b></td>';
print '<td class="right" style="width:130px"><b>Amount</b></td>';
print '</tr>';

renderSection('Revenue', $rev_rows, $total_revenue, true);
subtotalRow('Total Revenue', $total_revenue, false);
spacerRow();

renderSection('Cost of Goods Sold', $cogs_rows, $total_cogs, false);
subtotalRow('Total COGS', $total_cogs, false);
spacerRow();

subtotalRow('Gross Profit', $gross_profit, true);
spacerRow();

renderSection('Operating Expenses', $opex_rows, $total_opex, false);
subtotalRow('Total Operating Expenses', $total_opex, false);
spacerRow();

subtotalRow('Operating Income', $operating_income, true);
spacerRow();

if (!empty($other_rev_rows)) {
    renderSection('Other Revenue', $other_rev_rows, $total_other_rev, true);
    spacerRow();
}
if (!empty($other_exp_rows)) {
    renderSection('Other Expenses', $other_exp_rows, $total_other_exp, false);
    spacerRow();
}

$ni_bg = $net_income >= 0 ? '#1a7340' : '#8b0000';
print '<tr style="border-top:3px solid #333;background:'.$ni_bg.'">';
print '<td colspan="2" style="color:#fff"><b>NET INCOME</b></td>';
print '<td class="right" style="color:#fff"><b>'.price($net_income).'</b></td>';
print '</tr>';
print '</table>';

$sql_unmapped  = "SELECT DISTINCT bk.numero_compte, SUM(bk.credit) as cr, SUM(bk.debit) as dr";
$sql_unmapped .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping bk";
$sql_unmapped .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account aa";
$sql_unmapped .= "   ON aa.account_number = bk.numero_compte AND aa.entity = ".((int)$conf->entity)." AND aa.active = 1";
$sql_unmapped .= " WHERE bk.entity = ".((int)$conf->entity);
$sql_unmapped .= " AND bk.doc_date >= '".$db->idate($date_start)."'";
$sql_unmapped .= " AND bk.doc_date <= '".$db->idate($date_end)."'";
$sql_unmapped .= " AND aa.account_number IS NULL";
$sql_unmapped .= " GROUP BY bk.numero_compte";
$res_unmapped = $db->query($sql_unmapped);
if ($res_unmapped && $db->num_rows($res_unmapped) > 0) {
    print '<div class="warning" style="margin-top:1em;padding:0.75em">';
    print '<b>Warning: These account numbers appear in journal entries but are not in your Chart of Accounts and are excluded from this report:</b><br><br>';
    print '<table class="noborder"><tr class="liste_titre"><td>Account #</td><td class="right">Debits</td><td class="right">Credits</td></tr>';
    while ($u = $db->fetch_object($res_unmapped)) {
        print '<tr class="oddeven"><td>'.htmlspecialchars($u->numero_compte).'</td>';
        print '<td class="right">'.price($u->dr).'</td>';
        print '<td class="right">'.price($u->cr).'</td></tr>';
    }
    print '</table></div>';
}

llxFooter();
$db->close();