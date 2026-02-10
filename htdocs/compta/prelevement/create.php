<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019       Markus Welters          <markus@welters.de>
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
 *	\file       htdocs/compta/prelevement/create.php
 *  \ingroup    prelevement
 *	\brief      Page to create a direct debit order or a credit transfer order
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies', 'bills'));

// Get supervariables
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$toselect   = GETPOST('toselect', 'array:int'); // Array of ids of elements selected into a list
$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : 'real';

$type = GETPOST('type', 'aZ09');
$sourcetype = GETPOST('sourcetype', 'aZ09');
$format = GETPOST('format', 'aZ09');
$id_bankaccount = GETPOSTINT('id_bankaccount');
$executiondate = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$search_btn = GETPOST('button_search', 'alpha');
$search_remove_btn = GETPOST('button_removefilter', 'alpha');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn)) {
	$page = 0;
}	// If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'directdebitcreatecard';
// Search/filter parameters
$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST('search_ref', 'alpha');
$search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
$search_datelimit_startday = GETPOSTINT('search_datelimit_startday');
$search_datelimit_startmonth = GETPOSTINT('search_datelimit_startmonth');
$search_datelimit_startyear = GETPOSTINT('search_datelimit_startyear');
$search_datelimit_endday = GETPOSTINT('search_datelimit_endday');
$search_datelimit_endmonth = GETPOSTINT('search_datelimit_endmonth');
$search_datelimit_endyear = GETPOSTINT('search_datelimit_endyear');
$search_datelimit_start = dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end = dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);
$search_company = GETPOST('search_company', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$filter = GETPOST('filtre', 'alpha');
if (!GETPOST('sortorder', 'aZ09')) {
	$sortorder = "DESC";
} else {
	$sortorder = GETPOST('sortorder', 'aZ09');
}
if (!GETPOST('sortfield', 'aZ09')) {
	$sortfield = "f.date_lim_reglement,f.rowid";
} else {
	$sortfield = GETPOST('sortfield', 'aZ09');
}
// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'f.ref' => 'Ref',
	's.nom' => "ThirdParty",
);
// Array fields for column selection
$arrayfields = array(
	'f.ref' => array('label' => ($type == 'bank-transfer' ? 'SupplierInvoice' : 'Invoice'), 'checked' => 1),
	'f.date_lim_reglement' => array('label' => "DateDue", 'checked' => 1),
	's.nom' => array('label' => "ThirdParty", 'checked' => 1),
	'f.fk_account' => array('label' => "BankAccount", 'checked' => 1),
	'pfd.fk_soc_rib' => array('label' => "SupplierIBAN", 'checked' => 1),
	'rum' => array('label' => "RUM", 'checked' => 1),
	'pfd.amount' => array('label' => "AmountTTC", 'checked' => 1),
	'pfd.date_demande' => array('label' => "DateRequest", 'checked' => 1)
);
if ($type == 'bank-transfer') {
	$arrayfields['f.ref_supplier'] = array('label' => 'RefSupplier', 'checked' => 1);
}
$hookmanager->initHooks(array('directdebitcreatecard', 'globalcard'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');

	$permissiontoread = $user->hasRight('paymentbybanktransfer', 'read');
	$permissiontocreate = $user->hasRight('paymentbybanktransfer', 'create');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');

	$permissiontoread = $user->hasRight('prelevement', 'bons', 'lire');
	$permissiontocreate = $user->hasRight('prelevement', 'bons', 'creer');
}


$error = 0;
$option = "";
$mesg = '';

$object = new BonPrelevement($db);

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$massaction = '';
}

