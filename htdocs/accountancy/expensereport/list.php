<?php
/* Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017	Alexandre Spangaro		<aspangaro@zendsi.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 		htdocs/accountancy/expensereport/list.php
 * \ingroup 	Advanced accountancy
 * \brief 		Ventilation page from expense reports
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("trips");
$langs->load("main");
$langs->load("accountancy");
$langs->load("productbatch");

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

// Select Box
$mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
$search_expensereport = GETPOST('search_expensereport', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_desc = GETPOST('search_desc', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_vat = GETPOST('search_vat', 'alpha');
$btn_ventil = GETPOST('ventil', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit') ? GETPOST('limit', 'int') : (empty($conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION)?$conf->liste_limit:$conf->global->ACCOUNTING_LIMIT_LIST_VENTILATION);
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page','int');
if ($page < 0) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield)
	$sortfield = "erd.date, erd.rowid";
if (! $sortorder) {
	if ($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO > 0) {
		$sortorder = "DESC";
	}
}

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->bind->write)
	accessforbidden();

$formventilation = new FormVentilation($db);
$accounting = new AccountingAccount($db);


/*
 * Action
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $search_expensereport = '';
    $search_label = '';
    $search_desc = '';
    $search_amount = '';
    $search_account = '';
    $search_vat = '';
}

// Mass actions
$objectclass='Skeleton';
$objectlabel='Skeleton';
$permtoread = $user->rights->accounting->read;
$permtodelete = $user->rights->accounting->delete;
$uploaddir = $conf->accounting->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

if ($massaction == 'ventil') {
	$msg='';
    //print '<div><font color="red">' . $langs->trans("Processing") . '...</font></div>';
    if (! empty($mesCasesCochees)) {
        $msg = '<div>' . $langs->trans("SelectedLines") . ': '.count($mesCasesCochees).'</div>';
        $msg.='<div class="detail">';
        $mesCodesVentilChoisis = $codeventil;
        $cpt = 0;
        $ok=0;
        $ko=0;

        foreach ( $mesCasesCochees as $maLigneCochee ) {
            $maLigneCourante = explode("_", $maLigneCochee);
            $monId = $maLigneCourante[0];
            $monCompte = GETPOST('codeventil'.$monId);

            if ($monCompte <= 0)
            {
                $msg.= '<div><font color="red">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NoAccountSelected") . '</font></div>';
                $ko++;
            }
            else
            {
                $sql = " UPDATE " . MAIN_DB_PREFIX . "expensereport_det";
                $sql .= " SET fk_code_ventilation = " . $monCompte;
                $sql .= " WHERE rowid = " . $monId;
    
                $accountventilated = new AccountingAccount($db);
                $accountventilated->fetch($monCompte, '');
    
                dol_syslog('accountancy/expensereport/list.php:: sql=' . $sql, LOG_DEBUG);
                if ($db->query($sql)) {
                    $msg.= '<div><font color="green">' . $langs->trans("LineOfExpenseReport") . ' ' . $monId . ' - ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '</font></div>';
                    $ok++;
                } else {
                    $msg.= '<div><font color="red">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '<br/> <pre>' . $sql . '</pre></font></div>';
                    $ko++;
                }
            }
            
            $cpt++;
        }
        $msg.='</div>';
        $msg.= '<div>' . $langs->trans("EndProcessing") . '</div>';
    //} else {
    //    setEventMessages($langs->trans("NoRecordSelected"), null, 'warnings');
    }
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("ExpenseReportsVentilation"));

// Expense report lines
$sql = "SELECT er.ref, er.rowid as erid, er.date_debut,";
$sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht as price, erd.fk_code_ventilation, erd.tva_tx as tva_tx_line, erd.date,";
$sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label, f.accountancy_code as code_buy,";
$sql .= " aa.rowid as aarowid";
$sql .= " FROM " . MAIN_DB_PREFIX . "expensereport as er";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport_det as erd ON er.rowid = erd.fk_expensereport";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON f.accountancy_code = aa.account_number";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_system as accsys ON accsys.pcg_version = aa.fk_pcg_version";
$sql .= " WHERE er.fk_statut > 4 AND erd.fk_code_ventilation <= 0";
$sql .= " AND (accsys.rowid='" . $conf->global->CHARTOFACCOUNTS . "' OR f.accountancy_code IS NULL OR f.accountancy_code ='')";
// Add search filter like
if (strlen(trim($search_expensereport))) {
    $sql .= natural_search("er.ref",$search_expensereport);
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("f.label",$search_label);
}
if (strlen(trim($search_desc))) {
    $sql .= natural_search("erd.comments",$search_desc);
}
if (strlen(trim($search_amount))) {
    $sql .= natural_search("erd.total_ht",$search_amount,1);
}
if (strlen(trim($search_account))) {
    $sql .= natural_search("aa.account_number",$search_account);
}
if (strlen(trim($search_vat))) {
    $sql .= natural_search("erd.tva_tx",$search_vat,1);
}
$sql .= " AND er.entity IN (" . getEntity("expensereport", 0) . ")";  // We don't share object for accountancy

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/expensereport/list.php');
$result = $db->query($sql);
if ($result) {
	$num_lines = $db->num_rows($result);
	$i = 0;

	$arrayofselected=is_array($toselect)?$toselect:array();
	
	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

	$arrayofmassactions =  array(
	    'ventil'=>$langs->trans("Ventilate")
	    //'presend'=>$langs->trans("SendByMail"),
	    //'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->mymodule->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
	//if ($massaction == 'presend') $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('ventil', $arrayofmassactions, 1);
	
	
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">' . "\n";
	print '<input type="hidden" name="action" value="ventil">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	//$center='<div class="center"><input type="submit" class="butAction" value="' . $langs->trans("Ventilate") . '" name="ventil"></div>';

	print_barre_liste($langs->trans("ExpenseReportLines"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

	print $langs->trans("DescVentilTodoExpenseReport") . '</br><br>';

	if ($msg) print $msg.'<br>';

	$moreforfilter = '';

    print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("LineId"), $_SERVER["PHP_SELF"], "erd.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("ExpenseReport"), $_SERVER["PHP_SELF"], "er.ref", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "erd.date, erd.rowid", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("TypeFees"), $_SERVER["PHP_SELF"], "f.label", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], "erd.comments", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Amount"), $_SERVER["PHP_SELF"], "erd.total_ht", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("VATRate"), $_SERVER["PHP_SELF"], "erd.tva_tx", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AccountAccountingSuggest"), '', '', '', '', 'align="center"');
	print_liste_field_titre($langs->trans("IntoAccount"), '', '', '', '', 'align="center"');
	print_liste_field_titre('', '', '', '', '', 'align="center"');
	print "</tr>\n";

	// We add search filter
	print '<tr class="liste_titre">';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_expensereport" value="' . dol_escape_htmltag($search_expensereport) . '"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
	print '<td class="liste_titre"><input type="text" class="flat maxwidthonsmartphone" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
	print '<td class="liste_titre" align="right"><input type="text" class="flat maxwidth50" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
	print '<td class="liste_titre" align="right"><input type="text" class="flat maxwidth50" name="search_vat" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	print '<td align="right" class="liste_titre">';
	$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	$expensereport_static = new ExpenseReport($db);
	$form = new Form($db);

	$var = true;
	while ( $i < min($num_lines, $limit) ) {
		$objp = $db->fetch_object($result);
		$var = ! $var;

		$objp->aarowid_suggest = '';
		$objp->aarowid_suggest = $objp->aarowid;

		$expensereport_static->ref = $objp->ref;
		$expensereport_static->id = $objp->erid;

		print '<tr '. $bc[$var].'>';

		// Line id
		print '<td>' . $objp->rowid . '</td>';

		// Ref Expense report
		print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';

		print '<td align="center">' . dol_print_date($db->jdate($objp->date), 'day') . '</td>';

		// Fees label
		print '<td>';
		print ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code)));
		print '</td>';

		// Fees description -- Can be null
		print '<td>';
		$text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments));
		$trunclength = defined('ACCOUNTING_LENGTH_DESCRIPTION') ? ACCOUNTING_LENGTH_DESCRIPTION : 32;
		print $form->textwithtooltip(dol_trunc($text,$trunclength), $objp->comments);
		print '</td>';

		print '<td align="right">';
		print price($objp->price);
		print '</td>';

		// Vat rate
		print '<td align="right">';
		print price($objp->tva_tx_line);
		print '</td>';

		// Current account
		print '<td align="center">';
		print length_accountg(html_entity_decode($objp->code_buy));
		print '</td>';

		// Suggested accounting account
		print '<td align="center">';
		print $formventilation->select_account($objp->aarowid_suggest, 'codeventil'.$objp->rowid, 1, array(), 0, 0, 'maxwidth300 maxwidthonsmartphone', 'cachewithshowemptyone');
		print '</td>';

		print '<td align="right">';
		print '<input type="checkbox" class="flat checkforselect" name="toselect[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid ? "checked" : "") . '/>';
		print '</td>';

		print "</tr>";
		$i ++;
	}

	print '</table>';
	print "</div>";
	
	print '</form>';
} else {
	print $db->error();
}

llxFooter();
$db->close();
