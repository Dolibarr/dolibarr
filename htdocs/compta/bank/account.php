<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@@2byte.es>
 * Copyright (C) 2012-2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2011-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2016       Neil Orley			<neil.orley@oeris.fr>
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
 * \file htdocs/compta/bank/account.php
 * \ingroup banque
 * \brief List of details of bank transactions for an account
 */
require ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");
$langs->load("companies");
$langs->load("salaries");
$langs->load("loan");
$langs->load("donations");
$langs->load("trips");

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('account', 'int'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account&bank_account', '', '', $fieldtype);

$paiementtype = GETPOST('paiementtype', 'alpha', 3);
$req_nb = GETPOST("req_nb", '', 3);
$thirdparty = GETPOST("thirdparty", '', 3);
$req_desc = GETPOST("req_desc", '', 3);
$req_debit = GETPOST("req_debit", '', 3);
$req_credit = GETPOST("req_credit", '', 3);

$req_stdtmonth = GETPOST('req_stdtmonth', 'int');
$req_stdtday = GETPOST('req_stdtday', 'int');
$req_stdtyear = GETPOST('req_stdtyear', 'int');
$req_stdt = dol_mktime(0, 0, 0, $req_stdtmonth, $req_stdtday, $req_stdtyear);
$req_enddtmonth = GETPOST('req_enddtmonth', 'int');
$req_enddtday = GETPOST('req_enddtday', 'int');
$req_enddtyear = GETPOST('req_enddtyear', 'int');
$req_enddt = dol_mktime(23, 59, 59, $req_enddtmonth, $req_enddtday, $req_enddtyear);

$limit = GETPOST('limit', 'int');
$page = GETPOST('page', 'int');
if ($page == -1) {
	$page = 0;
}

$object = new Account($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$paiementtype = "";
	$req_nb = "";
	$thirdparty = "";
	$req_desc = "";
	$req_debit = "";
	$req_credit = "";
	$req_stdtmonth = "";
	$req_stdtday = "";
	$req_stdtyear = "";
	$req_stdt = "";
	$req_enddtmonth = "";
	$req_enddtday = "";
	$req_enddtyear = "";
	$req_enddt = "";
}

/*
 * Action
 */
$dateop = - 1;

if ($action == 'add' && $id && ! isset($_POST["cancel"]) && $user->rights->banque->modifier) {
	$error = 0;

	if (price2num($_POST["credit"]) > 0) {
		$amount = price2num($_POST["credit"]);
	} else {
		$amount = - price2num($_POST["debit"]);
	}

	$dateop = dol_mktime(12, 0, 0, $_POST["opmonth"], $_POST["opday"], $_POST["opyear"]);
	$operation = $_POST["operation"];
	$num_chq = $_POST["num_chq"];
	$label = $_POST["label"];
	$cat1 = $_POST["cat1"];

	if (! $dateop) {
		$error ++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Date")), null, 'errors');
	}
	if (! $operation) {
		$error ++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Type")), null, 'errors');
	}
	if (! $amount) {
		$error ++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Amount")), null, 'errors');
	}

	if (! $error) {
		$object->fetch($id);
		$insertid = $object->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);
		if ($insertid > 0) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . "&action=addline");
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		$action = 'addline';
	}
}
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->banque->modifier) {
	$accline = new AccountLine($db);
	$result = $accline->fetch(GETPOST("rowid"));
	$result = $accline->delete();
}

/*
 * View
 */

llxHeader('', $langs->trans("FinancialAccount") . '-' . $langs->trans("Transactions"));