$parameters = array('mode' => $mode, 'format' => $format, 'limit' => $limit, 'page' => $page, 'offset' => $offset);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
		include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
		// Reset filters
		if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {
			$search_all = "";
			$search_ref = "";
			$search_ref_supplier = "";
			$search_company = "";
			$search_account = "";
			$search_datelimit_startday = '';
			$search_datelimit_startmonth = '';
			$search_datelimit_startyear = '';
			$search_datelimit_endday = '';
			$search_datelimit_endmonth = '';
			$search_datelimit_endyear = '';
			$search_datelimit_start = '';
			$search_datelimit_end = '';
			$toselect = '';
			$filter = '';
			$option = '';
		}
	}
	if ($action == 'create' && $permissiontocreate) {
		$default_account = ($type == 'bank-transfer' ? 'PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT' : 'PRELEVEMENT_ID_BANKACCOUNT');
		//var_dump($default_account);var_dump(getDolGlobalString($default_account));var_dump($id_bankaccount);exit;

		if ($id_bankaccount != getDolGlobalInt($default_account)) {
			$res = dolibarr_set_const($db, $default_account, $id_bankaccount, 'chaine', 0, '', $conf->entity); // Set as default
		}
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$bank = new Account($db);
		$bank->fetch(getDolGlobalInt($default_account));
		// ICS is not mandatory with payment by bank transfer
		/*if ((empty($bank->ics) && $type !== 'bank-transfer')
			|| (empty($bank->ics_transfer) && $type === 'bank-transfer')
		) {*/

		if (empty($bank->ics) && $type !== 'bank-transfer') {
			$errormessage = str_replace('{url}', $bank->getNomUrl(1, '', '', -1, 1), $langs->trans("ErrorICSmissing", '{url}'));
			setEventMessages($errormessage, null, 'errors');
			$action = '';
			$error++;
		}
		if (empty($toselect)) {
			$errormessage = $langs->trans('ErrorBankTransferNoPaymentRequestSelected');
			setEventMessages($errormessage, null, 'errors');
			$action = '';
			$error++;
		}

		$bprev = new BonPrelevement($db);

		if (!$error) {
			// getDolGlobalString('PRELEVEMENT_CODE_BANQUE') and getDolGlobalString('PRELEVEMENT_CODE_GUICHET') should be empty (we don't use them anymore)
			$result = $bprev->create(getDolGlobalString('PRELEVEMENT_CODE_BANQUE'), getDolGlobalString('PRELEVEMENT_CODE_GUICHET'), $mode, $format, $executiondate, 0, $type, $toselect, 0, $sourcetype);
			if ($result < 0) {
				$mesg = '';

				if ($bprev->error || !empty($bprev->errors)) {
					setEventMessages($bprev->error, $bprev->errors, 'errors');
				} else {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorsOnXLines", count($bprev->invoice_in_error)), null, 'warnings');
				}

				if (!empty($bprev->invoice_in_error)) {
					foreach ($bprev->invoice_in_error as $key => $val) {
						$mesg .= '<span class="warning">'.$val."</span><br>\n";
					}
				}
			} elseif ($result == 0 || !empty($bprev->invoice_in_error)) {
				$mesg = '';

				if ($result == 0) {
					if ($type != 'bank-transfer') {
						$mesg = $langs->trans("NoInvoiceCouldBeWithdrawed", $format);
					}
					if ($type == 'bank-transfer' && $sourcetype != 'salary') {
						$mesg = $langs->trans("NoInvoiceCouldBeWithdrawedSupplier", $format);
					}
					if ($type == 'bank-transfer' && $sourcetype == 'salary') {
						$mesg = $langs->trans("NoSalariesCouldBeWithdrawed", $format);
					}
					setEventMessages($mesg, null, 'errors');
				}

				if (!empty($bprev->invoice_in_error)) {
					$mesg .= '<br>'."\n";
					foreach ($bprev->invoice_in_error as $key => $val) {
						$mesg .= '<span class="warning">'.$val."</span><br>\n";
					}
				}
			} else {
				if ($type != 'bank-transfer') {
					$texttoshow = $langs->trans("DirectDebitOrderCreated", '{s}');
					$texttoshow = str_replace('{s}', $bprev->getNomUrl(1), $texttoshow);
					setEventMessages($texttoshow, null);
				} else {
					$texttoshow = $langs->trans("CreditTransferOrderCreated", '{s}');
					$texttoshow = str_replace('{s}', $bprev->getNomUrl(1), $texttoshow);
					setEventMessages($texttoshow, null);
				}

				header("Location: ".DOL_URL_ROOT.'/compta/prelevement/card.php?id='.urlencode((string) ($bprev->id)).'&type='.urlencode((string) ($type)));
				exit;
			}
		}
	}

	$objectclass = "BonPrelevement";
	if ($type == 'bank-transfer') {
		$uploaddir = $conf->paymentbybanktransfer->dir_output;
	} else {
		$uploaddir = $conf->prelevement->dir_output;
	}
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);

$thirdpartystatic = new Societe($db);
if ($type != 'bank-transfer') {
	$invoicestatic = new Facture($db);
} else {
	$invoicestatic = new FactureFournisseur($db);
}
$bprev = new BonPrelevement($db);

$arrayofselected = is_array($toselect) ? $toselect : array();
// List of mass actions available
$arrayofmassactions = array();
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

if (prelevement_check_config($type) < 0) {
	$langs->load("errors");
	$modulenametoshow = "Withdraw";
	if ($type == 'bank-transfer') {
		$modulenametoshow = "PaymentByBankTransfer";
	}
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv($modulenametoshow)), null, 'errors');
}


$title = $langs->trans("NewStandingOrder");
if ($type == 'bank-transfer') {
	$title = $langs->trans("NewPaymentByBankTransfer");
}

llxHeader('', $title);


// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
$head = bon_prelevement_prepare_head($bprev, $bprev->nbOfInvoiceToPay($type), $bprev->nbOfInvoiceToPay($type, 'salary'));
if ($type) {
	print dol_get_fiche_head($head, ((GETPOSTISSET('sourcetype') && GETPOST('sourcetype') != '') ? 'salary' : 'invoice'), $langs->trans("Invoices"), -1, $bprev->picto);
} else {
	print load_fiche_titre($title);
	print dol_get_fiche_head(array(), '', '', -1);
}


if ($sourcetype != 'salary') {
	$nb = $bprev->nbOfInvoiceToPay($type);  // @phan-suppress-current-line PhanPluginSuspiciousParamPosition
	$pricetowithdraw = $bprev->SommeAPrelever($type);  // @phan-suppress-current-line PhanPluginSuspiciousParamPosition
} else {
	$nb = $bprev->nbOfInvoiceToPay($type, 'salary');  // @phan-suppress-current-line PhanPluginSuspiciousParamPosition
	$pricetowithdraw = $bprev->SommeAPrelever($type, 'salary');  // @phan-suppress-current-line PhanPluginSuspiciousParamPosition
}
if ($nb < 0) {
	dol_print_error($db, $bprev->error);
}

print '<div class="fichecenter">';

print '<table class="border centpercent tableforfield">';

$labeltoshow = $langs->trans("NbOfInvoiceToWithdraw");
if ($type == 'bank-transfer') {
	$labeltoshow = $langs->trans("NbOfInvoiceToPayByBankTransfer");
}
if ($sourcetype == 'salary') {
	$labeltoshow = $langs->trans("NbOfInvoiceToPayByBankTransferForSalaries");
}

print '<tr><td class="titlefield">'.$labeltoshow.'</td>';
print '<td class="nowraponall">';
print dol_escape_htmltag((string) $nb);
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountTotal").'</td>';
print '<td class="amount nowraponall">';
print price($pricetowithdraw, 0, $langs, 1, -1, -1, $conf->currency);
print '</td>';
print '</tr>';

print '</table>';

print '</div>';
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '<div class="tabsAction">'."\n";

print '<form id="createBankTransfer" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<input type="hidden" name="action" value="create">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="sourcetype" value="'.$sourcetype.'">';

if ($nb) {
	if ($pricetowithdraw) {
		$title = $langs->trans('BankToReceiveWithdraw').': ';
		if ($type == 'bank-transfer') {
			$title = $langs->trans('BankToPayCreditTransfer').': ';
		}
		print '<span class="hideonsmartphone">'.$title.'</span>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');

		$default_account = ($type == 'bank-transfer' ? 'PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT' : 'PRELEVEMENT_ID_BANKACCOUNT');

		print $form->select_comptes(getDolGlobalInt($default_account), 'id_bankaccount', 0, "courant=1", 0, '', 0, 'widthcentpercentminusx maxwidth300', 1);
		print ' &nbsp; &nbsp; ';

		if (empty($executiondate)) {
			$delayindays = 0;
			if ($type != 'bank-transfer') {
				$delayindays = getDolGlobalInt('PRELEVEMENT_ADDDAYS');
			} else {
				$delayindays = getDolGlobalInt('PAYMENTBYBANKTRANSFER_ADDDAYS');
			}

			$executiondate = dol_time_plus_duree(dol_now(), $delayindays, 'd');
		}

		print $langs->trans('ExecutionDate').' ';
		$datere = $executiondate;
		print $form->selectDate($datere, 're');
		// Total checked display
		if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
			print '<span class="hideonsmartphone">'.$langs->trans('Total').' </span>';
			print '<input id="total_checked" value="0" disabled class="maxwidth100">';
		}

		if ($mysoc->isInSEPA()) {
			$title = $langs->trans("CreateForSepa");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateSepaFileForPaymentByBankTransfer");
			}

			if ($type != 'bank-transfer') {
				print '<select name="format">';
				print '<option value="FRST"'.($format == 'FRST' ? ' selected="selected"' : '').'>'.$langs->trans('SEPAFRST').'</option>';
				print '<option value="RCUR"'.($format == 'RCUR' ? ' selected="selected"' : '').'>'.$langs->trans('SEPARCUR').'</option>';
				print '</select>';
			}
			print '<input type="submit" class="butAction margintoponly marginbottomonly" value="'.$title.'"/>';
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<input type="hidden" name="format" value="ALL">'."\n";
			print '<input type="submit" class="butAction margintoponly marginbottomonly" value="'.$title.'">'."\n";
		}
	} else {
		if ($mysoc->isInSEPA()) {
			$title = $langs->trans("CreateForSepaFRST");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateSepaFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly marginbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";

			if ($type != 'bank-transfer') {
				$title = $langs->trans("CreateForSepaRCUR");
				print '<a class="butActionRefused classfortooltip margintoponly marginbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";
			}
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly marginbottomonly" href="#">'.$title."</a>\n";
		}
	}
} else {
	$titlefortab = $langs->transnoentitiesnoconv("StandingOrders");
	$title = $langs->trans("CreateAll");
	if ($type == 'bank-transfer') {
		$titlefortab = $langs->transnoentitiesnoconv("PaymentByBankTransfers");
		$title = $langs->trans("CreateFileForPaymentByBankTransfer");
	}
	print '<a class="butActionRefused classfortooltip margintoponly marginbottomonly" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoInvoiceToWithdraw", $titlefortab, $titlefortab)).'">';
	print $title;
	print "</a>\n";
}

