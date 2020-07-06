<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013      Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2019 Frédéric France      <frederic.france@netlogic.fr>
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
 *
 */

/**
 * \file        htdocs/accountancy/bookkeeping/thirdparty_lettering_customer.php
 * \ingroup     accountancy
 * \brief       Tab to manage customer lettering
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/lettering.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "accountancy"));

$action     = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm    = GETPOST('confirm', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$socid      = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == - 1) {
	$page = 0;
} // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "")
	$sortorder = "ASC";
if ($sortfield == "")
	$sortfield = "bk.doc_date";

/*
$search_date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));
$search_date_end = dol_mktime(0, 0, 0, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));
//$search_doc_type = GETPOST("search_doc_type", 'alpha');
$search_doc_ref = GETPOST("search_doc_ref", 'alpha');
*/

$lettering = GETPOST('lettering', 'alpha');
if (!empty($lettering)) {
	$action = $lettering;
}

/*
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_date_start = '';
    $search_date_end = '';
	//$search_doc_type = '';
	$search_doc_ref = '';
}
*/

// Security check
$socid = GETPOST("socid", 'int');
// if ($user->socid) $socid=$user->socid;

$lettering = new Lettering($db);
$object = new Societe($db);
$object->id = $socid;
$result = $object->fetch($socid);
if ($result < 0)
{
	setEventMessages($object->error, $object->errors, 'errors');
}


/*
 * Action
 */

if ($action == 'lettering') {
	$result = $lettering->updateLettering($toselect);

	if ($result < 0) {
		setEventMessages('', $lettering->errors, 'errors');
		$error++;
	}
}

/*
if ($action == 'autolettrage') {

	$result = $lettering->letteringThirdparty($socid);

	if ($result < 0) {
		setEventMessages('', $lettering->errors, 'errors');
		$error++;
	}
}
*/

/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $object->name." - ".$langs->trans('TabLetteringCustomer');
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$head = societe_prepare_head($object);

dol_htmloutput_mesg(is_numeric($error) ? '' : $error, $errors, 'error');

dol_fiche_head($head, 'lettering_customer', $langs->trans("ThirdParty"), 0, 'company');

$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom', '', '', 0, '', '', 'arearefnobottom');

dol_fiche_end();

$sql = "SELECT bk.rowid, bk.doc_date, bk.doc_type, bk.doc_ref, ";
$sql .= " bk.subledger_account, bk.numero_compte , bk.label_compte, bk.debit, ";
$sql .= " bk.credit, bk.montant , bk.sens , bk.code_journal , bk.piece_num, bk.lettering_code ";
$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as bk";
$sql .= " WHERE (bk.subledger_account =  '".$object->code_compta."' AND bk.numero_compte = '".$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER."' )";

/*
if (dol_strlen($search_date_start) || dol_strlen($search_date_end)) {
	$sql .= " AND ( bk.doc_date BETWEEN  '" . $db->idate($search_date_start) . "' AND  '" . $db->idate($search_date_end) . "' )";
}
*/

$sql .= ' AND bk.entity IN ('.getEntity('accountingbookkeeping').')';
$sql .= $db->order($sortfield, $sortorder);

$debit = 0;
$credit = 0;
$solde = 0;
// Count total nb of records and calc total sum
$nbtotalofrecords = '';
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}
$nbtotalofrecords = $db->num_rows($resql);

