<?php
/* Copyright (C) 2005      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2006-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García         <marcosgdf@gmail.com>
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
 *	\file       htdocs/fourn/paiement/card.php
 *	\ingroup    facture, fournisseur
 *	\brief      Tab to show a payment of a supplier invoice
 *	\remarks	Fichier presque identique a compta/paiement/card.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

$langs->loadLangs(array('bills', 'banks', 'companies', 'suppliers'));

$id = GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');

$object = new PaiementFourn($db);
// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('supplierpaymentcard', 'globalcard'));

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$result = restrictedArea($user, $object->element, $object->id, 'paiementfourn', '');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Now check also permission on thirdparty of invoices of payments. Thirdparty were loaded by the fetch_object before based on first invoice.
// It should be enough because all payments are done on invoices of the same thirdparty.
if ($socid && $socid != $object->thirdparty->id) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'setnote' && ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer)) {
	$db->begin();

	$object->fetch($id);
	$result = $object->update_note(GETPOST('note', 'restricthtml'));
	if ($result > 0) {
		$db->commit();
		$action = '';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->facture->supprimer) {
	$db->begin();

	$object->fetch($id);
	$result = $object->delete();
	if ($result > 0) {
		$db->commit();
		header('Location: '.DOL_URL_ROOT.'/fourn/paiement/list.php');
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'confirm_validate' && $confirm == 'yes' &&
	((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && (!empty($user->rights->fournisseur->facture->creer) || !empty($user->rights->supplier_invoice->creer)))
	|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->validate)))
) {
	$db->begin();

	$object->fetch($id);
	if ($object->validate() >= 0) {
		$db->commit();
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'setnum_paiement' && GETPOST('num_paiement')) {
	$object->fetch($id);
	$res = $object->update_num(GETPOST('num_paiement'));
	if ($res === 0) {
		setEventMessages($langs->trans('PaymentNumberUpdateSucceeded'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('PaymentNumberUpdateFailed'), null, 'errors');
	}
}

if ($action == 'setdatep' && GETPOST('datepday')) {
	$object->fetch($id);
	$datepaye = dol_mktime(GETPOST('datephour', 'int'), GETPOST('datepmin', 'int'), GETPOST('datepsec', 'int'), GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'));
	$res = $object->update_date($datepaye);
	if ($res === 0) {
		setEventMessages($langs->trans('PaymentDateUpdateSucceeded'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('PaymentDateUpdateFailed'), null, 'errors');
	}
}

// Build document
$upload_dir = $conf->fournisseur->payment->dir_output;
// TODO: get the appropriate permission
$permissiontoadd = true;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

// Actions to send emails
$triggersendname = 'PAYMENTRECEIPT_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO';
$trackid = 'pre'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


/*
 * View
 */

llxHeader();

$result = $object->fetch($id);

$form = new Form($db);
$formfile = new FormFile($db);

$head = payment_supplier_prepare_head($object);

print dol_get_fiche_head($head, 'payment', $langs->trans('SupplierPayment'), -1, 'payment');

