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

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'bills'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', '', '');

$search_ref = GETPOST('search_ref', 'alpha');
$search_account = GETPOST('search_account', 'int');
$search_amount = GETPOST('search_amount', 'alpha');
$mode = GETPOST('mode', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
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
	$sortfield = "bc.date_bordereau";
}

$year = GETPOST("year");
$month = GETPOST("month");
$optioncss = GETPOST('optioncss', 'alpha');
$view = GETPOST("view", 'alpha');

$form = new Form($db);
$formother = new FormOther($db);
$checkdepositstatic = new RemiseCheque($db);
$accountstatic = new Account($db);

// List of payment mode to support
// Example: BANK_PAYMENT_MODES_FOR_DEPOSIT_MANAGEMENT = 'CHQ','TRA'
$arrayofpaymentmodetomanage = explode(',', getDolGlobalString('BANK_PAYMENT_MODES_FOR_DEPOSIT_MANAGEMENT', 'CHQ'));

$arrayoflabels = array();
foreach ($arrayofpaymentmodetomanage as $key => $val) {
	$labelval = ($langs->trans("PaymentType".$val) != "PaymentType".$val ? $langs->trans("PaymentType".$val) : $val);
	$arrayoflabels[$key] = $labelval;
}


/*
 * Actions
 */

// If click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = '';
	$search_amount = '';
	$search_account = '';
	$year = '';
	$month = '';
}



/*
 * View
 */

llxHeader('', $langs->trans("ChequeDeposits"));

$sql = "SELECT bc.rowid, bc.ref, bc.date_bordereau,";
$sql .= " bc.nbcheque, bc.amount, bc.statut, bc.type,";
$sql .= " ba.rowid as bid, ba.label";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc,";
$sql .= " ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE bc.fk_bank_account = ba.rowid";
$sql .= " AND bc.entity = ".((int) $conf->entity);

// Search criteria
if ($search_ref) {
	$sql .= natural_search("bc.ref", $search_ref);
}
if ($search_account > 0) {
	$sql .= " AND bc.fk_bank_account = ".((int) $search_account);
}
if ($search_amount) {
	$sql .= natural_search("bc.amount", price2num($search_amount));
}
$sql .= dolSqlDateFilter('bc.date_bordereau', 0, $month, $year);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print "$sql";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	$param = '';
	if (!empty($mode)) {
		$param .= '&mode='.urlencode($mode);
	}
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.$contextpage;
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	$url = DOL_URL_ROOT.'/compta/paiement/cheque/card.php?action=new';
	if (!empty($socid)) {
		$url .= '&socid='.$socid;
	}
	$newcardbutton  = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewCheckDeposit'), '', 'fa fa-plus-circle', $url, '', $user->rights->banque->cheque);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';


	print_barre_liste($langs->trans("MenuChequeDeposits"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bank_account', 0, $newcardbutton, '', $limit);

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	// Type
	print '<td class="liste_titre">';
	print '</td>';
	print '<td class="liste_titre center">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
		print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
	}
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	print $formother->selectyear($year ? $year : -1, 'year', 1, 20, 5);
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
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "bc.type", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "bc.date_bordereau", "", $param, 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
	print_liste_field_titre("NbOfCheques", $_SERVER["PHP_SELF"], "bc.nbcheque", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "bc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "bc.statut", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	if ($num > 0) {
		$savnbfield = 8;

		$imaxinloop = ($limit ? min($num, $limit) : $num);
		while ($i < $imaxinloop) {
			$objp = $db->fetch_object($resql);

			$checkdepositstatic->id = $objp->rowid;
			$checkdepositstatic->ref = ($objp->ref ? $objp->ref : $objp->rowid);
			$checkdepositstatic->statut = $objp->statut;
			$checkdepositstatic->nbcheque = $objp->nbcheque;
			$checkdepositstatic->amount = $objp->amount;
			$checkdepositstatic->date_bordereau = $objp->date_bordereau;
			$checkdepositstatic->type = $objp->type;

			$account = new Account($db);
			$account->fetch($objp->bid);
			$checkdepositstatic->account_id = $account->getNomUrl(1);

			if ($mode == 'kanban') {
				if ($i == 0) {
					print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
					print '<div class="box-flex-container kanban">';
				}
				// Output Kanban
				print $checkdepositstatic->getKanbanView('', array('selected' => in_array($checkdepositstatic->id, $arrayofselected)));
				if ($i == ($imaxinloop - 1)) {
					print '</div>';
					print '</td></tr>';
				}
			} else {
				print '<tr class="oddeven">';

				// Num ref cheque
				print '<td>';
				print $checkdepositstatic->getNomUrl(1);
				print '</td>';

				// Type
				$labelpaymentmode = ($langs->transnoentitiesnoconv("PaymentType".$checkdepositstatic->type) != "PaymentType".$checkdepositstatic->type ? $langs->transnoentitiesnoconv("PaymentType".$checkdepositstatic->type) : $checkdepositstatic->type);
				print '<td>'.dol_escape_htmltag($labelpaymentmode).'</td>';

				// Date
				print '<td class="center">'.dol_print_date($db->jdate($objp->date_bordereau), 'dayhour', 'tzuser').'</td>';

				// Bank
				print '<td>';
				if ($objp->bid) {
					print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"), 'account').' '.$objp->label.'</a>';
				} else {
					print '&nbsp;';
				}
				print '</td>';

				// Number of cheques
				print '<td class="right">'.$objp->nbcheque.'</td>';

				// Amount
				print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';

				// Statut
				print '<td class="right">';
				print $checkdepositstatic->LibStatut($objp->statut, 5);
				print '</td>';

				print '<td></td>';

				print "</tr>\n";
			}
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
