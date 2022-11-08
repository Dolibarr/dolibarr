<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * 	\file       htdocs/compta/prelevement/orders_list.php
 * 	\ingroup    prelevement
 * 	\brief      Page to list direct debit orders or credit transfer orders
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals'));

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'directdebitcredittransferlist'; // To manage different context of search

$type = GETPOST('type', 'aZ09');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "p.datec";
}

// Get supervariables
$statut = GETPOST('statut', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');

$bon = new BonPrelevement($db);
$hookmanager->initHooks(array('withdrawalsreceiptslist'));

$usercancreate = $user->rights->prelevement->bons->creer;
if ($type == 'bank-transfer') {
	$usercancreate = $user->rights->paymentbybanktransfer->create;
}

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = "";
	$search_amount = "";
}


/*
 * View
 */

llxHeader('', $langs->trans("WithdrawalsReceipts"));

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut, p.datec";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " WHERE p.entity IN (".getEntity('invoice').")";
if ($type == 'bank-transfer') {
	$sql .= " AND p.type = 'bank-transfer'";
} else {
	$sql .= " AND p.type = 'debit-order'";
}
if ($search_ref) {
	$sql .= natural_search("p.ref", $search_ref);
}
if ($search_amount) {
	$sql .= natural_search("p.amount", $search_amount, 1);
}

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($type == 'bank-transfer') {
		$param .= '&amp;type=bank-transfer';
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	$param .= "&statut=".urlencode($statut);

	$selectedfields = '';

	$newcardbutton = '';
	if ($usercancreate) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('NewStandingOrder'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/prelevement/create.php?type='.urlencode($type));
	}

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	$titlekey = "WithdrawalsReceipts";
	$title = $langs->trans("WithdrawalsReceipts");
	if ($type == 'bank-transfer') {
		$titlekey = "BankTransferReceipts";
		$title = $langs->trans("BankTransferReceipts");
	}

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre right"><input type="text" class="flat maxwidth100" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre($titlekey, $_SERVER["PHP_SELF"], "p.ref", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "p.amount", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ')."\n";
	print "</tr>\n";

	$directdebitorder = new BonPrelevement($db);

	if ($num) {
		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($result);

			$directdebitorder->id = $obj->rowid;
			$directdebitorder->ref = $obj->ref;
			$directdebitorder->datec = $obj->datec;
			$directdebitorder->amount = $obj->amount;
			$directdebitorder->statut = $obj->statut;

			print '<tr class="oddeven">';

			print '<td>';
			print $directdebitorder->getNomUrl(1);
			print "</td>\n";

			print '<td class="center">'.dol_print_date($db->jdate($obj->datec), 'day')."</td>\n";

			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";

			print '<td class="right">';
			print $bon->LibStatut($obj->statut, 5);
			print '</td>';

			print '<td class="right"></td>'."\n";

			print "</tr>\n";
			$i++;
		}
	} else {
		print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	print '</form>';

	$db->free($result);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
