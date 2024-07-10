<?php
/* Copyright (C) 2006		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2016		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

$search_ref = GETPOST('search_ref', 'alpha');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);	// Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_account = GETPOST('search_account', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$mode = GETPOST('mode', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
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

$arrayfields = array(
	'bc.ref'            => array('label' => "Ref", 'checked' => 1, 'position' => 10),
	'bc.type'			=> array('label' => "Type", 'checked' => 1, 'position' => 20),
	'bc.date_bordereau' => array('label' => "DateCreation", 'checked' => 1, 'position' => 30),
	'ba.label'			=> array('label' => "BankAccount", 'checked' => 1, 'position' => 40),
	'bc.nbcheque'		=> array('label' => "NbOfCheques", 'checked' => 1, 'position' => 50),
	'bc.amount'			=> array('label' => "Amount", 'checked' => 1, 'position' => 60),
	'bc.statut'			=> array('label' => "Status", 'checked' => 1, 'position' => 70)
);
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('chequelist'));
$object = new RemiseCheque($db);

// Security check
$result = restrictedArea($user, 'banque', '', '');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// All tests are required to be compatible with all browsers
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_ref = '';
		$search_amount = '';
		$search_account = '';
		$search_date_startday = '';
		$search_date_startmonth = '';
		$search_date_startyear = '';
		$search_date_endday = '';
		$search_date_endmonth = '';
		$search_date_endyear = '';
		$search_date_start = '';
		$search_date_end = '';
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("ChequeDeposits"), '', 0, 0, '', '', '', 'bodyforlist');

$sql = "SELECT bc.rowid, bc.ref, bc.date_bordereau,";
$sql .= " bc.nbcheque, bc.amount, bc.statut, bc.type,";
$sql .= " ba.rowid as bid, ba.label";

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

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
if ($search_date_start) {
	$sql .= " AND bc.date_bordereau >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
	$sql .= " AND bc.date_bordereau <= '" . $db->idate($search_date_end) . "'";
}

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

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

// Complete request and execute it with limit
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
	if ($search_date_startday) {
		$param .= '&search_date_startday='.urlencode((string) ($search_date_startday));
	}
	if ($search_date_startmonth) {
		$param .= '&search_date_startmonth='.urlencode((string) ($search_date_startmonth));
	}
	if ($search_date_startyear) {
		$param .= '&search_date_startyear='.urlencode((string) ($search_date_startyear));
	}
	if ($search_date_endday) {
		$param .= '&search_date_endday='.urlencode((string) ($search_date_endday));
	}
	if ($search_date_endmonth) {
		$param .= '&search_date_endmonth='.urlencode((string) ($search_date_endmonth));
	}
	if ($search_date_endyear) {
		$param .= '&search_date_endyear='.urlencode((string) ($search_date_endyear));
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}
	if ($search_amount != '') {
		$param .= '&search_amount='.urlencode($search_amount);
	}
	if ($search_account > 0) {
		$param .= '&search_account='.urlencode((string) ($search_account));
	}

	$url = DOL_URL_ROOT.'/compta/paiement/cheque/card.php?action=new';

	$newcardbutton  = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewCheckDeposit'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('banque', 'cheque'));

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


	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("MenuChequeDeposits"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bank_account', 0, $newcardbutton, '', $limit);

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
	$massactionbutton = '';
	if ($massactionbutton) {
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}

	$moreforfilter = '';
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : '').'">';

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center maxwidthsearch actioncolumn">';
		$searchpicto = $form->showFilterButtons('left');
		print $searchpicto;
		print '</td>';
	}

	// Filter: Ref
	if (!empty($arrayfields['bc.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" size="4" name="search_ref" value="' . $search_ref . '">';
		print '</td>';
	}

	// Filter: Type
	if (!empty($arrayfields['bc.type']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Filter: Date
	if (!empty($arrayfields['bc.date_bordereau']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';
	}

	// Filter: Bank account
	if (!empty($arrayfields['ba.label']['checked'])) {
		print '<td class="liste_titre">';
		$form->select_comptes($search_account, 'search_account', 0, '', 1);
		print '</td>';
	}

	// Filter: Number of cheques
	if (!empty($arrayfields['bc.nbcheque']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}

	// Filter: Amount
	if (!empty($arrayfields['bc.amount']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input class="flat maxwidth50" type="text" name="search_amount" value="' . $search_amount . '">';
		print '</td>';
	}

	// Filter: Status (only placeholder)
	if (!empty($arrayfields['bc.statut']['checked'])) {
		print '<td class="liste_titre"></td>';
	}

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center maxwidthsearch actioncolumn">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.ref']['checked'])) {
		print_liste_field_titre($arrayfields['bc.ref']['label'], $_SERVER["PHP_SELF"], "bc.ref", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.type']['checked'])) {
		print_liste_field_titre($arrayfields['bc.type']['label'], $_SERVER["PHP_SELF"], "bc.type", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.date_bordereau']['checked'])) {
		print_liste_field_titre($arrayfields['bc.date_bordereau']['label'], $_SERVER["PHP_SELF"], "bc.date_bordereau", "", $param, 'align="center"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['ba.label']['checked'])) {
		print_liste_field_titre($arrayfields['ba.label']['label'], $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.nbcheque']['checked'])) {
		print_liste_field_titre($arrayfields['bc.nbcheque']['label'], $_SERVER["PHP_SELF"], "bc.nbcheque", "", $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.amount']['checked'])) {
		print_liste_field_titre($arrayfields['bc.amount']['label'], $_SERVER["PHP_SELF"], "bc.amount", "", $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bc.statut']['checked'])) {
		print_liste_field_titre($arrayfields['bc.statut']['label'], $_SERVER["PHP_SELF"], "bc.statut", "", $param, 'class="right"', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
		$totalarray['nbfield']++;
	}

	print "</tr>\n";

	$checkedCount = 0;
	foreach ($arrayfields as $column) {
		if ($column['checked']) {
			$checkedCount++;
		}
	}

	if ($num > 0) {
		$savnbfield = 8;

		$i = 0;
		$totalarray = array();
		$totalarray['nbfield'] = 0;
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

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center"></td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Num ref cheque
				if (!empty($arrayfields['bc.ref']['checked'])) {
					print '<td>';
					print $checkdepositstatic->getNomUrl(1);
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Type
				if (!empty($arrayfields['bc.type']['checked'])) {
					$labelpaymentmode = ($langs->transnoentitiesnoconv("PaymentType".$checkdepositstatic->type) != "PaymentType".$checkdepositstatic->type ? $langs->transnoentitiesnoconv("PaymentType".$checkdepositstatic->type) : $checkdepositstatic->type);
					print '<td>'.dol_escape_htmltag($labelpaymentmode).'</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Date
				if (!empty($arrayfields['bc.date_bordereau']['checked'])) {
					print '<td class="center">'.dol_print_date($db->jdate($objp->date_bordereau), 'dayhour', 'tzuser').'</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Bank
				if (!empty($arrayfields['ba.label']['checked'])) {
					print '<td>';
					if ($objp->bid) {
						print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"), 'account').' '.$objp->label.'</a>';
					} else {
						print '&nbsp;';
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Number of cheques
				if (!empty($arrayfields['bc.nbcheque']['checked'])) {
					print '<td class="right">'.$objp->nbcheque.'</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Amount
				if (!empty($arrayfields['bc.amount']['checked'])) {
					print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
					if (empty($totalarray['val']['amount'])) {
						$totalarray['val']['amount'] = $objp->amount;
					} else {
						$totalarray['val']['amount'] += $objp->amount;
					}
				}

				// Status
				if (!empty($arrayfields['bc.statut']['checked'])) {
					print '<td class="right">';
					print $checkdepositstatic->LibStatut($objp->statut, 5);
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center"></td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				print "</tr>\n";
			}
			$i++;
		}
	} else {
		// If no record found
		if ($num == 0) {
			$colspan = 1;
			foreach ($arrayfields as $key => $val) {
				if (!empty($val['checked'])) {
					$colspan++;
				}
			}
			print '<tr class="oddeven">';
			print '<td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoRecordFound") . "</td>";
			print '</tr>';
		}
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