$societestatic = new Societe($db);
$userstatic = new User($db);
$chargestatic = new ChargeSociales($db);
$loanstatic = new Loan($db);
$memberstatic = new Adherent($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$donstatic = new Don($db);
$expensereportstatic = new ExpenseReport($db);
$bankstatic = new Account($db);
$banklinestatic = new AccountLine($db);

$form = new Form($db);

if ($id > 0 || ! empty($ref)) {
	if ($limit) {
		$viewline = $limit;
	} else {
		$viewline = empty($conf->global->MAIN_SIZE_LISTE_LIMIT) ? 20 : $conf->global->MAIN_SIZE_LISTE_LIMIT;
		$limit = empty($conf->global->MAIN_SIZE_LISTE_LIMIT) ? 20 : $conf->global->MAIN_SIZE_LISTE_LIMIT;
	}

	$result = $object->fetch($id, $ref);

	// Load bank groups
	require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
	$bankcateg = new BankCateg($db);
	$options = array();

	foreach ( $bankcateg->fetchAll() as $bankcategory ) {
		$options[$bankcategory->id] = $bankcategory->label;
	}

	// Definition de sql_rech et param
	$param = '';
	$sql_rech = '';
	$mode_search = 0;
	if ($req_nb) {
		$sql_rech .= " AND b.num_chq LIKE '%" . $db->escape($req_nb) . "%'";
		$param .= '&amp;req_nb=' . urlencode($req_nb);
		$mode_search = 1;
	}
	if ($req_desc) {
		$sql_rech .= " AND b.label LIKE '%" . $db->escape($req_desc) . "%'";
		$param .= '&amp;req_desc=' . urlencode($req_desc);
		$mode_search = 1;
	}
	if ($req_debit != '') {
		$sql_rech .= " AND b.amount = -" . price2num($req_debit);
		$param .= '&amp;req_debit=' . urlencode($req_debit);
		$mode_search = 1;
	}
	if ($req_credit != '') {
		$sql_rech .= " AND b.amount = " . price2num($req_credit);
		$param .= '&amp;req_credit=' . urlencode($req_credit);
		$mode_search = 1;
	}
	if ($thirdparty) {
		$sql_rech .= " AND s.nom LIKE '%" . $db->escape($thirdparty) . "%'";
		$param .= '&amp;thirdparty=' . urlencode($thirdparty);
		$mode_search = 1;
	}
	if ($paiementtype) {
		$sql_rech .= " AND b.fk_type = '" . $db->escape($paiementtype) . "'";
		$param .= '&amp;paiementtype=' . urlencode($paiementtype);
		$mode_search = 1;
	}

	if ($req_stdt && $req_enddt) {
		$sql_rech .= " AND (b.datev BETWEEN '" . $db->escape($db->idate($req_stdt)) . "' AND '" . $db->escape($db->idate($req_enddt)) . "')";
		$param .= '&amp;req_stdtmonth=' . $req_stdtmonth . '&amp;req_stdtyear=' . $req_stdtyear . '&amp;req_stdtday=' . $req_stdtday;
		$param .= '&amp;req_enddtmonth=' . $req_enddtmonth . '&amp;req_enddtday=' . $req_enddtday . '&amp;req_enddtyear=' . $req_enddtyear;
		$mode_search = 1;
	} elseif ($req_stdt) {
		$sql_rech .= " AND b.datev >= '" . $db->escape($db->idate($req_stdt)) . "'";
		$param .= '&amp;req_stdtmonth=' . $req_stdtmonth . '&amp;req_stdtyear=' . $req_stdtyear . '&amp;req_stdtday=' . $req_stdtday;
		$mode_search = 1;
	} elseif ($req_enddt) {
		$sql_rech .= " AND b.datev <= '" . $db->escape($db->idate($req_enddt)) . "'";
		$param .= '&amp;req_enddtmonth=' . $req_enddtmonth . '&amp;req_enddtday=' . $req_enddtday . '&amp;req_enddtyear=' . $req_enddtyear;
		$mode_search = 1;
	}

	$sql = "SELECT count(*) as total";
	$sql .= " FROM " . MAIN_DB_PREFIX . "bank_account as ba";
	$sql .= ", " . MAIN_DB_PREFIX . "bank as b";
	if ($mode_search) {
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON bu.url_id = s.rowid";
	}
	$sql .= " WHERE b.fk_account = " . $object->id;
	$sql .= " AND b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (" . getEntity('bank_account', 1) . ")";
	$sql .= $sql_rech;

	dol_syslog("account.php count transactions -", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$obj = $db->fetch_object($result);
		$total_lines = $obj->total;

		$db->free($result);
	} else {
		dol_print_error($db);
	}

	// Display the last page first
	if ($page == '') {
		$page = ceil(($total_lines / $limit) - 1);
		if ($page < 0) {
			$page = 0;
		}
	}
	// Reset page number to 0 if the number of line displayed is less to the search limit
	elseif ($page > ceil(($total_lines / $limit) - 1)) {
		$page = 0;
	}
	$limitsql = $limit;
	// Count the number of lines to display
	$nbline = $total_lines - ($limit * $page + 1);

	// Onglets
	$head = bank_prepare_head($object);
	dol_fiche_head($head, 'journal', $langs->trans("FinancialAccount"), 0, 'account');

	print '<table class="border" width="100%">';

	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/bank/index.php">' . $langs->trans("BackToList") . '</a>';

	// Ref
	print '<tr><td width="25%">' . $langs->trans("Ref") . '</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref');
	print '</td></tr>';

	// Label
	print '<tr><td>' . $langs->trans("Label") . '</td>';
	print '<td colspan="3">' . $object->label . '</td></tr>';

	print '</table>';

	dol_fiche_end();

	/*
	 * Buttons actions
	 */

	if ($action != 'delete') {
		print '<div class="tabsAction">';

		if ($action != 'addline') {
			if (empty($conf->global->BANK_DISABLE_DIRECT_INPUT)) {
				if (empty($conf->accounting->enabled)) {
					if ($user->rights->banque->modifier) {
						print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=addline&amp;id=' . $object->id . '&amp;page=' . $page . ($limit ? '&amp;limit=' . $limit : '') . '">' . $langs->trans("AddBankRecord") . '</a>';
					} else {
						print '<a class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '" href="#">' . $langs->trans("AddBankRecord") . '</a>';
					}
				} else {
					print '<a class="butActionRefused" title="' . $langs->trans("FeatureDisabled") . '" href="#">' . $langs->trans("AddBankRecord") . '</a>';
				}
			} else {
				print '<a class="butActionRefused" title="' . $langs->trans("FeatureDisabled") . '" href="#">' . $langs->trans("AddBankRecord") . '</a>';
			}
		}

		if ($object->canBeConciliated() > 0) {
			// If not cash account and can be reconciliate
			if ($user->rights->banque->consolidate) {
				print '<a class="butAction" href="' . DOL_URL_ROOT . '/compta/bank/rappro.php?account=' . $object->id . ($limit ? '&amp;limit=' . $limit : '') . '">' . $langs->trans("Conciliate") . '</a>';
			} else {
				print '<a class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '" href="#">' . $langs->trans("Conciliate") . '</a>';
			}
		}

		if (empty($conf->global->BANK_EXPORT_SEPARATOR)) {
			print '<a class="butActionRefused" title="' . $langs->trans("ConfigurationError") . '" href="#">' . $langs->trans("FullExport") . '</a>';
		} else {
			print '<a class="butAction" target="_blank" href="' . DOL_URL_ROOT . '/compta/bank/account_export.php?action=export&amp;id=' . $object->id .'">' . $langs->trans("FullExport") . '</a>';
		}

		print '</div>';
	}

	print '<br>';

	/**
	 * Search form
	 */
	$param .= '&amp;account=' . $object->id . '&amp;limit=' . $limit;

	// Confirmation delete
	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteTransaction');
		print $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;rowid=' . GETPOST("rowid"), $langs->trans('DeleteTransaction'), $text, 'confirm_delete');
	}

	// Define transaction list navigation string
	print '<form action="' . $_SERVER["PHP_SELF"] . '" name="newpage" method="POST">'; //

	print '<input type="hidden" name="token"        value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action"       value="add">';
	print '<input type="hidden" name="paiementtype" value="' . $paiementtype . '">';
	print '<input type="hidden" name="req_nb"       value="' . $req_nb . '">';
	print '<input type="hidden" name="req_desc"     value="' . $req_desc . '">';
	print '<input type="hidden" name="req_debit"    value="' . $req_debit . '">';
	print '<input type="hidden" name="req_credit"   value="' . $req_credit . '">';
	print '<input type="hidden" name="thirdparty"   value="' . $thirdparty . '">';
	print '<input type="hidden" name="page"      	value="' . $page . '">';
	print '<input type="hidden" name="id"           value="' . $object->id . '">';
	print '<input type="hidden" name="req_stdtmonth"     value="' . $req_stdtmonth . '">';
	print '<input type="hidden" name="req_stdtyear"     value="' . $req_stdtyear . '">';
	print '<input type="hidden" name="req_stdtday"     value="' . $req_stdtday . '">';
	print '<input type="hidden" name="req_enddtmonth"     value="' . $req_enddtmonth . '">';
	print '<input type="hidden" name="req_enddtday"     value="' . $req_enddtday . '">';
	print '<input type="hidden" name="req_enddtyear"     value="' . $req_enddtyear . '">';

	// Navigation controls for pagination
	print_barre_liste($langs->trans("FinancialAccount") . '-' . $langs->trans("Transactions"), $page, $_SERVER["PHP_SELF"], $param, '', '', '', $nbline, $total_lines, 'title_generic.png', 0, '', '', $limit, 0);

	if ($action != 'addline' && $action != 'delete') {
		print '<div class="floatright">' . $navig . '</div>';
	}

	// Form to add a transaction with no invoice
	if ($user->rights->banque->modifier && $action == 'addline') {
		print load_fiche_titre($langs->trans("AddBankRecordLong"), '', '');

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>' . $langs->trans("Date") . '</td>';
		print '<td>&nbsp;</td>';
		print '<td>' . $langs->trans("Type") . '</td>';
		print '<td>' . $langs->trans("Numero") . '</td>';
		print '<td colspan="2">' . $langs->trans("Description") . '</td>';
		print '<td align=right>' . $langs->trans("Debit") . '</td>';
		print '<td align=right>' . $langs->trans("Credit") . '</td>';
		print '<td colspan="2" align="center">&nbsp;</td>';
		print '</tr>';

		print '<tr ' . $bc[false] . '>';
		print '<td class="nowrap" colspan="2">';
		$form->select_date($dateop, 'op', 0, 0, 0, 'transaction');
		print '</td>';
		print '<td class="nowrap">';
		$form->select_types_paiements((GETPOST('operation') ? GETPOST('operation') : ($object->courant == Account::TYPE_CASH ? 'LIQ' : '')), 'operation', '1,2', 2, 1);
		print '</td><td>';
		print '<input name="num_chq" class="flat" type="text" size="4" value="' . GETPOST("num_chq") . '"></td>';
		print '<td colspan="2">';
		print '<input name="label" class="flat" type="text" size="24"  value="' . GETPOST("label") . '">';
		if ($options) {
			print '<br>' . $langs->trans("Rubrique") . ': ';
			print Form::selectarray('cat1', $options, GETPOST('cat1'), 1);
		}
		print '</td>';
		print '<td align=right><input name="debit" class="flat" type="text" size="4" value="' . GETPOST("debit") . '"></td>';
		print '<td align=right><input name="credit" class="flat" type="text" size="4" value="' . GETPOST("credit") . '"></td>';
		print '<td colspan="2" align="center">';
		print '<input type="submit" name="save" class="button" value="' . $langs->trans("Add") . '"><br>';
		print '<input type="submit" name="cancel" class="button" value="' . $langs->trans("Cancel") . '">';
		print '</td></tr>';
		print '</table>';
		print '</form>';
		print '<br>';
	}

	/*
	 * Show list of bank transactions
	 */

	print '<table class="noborder" width="100%">';

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Date") . '</td>';
	print '<td>' . $langs->trans("Value") . '</td>';
	print '<td>' . $langs->trans("Type") . '/' . $langs->trans("Numero") . '</td>';
	//print '<td>' . $langs->trans("Numero") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td>' . $langs->trans("ThirdParty") . '</td>';
	print '<td align="right">' . $langs->trans("Debit") . '</td>';
	print '<td align="right">' . $langs->trans("Credit") . '</td>';
	print '<td align="right" width="80">' . $langs->trans("BankBalance") . '</td>';
	print '<td align="center" width="60">';
	if ($object->canBeConciliated() > 0) {
		print $langs->trans("AccountStatementShort");
	} else {
		print '&nbsp;';
	}
	print '</td></tr>';

	print '<form action="' . $_SERVER["PHP_SELF"] . '?' . $param . '" name="search" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="search">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';

	$period_filter .= $langs->trans('From') . '&nbsp;' . $form->select_date($req_stdt, 'req_stdt', 0, 0, 1, null, 1, 0, 1);
	$period_filter .= '&nbsp;';
	$period_filter .= $langs->trans('to') . '&nbsp;' . $form->select_date($req_enddt, 'req_enddt', 0, 0, 1, null, 1, 0, 1);

	print '<tr class="liste_titre">';
	print '<td colspan="2">' . $period_filter . '</td>';
	print '<td>';
	// $filtertype=array('TIP'=>'TIP','PRE'=>'PRE',...)
	$filtertype = '';
	$form->select_types_paiements($paiementtype, 'paiementtype', $filtertype, 2, 1, 1, 8);
	print "&nbsp;/&nbsp;".'<input type="text" class="flat" name="req_nb" value="' . $req_nb . '" size="2"></td>';
	print '<td><input type="text" class="flat" name="req_desc" value="' . $req_desc . '" size="24"></td>';
	print '<td><input type="text" class="flat" name="thirdparty" value="' . $thirdparty . '" size="14"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_debit" value="' . $req_debit . '" size="4"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_credit" value="' . $req_credit . '" size="4"></td>';
	print '<td align="center">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	$searchpitco = $form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';
	print "</tr>\n";

	/*
	 * Another solution
	 * create temporary table solde type=heap select amount from llx_bank limit 100 ;
	 * select sum(amount) from solde ;
	 */

	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql .= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_bordereau,";
	$sql .= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
	if ($mode_search) {
		$sql .= ", s.rowid as socid, s.nom as thirdparty";
	}
	/*
	 if ($mode_search && ! empty($conf->adherent->enabled))
	 {

	 }
	 if ($mode_search && ! empty($conf->tax->enabled))
	 {

	 }
	 */
	$sql .= " FROM " . MAIN_DB_PREFIX . "bank_account as ba";
	$sql .= ", " . MAIN_DB_PREFIX . "bank as b";
	if ($mode_search) {
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON bu1.url_id = s.rowid";
	}
	if ($mode_search && ! empty($conf->tax->enabled)) {
		// VAT
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='payment_vat'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "tva as t ON bu2.url_id = t.rowid";

		// Salary payment
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='payment_salary'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "payment_salary as sal ON bu3.url_id = sal.rowid";
	}
	if ($mode_search && ! empty($conf->adherent->enabled)) {
		// TODO Mettre jointure sur adherent pour recherche sur un adherent
		// $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='company'";
		// $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu3.url_id = s.rowid";
	}
	$sql .= " WHERE b.fk_account=" . $object->id;
	$sql .= " AND b.fk_account = ba.rowid";
	$sql .= " AND ba.entity IN (" . getEntity('bank_account', 1) . ")";
	$sql .= $sql_rech;
	$sql .= $db->order("b.datev, b.datec", "ASC"); // We add date of creation to have correct order when everything is done the same day

	// Get Sub Total of all previous page : this is needed to display a consistent balance when page is > 0
	if ($page > 0) {
		$subtotal_sql = "SELECT sum(amount) as sous_total";
		$subtotal_sql .= " FROM (" . $sql . " LIMIT 0, " . ($limitsql * $page) . ") as sub_total";
		$result = $db->query($subtotal_sql);
		if ($result) {
			$objp = $db->fetch_object($result);
			$sous_total = $objp->sous_total;
		}
	} else {
		$sous_total = 0;
	}

	// Set the search limit
	$sql .= " LIMIT " . ($page * $limitsql) . ", " . $limitsql . ";";

	dol_syslog("account.php get transactions -", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {

		$now = dol_now();
		$nows = dol_print_date($now, '%Y%m%d');

		// $form->load_cache_types_paiements();
		// $form->cache_types_paiements

		$var = true;

		$num = $db->num_rows($result);
		$i = 0;
		$total = $sous_total;
		$sep = - 1;
		$total_deb = 0;
		$total_cred = 0;

		while ( $i < $num ) {
			$objp = $db->fetch_object($result);
			$total = price2num($total + $objp->amount, 'MT');
			if ($i >= ($viewline * (($totalPages - $page) - 1))) {
				$var = ! $var;

				// Is it a transaction in future ?
				$dos = dol_print_date($db->jdate($objp->do), '%Y%m%d');
				// print "dos=".$dos." nows=".$nows;
				if ($dos < $nows)
					$sep = 0; // 0 means there was at least one line before current date
				if ($dos > $nows && ! $sep) // We have found a line in future and we already found on line before current date
				{
					$sep = 1;
					print '<tr class="liste_total"><td colspan="7">';
					print $langs->trans("CurrentBalance");
					print ' ' . $object->currency_code . '</td>';
					print '<td align="right" class="nowrap"><b>' . price($total - $objp->amount) . '</b></td>';
					print "<td>&nbsp;</td>";
					print '</tr>';
				}

				print '<tr ' . $bc[$var] . '>';

				print '<td class="nowrap">' . dol_print_date($db->jdate($objp->do), "day") . "</td>\n";

				print '<td class="nowrap">' . dol_print_date($db->jdate($objp->dv), "day");
				print "</td>\n";

				// Payment type
				print '<td class="nowrap">';
				$label = ($langs->trans("PaymentTypeShort" . $objp->fk_type) != "PaymentTypeShort" . $objp->fk_type) ? $langs->trans("PaymentTypeShort" . $objp->fk_type) : $objp->fk_type;

				if ($objp->fk_type == 'SOLD')
					$label = '&nbsp;';
				if ($objp->fk_type == 'CHQ' && $objp->fk_bordereau > 0) {
					dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
					$bordereaustatic = new RemiseCheque($db);
					$bordereaustatic->id = $objp->fk_bordereau;
					$label .= ' ' . $bordereaustatic->getNomUrl(2);
				}
				print $label;

				// Add links to payment after Payment type
				$links = $object->get_url($objp->rowid);
				foreach ( $links as $key => $val ) {
					if ($links[$key]['type'] == 'payment') {
						$paymentstatic->id = $links[$key]['url_id'];
						$paymentstatic->ref = $links[$key]['url_id'];
						print ' ' . $paymentstatic->getNomUrl(2);
					} elseif ($links[$key]['type'] == 'payment_supplier') {
						$paymentsupplierstatic->id = $links[$key]['url_id'];
						$paymentsupplierstatic->ref = $links[$key]['url_id'];
						print ' ' . $paymentsupplierstatic->getNomUrl(2);
					} elseif ($links[$key]['type'] == 'payment_sc') {
						print '<a href="' . DOL_URL_ROOT . '/compta/payment_sc/card.php?id=' . $links[$key]['url_id'] . '">';
						print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
						// print $langs->trans("SocialContributionPayment");
						print '</a>';
					} elseif ($links[$key]['type'] == 'payment_vat') {
						$paymentvatstatic->id = $links[$key]['url_id'];
						$paymentvatstatic->ref = $links[$key]['url_id'];
						print ' ' . $paymentvatstatic->getNomUrl(2);
					} elseif ($links[$key]['type'] == 'payment_salary') {
						$paymentsalstatic->id = $links[$key]['url_id'];
						$paymentsalstatic->ref = $links[$key]['url_id'];
						print ' ' . $paymentsalstatic->getNomUrl(2);
					} elseif ($links[$key]['type'] == 'payment_loan') {
						print '<a href="' . DOL_URL_ROOT . '/loan/payment/card.php?id=' . $links[$key]['url_id'] . '">';
						print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
						print '</a>';
					} elseif ($links[$key]['type'] == 'payment_donation') {
						print '<a href="' . DOL_URL_ROOT . '/don/payment/card.php?id=' . $links[$key]['url_id'] . '">';
						print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
						print '</a>';
					} elseif ($links[$key]['type'] == 'payment_expensereport') {
						print '<a href="' . DOL_URL_ROOT . '/expensereport/payment/card.php?id=' . $links[$key]['url_id'] . '">';
						print ' ' . img_object($langs->trans('ShowPayment'), 'payment') . ' ';
						print '</a>';
					} elseif ($links[$key]['type'] == 'banktransfert') {
						// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
						if ($objp->amount > 0) {
							$banklinestatic->fetch($links[$key]['url_id']);
							$bankstatic->id = $banklinestatic->fk_account;
							$bankstatic->label = $banklinestatic->bank_account_label;
							print ' (' . $langs->trans("TransferFrom") . ' ';
							print $bankstatic->getNomUrl(1, 'transactions');
							print ' ' . $langs->trans("toward") . ' ';
							$bankstatic->id = $objp->bankid;
							$bankstatic->label = $objp->bankref;
							print $bankstatic->getNomUrl(1, '');
							print ')';
						} else {
							$bankstatic->id = $objp->bankid;
							$bankstatic->label = $objp->bankref;
							print ' (' . $langs->trans("TransferFrom") . ' ';
							print $bankstatic->getNomUrl(1, '');
							print ' ' . $langs->trans("toward") . ' ';
							$banklinestatic->fetch($links[$key]['url_id']);
							$bankstatic->id = $banklinestatic->fk_account;
							$bankstatic->label = $banklinestatic->bank_account_label;
							print $bankstatic->getNomUrl(1, 'transactions');
							print ')';
						}
						// var_dump($links);
					} elseif ($links[$key]['type'] == 'company') {
					} elseif ($links[$key]['type'] == 'user') {
					} elseif ($links[$key]['type'] == 'member') {
					} elseif ($links[$key]['type'] == 'sc') {
					} else {
						// Show link with label $links[$key]['label']
						if (! empty($objp->label) && ! empty($links[$key]['label']))
							print ' - ';
							print '<a href="' . $links[$key]['url'] . $links[$key]['url_id'] . '">';
							if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
								// Label generique car entre parentheses. On l'affiche en le traduisant
								if ($reg[1] == 'paiement')
									$reg[1] = 'Payment';
									print ' ' . $langs->trans($reg[1]);
							} else {
								print ' ' . $links[$key]['label'];
							}
							print '</a>';
					}
				}

				// Num editable
				print ($objp->num_chq ? ' '.$objp->num_chq : "");
				print "</td>";



				// Description
				print '<td>';
				// Show generic description
				if (preg_match('/^\((.*)\)$/i', $objp->label, $reg)) {
					// Generic description because between (). We show it after translating.
					print $langs->trans($reg[1]);
				} else {
					print dol_trunc($objp->label, 60);
				}

				// Add links to invoices after description for customers and suppliers only
				$links = $object->get_url($objp->rowid);
				foreach ( $links as $key => $val ) {
					if ($links[$key]['type'] == 'payment') {
						$paymentstatic->id = $objp->rowid;
						print ' ' . $paymentstatic->getInvoiceUrl(1);
					} elseif ($links[$key]['type'] == 'payment_supplier') {
						$paymentsupplierstatic->id = $objp->rowid;
						print ' ' . $paymentsupplierstatic->getInvoiceUrl(1);
					}
				}
				print '</td>';

				// Add third party column
				print '<td>';
				foreach ( $links as $key => $val ) {
					if ($links[$key]['type'] == 'company') {
						$societestatic->id = $links[$key]['url_id'];
						$societestatic->name = $links[$key]['label'];
						print $societestatic->getNomUrl(1, '', 16);
					} else if ($links[$key]['type'] == 'user') {
						$userstatic->id = $links[$key]['url_id'];
						$userstatic->lastname = $links[$key]['label'];
						print $userstatic->getNomUrl(1, '');
					} else if ($links[$key]['type'] == 'sc') {
						// sc=old value
						$chargestatic->id = $links[$key]['url_id'];
						if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
							if ($reg[1] == 'socialcontribution')
								$reg[1] = 'SocialContribution';
							$chargestatic->lib = $langs->trans($reg[1]);
						} else {
							$chargestatic->lib = $links[$key]['label'];
						}
						$chargestatic->ref = $chargestatic->lib;
						print $chargestatic->getNomUrl(1, 16);
					} else if ($links[$key]['type'] == 'loan') {
						$loanstatic->id = $links[$key]['url_id'];
						if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
							if ($reg[1] == 'loan')
								$reg[1] = 'Loan';
							$loanstatic->label = $langs->trans($reg[1]);
						} else {
							$loanstatic->label = $links[$key]['label'];
						}
						$loanstatic->ref = $loanstatic->label;
						print $loanstatic->getLinkUrl(1, 16);
					} else if ($links[$key]['type'] == 'member') {
						$memberstatic->id = $links[$key]['url_id'];
						$memberstatic->ref = $links[$key]['label'];
						print $memberstatic->getNomUrl(1, 16, 'card');
					}
				}
				print '</td>';

				// Amount
				if ($objp->amount < 0) {
					print '<td align="right" class="nowrap">' . price($objp->amount * - 1) . '</td><td>&nbsp;</td>' . "\n";
					$total_deb += $objp->amount;
				} else {
					print '<td>&nbsp;</td><td align="right" class="nowrap">&nbsp;' . price($objp->amount) . '</td>' . "\n";
					$total_cred += $objp->amount;
				}

				// Balance
				if (! $mode_search) {
					if ($total >= 0) {
						print '<td align="right" class="nowrap">&nbsp;' . price($total) . '</td>';
					} else {
						print '<td align="right" class="error nowrap">&nbsp;' . price($total) . '</td>';
					}
				} else {
					print '<td align="right">-</td>';
				}

				// Transaction reconciliated or edit link
				// If line not conciliated and account can be conciliated
				if ($objp->rappro && $object->canBeConciliated() > 0) {
					print '<td align="center" class="nowrap">';
					print '<a href="' . DOL_URL_ROOT . '/compta/bank/ligne.php?rowid=' . $objp->rowid . '&amp;account=' . $object->id . '&amp;page=' . $page . '">';
					print img_edit();
					print '</a>';
					print "&nbsp; ";
					print '<a href="releve.php?num=' . $objp->num_releve . '&amp;account=' . $object->id . '">' . $objp->num_releve . '</a>';
					print "</td>";
				} else {
					print '<td align="center">';
					if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
						print '<a href="' . DOL_URL_ROOT . '/compta/bank/ligne.php?rowid=' . $objp->rowid . '&amp;account=' . $object->id . '&amp;page=' . $page . '">';
						print img_edit();
						print '</a>';
					} else {
						print '<a href="' . DOL_URL_ROOT . '/compta/bank/ligne.php?rowid=' . $objp->rowid . '&amp;account=' . $object->id . '&amp;page=' . $page . '">';
						print img_view();
						print '</a>';
					}
					if ($object->canBeConciliated() > 0 && empty($objp->rappro)) {
						if ($db->jdate($objp->dv) < ($now - $conf->bank->rappro->warning_delay)) {
							print ' ' . img_warning($langs->trans("Late"));
						}
					}
					print '&nbsp;';
					if ($user->rights->banque->modifier) {
						print '<a href="' . $_SERVER["PHP_SELF"] . '?action=delete&amp;rowid=' . $objp->rowid . '&amp;id=' . $object->id . '&amp;page=' . $page . '">';
						print img_delete();
						print '</a>';
					}
					print '</td>';
				}

				print "</tr>";
			}

			$i ++;
		}

		// Show total according row displayed
		print '<tr class="liste_total"><td align="left" colspan="5">';
		// if ($sep > 0) print '&nbsp;'; // If we had at least one line in future
		// else print $langs->trans("Total");
		print $langs->trans("Total");
		print ' ' . $object->currency_code . '</td>';
		print '<td align="right" class="nowrap"><b>' . price($total_deb * - 1) . '</b></td>';
		print '<td align="right" class="nowrap"><b>' . price($total_cred) . '</b></td>';
		print '<td align="right" class="nowrap"><b>' . price($sous_total + $total_cred - ($total_deb * - 1)) . '</b></td>';
		print '<td>&nbsp;</td>';
		print '</tr>';

		// Show total according row displayed
		if ($sep > 0) {
			// Real account situation
			print '<tr class="liste_total"><td align="left" colspan="7">';
			// if ($sep > 0) print '&nbsp;'; // If we had at least one line in future
			// else print $langs->trans("CurrentBalance");
			print $langs->trans("FutureBalance");
			print ' ' . $object->currency_code . '</td>';
			print '<td align="right" class="nowrap"><b>' . price($total) . '</b></td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}

		$db->free($result);
	} else {
		dol_print_error($db);
	}

	print "</table>";

	print "</form>\n";

	print '<br>';
} else {
	print $langs->trans("ErrorBankAccountNotFound");
}

llxFooter();

$db->close();
