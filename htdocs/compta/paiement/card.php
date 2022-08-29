<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Marcos García		 <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Juanjo Menent		 <jmenent@2byte.es>
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
 *	    \file       htdocs/compta/paiement/card.php
 *		\ingroup    facture
 *		\brief      Page of a customer payment
 *		\remarks	Nearly same file than fournisseur/paiement/card.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
if (isModEnabled('banque')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$object = new Paiement($db);
// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentcard', 'globalcard'));

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$result = restrictedArea($user, $object->element, $object->id, 'paiement');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Now check also permission on thirdparty of invoices of payments. Thirdparty were loaded by the fetch_object before based on first invoice.
// It should be enough because all payments are done on invoices of the same thirdparty.
if ($socid && $socid != $object->thirdparty->id) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->facture->paiement) {
	$db->begin();

	$result = $object->update_note(GETPOST('note', 'restricthtml'));
	if ($result > 0) {
		$db->commit();
		$action = '';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->facture->paiement) {
	$db->begin();

	$result = $object->delete();
	if ($result > 0) {
		$db->commit();

		if ($backtopage) {
			header("Location: ".$backtopage);
			exit;
		} else {
			header("Location: list.php");
			exit;
		}
	} else {
		$langs->load("errors");
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->facture->paiement) {
	$db->begin();

	if ($object->validate($user) > 0) {
		$db->commit();

		// Loop on each invoice linked to this payment to rebuild PDF
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
			$outputlangs = $langs;
			if (GETPOST('lang_id', 'aZ09')) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
			}

			$hidedetails = ! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0;
			$hidedesc = ! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0;
			$hideref = !empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0;

			$sql = 'SELECT f.rowid as facid';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
			$sql .= ' WHERE pf.fk_facture = f.rowid';
			$sql .= ' AND f.fk_soc = s.rowid';
			$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
			$sql .= ' AND pf.fk_paiement = '.((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$i = 0;
				$num = $db->num_rows($resql);

				if ($num > 0) {
					while ($i < $num) {
						$objp = $db->fetch_object($resql);

						$invoice = new Facture($db);

						if ($invoice->fetch($objp->facid) <= 0) {
							$errors++;
							setEventMessages($invoice->error, $invoice->errors, 'errors');
							break;
						}

						if ($invoice->generateDocument($invoice->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref) < 0) {
							$errors++;
							setEventMessages($invoice->error, $invoice->errors, 'errors');
							break;
						}

						$i++;
					}
				}

				$db->free($resql);
			} else {
				$errors++;
				setEventMessages($db->error, $db->errors, 'errors');
			}
		}

		if (! $errors) {
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
			exit;
		}
	} else {
		$db->rollback();

		$langs->load("errors");
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'setnum_paiement' && GETPOST('num_paiement')) {
	$res = $object->update_num(GETPOST('num_paiement'));
	if ($res === 0) {
		setEventMessages($langs->trans('PaymentNumberUpdateSucceeded'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('PaymentNumberUpdateFailed'), null, 'errors');
	}
}

if ($action == 'setdatep' && GETPOST('datepday')) {
	$datepaye = dol_mktime(GETPOST('datephour', 'int'), GETPOST('datepmin', 'int'), GETPOST('datepsec', 'int'), GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'));
	$res = $object->update_date($datepaye);
	if ($res === 0) {
		setEventMessages($langs->trans('PaymentDateUpdateSucceeded'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('PaymentDateUpdateFailed'), null, 'errors');
	}
}
if ($action == 'createbankpayment' && !empty($user->rights->facture->paiement)) {
	$db->begin();

	// Create the record into bank for the amount of payment $object
	if (!$error) {
		$label = '(CustomerInvoicePayment)';
		if (GETPOST('type') == Facture::TYPE_CREDIT_NOTE) {
			$label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
		}

		$bankaccountid = GETPOST('accountid', 'int');
		if ($bankaccountid > 0) {
			$object->paiementcode = $object->type_code;
			$object->amounts = $object->getAmountsArray();

			$result = $object->addPaymentToBank($user, 'payment', $label, $bankaccountid, '', '');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		} else {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
			$error++;
		}
	}


	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("Payment"));

$thirdpartystatic = new Societe($db);

$result = $object->fetch($id, $ref);
if ($result <= 0) {
	dol_print_error($db, 'Payement '.$id.' not found in database');
	exit;
}

$form = new Form($db);

$head = payment_prepare_head($object);

print dol_get_fiche_head($head, 'payment', $langs->trans("PaymentCustomerInvoice"), -1, 'payment');

// Confirmation of payment delete
if ($action == 'delete') {
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}

// Confirmation of payment validation
if ($action == 'valide') {
	$facid = $_GET['facid'];
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_validate', '', 0, 2);
}

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">'."\n";

// Date payment
print '<tr><td class="titlefield">'.$form->editfieldkey("Date", 'datep', $object->date, $object, $user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Date", 'datep', $object->date, $object, $user->rights->facture->paiement, 'datehourpicker', '', null, $langs->trans('PaymentDateUpdateSucceeded'), '', 0, '', 'id', 'tzuser');
print '</td></tr>';

// Payment type (VIR, LIQ, ...)
$labeltype = $langs->trans("PaymentType".$object->type_code) != ("PaymentType".$object->type_code) ? $langs->trans("PaymentType".$object->type_code) : $object->type_label;
print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>'.$labeltype;
print $object->num_payment ? ' - '.$object->num_payment : '';
print '</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, '', $langs, 0, -1, -1, $conf->currency).'</td></tr>';

$disable_delete = 0;
// Bank account
if (isModEnabled('banque')) {
	$bankline = new AccountLine($db);

	if ($object->fk_account > 0) {
		$bankline->fetch($object->bank_line);
		if ($bankline->rappro) {
			$disable_delete = 1;
			$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
		}

		print '<tr>';
		print '<td>'.$langs->trans('BankAccount').'</td>';
		print '<td>';
		$accountstatic = new Account($db);
		$accountstatic->fetch($bankline->fk_account);
		print $accountstatic->getNomUrl(1);
		print '</td>';
		print '</tr>';
	}
}

// Payment numero
/*
$titlefield=$langs->trans('Numero').' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
print '</td></tr>';

// Check transmitter
$titlefield=$langs->trans('CheckTransmitter').' <em>('.$langs->trans("ChequeMaker").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqemetteur',$object->,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqemetteur',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeMakeUpdateSucceeded'));
print '</td></tr>';

// Bank name
$titlefield=$langs->trans('Bank').' <em>('.$langs->trans("ChequeBank").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeBankUpdateSucceeded'));
print '</td></tr>';
*/

// Bank account
if (isModEnabled('banque')) {
	if ($object->fk_account > 0) {
		if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0) {
			include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
			$bordereau = new RemiseCheque($db);
			$bordereau->fetch($bankline->fk_bordereau);

			print '<tr>';
			print '<td>'.$langs->trans('CheckReceipt').'</td>';
			print '<td>';
			print $bordereau->getNomUrl(1);
			print '</td>';
			print '</tr>';
		}
	}

	print '<tr>';
	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
	print '<td>';
	if ($object->fk_account > 0) {
		print $bankline->getNomUrl(1, 0, 'showconciliatedandaccounted');
	} else {
		$langs->load("admin");
		print '<span class="opacitymedium">';
		print $langs->trans("NoRecordFoundIBankcAccount", $langs->transnoentitiesnoconv("Module85Name"));
		print '</span>';
		if (!empty($user->rights->facture->paiement)) {
			// Try to guess $bankaccountidofinvoices that is ID of bank account defined on invoice.
			// Return null if not found, return 0 if it has different value for at least 2 invoices, return the value if same on all invoices where a bank is defined.
			$amountofpayments = $object->getAmountsArray();
			$bankaccountidofinvoices = null;
			foreach ($amountofpayments as $idinvoice => $amountofpayment) {
				$tmpinvoice = new Facture($db);
				$tmpinvoice->fetch($idinvoice);
				if ($tmpinvoice->fk_account > 0 && $bankaccountidofinvoices !== 0) {
					if (is_null($bankaccountidofinvoices)) {
						$bankaccountidofinvoices = $tmpinvoice->fk_account;
					} elseif ($bankaccountidofinvoices != $tmpinvoice->fk_account) {
						$bankaccountidofinvoices = 0;
					}
				}
			}

			print '<form method="POST" name="createbankpayment">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="createbankpayment">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print ' '.$langs->trans("ToCreateRelatedRecordIntoBank").': ';
			print $form->select_comptes($bankaccountidofinvoices, 'accountid', 0, '', 2, '', 0, '', 1);
			//print '<span class="opacitymedium">';
			print '<input type="submit" class="button small smallpaddingimp" name="createbankpayment" value="'.$langs->trans("ClickHere").'">';
			//print '</span>';
			print '</form>';
		}
	}
	print '</td>';
	print '</tr>';
}

// Comments
print '<tr><td class="tdtop">'.$form->editfieldkey("Comments", 'note', $object->note, $object, $user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Note", 'note', $object->note, $object, $user->rights->facture->paiement, 'textarea:'.ROWS_3.':90%');
print '</td></tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();


/*
 * List of invoices
 */

$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total_ttc, f.paye, f.entity, f.fk_statut, pf.amount, s.nom as name, s.rowid as socid';
$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql .= ' WHERE pf.fk_facture = f.rowid';
$sql .= ' AND f.fk_soc = s.rowid';
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
$sql .= ' AND pf.fk_paiement = '.((int) $object->id);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;

	$moreforfilter = '';

	print '<br>';

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Bill').'</td>';
	print '<td>'.$langs->trans('Company').'</td>';
	if (isModEnabled('multicompany') && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED)) {
		print '<td>'.$langs->trans('Entity').'</td>';
	}
	print '<td class="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td class="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print '<td class="right">'.$langs->trans('RemainderToPay').'</td>';
	print '<td class="right">'.$langs->trans('Status').'</td>';
	print "</tr>\n";

	if ($num > 0) {
		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			$thirdpartystatic->fetch($objp->socid);

			$invoice = new Facture($db);
			$invoice->fetch($objp->facid);

			$paiement = $invoice->getSommePaiement();
			$creditnotes = $invoice->getSumCreditNotesUsed();
			$deposits = $invoice->getSumDepositsUsed();
			$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
			$remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

			print '<tr class="oddeven">';

			// Invoice
			print '<td>';
			print $invoice->getNomUrl(1);
			print "</td>\n";

			// Third party
			print '<td class="tdoverflowmax150">';
			print $thirdpartystatic->getNomUrl(1);
			print '</td>';

			// Expected to pay
			if (isModEnabled('multicompany') && !empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED)) {
				print '<td>';
				$mc->getInfo($objp->entity);
				print $mc->label;
				print '</td>';
			}
			// Expected to pay
			print '<td class="right"><span class="amount">'.price($objp->total_ttc).'</span></td>';

			// Amount payed
			print '<td class="right"><span class="amount">'.price($objp->amount).'</span></td>';

			// Remain to pay
			print '<td class="right"><span class="amount">'.price($remaintopay).'</span></td>';

			// Status
			print '<td class="right">'.$invoice->getLibStatut(5, $alreadypayed).'</td>';

			print "</tr>\n";

			// If at least one invoice is paid, disable delete. INVOICE_CAN_DELETE_PAYMENT_EVEN_IF_INVOICE_CLOSED Can be use for maintenance purpose. Never use this in production
			if ($objp->paye == 1 && empty($conf->global->INVOICE_CAN_DELETE_PAYMENT_EVEN_IF_INVOICE_CLOSED)) {
				$disable_delete = 1;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid"));
			}

			$total = $total + $objp->amount;
			$i++;
		}
	}


	print "</table>\n";
	print '</div>';

	$db->free($resql);
} else {
	dol_print_error($db);
}



/*
 * Actions Buttons
 */

print '<div class="tabsAction">';

if (!empty($conf->global->BILL_ADD_PAYMENT_VALIDATION)) {
	if ($user->socid == 0 && $object->statut == 0 && $_GET['action'] == '') {
		if ($user->rights->facture->paiement) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&facid='.$objp->facid.'&action=valide&token='.newToken().'">'.$langs->trans('Valid').'</a>';
		}
	}
}

if ($user->socid == 0 && $action == '') {
	if ($user->rights->facture->paiement) {
		if (!$disable_delete) {
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$title_button.'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

// End of page
llxFooter();
$db->close();
