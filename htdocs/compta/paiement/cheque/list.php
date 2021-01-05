<?php
/* Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2016		Juanjo Menent   		<jmenent@2byte.es>
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
 *   \file       htdocs/compta/paiement/cheque/list.php
 *   \ingroup    compta
 *   \brief      Page list of cheque deposits
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'bills'));

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'banque', '', '');

$search_ref = GETPOST('search_ref', 'alpha');
$search_account = GETPOST('search_account', 'int');
$search_amount = GETPOST('search_amount', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "dp";

$year = GETPOST("year");
$month = GETPOST("month");

$form = new Form($db);
$formother = new FormOther($db);
$checkdepositstatic = new RemiseCheque($db);
$accountstatic = new Account($db);


/*
 * Actions
 */

// If click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_ref = '';
	$search_amount = '';
	$search_account = '';
	$year = '';
	$month = '';
}



/*
 * View
 */

llxHeader('', $langs->trans("ChequesReceipts"));

$sql = "SELECT bc.rowid, bc.ref as ref, bc.date_bordereau as dp,";
$sql .= " bc.nbcheque, bc.amount, bc.statut,";
$sql .= " ba.rowid as bid, ba.label";
$sql .= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc,";
$sql .= " ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE bc.fk_bank_account = ba.rowid";
$sql .= " AND bc.entity = ".$conf->entity;

// Search criteria
if ($search_ref)			$sql .= natural_search("bc.ref", $search_ref);
if ($search_account > 0)	$sql .= " AND bc.fk_bank_account=".$search_account;
if ($search_amount)			$sql .= natural_search("bc.amount", price2num($search_amount));
$sql .= dolSqlDateFilter('bc.date_bordereau', 0, $month, $year);

$sql .= $db->order($sortfield, $sortorder);

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
//print "$sql";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;

	$url = DOL_URL_ROOT.'/compta/paiement/cheque/card.php?action=new';
	if (!empty($socid)) $url .= '&socid='.$socid;
	$newcardbutton = dolGetButtonTitle($langs->trans('NewCheckDeposit'), '', 'fa fa-plus-circle', $url, '', $user->rights->banque->cheque);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("MenuChequeDeposits"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bank_account', 0, $newcardbutton, '', $limit);

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	$formother->select_year($year ? $year : -1, 'year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre">';
	$form->select_comptes($search_account, 'search_account', 0, '', 1);
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre right">';
	print '<input class="flat maxwidth50" type="text" name="search_amount" value="'.$search_amount.'">';
	print '</td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "bc.ref", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "dp", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("NbOfCheques", $_SERVER["PHP_SELF"], "bc.nbcheque", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "bc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "bc.statut", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	if ($num > 0)
	{
		while ($i < min($num, $limit))
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';

			// Num ref cheque
			print '<td>';
			$checkdepositstatic->id = $objp->rowid;
			$checkdepositstatic->ref = ($objp->ref ? $objp->ref : $objp->rowid);
			$checkdepositstatic->statut = $objp->statut;
			print $checkdepositstatic->getNomUrl(1);
			print '</td>';

			// Date
			print '<td class="center">'.dol_print_date($db->jdate($objp->dp), 'day').'</td>'; // TODO Use date hour

			// Bank
			print '<td>';
			if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"), 'account').' '.$objp->label.'</a>';
			else print '&nbsp;';
			print '</td>';

			// Number of cheques
			print '<td class="right">'.$objp->nbcheque.'</td>';

			// Amount
			print '<td class="right">'.price($objp->amount).'</td>';

			// Statut
			print '<td class="right">';
			print $checkdepositstatic->LibStatut($objp->statut, 5);
			print '</td>';

			print '<td></td>';

			print "</tr>\n";
			$i++;
		}
	} else {
   		print '<tr class="oddeven">';
   		print '<td colspan="7" class="opacitymedium">'.$langs->trans("None")."</td>";
   		print '</tr>';
	}
	print "</table>";
	print "</div>";
	print "</form>\n";
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
