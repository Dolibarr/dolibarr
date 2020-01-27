<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *	    \file       htdocs/compta/tva/list.php
 *      \ingroup    tax
 *		\brief      List of VAT payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$search_ref = GETPOST('search_ref', 'int');
$search_label = GETPOST('search_label', 'alpha');
$search_account = GETPOST('search_account', 'int');
$search_dateend_start = dol_mktime(0, 0, 0, GETPOST('search_dateend_startmonth', 'int'), GETPOST('search_dateend_startday', 'int'), GETPOST('search_dateend_startyear', 'int'));
$search_dateend_end = dol_mktime(23, 59, 59, GETPOST('search_dateend_endmonth', 'int'), GETPOST('search_dateend_endday', 'int'), GETPOST('search_dateend_endyear', 'int'));
$search_datepayment_start = dol_mktime(0, 0, 0, GETPOST('search_datepayment_startmonth', 'int'), GETPOST('search_datepayment_startday', 'int'), GETPOST('search_datepayment_startyear', 'int'));
$search_datepayment_end = dol_mktime(23, 59, 59, GETPOST('search_datepayment_endmonth', 'int'), GETPOST('search_datepayment_endday', 'int'), GETPOST('search_datepayment_endyear', 'int'));
$search_amount = GETPOST('search_amount', 'alpha');
$month = GETPOST("month", "int");
$year = GETPOST("year", "int");

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "t.datev";
if (!$sortorder) $sortorder = "DESC";

$filtre = $_GET["filtre"];

if (empty($_REQUEST['typeid']))
{
	$newfiltre = str_replace('filtre=', '', $filtre);
	$filterarray = explode('-', $newfiltre);
	foreach ($filterarray as $val)
	{
		$part = explode(':', $val);
		if ($part[0] == 't.fk_typepayment') $typeid = $part[1];
	}
}
else
{
	$typeid = $_REQUEST['typeid'];
}

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
{
	$search_ref = "";
	$search_label = "";
	$search_dateend_start = '';
	$search_dateend_end = '';
	$search_datepayment_start = '';
	$search_datepayment_end = '';
	$search_account = '';
	$search_amount = "";
	$year = "";
	$month = "";
    $typeid = "";
}


/*
 * View
 */

llxHeader('', $langs->trans("VATPayments"));

$form = new Form($db);
$formother = new FormOther($db);
$tva_static = new Tva($db);
$bankstatic = new Account($db);

