<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
// InfraS add begin
$searchsql = GETPOST('searchsql', 'alpha');
$search_all 						= trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref 						= GETPOST('search_ref', 'alpha');
$search_ref_supplier 				= GETPOST('search_ref_supplier', 'alpha');
$search_datelimit_startday 			= GETPOST('search_datelimit_startday', 'int');
$search_datelimit_startmonth 		= GETPOST('search_datelimit_startmonth', 'int');
$search_datelimit_startyear 		= GETPOST('search_datelimit_startyear', 'int');
$search_datelimit_endday 			= GETPOST('search_datelimit_endday', 'int');
$search_datelimit_endmonth 			= GETPOST('search_datelimit_endmonth', 'int');
$search_datelimit_endyear 			= GETPOST('search_datelimit_endyear', 'int');
$search_datelimit_start 			= dol_mktime(0, 0, 0, $search_datelimit_startmonth, $search_datelimit_startday, $search_datelimit_startyear);
$search_datelimit_end 				= dol_mktime(23, 59, 59, $search_datelimit_endmonth, $search_datelimit_endday, $search_datelimit_endyear);
$search_date_demande_startday 		= GETPOST('search_date_demande_startday', 'int');
$search_date_demande_startmonth 	= GETPOST('search_date_demande_startmonth', 'int');
$search_date_demande_startyear 		= GETPOST('search_date_demande_startyear', 'int');
$search_date_demande_endday 		= GETPOST('search_date_demande_endday', 'int');
$search_date_demande_endmonth 		= GETPOST('search_date_demande_endmonth', 'int');
$search_date_demande_endyear 		= GETPOST('search_date_demande_endyear', 'int');
$search_date_demande_start 			= dol_mktime(0, 0, 0, $search_date_demande_startmonth, $search_date_demande_startday, $search_date_demande_startyear);
$search_date_demande_end 			= dol_mktime(23, 59, 59, $search_date_demande_endmonth, $search_date_demande_endday, $search_date_demande_endyear);
$search_company 					= GETPOST('search_company', 'alpha');
$search_employee 					= GETPOST('search_employee', 'alpha');
$search_account 					= GETPOST('search_account', 'alpha');
$search_amount 						= GETPOST('search_amount', 'alpha');
$search_btn 						= GETPOST('button_search', 'alpha');
$search_remove_btn 					= GETPOST('button_removefilter', 'alpha');
$sortfield 							= GETPOST('sortfield', 'aZ09comma');
$sortorder 							= GETPOST('sortorder', 'aZ09comma');

$option = GETPOST('search_option'); 
$filter = GETPOST('filtre', 'alpha');
// InfraS add end
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;