if ($result > 0) {
	/*
	 * Confirmation of payment's delete
	 */
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete');
	}

	/*
	 * Confirmation of payment's validation
	 */
	if ($action == 'validate') {
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_validate');
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	/*print '<tr>';
	print '<td width="20%">'.$langs->trans('Ref').'</td><td>';
	print $form->showrefnav($object,'id','',1,'rowid','ref');
	print '</td></tr>';*/

	// Date of payment
	print '<tr><td class="titlefield">'.$form->editfieldkey("Date", 'datep', $object->date, $object, $object->statut == 0 && ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer)).'</td>';
	print '<td>';
	print $form->editfieldval("Date", 'datep', $object->date, $object, $object->statut == 0 && ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer), 'datehourpicker', '', null, $langs->trans('PaymentDateUpdateSucceeded'));
	print '</td></tr>';

	// Payment mode
	$labeltype = $langs->trans("PaymentType".$object->type_code) != ("PaymentType".$object->type_code) ? $langs->trans("PaymentType".$object->type_code) : $object->type_label;
	print '<tr><td>'.$langs->trans('PaymentMode').'</td>';
	print '<td>'.$labeltype;
	print $object->num_payment ? ' - '.$object->num_payment : '';
	print '</td></tr>';

	// Payment numero
	/* TODO Add field num_payment into payment table and save it
	print '<tr><td>'.$form->editfieldkey("Numero",'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td>';
	print '<td>';
	print $form->editfieldval("Numero",'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
	print '</td></tr>';
	*/

	// Amount
	print '<tr><td>'.$langs->trans('Amount').'</td>';
	print '<td>'.price($object->amount, '', $langs, 0, 0, -1, $conf->currency).'</td></tr>';

	if (!empty($conf->global->BILL_ADD_PAYMENT_VALIDATION)) {
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td>'.$object->getLibStatut(4).'</td></tr>';
	}

	$allow_delete = 1;
	// Bank account
	if (!empty($conf->banque->enabled)) {
		if ($object->fk_account) {
			$bankline = new AccountLine($db);
			$bankline->fetch($object->bank_line);
			if ($bankline->rappro) {
				$allow_delete = 0;
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

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td>';
			print $bankline->getNomUrl(1, 0, 'showconciliated');
			print '</td>';
			print '</tr>';
		}
	}

	// Note
	print '<tr><td>'.$form->editfieldkey("Comments", 'note', $object->note, $object, ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer)).'</td>';
	print '<td>';
	print $form->editfieldval("Note", 'note', $object->note, $object, ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer), 'textarea');
	print '</td></tr>';

	print '</table>';

	print '</div>';

	print '<br>';

	/**
	 *	List of seller's invoices
	 */
	$sql = 'SELECT f.rowid, f.rowid as facid, f.ref, f.ref_supplier, f.type, f.paye, f.total_ht, f.total_tva, f.total_ttc, f.datef as date, f.fk_statut as status,';
	$sql .= ' pf.amount, s.nom as name, s.rowid as socid';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf,'.MAIN_DB_PREFIX.'facture_fourn as f,'.MAIN_DB_PREFIX.'societe as s';
	$sql .= ' WHERE pf.fk_facturefourn = f.rowid AND f.fk_soc = s.rowid';
	$sql .= ' AND pf.fk_paiementfourn = '.((int) $object->id);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		$i = 0;
		$total = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Invoice').'</td>';
		print '<td>'.$langs->trans('RefSupplier').'</td>';
		print '<td>'.$langs->trans('Company').'</td>';
		print '<td class="right">'.$langs->trans('ExpectedToPay').'</td>';
		print '<td class="right">'.$langs->trans('PayedByThisPayment').'</td>';
		print '<td class="right">'.$langs->trans('Status').'</td>';
		print "</tr>\n";

		if ($num > 0) {
			$facturestatic = new FactureFournisseur($db);

			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$facturestatic->id = $objp->facid;
				$facturestatic->ref = ($objp->ref ? $objp->ref : $objp->rowid);
				$facturestatic->date = $db->jdate($objp->date);
				$facturestatic->type = $objp->type;
				$facturestatic->total_ht = $objp->total_ht;
				$facturestatic->total_tva = $objp->total_tva;
				$facturestatic->total_ttc = $objp->total_ttc;
				$facturestatic->statut = $objp->status;
				$facturestatic->alreadypaid = -1; // unknown

				print '<tr class="oddeven">';
				// Ref
				print '<td>';
				print $facturestatic->getNomUrl(1);
				print "</td>\n";
				// Ref supplier
				print '<td>'.$objp->ref_supplier."</td>\n";
				// Third party
				print '<td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'), 'company').' '.$objp->name.'</a></td>';
				// Expected to pay
				print '<td class="right">'.price($objp->total_ttc).'</td>';
				// Paid
				print '<td class="right">'.price($objp->amount).'</td>';
				// Status
				print '<td class="right">'.$facturestatic->LibStatut($objp->paye, $objp->status, 6, 1).'</td>';
				print "</tr>\n";

				if ($objp->paye == 1) {
					$allow_delete = 0;
					$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid"));
				}
				$total = $total + $objp->amount;
				$i++;
			}
		}


		print "</table>\n";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print '</div>';


	/*
	 * Actions Buttons
	 */

	print '<div class="tabsAction">';

	// Send by mail
	if ($user->socid == 0 && $action == '') {
		$usercansend = (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->send)));
		if ($usercansend) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a>';
		} else {
			print '<span class="butActionRefused classfortooltip">'.$langs->trans('SendMail').'</span>';
		}
	}

	// Payment validation
	if (!empty($conf->global->BILL_ADD_PAYMENT_VALIDATION)) {
		if ($user->socid == 0 && $object->statut == 0 && $action == '') {
			if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && (!empty($user->rights->fournisseur->facture->creer) || !empty($user->rights->supplier_invoice->creer)))
			|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->fournisseur->supplier_invoice_advance->validate))) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Valid').'</a>';
			}
		}
	}

	// Delete payment
	if ($user->socid == 0 && $action == '') {
		if ($user->rights->fournisseur->facture->supprimer) {
			if ($allow_delete) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans('Delete').'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$title_button.'">'.$langs->trans('Delete').'</a>';
			}
		}
	}
	print '</div>';


	print '<div class="fichecenter"><div class="fichehalfleft">';

	// Generated documents
	include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
	$modellist = ModelePDFSuppliersPayments::liste_modeles($db);
	if (is_array($modellist)) {
		$ref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->fournisseur->payment->dir_output.'/'.dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER['PHP_SELF'].'?id='.$object->id;
		$genallowed = ($user->rights->fournisseur->facture->lire || $user->rights->supplier_invoice->lire);
		$delallowed = ($user->rights->fournisseur->facture->creer || $user->rights->supplier_invoice->creer);
		$modelpdf = (!empty($object->model_pdf) ? $object->model_pdf : (empty($conf->global->SUPPLIER_PAYMENT_ADDON_PDF) ? '' : $conf->global->SUPPLIER_PAYMENT_ADDON_PDF));

		print $formfile->showdocuments('supplier_payment', $ref, $filedir, $urlsource, $genallowed, $delallowed, $modelpdf, 1, 0, 0, 40, 0, '', '', '', $societe->default_lang);
		$somethingshown = $formfile->numoffiles;
	}

	print '</div><div class="fichehalfright">';
	//print '<br>';

	// List of actions on element
	/*include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions=new FormActions($db);
	$somethingshown = $formactions->showactions($object,'supplier_payment',$socid,1,'listaction'.($genallowed?'largetitle':''));
	*/

	print '</div></div>';

	// Presend form
	$modelmail = ''; //TODO: Add new 'payment receipt' model in email models
	$defaulttopic = 'SendPaymentReceipt';
	$diroutput = $conf->fournisseur->payment->dir_output;
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO';
	$trackid = 'pre'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
} else {
	$langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
