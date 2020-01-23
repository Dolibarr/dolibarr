<?php
/* Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)		<github@accedinfo.com>
 * Copyright (C) 2013-2014	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent			<jmenent@2byte.es>s
 * Copyright (C) 2016	  	Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * \file 		htdocs/accountancy/expensereport/list.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Ventilation page from expense reports
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "trips", "productbatch"));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

// Select Box
$mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
$search_lineid = GETPOST('search_lineid', 'alpha');
$search_expensereport = GETPOST('search_expensereport', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$search_day = GETPOST("search_day", "int");
$search_month = GETPOST("search_month", "int");
$search_year = GETPOST("search_year", "int");

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION) ? $conf->liste_limit : $conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield)
	$sortfield = "erd.date, erd.rowid";
if (!$sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->socid > 0)
	accessforbidden();
if (!$user->rights->accounting->bind->write)
	accessforbidden();

$formaccounting = new FormAccounting($db);
$accounting = new AccountingAccount($db);

$chartaccountcode = dol_getIdFromCode($db, $conf->global->CHARTOFACCOUNTS, 'accounting_system', 'rowid', 'pcg_version');


/*
 * Action
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All test are required to be compatible with all browsers
{
    $search_expensereport = '';
    $search_label = '';
    $search_desc = '';
    $search_amount = '';
    $search_account = '';
    $search_vat = '';
    $search_day = '';
    $search_month = '';
    $search_year = '';
}

// Mass actions
$objectclass = 'ExpenseReport';
$objectlabel = 'ExpenseReport';
$permissiontoread = $user->rights->expensereport->read;
$permissiontodelete = $user->rights->expensereport->delete;
$uploaddir = $conf->expensereport->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

if ($massaction == 'ventil') {
	$msg = '';
    //print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
    if (!empty($mesCasesCochees)) {
        $msg = '<div>'.$langs->trans("SelectedLines").': '.count($mesCasesCochees).'</div>';
        $msg .= '<div class="detail">';
        $cpt = 0;
        $ok = 0;
        $ko = 0;

        foreach ($mesCasesCochees as $maLigneCochee) {
            $maLigneCourante = explode("_", $maLigneCochee);
            $monId = $maLigneCourante[0];
            $monCompte = GETPOST('codeventil'.$monId);

            if ($monCompte <= 0)
            {
                $msg .= '<div><font color="red">'.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NoAccountSelected").'</font></div>';
                $ko++;
            }
            else
            {
                $sql = " UPDATE ".MAIN_DB_PREFIX."expensereport_det";
                $sql .= " SET fk_code_ventilation = ".$monCompte;
                $sql .= " WHERE rowid = ".$monId;

                $accountventilated = new AccountingAccount($db);
                $accountventilated->fetch($monCompte, '');

                dol_syslog('accountancy/expensereport/list.php:: sql='.$sql, LOG_DEBUG);
                if ($db->query($sql)) {
                    $msg .= '<div><font color="green">'.$langs->trans("LineOfExpenseReport").' '.$monId.' - '.$langs->trans("VentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'</font></div>';
                    $ok++;
                } else {
                    $msg .= '<div><font color="red">'.$langs->trans("ErrorDB").' : '.$langs->trans("Lineofinvoice").' '.$monId.' - '.$langs->trans("NotVentilatedinAccount").' : '.length_accountg($accountventilated->account_number).'<br/> <pre>'.$sql.'</pre></font></div>';
                    $ko++;
                }
            }

            $cpt++;
        }
        $msg .= '</div>';
        $msg .= '<div>'.$langs->trans("EndProcessing").'</div>';
    }
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans("ExpenseReportsVentilation"));

if (empty($chartaccountcode))
{
	print $langs->trans("ErrorChartOfAccountSystemNotSelected");
	// End of page
    llxFooter();
    $db->close();
	exit;
}

// Expense report lines
$sql = "SELECT er.ref, er.rowid as erid, er.date_debut,";
$sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht as price, erd.fk_code_ventilation, erd.tva_tx as tva_tx_line, erd.vat_src_code, erd.date,";
$sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label, f.accountancy_code as code_buy,";
$sql .= " aa.rowid as aarowid";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as er";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expensereport_det as erd ON er.rowid = erd.fk_expensereport";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON f.accountancy_code = aa.account_number AND aa.fk_pcg_version = '".$chartaccountcode."' AND aa.entity = ".$conf->entity;
$sql .= " WHERE er.fk_statut IN (".ExpenseReport::STATUS_APPROVED.", ".ExpenseReport::STATUS_CLOSED.") AND erd.fk_code_ventilation <= 0";
// Add search filter like
if (strlen(trim($search_expensereport))) {
    $sql .= natural_search("er.ref", $search_expensereport);
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("f.label", $search_label);
}
if (strlen(trim($search_desc))) {
    $sql .= natural_search("erd.comments", $search_desc);
}
if (strlen(trim($search_amount))) {
    $sql .= natural_search("erd.total_ht", $search_amount, 1);
}
if (strlen(trim($search_account))) {
    $sql .= natural_search("aa.account_number", $search_account);
}
if (strlen(trim($search_vat))) {
    $sql .= natural_search("erd.tva_tx", $search_vat, 1);
}
$sql .= dolSqlDateFilter('erd.date', $search_day, $search_month, $search_year);
$sql .= " AND er.entity IN (".getEntity('expensereport', 0).")"; // We don't share object for accountancy

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
    	$page = 0;
    	$offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/expensereport/list.php');
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
	if ($search_lineid)      $param .= '&search_lineid='.urlencode($search_lineid);
	if ($search_day)         $param .= '&search_day='.urlencode($search_day);
	if ($search_month)       $param .= '&search_month='.urlencode($search_month);
	if ($search_year)        $param .= '&search_year='.urlencode($search_year);
	if ($search_expensereport) $param .= '&search_expensereport='.urlencode($search_expensereport);
	if ($search_label)       $param .= '&search_label='.urlencode($search_label);
	if ($search_desc)        $param .= '&search_desc='.urlencode($search_desc);
	if ($search_amount)      $param .= '&search_amount='.urlencode($search_amount);
	if ($search_vat)         $param .= '&search_vat='.urlencode($search_vat);

	$arrayofmassactions = array(
	    'ventil' => $langs->trans("Ventilate")
	);
	$massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);


	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("ExpenseReportLines"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	print '<span class="opacitymedium">'.$langs->trans("DescVentilTodoExpenseReport").'</span></br><br>';

	/*$topicmail="Information";
	$modelmail="project";
	$objecttmp=new Project($db);
	$trackid='prj'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';*/

	if ($msg) print $msg.'<br>';

	$moreforfilter = '';

    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// We add search filter
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_expensereport" value="'.dol_escape_htmltag($search_expensereport).'"></td>';
	print '<td class="liste_titre center nowraponall minwidth100imp">';
   	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_day" value="'.$search_day.'">';
   	print '<input class="flat valignmiddle maxwidth25" type="text" maxlength="2" name="search_month" value="'.$search_month.'">';
   	$formother->select_year($search_year, 'search_year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidthonsmartphone" name="search_desc" value="'.dol_escape_htmltag($search_desc).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_vat" placeholder="%" size="1" value="'.dol_escape_htmltag($search_vat).'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="center" class="liste_titre">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("LineId", $_SERVER["PHP_SELF"], "erd.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("ExpenseReport", $_SERVER["PHP_SELF"], "er.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "erd.date, erd.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("TypeFees", $_SERVER["PHP_SELF"], "f.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Description", $_SERVER["PHP_SELF"], "erd.comments", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "erd.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
	print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], "erd.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("IntoAccount", '', '', '', '', '', $sortfield, $sortorder, 'center ');
	$checkpicto = '';
	if ($massactionbutton) $checkpicto = $form->showCheckAddButtons('checkforselect', 1);
	print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
	print "</tr>\n";


	$expensereport_static = new ExpenseReport($db);
	$form = new Form($db);

	while ($i < min($num_lines, $limit)) {
		$objp = $db->fetch_object($result);

		$objp->aarowid_suggest = '';
		$objp->aarowid_suggest = $objp->aarowid;

		$expensereport_static->ref = $objp->ref;
		$expensereport_static->id = $objp->erid;

		print '<tr class="oddeven">';

		// Line id
		print '<td>'.$objp->rowid.'</td>';

		// Ref Expense report
		print '<td>'.$expensereport_static->getNomUrl(1).'</td>';

		// Date
		print '<td class="center">'.dol_print_date($db->jdate($objp->date), 'day').'</td>';

		// Fees label
		print '<td>';
		print ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code)));
		print '</td>';

		// Fees description -- Can be null
		print '<td>';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments));
		$trunclength = empty($conf->global->ACCOUNTING_LENGTH_DESCRIPTION) ? 32 : $conf->global->ACCOUNTING_LENGTH_DESCRIPTION;
		print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->comments);
		print '</td>';

		print '<td class="nowrap right">';
		print price($objp->price);
		print '</td>';

		// Vat rate
		print '<td class="right">';
		print vatrate($objp->tva_tx_line.($objp->vat_src_code ? ' ('.$objp->vat_src_code.')' : ''));
		print '</td>';

		// Current account
		print '<td class="center">';
		print length_accountg(html_entity_decode($objp->code_buy));
		print '</td>';

		// Suggested accounting account
		print '<td class="center">';
		print $formaccounting->select_account($objp->aarowid_suggest, 'codeventil'.$objp->rowid, 1, array(), 0, 0, 'codeventil maxwidth300 maxwidthonsmartphone', 'cachewithshowemptyone');
		print '</td>';

		print '<td class="center">';
		print '<input type="checkbox" class="flat checkforselect checkforselect'.$objp->rowid.'" name="toselect[]" value="'.$objp->rowid."_".$i.'"'.($objp->aarowid ? "checked" : "").'/>';
		print '</td>';

		print "</tr>";
		$i++;
	}

	print '</table>';
	print "</div>";

	print '</form>';
} else {
	print $db->error();
}

// Add code to auto check the box when we select an account
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
llxFooter();
$db->close();