while ($obj = $db->fetch_object($resql)) {
	$debit += $obj->debit;
	$credit += $obj->credit;

	$solde += ($obj->credit - $obj->debit);
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("/accountancy/bookkeeping/thirdparty_lettering_customer.php", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit();
}

$param = '';
$param .= "&socid=".urlencode($socid);

$num = $db->num_rows($resql);

dol_syslog("/accountancy/bookkeeping/thirdparty_lettering_customer.php", LOG_DEBUG);
if ($resql) {
	$i = 0;

    $param = "&socid=".$socid;
	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="POST">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $letteringbutton = '<a class="divButAction"><span class="valignmiddle"><input class="butAction" type="submit" value="lettering" name="lettering" id="lettering"></span></a>';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, $letteringbutton, '', $limit);

    print '<div class="div-table-responsive-no-min">';
    print '<table class="liste centpercent">'."\n";

	/*
    print '<tr class="liste_titre">';
    //print '<td><input type="text" name="search_doc_type" value="' . $search_doc_type . '"></td>';

    // Date
    print '<td class="liste_titre center">';
    print '<div class="nowrap">';
    print $langs->trans('From') . ' ';
    print $form->selectDate($search_date_start, 'date_creation_start', 0, 0, 1);
    print '</div>';
    print '<div class="nowrap">';
    print $langs->trans('to') . ' ';
    print $form->selectDate($search_date_end, 'date_creation_end', 0, 0, 1);
    print '</div>';
    print '</td>';

    // Piece
    print '<td><input type="text" name="search_doc_ref" value="' . $search_doc_ref . '"></td>';

    print '<td colspan="6">&nbsp;</td>';
    print '<td class="right">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print '</tr>';
	*/

	print '<tr class="liste_titre">';
	//print_liste_field_titre("Doctype", $_SERVER["PHP_SELF"], "bk.doc_type", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Docdate", $_SERVER["PHP_SELF"], "bk.doc_date", "", $param, "", $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Piece", $_SERVER["PHP_SELF"], "bk.doc_ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("LabelAccount", $_SERVER["PHP_SELF"], "bk.label_compte", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Debit", $_SERVER["PHP_SELF"], "bk.debit", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Credit", $_SERVER["PHP_SELF"], "bk.credit", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Balancing", $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Codejournal", $_SERVER["PHP_SELF"], "bk.code_journal", "", $param, "", $sortfield, $sortorder, 'center ');
	print_liste_field_titre("LetteringCode", $_SERVER["PHP_SELF"], "bk.lettering_code", "", $param, "", $sortfield, $sortorder, 'center ');
    print_liste_field_titre("", "", "", '', '', "", $sortfield, $sortorder, 'maxwidthsearch center ');
	print "</tr>\n";

	$solde = 0;
	$tmp = '';

    while ($obj = $db->fetch_object($resql)) {
		if ($tmp != $obj->lettering_code || empty($tmp))						$tmp = $obj->lettering_code;
		/*if ($tmp != $obj->lettering_code || empty($obj->lettering_code))*/	$solde += ($obj->credit - $obj->debit);

		print '<tr class="oddeven">';

		//print '<td>' . $obj->doc_type . '</td>' . "\n";
		print '<td class="center">'.dol_print_date($db->jdate($obj->doc_date), 'day').'</td>';
		print '<td>'.$obj->doc_ref.'</td>';
		print '<td>'.$obj->label_compte.'</td>';
		print '<td class="nowrap right">'.price($obj->debit).'</td>';
		print '<td class="nowrap right">'.price($obj->credit).'</td>';
		print '<td class="nowrap right">'.price(round($solde, 2)).'</td>';

		// Journal
        $accountingjournal = new AccountingJournal($db);
        $result = $accountingjournal->fetch('', $obj->code_journal);
        $journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $obj->code_journal);
        print '<td class="center">'.$journaltoshow.'</td>';

        if (empty($obj->lettering_code)) {
            print '<td class="nowrap center"><input type="checkbox" class="flat checkforselect" name="toselect[]" id="toselect[]" value="'.$obj->rowid.'" /></td>';
            print '<td><a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num='.$obj->piece_num.'">';
            print img_edit();
            print '</a></td>'."\n";
        } else {
            print '<td class="center">'.$obj->lettering_code.'</td>';
            print '<td></td>';
        }

		print "</tr>\n";
	}

	print '<tr class="oddeven">';
	print '<td class="right" colspan="3">'.$langs->trans("Total").':</td>'."\n";
	print '<td class="nowrap right"><strong>'.price($debit).'</strong></td>';
	print '<td class="nowrap right"><strong>'.price($credit).'</strong></td>';
	print '<td colspan="4"></td>';
	print "</tr>\n";

	print '<tr class="oddeven">';
	print '<td class="right" colspan="3">'.$langs->trans("Balancing").':</td>'."\n";
	print '<td colspan="2">&nbsp;</td>';
	print '<td class="nowrap right"><strong>'.price($credit - $debit).'</strong></td>';
	print '<td colspan="6"></td>';
	print "</tr>\n";

	print "</table>";

    print '<div class="tabsAction tabsActionNoBottom">'."\n";
    print $letteringbutton;
    print '</div>';

	print "</form>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
