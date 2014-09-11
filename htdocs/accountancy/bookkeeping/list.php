<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com> 
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
 * \file		htdocs/accountancy/bookkeeping/list.php
 * \ingroup		Accounting Expert
 * \brief		List operation of book keeping
 */

require '../../main.inc.php';
	
// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';


$page = GETPOST("page");
$sortorder = GETPOST("sortorder");
$sortfield = GETPOST("sortfield");
$action = GETPOST('action', 'alpha');

if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "bk.rowid";

$offset = $conf->liste_limit * $page;

$formventilation = new FormVentilation($db);

/*
 * Action
 */
if ($action == 'delbookkeeping') {
	
	$import_key = GETPOST('importkey', 'alpha');
	
	if (! empty($import_key)) {
		$object = new BookKeeping($db);
		$result = $object->delete_by_importkey($import_key);
		Header("Location: list.php");
		if ($result < 0) {
			setEventMessage($object->errors, 'errors');
		}
	}
} // export csv
else if ($action == 'export_csv') {
	
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename=export_csv.csv');
	
	$object = new BookKeeping($db);
	$result = $object->export_bookkeping('ebp');
	if ($result < 0) {
		setEventMessage($object->errors, 'errors');
	}
	
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

else {
	
	llxHeader('', $langs->trans("Accounting") . ' - ' . $langs->trans("Bookkeeping"));
	
	/*
 * List
 */
	
	$sql = "SELECT bk.rowid, bk.doc_date, bk.doc_type, bk.doc_ref, bk.code_tiers, bk.numero_compte , bk.label_compte, bk.debit , bk.credit, bk.montant , bk.sens , bk.code_journal , bk.piece_num ";
	$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as bk";
	
	if (dol_strlen(trim(GETPOST("search_doc_type")))) {
		
		$sql .= " WHERE bk.doc_type LIKE '%" . GETPOST("search_doc_type") . "%'";
		
		if (dol_strlen(trim(GETPOST("search_doc_ref")))) {
			$sql .= " AND bk.doc_ref LIKE '%" . GETPOST("search_doc_ref") . "%'";
		}
	}
	if (dol_strlen(trim(GETPOST("search_doc_ref")))) {
		$sql .= " WHERE bk.doc_ref LIKE '%" . GETPOST("search_doc_ref") . "%'";
	}
	if (dol_strlen(trim(GETPOST("search_compte")))) {
		$sql .= " WHERE bk.numero_compte LIKE '%" . GETPOST("search_compte") . "%'";
	}
	if (dol_strlen(trim(GETPOST("search_tiers")))) {
		$sql .= " WHERE bk.code_tiers LIKE '%" . GETPOST("search_tiers") . "%'";
	}
	if (dol_strlen(trim(GETPOST("search_journal")))) {
		$sql .= " WHERE bk.code_journal LIKE '%" . GETPOST("search_journal") . "%'";
	}
	
	$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit + 1, $offset);
	
	dol_syslog('accountancy/bookkeeping/list.php:: $sql=' . $sql);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		
		print_barre_liste($langs->trans("Bookkeeping"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num);
		
		print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="delbookkeeping">';
		
		print $formventilation->select_bookkeeping_importkey('importkey', GETPOST('importkey'));
		
		print '<div class="inline-block divButAction"><input type="submit" class="butAction" value="' . $langs->trans("DelBookKeeping") . '" /></div>';
		
		print '</form>';
		
		print '<a href="./card.php?action=create" class="butAction">' . $langs->trans("NewAccountingMvt") . '</a>';
		
		print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="export_csv">';
		print '<input type="submit" class="button" style="float: right;" value="Export CSV" />';
		print '</form>';
		
		print "<table class=\"noborder\" width=\"100%\">";
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Doctype"), $_SERVER['PHP_SELF'], "bk.doc_type", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Docdate"), $_SERVER['PHP_SELF'], "bk.doc_date", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Docref"), $_SERVER['PHP_SELF'], "bk.doc_ref", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Numerocompte"), $_SERVER['PHP_SELF'], "bk.numero_compte", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Code_tiers"), $_SERVER['PHP_SELF'], "bk.code_tiers", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Labelcompte"), $_SERVER['PHP_SELF'], "bk_label_compte", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Debit"), $_SERVER['PHP_SELF'], "bk.debit", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Credit"), $_SERVER['PHP_SELF'], "bk.credit", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Amount"), $_SERVER['PHP_SELF'], "bk.montant", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Sens"), $_SERVER['PHP_SELF'], "bk.sens", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Codejournal"), $_SERVER['PHP_SELF'], "bk.code_journal", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre("&nbsp;");
		print "</tr>\n";
		
		print '<tr class="liste_titre">';
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="GET">';
		print '<td><input type="text" name="search_doc_type" value="' . $_GET["search_doc_type"] . '"></td>';
		print '<td>&nbsp;</td>';
		print '<td><input type="text" name="search_doc_ref" value="' . $_GET["search_doc_ref"] . '"></td>';
		print '<td><input type="text" name="search_compte" value="' . $_GET["search_compte"] . '"></td>';
		print '<td><input type="text" name="search_tiers" value="' . $_GET["search_tiers"] . '"></td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td><input type="text" name="search_journal" size="3" value="' . $_GET["search_journal"] . '"></td>';
		print '<td align="right">';
		print '<input type="image" class="liste_titre" name="button_search" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '</td>';
		print '</form>';
		print '</tr>';
		
		$var = True;
		
		while ( $i < min($num, $conf->liste_limit) ) {
			$obj = $db->fetch_object($resql);
			$var = ! $var;
			
			print "<tr $bc[$var]>";
			
			print '<td>' . $obj->doc_type . '</td>';
			print '<td>' . dol_print_date($db->jdate($obj->doc_date), 'day') . '</td>';
			print '<td>' . $obj->doc_ref . '</td>';
			print '<td>' . length_accountg($obj->numero_compte) . '</td>';
			print '<td>' . length_accounta($obj->code_tiers) . '</td>';
			print '<td>' . $obj->label_compte . '</td>';
			print '<td align="right">' . price($obj->debit) . '</td>';
			print '<td align="right">' . price($obj->credit) . '</td>';
			print '<td align="right">' . price($obj->montant) . '</td>';
			print '<td>' . $obj->sens . '</td>';
			print '<td>' . $obj->code_journal . '</td>';
			print '<td><a href="./card.php?piece_num=' . $obj->piece_num . '">' . img_edit() . '</a></td>';
			print "</tr>\n";
			$i ++;
		}
		print "</table>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

llxFooter();
$db->close();