print "</form>\n";

// Send selected lines to build the transfer file
print '<script>
	$().ready(() => {
		let form_create_transfer = $("#createBankTransfer");
		let form_list = $("#searchFormList");
		form_create_transfer.submit(() => {
			let selected_lines = Array.from(document.querySelectorAll("input.checkforselect:checked"))
			selected_lines.map(line => {
				form_create_transfer.append(line);
			})
		})
		// Compute total checked
		function computeTotalChecked() {
			let total_checked = 0;
			let checked_pfd = Array.from($("[id^=cb].checkforselect:checked"));
			checked_pfd.forEach((pfd) => {
				let amount = Number(pfd.getAttribute("amount"));
				total_checked += amount;
			})
			let precision = Math.pow(10, '.getDolGlobalInt('MAIN_MAX_DECIMALS_TOT', 2).');
			$("#total_checked").val(Math.round(total_checked * precision) / precision);
		}
		$(".checkforselect").change(computeTotalChecked);
		computeTotalChecked();
	})
</script>';

print "</div>\n";

// Show errors or warnings
if ($mesg) {
	print $mesg;
	print '<br>';
}

print '<br>';


/*
 * Invoices waiting for withdraw
 */
if ($sourcetype != 'salary') {
	$sql = "SELECT f.ref, f.rowid, f.date_lim_reglement as datelimite, f.total_ttc, f.fk_account, s.nom as name, s.rowid as socid,";
	if ($type == 'bank-transfer') {
		$sql .= " f.ref_supplier,";
	}
	$sql .= " pd.rowid as request_row_id, pd.date_demande, pd.amount, pd.fk_societe_rib as soc_rib_id";
	if ($type == 'bank-transfer') {
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	} else {
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	}
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account AS ba ON f.fk_account = ba.rowid";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."prelevement_demande as pd";
	$sql .= " WHERE s.rowid = f.fk_soc";
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if (!getDolGlobalString('WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS')) {
		$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
	}
	//$sql .= " AND pd.amount > 0";
	$sql .= " AND f.total_ttc > 0"; // Avoid credit notes
	$sql .= " AND pd.traite = 0";
	$sql .= " AND pd.ext_payment_id IS NULL";
	if ($type == 'bank-transfer') {
		$sql .= " AND pd.fk_facture_fourn = f.rowid";
	} else {
		$sql .= " AND pd.fk_facture = f.rowid";
	}
	if ($socid > 0) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	// Add search filters
	if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
		$searchsql = '';
		if ($search_ref) {
			$searchsql .= natural_search('f.ref', $search_ref);
		}
		if ($search_ref_supplier) {
			$searchsql .= natural_search('f.ref_supplier', $search_ref_supplier);
		}
		if ($search_company) {
			$searchsql .= natural_search('s.nom', $search_company);
		}
		if ($search_account) {
			$searchsql .= natural_search(array('ba.ref', 'ba.label', 'ba.bank'), $search_account);
		}
		if ($search_datelimit_start) {
			$searchsql .= " AND f.date_lim_reglement >= '".$db->idate($search_datelimit_start)."'";
		}
		if ($search_datelimit_end) {
			$searchsql .= " AND f.date_lim_reglement <= '".$db->idate($search_datelimit_end)."'";
		}
		if ($option == 'late') {
			$searchsql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
		}
		if ($filter && $filter != -1) {
			$aFilter = explode(',', $filter);
			foreach ($aFilter as $fil) {
				$filt = explode(':', $fil);
				$searchsql .= ' AND '.$db->escape(trim($filt[0]))." = '".$db->escape(trim($filt[1]))."'";
			}
		}
		if ($search_all) {
			$searchsql .= natural_search(array_keys($fieldstosearchall), $search_all);
		}
		$sql .= !empty($searchsql) ? $searchsql : '';
		$sql .= $db->order($sortfield, $sortorder);
	} else {
		// Default sort when extended list is disabled
		$sql .= " ORDER BY f.date_lim_reglement ASC, f.rowid ASC";
	}
} else {
	$sql = "SELECT s.ref, s.rowid, s.amount, CONCAT(u.lastname, ' ', u.firstname) as name, u.rowid as uid,";
	$sql .= " pd.rowid as request_row_id, pd.date_demande, pd.amount, pd.fk_societe_rib as soc_rib_id";
	$sql .= " FROM ".MAIN_DB_PREFIX."salary as s,";
	$sql .= " ".MAIN_DB_PREFIX."user as u,";
	$sql .= " ".MAIN_DB_PREFIX."prelevement_demande as pd";
	$sql .= " WHERE s.fk_user = u.rowid";
	$sql .= " AND s.entity IN (".getEntity('salary').")";
	/*if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS)) {
		$sql .= " AND s.fk_statut = ".Facture::STATUS_VALIDATED;
	}*/
	$sql .= " AND s.amount > 0";
	$sql .= " AND pd.traite = 0";
	$sql .= " AND pd.ext_payment_id IS NULL";
	$sql .= " AND s.rowid = pd.fk_salary AND s.paye = ".Salary::STATUS_UNPAID;
	$sql .= " AND pd.traite = 0";
	// Default sort for salary
	$sql .= " ORDER BY s.rowid ASC";
}

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > (int) $nbtotalofrecords) {
		// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
		$arrayofselected = is_array($toselect) ? $toselect : array();

		$param = '&socid='.$socid;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.urlencode($contextpage);
		}
		if ($type) {
			$param .= '&type=' . urlencode((string) $type);
		}
		if ($sourcetype) {
			$param .= '&sourcetype=' . urlencode((string) $sourcetype);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($search_all) {
			$param .= '&search_all='.urlencode($search_all);
		}
		if ($search_ref) {
			$param .= '&search_ref='.urlencode($search_ref);
		}
		if ($search_ref_supplier) {
			$param .= '&search_ref_supplier='.urlencode($search_ref_supplier);
		}
		if ($search_company) {
			$param .= '&search_company='.urlencode($search_company);
		}
		if ($search_account) {
			$param .= '&search_account='.urlencode($search_account);
		}
		if ($search_datelimit_startday) {
			$param .= '&search_datelimit_startday='.urlencode($search_datelimit_startday);
		}
		if ($search_datelimit_startmonth) {
			$param .= '&search_datelimit_startmonth='.urlencode($search_datelimit_startmonth);
		}
		if ($search_datelimit_startyear) {
			$param .= '&search_datelimit_startyear='.urlencode($search_datelimit_startyear);
		}
		if ($search_datelimit_endday) {
			$param .= '&search_datelimit_endday='.urlencode($search_datelimit_endday);
		}
		if ($search_datelimit_endmonth) {
			$param .= '&search_datelimit_endmonth='.urlencode($search_datelimit_endmonth);
		}
		if ($search_datelimit_endyear) {
			$param .= '&search_datelimit_endyear='.urlencode($search_datelimit_endyear);
		}
		if ($option) {
			$param .= "&search_option=".urlencode($option);
		}
		// List of mass actions available
		$arrayofmassactions = array();
		if (in_array($massaction, array('presend', 'predelete'))) {
			$arrayofmassactions = array();
		}
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
		print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		print '<input type="hidden" name="sourcetype" value="'.$sourcetype.'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	} else {
		// Original simple display
		$arrayofselected = is_array($toselect) ? $toselect : array();
		$param = '';
		if ($type) {
			$param .= '&type=' . urlencode((string) $type);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($socid) {
			$param .= '&socid='.urlencode((string) ($socid));
		}
		if ($option) {
			$param .= "&option=".urlencode($option);
		}

		print '<form method="POST" id="searchFormList" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="page" value="'.$page.'">';
		if (!empty($limit)) {
			print '<input type="hidden" name="limit" value="'.$limit.'"/>';
		}
		if ($type != '') {
			print '<input type="hidden" name="type" value="'.$type.'">';
		}
	}
	$title = $langs->trans("InvoiceWaitingWithdraw");
	$picto = 'bill';
	if ($type == 'bank-transfer') {
		if ($sourcetype != 'salary') {
			$title = $langs->trans("InvoiceWaitingPaymentByBankTransfer");
		} else {
			$title = $langs->trans("SalaryWaitingWithdraw");
			$picto = 'salary';
		}
	}
	if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
		// Show search fields description
		if ($search_all) {
			foreach ($fieldstosearchall as $key => $val) {
				$fieldstosearchall[$key] = $langs->trans($val);
			}
			print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
		}
		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', ''));
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
		print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, '', '', $limit, 0, 0, 1);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="tagtable liste">';

		// Line for filters
		print '<tr class="liste_titre_filter">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center maxwidthsearch">';
			$searchpicto = $form->showFilterButtons('left');
			print $searchpicto;
			print '</td>';
		}
		// Ref
		if (!empty($arrayfields['f.ref']['checked'])) {
			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
			print '</td>';
		}
		// Ref supplier
		if ($type == 'bank-transfer' && !empty($arrayfields['f.ref_supplier']['checked'])) {
			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth50" type="text" name="search_ref_supplier" value="'.dol_escape_htmltag($search_ref_supplier).'">';
			print '</td>';
		}
		// Date due
		if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
			print '<td class="liste_titre center">';
			print '<div class="nowrap">';
			print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("Before"));
			print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
			print '</div>';
			print '</td>';
		}
		// Thirdparty
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"></td>';
		}
		// Bank account
		if (!empty($arrayfields['f.fk_account']['checked'])) {
			print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_account" value="'.dol_escape_htmltag($search_account).'"></td>';
		}
		// RIB
		if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
		// RUM
		if (!empty($arrayfields['rum']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
		// Amount
		if (!empty($arrayfields['pfd.amount']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
		// Date request
		if (!empty($arrayfields['pfd.date_demande']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center maxwidthsearch">';
			$searchpicto = $form->showFilterButtons();
			print $searchpicto;
			print '</td>';
		}
		print "</tr>\n";
		// Column headers
		print '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		}
		if (!empty($arrayfields['f.ref']['checked'])) {
			print_liste_field_titre($arrayfields['f.ref']['label'], $_SERVER['PHP_SELF'], 'f.ref,f.rowid', '', $param, '', $sortfield, $sortorder);
		}
		if ($type == 'bank-transfer' && !empty($arrayfields['f.ref_supplier']['checked'])) {
			print_liste_field_titre($arrayfields['f.ref_supplier']['label'], $_SERVER['PHP_SELF'], 'f.ref_supplier,f.rowid', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
			print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], 'f.date_lim_reglement', '', $param, '', $sortfield, $sortorder, 'center ');
		}
		if (!empty($arrayfields['s.nom']['checked'])) {
			print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['f.fk_account']['checked'])) {
			print_liste_field_titre($arrayfields['f.fk_account']['label'], $_SERVER['PHP_SELF'], 'f.fk_account', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
			print_liste_field_titre($arrayfields['pfd.fk_soc_rib']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['rum']['checked'])) {
			print_liste_field_titre($arrayfields['rum']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['pfd.amount']['checked'])) {
			print_liste_field_titre($arrayfields['pfd.amount']['label'], $_SERVER['PHP_SELF'], 'pd.amount', '', $param, '', $sortfield, $sortorder, 'right ');
		}
		if (!empty($arrayfields['pfd.date_demande']['checked'])) {
			print_liste_field_titre($arrayfields['pfd.date_demande']['label'], $_SERVER['PHP_SELF'], 'pd.date_demande', '', $param, '', $sortfield, $sortorder, 'center ');
		}
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		}
		print "</tr>\n";
	} else {
		// Original simple table headers
		$tradinvoice = "Invoice";
		if ($type == 'bank-transfer') {
			if ($sourcetype != 'salary') {
				$tradinvoice = "SupplierInvoice";
			} else {
				$tradinvoice = "RefSalary";
			}
		}
			print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, '', '', '', $num, $nbtotalofrecords, $picto, 0, '', '', $limit);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			if ($num) {
				print '<td align="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
			}
		}
		// Ref invoice or salary
		print '<td>'.$langs->trans($tradinvoice).'</td>';
		// Ref supplier
		if ($type == 'bank-transfer' && $sourcetype != 'salary') {
			print '<td>'.$langs->trans("RefSupplier").'</td>';
		}
		// Thirdparty or user
		if ($sourcetype != 'salary') {
			print '<td>'.$langs->trans("ThirdParty").'</td>';
		} else {
			print '<td>'.$langs->trans("Employee").'</td>';
		}
		// BAN
		print '<td>'.$langs->trans("RIB").'</td>';
		// RUM
			if (empty($type) || $type == 'direct-debit') { // RUM is only relevant for direct debit
			print '<td>'.$langs->trans("RUM").'</td>';
		}
		print '<td class="right">';
		if ($sourcetype == 'salary') {
			print $langs->trans("Amount");
		} else {
			print $langs->trans("AmountTTC");
		}
		print '</td>';
		print '<td class="right">'.$langs->trans("PendingSince").'</td>';
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td align="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
		}
		print '</tr>';
	}
	if ($num) {
		if ($sourcetype != 'salary') {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		} else {
			require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
			require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
		}
		$totalarray = array();
		$bankaccountstatic = new Account($db);
		while ($i < $num && $i < $limit) {
			$obj = $db->fetch_object($resql);
			if ($sourcetype != 'salary') {
				$bac = new CompanyBankAccount($db);	// Must include the new in loop so the fetch is clean
				if (!empty($obj->soc_rib_id)) {
					$bac->fetch($obj->soc_rib_id);
				} else {
					$bac->fetch(0, '', $obj->socid);
				}

				$datelimit = $db->jdate($obj->datelimite);
				$invoicestatic->fetch($obj->rowid);
				$thirdpartystatic->fetch($obj->socid);
				$invoicestatic->id = $obj->rowid;
				$invoicestatic->ref = $obj->ref;
				if ($type == 'bank-transfer') {
					$invoicestatic->ref_supplier = $obj->ref_supplier;
				}
				$salary = null;
			} else {
				$bac = new UserBankAccount($db);
				$bac->fetch($obj->soc_rib_id ?? 0, '', $obj->uid);

				$salary = new Salary($db);
				$salary->fetch($obj->rowid);
				$datelimit = null;
			}
			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				$selected = 0;
				if (in_array($obj->request_row_id, $arrayofselected) || empty($arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').' amount="'.$obj->amount.'">';
				print '</td>';
			}
			if (getDolGlobalString('WITHDRAW_ENABLED_EXTENDED_LIST')) {
				// Extended display with arrayfields
				// Ref invoice
				if (!empty($arrayfields['f.ref']['checked'])) {
					print '<td class="tdoverflowmax150">';
					if ($sourcetype != 'salary' || $salary === null) {
						print $invoicestatic->getNomUrl(1, 'withdraw', 0, 0, '', 0, -1, 1);
					} else {
						print $salary->getNomUrl(1, 'withdraw');
					}
					print "</td>\n";
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}

				// Ref supplier
				if ($type == 'bank-transfer' && !empty($arrayfields['f.ref_supplier']['checked'])) {
					print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($invoicestatic->ref_supplier).'">';
					print dol_escape_htmltag($invoicestatic->ref_supplier);
					print "</td>\n";
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// Date limit
				if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
					print '<td class="center nowraponall">'.dol_print_date($datelimit, 'day');
					if ($invoicestatic->hasDelay()) {
						print img_warning($langs->trans('Alert').' - '.$langs->trans('Late'));
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// Third party
				if (!empty($arrayfields['s.nom']['checked'])) {
					if ($sourcetype != 'salary') {
						print '<td class="tdoverflowmax200">';
						print $thirdpartystatic->getNomUrl(1, 'ban');
						print '</td>';
					} else {
						print '<td class="tdoverflowmax200">';
						$user->fetch($obj->uid);
						print $user->getNomUrl(-1);
						print '</td>';
					}
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// Bank account
				if (!empty($arrayfields['f.fk_account']['checked'])) {
					if (!empty($obj->fk_account)) {
						$bankaccountstatic->fetch($obj->fk_account);
						print '<td class="tdoverflowmax200">'.$bankaccountstatic->getNomUrl(1, '', 'reflabel');
						print "</td>\n";
					} else {
						print '<td class="tdoverflowmax200">&nbsp;</td>'."\n";
					}
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// RIB
				if (!empty($arrayfields['pfd.fk_soc_rib']['checked'])) {
					print '<td>';
					if ($bac->id > 0) {
						if (!empty($bac->iban) || !empty($bac->bic)) {
							print (!empty($bac->label) ? $bac->label.' - ' : '').$bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
							if ($bac->verif() <= 0) {
								print img_warning('Error on default bank number for IBAN : '.$langs->trans($bac->error_message));
							}
						} else {
							print img_warning($langs->trans("IBANNotDefined"));
						}
					} else {
						print img_warning($langs->trans("NoBankAccountDefined"));
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// RUM
				if (!empty($arrayfields['rum']['checked'])) {
					print '<td>';
					$rumToShow = $thirdpartystatic->display_rib('rum');
					if ($rumToShow) {
						print $rumToShow;
						$format = $thirdpartystatic->display_rib('format');
						if ($type != 'bank-transfer') {
							if ($format) {
								print ' ('.$format.')';
							}
						}
					} else {
						print img_warning($langs->trans("NoBankAccountDefined"));
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
				// Amount
				if (!empty($arrayfields['pfd.amount']['checked'])) {
					print '<td class="right nowrap"><span id="amount_'.$obj->request_row_id.'" class="amount">'.price($obj->amount)."</span></td>\n";
					if (!$i) {
						$totalarray['nbfield']++;
						$totalarray['pos'][$totalarray['nbfield']] = 'pfd.amount';
					}
					if (!isset($totalarray['val']['pfd.amount'])) {
						$totalarray['val']['pfd.amount'] = 0;
					}
					$totalarray['val']['pfd.amount'] += $obj->amount;
				}
				// Date request
				if (!empty($arrayfields['pfd.date_demande']['checked'])) {
					print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'day').'</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			} else {
				// Simple display without arrayfields
				// Ref invoice
				print '<td class="tdoverflowmax150">';
				if ($sourcetype != 'salary' || $salary === null) {
					print $invoicestatic->getNomUrl(1, 'withdraw');
				} else {
					print $salary->getNomUrl(1, 'withdraw');
				}
				print '</td>';
	
					// Ref supplier
				if ($type == 'bank-transfer' && $sourcetype != 'salary') {
					print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($invoicestatic->ref_supplier).'">';
					print dol_escape_htmltag($invoicestatic->ref_supplier);
					print '</td>';
				}
	
					// Thirdparty / User
				if ($sourcetype != 'salary') {
					print '<td class="tdoverflowmax100">';
					print $thirdpartystatic->getNomUrl(1, 'ban');
					print '</td>';
				} else {
					print '<td class="tdoverflowmax100">';
					$user->fetch($obj->uid);
					print $user->getNomUrl(-1);
					print '</td>';
				}
	
				// BAN
				print '<td>';
				if ($bac->id > 0) {
					if (!empty($bac->iban) || !empty($bac->bic)) {
						print $bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
						if ($bac->verif() <= 0) {
							print img_warning('Error on default bank number for IBAN : '.$langs->trans($bac->error));
						}
						if ($obj->soc_rib_id > 0) {
							print $form->textwithpicto('', $langs->trans("BankAccountForcedOnRequest"));
						} else {
							print $form->textwithpicto('', $langs->trans("BankAccountUsedByDefault").'<br><b>'.$langs->trans("Label").'</b> : '.$bac->label.'<br><b>'.$langs->trans("BankName").'</b> : '.$bac->bank, 1, 'help', 'valigmiddle warning');
						}
					} else {
						print img_warning($langs->trans("IBANNotDefined"));
					}
				} else {
					$langs->load("banks");
					print img_warning($langs->trans("NoBankAccountDefined"));
				}
				print '</td>';
	
				// RUM
				if (empty($type) || $type == 'direct-debit') {
					print '<td>';
					if (!empty($bac->rum)) {
						print $bac->rum;
					} else {
						$rumToShow = $thirdpartystatic->display_rib('rum');
						if ($rumToShow) {
							print $rumToShow;
							$format = $thirdpartystatic->display_rib('format');
							if ($type != 'bank-transfer') {
								if ($format) {
									print ' ('.$format.')';
								}
							}
						} else {
							$langs->load("banks");
							print img_warning($langs->trans("NoBankAccountDefined"));
						}
					}
					print '</td>';
				}
	
				// Amount
				print '<td class="right amount">';
				print price($obj->amount, 0, $langs, 0, 0, -1, $conf->currency);
				print '</td>';
				// Date
				print '<td class="right">';
				print dol_print_date($db->jdate($obj->date_demande), 'day');
				print '</td>';
			}
			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="nowrap center">';
				$selected = 0;
				if (in_array($obj->request_row_id, $arrayofselected) || empty($arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').' amount="'.$obj->amount.'">';
				print '</td>';
			}
			print '</tr>';
			$i++;
		}
		// Show total line
		include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
	} else {
		$colspan = 6;
		if ($type == 'bank-transfer') {
			$colspan++;
		}
		if ($massactionbutton || $massaction) {
			$colspan++;
		}
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table>";
	print "</div>";

	print "</form>";
	print "<br>\n";
} else {
	dol_print_error($db);
}


/*
 * List of latest withdraws
 */
/*
$limit=5;

print load_fiche_titre($langs->trans("LastWithdrawalReceipts",$limit),'','');

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut";
$sql.= ", p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity IN (".getEntity('invoice').")";
$sql.= " ORDER BY datec DESC";
$sql.=$db->plimit($limit);

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td>';
	print '<td class="center">'.$langs->trans("Date").'</td><td class="right">'.$langs->trans("Amount").'</td>';
	print '</tr>';

	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);


		print '<tr class="oddeven">';

		print "<td>";
		$bprev->id=$obj->rowid;
		$bprev->ref=$obj->ref;
		print $bprev->getNomUrl(1);
		print "</td>\n";

		print '<td class="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

		print '<td class="right"><span class="amount">'.price($obj->amount,0,$langs,0,0,-1,$conf->currency)."</span></td>\n";

		print "</tr>\n";
		$i++;
	}
	print "</table><br>";
	$db->free($result);
}
else
{
	dol_print_error($db);
}
*/

// End of page
llxFooter();
$db->close();