// InfraS add begin
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sourcetype != 'salary') {
	if (!$sortfield) {
		$sortfield = "f.date_lim_reglement,f.rowid";
	}

	$fieldstosearchall = array(
		'f.ref' => 'Ref',
		's.nom' => "ThirdParty",
	);

	$arrayfields = array(
		'f.ref'=>array('label'=>($type == 'bank-transfer' ? 'SupplierInvoice' : 'Invoice'), 'checked'=>1),
		'f.date_lim_reglement'=>array('label'=>"DateDue", 'checked'=>1),
		's.nom'=>array('label'=>"ThirdParty", 'checked'=>1),
		'f.fk_account'=>array('label'=>"BankAccount", 'checked'=>1),
		'pd.fk_soc_rib'=>array('label'=>"SupplierIBAN", 'checked'=>1),
		'pd.amount'=>array('label'=>"AmountTTC", 'checked'=>1),
		'pd.date_demande'=>array('label'=>"PendingSince", 'checked'=>1)
	);

	if ($type == 'bank-transfer') {
		$arrayfields['f.ref_supplier'] = array('label'=>'RefSupplier', 'checked'=>1);
	}

} else {
	if (!$sortfield) {
		$sortfield = "s.rowid"; 
	}

	$fieldstosearchall = array(
		's.rowid' => 'RefSalary',
		's.nom' => "Employee",
	);
	
	$arrayfields = array(
		's.rowid'=>array('label'=>"RefSalary", 'checked'=>1),
		's.nom'=>array('label'=>"Employee", 'checked'=>1),
		'pd.fk_soc_rib'=>array('label'=>"SalaryIBAN", 'checked'=>1),
		'pd.amount'=>array('label'=>"Amount", 'checked'=>1),
		'pd.date_demande'=>array('label'=>"PendingSince", 'checked'=>1)
	);
}
// InfraS add end

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
// $option = ""; InfraS comment
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
	// InfraS add begin
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha')) {		// All tests must be present to be compatible with all browsers
		$search_all 							= "";
		$search_ref 						= "";
		$search_ref_supplier 				= "";
		$search_company 					= "";
		$search_account 					= "";
		$search_datelimit_startday 			= '';
		$search_datelimit_startmonth 		= '';
		$search_datelimit_startyear 		= '';
		$search_datelimit_endday 			= '';
		$search_datelimit_endmonth 			= '';
		$search_datelimit_endyear 			= '';
		$search_datelimit_start 			= '';
		$search_datelimit_end 				= '';
		$search_date_demande_startday 		= '';
		$search_date_demande_startmonth 	= '';
		$search_date_demande_startyear 		= '';
		$search_date_demande_endday 		= '';
		$search_date_demande_endmonth 		= '';
		$search_date_demande_endyear 		= '';
		$search_date_demande_start 			= '';
		$search_date_demande_end 			= '';
		$search_employee 					= "";
		$search_amount 						= "";
		$toselect 							= '';
		$filter 							= '';
		$option 							= '';
		$socid 								= "";
	}
	// InfraS add end
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
		// InfraS add begin
		if ($sourcetype != 'salary'){
			if (empty($toselect)) {
				$mesg = $langs->trans("NoInvoiceSelected");
				setEventMessages($mesg, null, 'errors');
				$action = '';
				$error++;
			}
		}
		// InfraS add end

		$bprev = new BonPrelevement($db);

		if (!$error) {
			// getDolGlobalString('PRELEVEMENT_CODE_BANQUE') and getDolGlobalString('PRELEVEMENT_CODE_GUICHET') should be empty (we don't use them anymore)
			// InfraS add begin
			$selected_invoices = array();
			foreach($toselect as $select) {
				$selected_invoices[] = (int) $select;
			}
			// InfraS add end
			$result = $bprev->create(getDolGlobalString('PRELEVEMENT_CODE_BANQUE'), getDolGlobalString('PRELEVEMENT_CODE_GUICHET'), $mode, $format, $executiondate, 0, $type, 0, 0, $sourcetype);
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
$arrayofmassactions = array(
);
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

print '<form action="'.$_SERVER['PHP_SELF'].'"  id="createFilePayment"  method="POST">'; // InfraS change
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

		// InfraS add begin - Display total amount of selected direct debit requests
		print '<span class="hideonsmartphone">'.$langs->trans('Total').' </span>';
		print '<input id="total_checked" value=0 disabled>';
		// InfraS add end

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
	$sql = "SELECT f.ref, f.rowid, f.date_lim_reglement as datelimite, f.total_ttc, s.nom as name, s.rowid as socid,"; // InfraS change
	if ($type == 'bank-transfer') {
		$sql .= " f.ref_supplier,";
	}
	$sql .= " pd.rowid as request_row_id, pd.date_demande, pd.amount, pd.fk_societe_rib as soc_rib_id";
	if ($type == 'bank-transfer') {
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f,";
	} else {
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,";
	}
	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
	$sql .= " ".MAIN_DB_PREFIX."prelevement_demande as pd";
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
}

