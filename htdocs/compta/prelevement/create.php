<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Markus Welters          <markus@welters.de>
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

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies', 'bills'));

// Get supervariables
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : 'real';

$type = GETPOST('type', 'aZ09');
$sourcetype = GETPOST('sourcetype', 'aZ09');
$format = GETPOST('format', 'aZ09');
$id_bankaccount = GETPOST('id_bankaccount', 'int');
$executiondate = dol_mktime(0, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;

$hookmanager->initHooks(array('directdebitcreatecard', 'globalcard'));

// Security check
$socid = GETPOST('socid', 'int');
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
	// Change customer bank information to withdraw
	/*
	if ($action == 'modify') {
		for ($i = 1; $i < 9; $i++) {
			dolibarr_set_const($db, GETPOST("nom".$i), GETPOST("value".$i), 'chaine', 0, '', $conf->entity);
		}
	}
	*/
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


		$bprev = new BonPrelevement($db);

		if (!$error) {
			// getDolGlobalString('PRELEVEMENT_CODE_BANQUE') and getDolGlobalString('PRELEVEMENT_CODE_GUICHET') should be empty (we don't use them anymore)
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

				header("Location: ".DOL_URL_ROOT.'/compta/prelevement/card.php?id='.urlencode($bprev->id).'&type='.urlencode($type));
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
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
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


$head = bon_prelevement_prepare_head($bprev, $bprev->nbOfInvoiceToPay($type), $bprev->nbOfInvoiceToPay($type, 'salary'));
if ($type) {
	print dol_get_fiche_head($head, (!GETPOSTISSET('sourcetype') ? 'invoice' : 'salary'), $langs->trans("Invoices"), -1, $bprev->picto);
} else {
	print load_fiche_titre($title);
	print dol_get_fiche_head();
}


if ($sourcetype != 'salary') {
	$nb = $bprev->nbOfInvoiceToPay($type);
	$pricetowithdraw = $bprev->SommeAPrelever($type);
} else {
	$nb = $bprev->nbOfInvoiceToPay($type, 'salary');
	$pricetowithdraw = $bprev->SommeAPrelever($type, 'salary');
}
if ($nb < 0) {
	dol_print_error($bprev->error);
}
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
print dol_escape_htmltag($nb);
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountTotal").'</td>';
print '<td class="amount nowraponall">';
print price($pricetowithdraw, 0, $langs, 1, -1, -1, $conf->currency);
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<div class="tabsAction">'."\n";

print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
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
		print img_picto('', 'bank_account');

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


		if ($mysoc->isInEEC()) {
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
			print '<input type="submit" class="butAction margintoponly maringbottomonly" value="'.$title.'"/>';
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<input type="hidden" name="format" value="ALL">'."\n";
			print '<input type="submit" class="butAction margintoponly maringbottomonly" value="'.$title.'">'."\n";
		}
	} else {
		if ($mysoc->isInEEC()) {
			$title = $langs->trans("CreateForSepaFRST");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateSepaFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";

			if ($type != 'bank-transfer') {
				$title = $langs->trans("CreateForSepaRCUR");
				print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.$langs->trans("AmountMustBePositive").'">'.$title."</a>\n";
			}
		} else {
			$title = $langs->trans("CreateAll");
			if ($type == 'bank-transfer') {
				$title = $langs->trans("CreateFileForPaymentByBankTransfer");
			}
			print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#">'.$title."</a>\n";
		}
	}
} else {
	$titlefortab = $langs->transnoentitiesnoconv("StandingOrders");
	$title = $langs->trans("CreateAll");
	if ($type == 'bank-transfer') {
		$titlefortab = $langs->transnoentitiesnoconv("PaymentByBankTransfers");
		$title = $langs->trans("CreateFileForPaymentByBankTransfer");
	}
	print '<a class="butActionRefused classfortooltip margintoponly maringbottomonly" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoInvoiceToWithdraw", $titlefortab, $titlefortab)).'">';
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
	$sql = "SELECT f.ref, f.rowid, f.total_ttc, s.nom as name, s.rowid as socid,";
	if ($type == 'bank-transfer') {
		$sql .= " f.ref_supplier,";
	}
	$sql .= " pd.rowid as request_row_id, pd.date_demande, pd.amount";
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
	$sql .= " pd.rowid as request_row_id, pd.date_demande, pd.amount";
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

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {
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
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($socid) {
		$param .= '&socid='.urlencode($socid);
	}
	if ($option) {
		$param .= "&option=".urlencode($option);
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	if (!empty($limit)) {
		print '<input type="hidden" name="limit" value="'.$limit.'"/>';
	}
	if ($type != '') {
		print '<input type="hidden" name="type" value="'.$type.'">';
	}
	$title = $langs->trans("InvoiceWaitingWithdraw");
	$picto = 'bill';
	if ($type =='bank-transfer') {
		if ($sourcetype != 'salary') {
			$title = $langs->trans("InvoiceWaitingPaymentByBankTransfer");
		} else {
			$title = $langs->trans("SalaryWaitingWithdraw");
			$picto = 'salary';
		}
	}
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, '', '', $massactionbutton, $num, $nbtotalofrecords, $picto, 0, '', '', $limit);


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
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
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
	if (empty($type) || $type == 'direc-debit') {
		print '<td>'.$langs->trans("RUM").'</td>';
	}
	print '<td class="right">';
	if ($sourcetype == 'salary') {
		print $langs->trans("Amount");
	} else {
		print $langs->trans("AmountTTC");
	}
	print '</td>';
	print '<td class="right">'.$langs->trans("DateRequest").'</td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			print '<td align="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
		}
	}
	print '</tr>';

	if ($num) {
		if ($sourcetype != 'salary') {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		} else {
			require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
			require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
		}

		while ($i < $num && $i < $limit) {
			$obj = $db->fetch_object($resql);
			if ($sourcetype != 'salary') {
				$bac = new CompanyBankAccount($db);	// Must include the new in loop so the fetch is clean
				$bac->fetch(0, $obj->socid);

				$invoicestatic->id = $obj->rowid;
				$invoicestatic->ref = $obj->ref;
				$invoicestatic->ref_supplier = $obj->ref_supplier;
			} else {
				$bac = new UserBankAccount($db);
				$bac->fetch(0, '', $obj->uid);

				$salary = new Salary($db);
				$salary->fetch($obj->rowid);
			}
			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					print '<td class="nowrap center">';
					$selected = 0;
					if (in_array($obj->request_row_id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').'>';
					print '</td>';
				}
			}

			// Ref invoice
			print '<td class="tdoverflowmax150">';
			if ($sourcetype != 'salary') {
				print $invoicestatic->getNomUrl(1, 'withdraw');
			} else {
				print $salary->getNomUrl(1, 'withdraw');
			}
			print '</td>';

			if ($type == 'bank-transfer' && $sourcetype != 'salary') {
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($invoicestatic->ref_supplier).'">';
				print dol_escape_htmltag($invoicestatic->ref_supplier);
				print '</td>';
			}

			// Thirdparty
			if ($sourcetype != 'salary') {
				print '<td class="tdoverflowmax100">';
				$thirdpartystatic->fetch($obj->socid);
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
				} else {
					print img_warning($langs->trans("IBANNotDefined"));
				}
			} else {
				print img_warning($langs->trans("NoBankAccountDefined"));
			}
			print '</td>';

			// RUM
			if (empty($type) || $type == 'direct-debit') {
				print '<td>';
				$rumtoshow = $thirdpartystatic->display_rib('rum');
				if ($rumtoshow) {
					print $rumtoshow;
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
			}

			// Amount
			print '<td class="right amount">';
			print price($obj->amount, 0, $langs, 0, 0, -1, $conf->currency);
			print '</td>';
			// Date
			print '<td class="right">';
			print dol_print_date($db->jdate($obj->date_demande), 'day');
			print '</td>';
			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					print '<td class="nowrap center">';
					$selected = 0;
					if (in_array($obj->request_row_id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb'.$obj->request_row_id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->request_row_id.'"'.($selected ? ' checked="checked"' : '').'>';
					print '</td>';
				}
			}
			print '</tr>';
			$i++;
		}
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