$sql = "SELECT t.rowid, t.amount, t.label, t.datev, t.datep, t.fk_typepayment as type, t.num_payment, t.fk_bank, pst.code as payment_code,";
$sql .= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.fk_accountancy_journal, ba.label as blabel";
$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON t.fk_typepayment = pst.id";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON t.fk_bank = b.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql .= " WHERE t.entity IN (".getEntity('tax').")";
if ($search_ref)				$sql .= natural_search("t.rowid", $search_ref);
if ($search_label)				$sql .= natural_search("t.label", $search_label);
if ($search_account > 0)		$sql .= " AND b.fk_account=".$search_account;
if ($search_amount)				$sql .= natural_search("t.amount", price2num(trim($search_amount)), 1);
if ($search_dateend_start)		$sql .= " AND t.datev >= '".$db->idate($search_dateend_start)."'";
if ($search_dateend_end)		$sql .= " AND t.datev <= '".$db->idate($search_dateend_end)."'";
if ($search_datepayment_start)  $sql .= " AND t.datep >= '".$db->idate($search_datepayment_start)."'";
if ($search_datepayment_end)	$sql .= " AND t.datep <= '".$db->idate($search_datepayment_end)."'";
if ($filtre) {
    $filtre = str_replace(":", "=", $filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND t.fk_typepayment=".$typeid;
}
$sql .= $db->order($sortfield, $sortorder);
$totalnboflines = 0;
$result = $db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0;

	$param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
	if ($typeid) $param .= '&amp;typeid='.$typeid;

	$newcardbutton = '';
	if ($user->rights->tax->charges->creer)
	{
        $newcardbutton .= dolGetButtonTitle($langs->trans('NewVATPayment', ($ltt + 1)), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/tva/card.php?action=create');
    }

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("VATPayments"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $totalnboflines, 'title_accountancy', 0, $newcardbutton, '', $limit);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	// Ref
	print '<td class="liste_titre"><input type="text" class="flat" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	// Label
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';
	// Date end period
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $langs->trans('From').' ';
	print $form->selectDate($search_dateend_start ? $search_dateend_start : -1, 'search_dateend_start', 0, 0, 1);
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to').' ';
	print $form->selectDate($search_dateend_end ? $search_dateend_end : -1, 'search_dateend_end', 0, 0, 1);
	print '</div>';
	// Date payment
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $langs->trans('From').' ';
	print $form->selectDate($search_datepayment_start ? $search_datepayment_start : -1, 'search_datepayment_start', 0, 0, 1);
	print '</div>';
	print '<div class="nowrap">';
	print $langs->trans('to').' ';
	print $form->selectDate($search_datepayment_end ? $search_datepayment_end : -1, 'search_datepayment_end', 0, 0, 1);
	print '</div>';
	// Type
	print '<td class="liste_titre left">';
	$form->select_types_paiements($typeid, 'typeid', '', 0, 1, 1, 16);
	print '</td>';
	// Account
	if (!empty($conf->banque->enabled))
    {
	    print '<td class="liste_titre">';
	    $form->select_comptes($search_account, 'search_account', 0, '', 1);
	    print '</td>';
    }
	// Amount
	print '<td class="liste_titre right"><input name="search_amount" class="flat" type="text" size="8" value="'.$search_amount.'"></td>';
    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto = $form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "t.rowid", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "t.label", "", $param, 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre("PeriodEndDate", $_SERVER["PHP_SELF"], "t.datev", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("DatePayment", $_SERVER["PHP_SELF"], "t.datep", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "type", "", $param, '', $sortfield, $sortorder, 'left ');
	if (!empty($conf->banque->enabled)) print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("PayedByThisPayment", $_SERVER["PHP_SELF"], "t.amount", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	while ($i < min($num, $limit))
    {
        $obj = $db->fetch_object($result);

		if ($obj->payment_code <> '')
		{
			$type = '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
		}
		else
		{
			$type = '<td>&nbsp;</td>';
		}

        print '<tr class="oddeven">';

		$tva_static->id = $obj->rowid;
		$tva_static->ref = $obj->rowid;

		// Ref
		print "<td>".$tva_static->getNomUrl(1)."</td>\n";
        // Label
		print "<td>".dol_trunc($obj->label, 40)."</td>\n";
		// Date end period
        print '<td class="center">'.dol_print_date($db->jdate($obj->datev), 'day')."</td>\n";
        // Date payment
        print '<td class="center">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";
        // Type
		print $type;
		// Account
    	if (!empty($conf->banque->enabled))
	    {
	        print '<td>';
	        if ($obj->fk_bank > 0)
			{
				$bankstatic->id = $obj->bid;
				$bankstatic->ref = $obj->bref;
				$bankstatic->number = $obj->bnumber;
				$bankstatic->account_number = $obj->account_number;

				$accountingjournal = new AccountingJournal($db);
				$accountingjournal->fetch($obj->fk_accountancy_journal);
				$bankstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);

				$bankstatic->label = $obj->blabel;
				print $bankstatic->getNomUrl(1);
			}
			else print '&nbsp;';
			print '</td>';
		}
		// Amount
        $total = $total + $obj->amount;
		print '<td class="nowrap right">'.price($obj->amount)."</td>";
	    print "<td>&nbsp;</td>";
        print "</tr>\n";

        $i++;
    }

    $colspan = 5;
    if (!empty($conf->banque->enabled)) $colspan++;
    print '<tr class="liste_total"><td colspan="'.$colspan.'">'.$langs->trans("Total").'</td>';
    print '<td class="right">'.price($total).'</td>';
	print "<td>&nbsp;</td></tr>";

    print "</table>";
    print '</div>';

	print '</form>';

    $db->free($result);
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
