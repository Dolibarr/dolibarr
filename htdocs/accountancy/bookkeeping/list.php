<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com> 
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
 *
 */

/**
 * \file htdocs/accountancy/bookkeeping/list.php
 * \ingroup Accounting Expert
 * \brief List operation of book keeping
 */
require '../../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Langs
$langs->load("accountancy");

$page = GETPOST("page");
$sortorder = GETPOST("sortorder");
$sortfield = GETPOST("sortfield");
$action = GETPOST('action', 'alpha');
$search_doc_type = GETPOST("search_doc_type");
$search_doc_ref = GETPOST("search_doc_ref");
$search_account = GETPOST("search_account");
$search_thirdparty = GETPOST("search_thirdparty");
$search_journal = GETPOST("search_journal");
$limit = GETPOST('limit') ? GETPOST('limit', 'int') : $conf->liste_limit;

if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "t.rowid";

$offset = $limit * $page;

$object = new BookKeeping($db);

$formventilation = new FormVentilation($db);
$formother = new FormOther($db);
$form = new Form($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_piece_num = "";
	$search_doc_ref = "";
	$search_account = "";
	$search_thirdparty = "";
	$search_journal = "";
}

$options='';
$filter = array ();
if (! empty($search_doc_type)) {
	$filter['t.piece_num'] = $search_piece_num;
	$options.='&amp;search_doc_type='.$search_piece_num;
	if (! empty($search_doc_ref)) {
		$filter['t.doc_ref'] = $search_doc_ref;
		$options.='&amp;search_doc_ref='.$search_doc_ref;
	}
}
if (! empty($search_doc_ref)) {
	$filter['t.doc_ref'] = $search_doc_ref;
	$options.='&amp;search_doc_ref='.$search_doc_ref;
}
if (! empty($search_account)) {
	$filter['t.numero_compte'] = $search_account;
	$options.='&amp;search_account='.$search_account;
}
if (! empty($search_thirdparty)) {
	$filter['t.code_tiers'] = $search_thirdparty;
	$options.='&amp;search_thirdparty='.$search_thirdparty;
}
if (! empty($search_journal)) {
	$filter['t.code_journal'] = $search_journal;
	$options.='&amp;search_journal='.$search_journal;
}

/*
 * Action
 */
if ($action == 'delbookkeeping') {
	
	$import_key = GETPOST('importkey', 'alpha');
	
	if (! empty($import_key)) {
		$result = $object->delete_by_importkey($import_key);
		Header("Location: list.php");
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
} elseif ($action == 'delbookkeepingyear') {
	
	$delyear = GETPOST('delyear', 'int');
	
	if (! empty($delyear)) {
		$result = $object->delete_by_year($delyear);
		Header("Location: list.php");
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
} elseif ($action == 'delmouvconfirm') {
	
	$piece_num = GETPOST('piece_num', 'int');
	
	if (! empty($piece_num)) {
		$result = $object->delete_piece_num($piece_num);
		Header("Location: list.php");
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
} elseif ($action == 'export_csv') {
	// Export
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename=export_csv.csv');
	
	$result = $object->export_bookkeping('ebp');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	// Model classic Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 1) {
		
		foreach ( $object->linesexport as $line ) {
			print '"' . dol_print_date($line->doc_date, '%d%m%Y') . '",';
			print '"' . $line->code_journal . '",';
			print '"' . $line->numero_compte . '",';
			print '"' . substr($line->code_journal, 0, 2) . '",';
			print '"' . substr($line->doc_ref, 0, 40) . '",';
			print '"' . $line->num_piece . '",';
			print '"' . $line->debit . '",';
			print '"' . $line->credit . '",';
			print '"' . $conf->currency . '",';
			print "\n";
		}
	}
	// Model cegid Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2) {
		foreach ( $object->linesexport as $line ) {
			print $line->id . ',';
			print '"' . dol_print_date($line->doc_date, '%d%m%Y') . '",';
			print '"' . $line->code_journal . '",';
			print '"' . $line->numero_compte . '",';
			print '"' . substr($line->code_journal, 0, 2) . '",';
			print '"' . substr($line->doc_ref, 0, 40) . '",';
			print '"' . $line->num_piece . '",';
			print '"' . $line->montant . '",';
			print '"' . $line->sens . '",';
			print '"' . dol_print_date($line->doc_date, '%d%m%Y') . '",';
			print '"' . $conf->currency . '",';
			print "\n";
		}
	}
	// Model Coala Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 3) {
		foreach ( $object->linesexport as $line ) {
			print $line->id . ',';
			print '"' . dol_print_date($line->doc_date, '%d%m%Y') . '",';
			print '"' . $line->code_journal . '",';
			print '"' . $line->numero_compte . '",';
			print '"' . substr($line->code_journal, 0, 2) . '",';
			print '"' . substr($line->doc_ref, 0, 40) . '",';
			print '"' . $line->num_piece . '",';
			print '"' . $line->montant . '",';
			print '"' . $line->sens . '",';
			print '"' . dol_print_date($line->doc_date, '%d%m%Y') . '",';
			print '"' . $conf->currency . '",';
			print "\n";
		}
	}
} 

