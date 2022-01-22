<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2015       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016       Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/loan/list.php
 *  \ingroup    loan
 *  \brief      Page to list all loans
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';

// Load translation files required by the page
$langs->loadLangs(array("loan", "compta", "banks", "bills"));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'loan', '', '', '');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$loan_static = new Loan($db);
$extrafields = new ExtraFields($db);

if (!$sortfield) $sortfield = "l.rowid";
if (!$sortorder) $sortorder = "DESC";

$search_ref = GETPOST('search_ref', 'int');
$search_label = GETPOST('search_label', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'loanlist'; // To manage different context of search
$optioncss = GETPOST('optioncss', 'alpha');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_ref = "";
		$search_label = "";
		$search_amount = "";
	}
}


/*
 *	View
 */

$now = dol_now();

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';
$title = $langs->trans('Loans');

$sql = "SELECT l.rowid, l.label, l.capital, l.datestart, l.dateend, l.paid,";
$sql .= " SUM(pl.amount_capital) as alreadypaid";
$sql .= " FROM ".MAIN_DB_PREFIX."loan as l LEFT JOIN ".MAIN_DB_PREFIX."payment_loan AS pl";
$sql .= " ON l.rowid = pl.fk_loan";
$sql .= " WHERE l.entity = ".$conf->entity;
if ($search_amount)	$sql .= natural_search("l.capital", $search_amount, 1);
if ($search_ref) 	$sql .= " AND l.rowid = ".$db->escape($search_ref);
if ($search_label)	$sql .= natural_search("l.label", $search_label);
$sql .= " GROUP BY l.rowid, l.label, l.capital, l.paid, l.datestart, l.dateend";
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
{
	$num = $nbtotalofrecords;
} else {
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

if ($resql)
{
	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($search_ref) $param .= "&search_ref=".urlencode($search_ref);
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_amount) $param .= "&search_amount=".urlencode($search_amount);
	if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);

	$url = DOL_URL_ROOT.'/loan/card.php?action=create';
	if (!empty($socid)) $url .= '&socid='.$socid;
	$newcardbutton = dolGetButtonTitle($langs->trans('NewLoan'), '', 'fa fa-plus-circle', $url, '', $user->rights->loan->write);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($langs->trans("Loans"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'money-bill-alt', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Filters lines
	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre"><input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'"></td>';
	print '<td class="liste_titre"><input class="flat" size="12" type="text" name="search_label" value="'.$search_label.'"></td>';
	print '<td class="liste_titre right" ><input class="flat" size="8" type="text" name="search_amount" value="'.$search_amount.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "l.rowid", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "l.label", "", $param, '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre("LoanCapital", $_SERVER["PHP_SELF"], "l.capital", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("DateStart", $_SERVER["PHP_SELF"], "l.datestart", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("DateEnd", $_SERVER["PHP_SELF"], "l.dateend", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "l.paid", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	print "</tr>\n";

	// Loop on record
	// --------------------------------------------------------------------
	$i = 0;
	$totalarray = array();
	while ($i < ($limit ? min($num, $limit) : $num))
	{
		$obj = $db->fetch_object($resql);
		if (empty($obj)) break; // Should not happen

		$loan_static->id = $obj->rowid;
		$loan_static->ref = $obj->rowid;
		$loan_static->label = $obj->label;
		$loan_static->paid = $obj->paid;

		print '<tr class="oddeven">';

		// Ref
		print '<td>'.$loan_static->getNomUrl(1).'</td>';

		// Label
		print '<td>'.dol_trunc($obj->label, 42).'</td>';

		// Capital
		print '<td class="right maxwidth100">'.price($obj->capital).'</td>';

		// Date start
		print '<td class="center width100">'.dol_print_date($db->jdate($obj->datestart), 'day').'</td>';

		// Date end
		print '<td class="center width100">'.dol_print_date($db->jdate($obj->dateend), 'day').'</td>';

		print '<td class="right nowrap">';
		print $loan_static->LibStatut($obj->paid, 5, $obj->alreadypaid);
		print '</td>';

		print '<td></td>';

		print "</tr>\n";

		$i++;
	}

	// If no record found
	if ($num == 0)
	{
		$colspan = 7;
		//foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