// InfraS add begin
$searchsql = '';
if ($sourcetype != 'salary') {
	if ($search_ref) {
		if (is_numeric($search_ref)) {
			$searchsql .= natural_search(array('f.ref'), $search_ref);
		} else {
			$searchsql .= natural_search('f.ref', $search_ref);
		}
	}
	if ($search_ref_supplier) {
		$searchsql .= natural_search('f.ref_supplier', $search_ref_supplier);
	}
	if ($search_account) {
		$searchsql .= natural_search(array('ba.ref', 'ba.label', 'ba.bank'), $search_account);
	}
	if ($search_datelimit_start) {
		$searchsql .= " AND f.date_lim_reglement >= '" . $db->idate($search_datelimit_start) . "'";
	}
	if ($search_datelimit_end) {
		$searchsql .= " AND f.date_lim_reglement <= '" . $db->idate($search_datelimit_end) . "'";
	}
	if ($option == 'late') {
		$searchsql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
	}
	if ($search_company) {
		$searchsql .= natural_search('s.nom', $search_company);
	}
	if ($filter && $filter != -1) {
		$aFilter = explode(',', $filter);
		foreach ($aFilter as $fil) {
			$filt = explode(':', $fil);
			$searchsql .= ' AND '.$db->escape(trim($filt[0]))." = '".$db->escape(trim($filt[1]))."'";
		}
	}
} else {
	if ($search_ref) {
        $searchsql .= natural_search('s.rowid', $search_ref);
    }
	if ($search_employee) {
		$searchsql .= natural_search(array('u.lastname', 'u.firstname'), $search_employee);
	}
	if ($search_amount) {
		$search_amount = trim($search_amount);
		// Vérifier si l’utilisateur a mis un signe de comparaison
		if (preg_match('/^(<=|>=|=|<|>){0,1}\s*([\d\.]+)$/', $search_amount, $matches)) {
			$operator = $matches[1] ?: '='; // si pas d’opérateur, on met '='
			$value = (float) $matches[2];

			$searchsql .= " AND pd.amount $operator " . ((float) $value);
		} else {
			// fallback si la saisie n’est pas conforme
			$searchsql .= " AND pd.amount = " . ((float) $search_amount);
		}
	}
	if ($search_date_demande_start) {
		$searchsql .= " AND pd.date_demande >= '" . $db->idate($search_date_demande_start) . "'";
	}
	if ($search_date_demande_end) {
		$searchsql .= " AND pd.date_demande <= '" . $db->idate($search_date_demande_end) . "'";
	}

}

$sql .= !empty($searchsql) ? $searchsql : '';

if (!$search_all) {
    if ($sourcetype != 'salary') {
        $sql .= " GROUP BY f.rowid, f.ref, f.date_lim_reglement, s.rowid, s.nom";
    } else {
        $sql .= " GROUP BY s.rowid, s.ref, s.amount, u.rowid, u.lastname, u.firstname";
    }
} else {
    $searchsql .= natural_search(array_keys($fieldstosearchall), $search_all);
    $sql .= $searchsql;
}