else {
	
	llxHeader('', $langs->trans("Bookkeeping"));
	
	/*
	 * List
	 */
	
	$nbtotalofrecords = 0;
	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
		$nbtotalofrecords = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);
		if ($nbtotalofrecords < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	
	$result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	
	if ($action == 'delmouv') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?piece_num=' . GETPOST('piece_num'), $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt'), 'delmouvconfirm', '', 0, 1);
		print $formconfirm;
	}
	
	print_barre_liste($langs->trans("Bookkeeping"), $page, $_SERVER["PHP_SELF"], $options, $sortfield, $sortorder, '', $result, $nbtotalofrecords);
	
	/*print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	 print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	 print '<input type="hidden" name="action" value="delbookkeeping">';
	 
	 print $formventilation->select_bookkeeping_importkey('importkey', GETPOST('importkey'));
	 
	 print '<div class="inline-block divButAction"><input type="submit" class="butAction" value="' . $langs->trans("DelBookKeeping") . '" /></div>';
	 
	 print '</form>';*/
	
	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="delbookkeepingyear">';
	
	print $formother->select_year(GETPOST('delyear'), 'delyear');
	
	print '<div class="inline-block divButAction"><input type="submit" class="butAction" value="' . $langs->trans("DelBookKeeping") . '" /></div>';
	print '<a class="butAction" href="./card.php?action=create">' . $langs->trans("NewAccountingMvt") . '</a>';
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=export_csv">' . $langs->trans("Export") . '</a>';
	
	print '</form>';
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("NumPiece"), $_SERVER['PHP_SELF'], "t.piece_num", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Docdate"), $_SERVER['PHP_SELF'], "t.doc_date", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Docref"), $_SERVER['PHP_SELF'], "t.doc_ref", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Numerocompte"), $_SERVER['PHP_SELF'], "t.numero_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Code_tiers"), $_SERVER['PHP_SELF'], "t.code_tiers", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Labelcompte"), $_SERVER['PHP_SELF'], "bk_label_compte", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Debit"), $_SERVER['PHP_SELF'], "t.debit", "", $options, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Credit"), $_SERVER['PHP_SELF'], "t.credit", "", $options, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Amount"), $_SERVER['PHP_SELF'], "t.montant", "", $options, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Sens"), $_SERVER['PHP_SELF'], "t.sens", "", $options, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Codejournal"), $_SERVER['PHP_SELF'], "t.code_journal", "", $options, "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Action"), $_SERVER["PHP_SELF"], "", $options, "", 'width="60" align="center"', $sortfield, $sortorder);
	print "</tr>\n";
	
	print '<tr class="liste_titre">';
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="GET">';
	print '<td><input type="text" name="search_doc_type" size="8" value="' . $search_piece_num . '"></td>';
	print '<td>&nbsp;</td>';
	print '<td><input type="text" name="search_doc_ref" size="8" value="' . $search_doc_ref . '"></td>';
	print '<td><input type="text" name="search_account" size="8" value="' . $search_account . '"></td>';
	print '<td><input type="text" name="search_thirdparty" size="8" value="' . $search_thirdparty . '"></td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td><input type="text" name="search_journal" size="3" value="' . $search_journal . '"></td>';
	print '<td align="right" colspan="2" class="liste_titre">';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp;';
	print '<input type="image" class="liste_titre" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';
	print '</form>';
	print '</tr>';
	
	$var = True;
	
	foreach ( $object->lines as $line ) {
		$var = ! $var;
		
		print "<tr $bc[$var]>";
		
		/*if ($old_piecenum!=$obj->piece_num) {
		 $total_debit=0;
		 $total_credit=0;
		 } else {
		 $total_debit+=$obj->debit;
		 $total_credit+=$obj->credit;
		 }
		 */
		
		print '<td><a href="./card.php?piece_num=' . $line->piece_num . '">' . $line->piece_num . '</a></td>';
		print '<td align="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
		print '<td>' . $line->doc_ref . '</td>';
		print '<td>' . length_accountg($line->numero_compte) . '</td>';
		print '<td>' . length_accounta($line->code_tiers) . '</td>';
		print '<td>' . $line->label_compte . '</td>';
		print '<td align="right">' . price($line->debit) . '</td>';
		print '<td align="right">' . price($line->credit) . '</td>';
		print '<td align="right">' . price($line->montant) . '</td>';
		print '<td align="center">' . $line->sens . '</td>';
		print '<td>' . $line->code_journal . '</td>';
		print '<td align="center">';
		print '<a href="./card.php?piece_num=' . $line->piece_num . '">' . img_edit() . '</a>';
		print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delmouv&piece_num=' . $line->piece_num . '">' . img_delete() . '</a>';
		print '</td>';
		print "</tr>\n";
	}
	print "</table>";
	
	llxFooter();
}

$db->close();