$sql .= $db->order($sortfield, $sortorder);
// InfraS add end

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

	$param = '';
	// InfraS add begin
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_all) {
		$param .= '&search_all='.urlencode($search_all);
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
	if ($search_date_demande_startday) {
		$param .= '&search_date_demande_startday='.urlencode($search_date_demande_startday);
	}
	if ($search_date_demande_startmonth) {
		$param .= '&search_date_demande_startmonth='.urlencode($search_date_demande_startmonth);
	}
	if ($search_date_demande_startyear) {
		$param .= '&search_date_demande_startyear='.urlencode($search_date_demande_startyear);
	}
	if ($search_date_demande_endday) {
		$param .= '&search_date_demande_endday='.urlencode($search_date_demande_endday);
	}
	if ($search_date_demande_endmonth) {
		$param .= '&search_date_demande_endmonth='.urlencode($search_date_demande_endmonth);
	}
	if ($search_date_demande_endyear) {
		$param .= '&search_date_demande_endyear='.urlencode($search_date_demande_endyear);
	}
	if ($search_ref) {
		$param .= '&search_ref='.urlencode($search_ref);
	}
	if ($search_company) {
		$param .= '&search_company='.urlencode($search_company);
	}
	if ($search_employee) {
		$param .= '&search_employee='.urlencode($search_employee);
	}
	if ($search_amount) {
		$param .= '&search_amount='.urlencode($search_amount);
	}
	if ($sourcetype) {
		$param .= '&sourcetype=' . urlencode((string) $sourcetype);
	}
	// InfraS add end 
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

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	// InfraS add begin
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="sourcetype" value="'.$sourcetype.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	// InfraS add end
	if (!empty($limit)) {
		print '<input type="hidden" name="limit" value="'.$limit.'"/>';
	}
	if ($type != '') {
		print '<input type="hidden" name="type" value="'.$type.'">';
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
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, '', '', $massactionbutton, $num, $nbtotalofrecords, $picto, 0, '', '', $limit, 0, 0, 1); // InfraS change 

	$tradinvoice = "Invoice";
	if ($type == 'bank-transfer') {
		if ($sourcetype != 'salary') {
			$tradinvoice = "SupplierInvoice";
		} else {
			$tradinvoice = "RefSalary";
		}
	}

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
		// InfraS change begin
	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '')); // This also change content of $arrayfields
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

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
	if ($sourcetype != 'salary' && !empty($arrayfields['f.ref']['checked'])) {
		// Cas facture/fournisseur : afficher input
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	} elseif ($sourcetype == 'salary' && !empty($arrayfields['s.rowid']['checked'])) {
		// Cas salaire : afficher input
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth50" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	} 
	// Ref suppllier
	if ($sourcetype != 'salary' && !empty($arrayfields['f.ref_supplier']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth50" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'">';
		print '</td>';
	}
	// Date due
	if ($sourcetype != 'salary' && !empty($arrayfields['f.date_lim_reglement']['checked'])) {
		print '<td class="liste_titre center">';
		print '<div class="nowrap">';
		print $form->selectDate($search_datelimit_end ? $search_datelimit_end : -1, 'search_datelimit_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("Before"));
		print '<br><input type="checkbox" name="search_option" value="late"'.($option == 'late' ? ' checked' : '').'> '.$langs->trans("Alert");
		print '</div>';
		print '</td>';
	}
	// Thirpdarty
	if ($sourcetype != 'salary') {
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_company" value="'.$search_company.'"></td>';
		}
	} else {
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="liste_titre">';
			print '<input class="flat maxwidth50" type="text" name="search_employee" value="'.$search_employee.'">';
			print '</td>';
		}
	}
	// bank account
	if (!empty($arrayfields['f.fk_account']['checked'])) {
		print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_account" value="'.$search_account.'"></td>';
	}
	// RIB
	if (!empty($arrayfields['pd.fk_soc_rib']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Amount
	if ($sourcetype != 'salary') {
		if (!empty($arrayfields['pd.amount']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		} 
	} else {
		if (!empty($arrayfields['pd.amount']['checked'])) {
			print '<td class="liste_titre"><input class="flat maxwidth50" type="text" name="search_amount" value="'.$search_amount.'"></td>';
		}
	}
	// Date request
	if ($sourcetype != 'salary') {
		if (!empty($arrayfields['pd.date_demande']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
	} else {
		if (!empty($arrayfields['pd.date_demande']['checked'])) {
			print '<td class="liste_titre center">';
			print '<div class="nowrap">';
			print $form->selectDate($search_date_demande_end ? $search_date_demande_end : -1, 'search_date_demande_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("Before"));
			print '</div>';
		}
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center maxwidthsearch">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print "</tr>\n";

	print '<tr class="liste_titre">';
	// Infras change end
	// Ref invoice or salary
	// InfraS add begin
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}
	// InfraS add end
	// InfraS change begin
	$refKey   = ($sourcetype != 'salary') ? 'f.ref' : 's.rowid';
	$rowidKey = ($sourcetype != 'salary') ? 'f.rowid' : 's.rowid';
	if (!empty($arrayfields[$refKey]['checked'])) {
		$label = ($sourcetype != 'salary') ? $langs->trans($type == 'bank-transfer' ? "SupplierInvoice" : "Invoice") : $langs->trans("RefSalary");
		print_liste_field_titre($label, $_SERVER['PHP_SELF'], $refKey.','.$rowidKey, '', $param, '', $sortfield, $sortorder);
	}

	// Ref supplier
	if (!empty($arrayfields['f.ref_supplier']['checked'])) {
		if ($type == 'bank-transfer' && $sourcetype != 'salary') {
			print_liste_field_titre($langs->trans("RefSupplier"), $_SERVER['PHP_SELF'], 'f.ref_supplier,f.rowid', '', $param, '', $sortfield, $sortorder);
		}
	}
	// Date limite
	if (!empty($arrayfields['f.date_lim_reglement']['checked'])) {
		print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'], $_SERVER['PHP_SELF'], 'f.date_lim_reglement', '', $param, '', $sortfield, $sortorder, 'center ');
	}
	// Thirdparty or user
	if (!empty($arrayfields['s.nom']['checked'])) {
		if ($sourcetype != 'salary') {
			print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER['PHP_SELF'], 's.nom', '', $param, '', $sortfield, $sortorder);
		} else {
			print_liste_field_titre($langs->trans("Employee"), $_SERVER['PHP_SELF'], 'u.lastname', '', $param, '', $sortfield, $sortorder);
		}
	}
	// Bank account
	if (!empty($arrayfields['f.fk_account']['checked'])) {
		print_liste_field_titre($arrayfields['f.fk_account']['label'], $_SERVER['PHP_SELF'], 'f.fk_account', '', $param, '', $sortfield, $sortorder);
	}
	// BAN
	if (!empty($arrayfields['pd.fk_soc_rib']['checked'])) {
		if ($sourcetype != 'salary') {
			print_liste_field_titre($langs->trans("SupplierIBAN"), '', '', '', $param, '', $sortfield, $sortorder);
		} else {
			print_liste_field_titre($langs->trans("SalaryIBAN"), '', '', '', $param, '', $sortfield, $sortorder);
		}
	}
	// RUM
	if (empty($type) || $type == 'direc-debit') {
		print '<td>'.$langs->trans("RUM").'</td>';
	}
	if (!empty($arrayfields['pd.amount']['checked'])) {
		if ($sourcetype == 'salary') {
			print_liste_field_titre($langs->trans("Amount"), $_SERVER['PHP_SELF'], 'pd.amount', '', $param, '', $sortfield, $sortorder, 'center');
		} else {
			print_liste_field_titre($langs->trans("AmountTTC"), $_SERVER['PHP_SELF'], 'pd.amount', '', $param, '', $sortfield, $sortorder, 'center');
		}
	}
	if (!empty($arrayfields['pd.date_demande']['checked'])) {
		print_liste_field_titre($langs->trans("PendingSince"), $_SERVER['PHP_SELF'], 'pd.date_demande', '', $param, '', $sortfield, $sortorder, 'center');
	}
	// InfraS change end
	// InfraS add begin
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	}
	// InfraS add end
	print '</tr>';

	if ($num) {
		// InfraS add begin
		// Initialiser totalarray si pas déjà fait
		if (!isset($totalarray['nbfield'])) {
			$totalarray['nbfield'] = 0;
		}
		if (!isset($totalarray['pos'])) {
			$totalarray['pos'] = array();
		}
		if (!isset($totalarray['val'])) {
			$totalarray['val'] = array();
		}
		if (!isset($totalarray['val']['pd.amount'])) {
			$totalarray['val']['pd.amount'] = 0;
		}
		// InfraS add end
		if ($sourcetype != 'salary') {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		} else {
			require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
			require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
		}

		while ($i < $num && $i < $limit) {
			$obj = $db->fetch_object($resql);
			if ($sourcetype != 'salary') {
				$bankaccountstatic = new Account($db); // InfraS add
				$bac = new CompanyBankAccount($db);	// Must include the new in loop so the fetch is clean
				$bac->fetch($obj->soc_rib_id ?? 0, '', $obj->socid);
				// InfraS add begin
				$datelimit = $db->jdate($obj->datelimite);
				$invoicestatic->fetch($obj->rowid);
				// InfraS add end

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
			}
			print '<tr class="oddeven">';

			// InfraS change begin
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

			// Ref invoice
			$refKey = ($sourcetype != 'salary') ? 'f.ref' : 's.rowid';
			if (!empty($arrayfields[$refKey]['checked'])) {
				print '<td class="tdoverflowmax150">';
				if ($sourcetype != 'salary' || $salary === null) {
					print $invoicestatic->getNomUrl(1, 'withdraw');
				} else {
					print $salary->getNomUrl(1, 'withdraw');
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			// Ref supplier
			if (!empty($arrayfields['f.ref_supplier']['checked'])) {
				if ($type == 'bank-transfer' && $sourcetype != 'salary') {
					print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($invoicestatic->ref_supplier).'">';
					print dol_escape_htmltag($invoicestatic->ref_supplier);
					print '</td>';
				}
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
			
			// Thirdparty
			if (!empty($arrayfields['s.nom']['checked'])) {
				if ($sourcetype != 'salary') {
					print '<td class="tdoverflowmax100">';
					$thirdpartystatic->fetch($obj->socid);
					print $thirdpartystatic->getNomUrl(1, 'ban');
					print '</td>';
				} else {
					print '<td class="tdoverflowmax100">';
					$user->fetch($obj->uid);
					print $user->getNomUrl(withpictoimg: -1);
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
					print '<input type="hidden" name="account_searched" value="'.$obj->fk_account.'">';
					print "</td>\n";
				}
				else	print '<td class="tdoverflowmax200">&nbsp;</td>'."\n";
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
			
			// RIB
			if (!empty($arrayfields['pd.fk_soc_rib']['checked'])) {
				print '<td>';
				if ($bac->id > 0) {
					if (!empty($bac->iban) || !empty($bac->bic)) {
						print $bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
						if ($bac->verif() <= 0) {
							print img_warning('Error on default bank number for IBAN : '.$langs->trans($bac->error));
						}
					} else {
						print img_warning($langs->trans("IBANNotDefined"));
					}
				} else {
					$langs->load("banks");
					print img_warning($langs->trans("NoBankAccountDefined"));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

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
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Amount
			if (!empty($arrayfields['pd.amount']['checked'])) {
				print '<td class="center nowrap"><span id="amount_'.$obj->request_row_id.'" class="amount">'.price($obj->amount)."</span></td>\n";
				if (!$i) {
					$totalarray['nbfield']++;
					$totalarray['pos'][$totalarray['nbfield']] = 'pd.amount';
				}
				$totalarray['val']['pd.amount'] += $obj->amount;
			}
			// Date
			if (!empty($arrayfields['pd.date_demande']['checked'])) {
				print '<td class="center nowrap">';
				print dol_print_date($db->jdate($obj->date_demande), 'day');
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
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
			// InfraS change end
			print '</tr>';
			$i++;
		}
	} else {
		$colspan = 8; // InfraS change
		if ($type == 'bank-transfer') {
			$colspan++;
		}
		if ($massactionbutton || $massaction) {
			$colspan++;
		}
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table>";
	// InfraS add begin
	?>
	<script>
	function computeTotalChecked() {
		console.log($('[id^="cb"]').length, $('[id^="cb"]'));
		let total_checked = 0;
		let checked_pd = Array.from($('[id^="cb"]').filter(':checked'));
		checked_pd.forEach((pd) => {
			let amount = Number(pd.getAttribute('amount'));
			total_checked += amount;
		})
		let precision = Math.pow(10, <?= getDolGlobalInt('MAIN_MAX_DECIMALS_TOT') ?>);
		$('#total_checked').val(Math.round(total_checked * precision) / precision);
	}

	$('[id^="cb"]').change(computeTotalChecked);
	computeTotalChecked();

	</script>
	<?php
	// InfraS add end
	print "</div>";

	print "</form>";
	print "<br>\n";
} else {
	dol_print_error($db);
}
// InfraS add begin
?>
<script>
function updateToselectHidden() {
    // Récupère toutes les checkbox cochées
    let checked_pd = Array.from($('[id^="cb"]').filter(':checked'));

    // On récupère le formulaire cible
    let targetForm = document.getElementById('createFilePayment');

    // Supprime les anciens champs toselect[] s'ils existent
    targetForm.querySelectorAll('input[name="toselect[]"]').forEach(el => el.remove());

    // Crée un input hidden pour chaque checkbox cochée
    checked_pd.forEach(pd => {
        let hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'toselect[]';
        hidden.value = pd.value;
        targetForm.appendChild(hidden);
    });

    // Optionnel : mettre à jour le total affiché
    let total_checked = checked_pd.reduce((sum, pd) => sum + Number(pd.getAttribute('amount')), 0);
    let precision = Math.pow(10, <?= getDolGlobalInt('MAIN_MAX_DECIMALS_TOT') ?>);
    $('#total_checked').val(Math.round(total_checked * precision) / precision);

    // console.log('Mise à jour du hidden toselect[]', checked_pd.map(pd => pd.value));
}

// On écoute le changement sur toutes les checkbox
$('[id^="cb"]').change(updateToselectHidden);

// Calcul initial
updateToselectHidden();
</script>

<?php
// InfraS add end

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
