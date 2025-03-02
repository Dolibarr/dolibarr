<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/compta/tva/card.php
 *      \ingroup    tax
 *		\brief      Page of VAT payments
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/vat.lib.php';

if (isModEnabled('accounting')) {
	include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('compta', 'banks', 'bills'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'myobjectcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$refund = GETPOSTINT("refund");
if (GETPOSTISSET('auto_create_paiement') || $action === 'add') {
	$auto_create_payment = GETPOSTINT("auto_create_paiement");
} else {
	$auto_create_payment = !getDolGlobalString('CREATE_NEW_VAT_WITHOUT_AUTO_PAYMENT');
}

if (empty($refund)) {
	$refund = 0;
}

$datev = dol_mktime(12, 0, 0, GETPOSTINT("datevmonth"), GETPOSTINT("datevday"), GETPOSTINT("datevyear"));
$datep = dol_mktime(12, 0, 0, GETPOSTINT("datepmonth"), GETPOSTINT("datepday"), GETPOSTINT("datepyear"));

// Initialize a technical objects
$object = new Tva($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->tax->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('taxvatcard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

$permissiontoread = $user->hasRight('tax', 'charges', 'lire');
$permissiontoadd = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->tax->charges->supprimer || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);
$permissionnote = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('tax', 'charges', 'creer'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->tax->multidir_output[isset($object->entity) ? $object->entity : 1].'/vat';

// Security check
$socid = GETPOSTINT('socid');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', $object->id, 'tva', 'charges');


$resteapayer = 0;

/*
 * Actions
 */


$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel && !$id) {
		header("Location: list.php");
		exit;
	}

	if ($action == 'setlib' && $user->hasRight('tax', 'charges', 'creer')) {
		$object->fetch($id);
		$result = $object->setValueFrom('label', GETPOST('lib', 'alpha'), '', null, 'text', '', $user, 'TAX_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setdatev' && $user->hasRight('tax', 'charges', 'creer')) {
		$object->fetch($id);
		$object->datev = $datev;
		$result = $object->update($user);
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}

		$action = '';
	}

	// payment mode
	if ($action == 'setmode' && $user->hasRight('tax', 'charges', 'creer')) {
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Bank account
	if ($action == 'setbankaccount' && $user->hasRight('tax', 'charges', 'creer')) {
		$object->fetch($id);
		$result = $object->setBankAccount(GETPOSTINT('fk_account'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Classify paid
	if ($action == 'confirm_paid' && $user->hasRight('tax', 'charges', 'creer') && $confirm == 'yes') {
		$object->fetch($id);
		$result = $object->setPaid($user);
	}

	if ($action == 'reopen' && $user->hasRight('tax', 'charges', 'creer')) {
		$result = $object->fetch($id);
		if ($object->paye) {
			$result = $object->setUnpaid($user);
			if ($result > 0) {
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'add' && !$cancel && $permissiontoadd) {
		$error = 0;

		$object->fk_account = GETPOSTINT("accountid");
		$object->type_payment = GETPOSTINT("type_payment");
		$object->num_payment = GETPOST("num_payment", 'alphanohtml');

		$object->datev = $datev;
		$object->datep = $datep;

		$amount = (float) price2num(GETPOST("amount", 'alpha'));
		if ($refund == 1) {
			$amount = price2num(-1 * $amount);
		}
		$object->amount = $amount;
		$object->label = GETPOST("label", 'alpha');
		$object->note = GETPOST("note", 'restricthtml');
		$object->note_private = GETPOST("note", 'restricthtml');

		if (empty($object->datep)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment")), null, 'errors');
			$error++;
		}
		if (empty($object->datev)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PeriodEndDate")), null, 'errors');
			$error++;
		}
		if (!empty($auto_create_payment) && (empty($object->type_payment) || $object->type_payment < 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
			$error++;
		}
		if (empty($object->amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$error++;
		}
		if (!empty($auto_create_payment) && ($object->fk_account <= 0)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountToCredit")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$ret = $object->create($user);
			if ($ret < 0) {
				$error++;
			}

			// Auto create payment
			if (!empty($auto_create_payment) && !$error) {
				$db->begin();

				// Create a line of payments
				$paiement = new PaymentVAT($db);
				$paiement->chid         = $object->id;
				$paiement->datepaye     = $datep;
				$paiement->amounts      = array($object->id => $amount); // Tableau de montant
				$paiement->paiementtype = GETPOST("type_payment", 'alphanohtml');
				$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
				$paiement->note = GETPOST("note", 'restricthtml');
				$paiement->note_private = GETPOST("note", 'restricthtml');

				if (!$error) {
					$paymentid = $paiement->create($user, (int) GETPOST('closepaidtva'));
					if ($paymentid < 0) {
						$error++;
						setEventMessages($paiement->error, null, 'errors');
						$action = 'create';
					}
				}

				if (!$error) {
					$result = $paiement->addPaymentToBank($user, 'payment_vat', '(VATPayment)', GETPOSTINT('accountid'), '', '');
					if (!($result > 0)) {
						$error++;
						setEventMessages($paiement->error, null, 'errors');
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}
			}
			if (empty($error)) {
				header("Location: card.php?id=" . $object->id);
				exit;
			}
		}

		$action = 'create';
	}

	if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
		$result = $object->fetch($id);
		$totalpaid = $object->getSommePaiement();

		if (empty($totalpaid)) {
			$db->begin();

			$ret = $object->delete($user);
			if ($ret > 0) {
				$accountline = null;
				if ($object->fk_bank) {
					$accountline = new AccountLine($db);
					$result = $accountline->fetch($object->fk_bank);
					if ($result > 0) {
						$result = $accountline->delete($user); // $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
					}
				}

				if ($result >= 0) {
					$db->commit();
					header("Location: ".DOL_URL_ROOT.'/compta/tva/list.php');
					exit;
				} else {
					$object->error = $accountline !== null ? $accountline->error : 'No account line (no bank)';
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages($langs->trans('DisabledBecausePayments'), null, 'errors');
		}
	}

	if ($action == 'update' && !GETPOST("cancel") && $user->hasRight('tax', 'charges', 'creer')) {
		$amount = price2num(GETPOST('amount', 'alpha'), 'MT');

		if (empty($amount)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'edit';
		} elseif (!is_numeric($amount)) {
			setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
			$action = 'create';
		} else {
			$result = $object->fetch($id);

			$object->amount	= $amount;

			$result = $object->update($user);
			if ($result <= 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm != 'yes') {	// Test on permission not required here
		$action = '';
	}

	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->hasRight('tax', 'charges', 'creer')) {
		$db->begin();

		$originalId = $id;

		$object->fetch($id);

		if ($object->id > 0) {
			$object->id = 0;
			$object->ref = '';
			$object->paye = 0;

			if (GETPOST('amount', 'alphanohtml')) {
				$object->amount = price2num(GETPOST('amount', 'alphanohtml'), 'MT', 2);
			}

			if (GETPOST('clone_label', 'alphanohtml')) {
				$object->label = GETPOST('clone_label', 'alphanohtml');
			} else {
				$object->label = $langs->trans("CopyOf").' '.$object->label;
			}

			$newdateperiod = dol_mktime(0, 0, 0, GETPOSTINT('clone_periodmonth'), GETPOSTINT('clone_periodday'), GETPOSTINT('clone_periodyear'));
			if ($newdateperiod) {
				$object->datev = $newdateperiod;
			}

			//if ($object->check()) {
			$id = $object->create($user);
			if ($id > 0) {
				$db->commit();
				$db->close();

				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				$id = $originalId;
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
			//}
		} else {
			$db->rollback();
			dol_print_error($db, $object->error);
		}
	}

	// Actions to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("VAT")." - ".$langs->trans("Card");
$help_url = '';
llxHeader('', $title, $help_url);

// Form to create a VAT
if ($action == 'create') {
	print load_fiche_titre($langs->trans("VAT").' - '.$langs->trans("New"));

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		print /** @lang JavaScript */'
			$(document).ready(function () {
				let onAutoCreatePaiementChange = function () {
					if($("#auto_create_paiement").is(":checked")) {
						$("#label_fk_account").addClass("fieldrequired");
						$("#label_type_payment").addClass("fieldrequired");
						$(".hide_if_no_auto_create_payment").show();
					} else {
						$("#label_fk_account").removeClass("fieldrequired");
						$("#label_type_payment").removeClass("fieldrequired");
						$(".hide_if_no_auto_create_payment").hide();
					}
				}
				$("#radiopayment").click(function() {
					$("#label").val($(this).data("label"));
				});
				$("#radiorefund").click(function() {
					$("#label").val($(this).data("label"));

				});
				$("#auto_create_paiement").click(function () {
					onAutoCreatePaiementChange();
				});
				onAutoCreatePaiementChange();
			});
			';

		print '</script>'."\n";
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" name="formvat" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">';
	//print $langs->trans("Type");
	print '</td><td>';

	print '<div id="selectmethod">';
	print '<label for="radiopayment">';
	print '<input type="radio" id="radiopayment" data-label="'.$langs->trans('VATPayment').'" class="flat" name="refund" value="0"'.($refund ? '' : ' checked="checked"').'>';
	print '&nbsp;';
	print $langs->trans("Payment");
	print '</label>';
	print '&nbsp;&nbsp;&nbsp;';
	print '<label for="radiorefund">';
	print '<input type="radio" id="radiorefund" data-label="'.$langs->trans('VATRefund').'" class="flat" name="refund" value="1"'.($refund ? ' checked="checked"' : '').'>';
	print '&nbsp;';
	print $langs->trans("Refund");
	print '</label>';
	print '</div>';

	print '</td>';
	print "</tr>\n";

	// Label
	if ($refund == 1) {
		$label = $langs->trans("VATRefund");
	} else {
		$label = $langs->trans("VATPayment");
	}
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input class="minwidth300" name="label" id="label" value="'.(GETPOSTISSET("label") ? GETPOST("label", '', 2) : $label).'" autofocus></td></tr>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")).'</td><td>';
	print $form->selectDate((GETPOSTINT("datevmonth") ? $datev : -1), "datev", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" class="right width75" value="'.GETPOST("amount", "alpha").'"></td></tr>';

	print '<tr><td colspan="2"><hr></td></tr>';

	// Auto create payment
	print '<tr><td><label for="auto_create_paiement">'.$langs->trans('AutomaticCreationPayment').'</label></td>';
	print '<td><input id="auto_create_paiement" name="auto_create_paiement" type="checkbox" ' . (empty($auto_create_payment) ? '' : 'checked="checked"') . ' value="1"></td></tr>'."\n";

	print '<tr class="hide_if_no_auto_create_payment">';
	print '<td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
	print $form->selectDate($datep, "datep", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Type payment
	print '<tr><td class="fieldrequired" id="label_type_payment">'.$langs->trans("PaymentMode").'</td><td>';
	print $form->select_types_paiements(GETPOSTINT("type_payment"), "type_payment", '', 0, 1, 0, 0, 1, 'maxwidth500 widthcentpercentminusx', 1);
	print "</td>\n";
	print "</tr>";

	if (isModEnabled("bank")) {
		// Bank account
		print '<tr><td class="fieldrequired" id="label_fk_account">'.$langs->trans("BankAccount").'</td><td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		$form->select_comptes(GETPOSTINT("accountid"), "accountid", 0, "courant=1", 1, '', 0, 'maxwidth500 widthcentpercentminusx'); // List of bank account available
		print '</td></tr>';
	}

	// Number
	print '<tr class="hide_if_no_auto_create_payment"><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '<td><input name="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";

	// Comments
	print '<tr class="hide_if_no_auto_create_payment">';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note" wrap="soft" rows="'.ROWS_3.'" class="quatrevingtpercent">'.GETPOST('note', 'restricthtml').'</textarea></td>';
	print '</tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<div class="hide_if_no_auto_create_payment paddingbottom">';
	print '<input type="checkbox" checked value="1" name="closepaidtva"> <span class="">'.$langs->trans("ClosePaidVATAutomatically").'</span>';
	print '<br>';
	print '</div>';

	print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// View mode
if ($id > 0) {
	$formconfirm = '';

	$head = vat_prepare_head($object);

	$totalpaid = $object->getSommePaiement();

	// Clone confirmation
	if ($action === 'clone') {
		$formquestion = array(
			array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
		);

		//$formquestion[] = array('type' => 'date', 'name' => 'clone_date_ech', 'label' => $langs->trans("Date"), 'value' => -1);
		$formquestion[] = array('type' => 'date', 'name' => 'clone_period', 'label' => $langs->trans("PeriodEndDate"), 'value' => -1);
		$formquestion[] = array('type' => 'text', 'name' => 'amount', 'label' => $langs->trans("Amount"), 'value' => price($object->amount), 'morecss' => 'width100');

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneVAT', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 240);
	}

	if ($action == 'paid') {
		$text = $langs->trans('ConfirmPayVAT');
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PayVAT'), $text, "confirm_paid", '', '', 2);
	}

	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteVAT');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteVAT'), $text, 'confirm_delete', '', '', 2);
	}

	if ($action == 'edit') {
		print '<form name="charge" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
	}
	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	print dol_get_fiche_head($head, 'card', $langs->trans("VATPayment"), -1, 'payment', 0, '', '', 0, '', 1);

	// Print form confirm
	print $formconfirm;

	$morehtmlref = '<div class="refidno">';
	// Label of social contribution
	$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', null, null, '', 1);
	// Project
	$morehtmlref .= '</div>';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/tva/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	// Label
	//print '<tr><td class="titlefield">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	/*print "<tr>";
	print '<td class="titlefield">'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';*/

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")), 'datev', $object->datev, $object, $user->hasRight('tax', 'charges', 'creer'), 'day');
	print '</td><td>';
	print $form->editfieldval("PeriodEndDate", 'datev', $object->datev, $object, $user->hasRight('tax', 'charges', 'creer'), 'day');
	//print dol_print_date($object->datev,'day');
	print '</td></tr>';

	if ($action == 'edit') {
		print '<tr><td class="fieldrequired">' . $langs->trans("Amount") . '</td><td><input name="amount" size="10" value="' . price($object->amount) . '"></td></tr>';
	} else {
		print '<tr><td>' . $langs->trans("Amount") . '</td><td>' . price($object->amount) . '</td></tr>';
	}

	// Mode of payment
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode') {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->type_payment, 'mode_reglement_id');
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->type_payment, 'none');
	}
	print '</td></tr>';

	// Bank account
	if (isModEnabled("bank")) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('BankAccount');
		print '<td>';
		if ($action != 'editbankaccount' && $user->hasRight('tax', 'charges', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbankaccount') {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
		} else {
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print '</div>';

	print '<div class="fichehalfright">';

	$nbcols = 3;
	if (isModEnabled("bank")) {
		$nbcols++;
	}

	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_paiement as num_payment, p.datep as dp, p.amount,";
	$sql .= " c.code as type_code,c.libelle as paiement_type,";
	$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.currency_code as bacurrency_code, ba.fk_accountancy_journal';
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_vat as p";
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepaiement = c.id";
	$sql .= ", ".MAIN_DB_PREFIX."tva as tva";
	$sql .= " WHERE p.fk_tva = ".((int) $id);
	$sql .= " AND p.fk_tva = tva.rowid";
	$sql .= " AND tva.entity IN (".getEntity('tax').")";
	$sql .= " ORDER BY dp DESC";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		$totalpaid = 0;

		$num = $db->num_rows($resql);
		$i = 0;
		$total = 0;

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder paymenttable">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		if (isModEnabled("bank")) {
			print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
		}
		print '<td class="right">'.$langs->trans("Amount").'</td>';
		print '</tr>';

		if ($num > 0) {
			$bankaccountstatic = new Account($db);
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print '<a href="'.DOL_URL_ROOT.'/compta/payment_vat/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a>';
				print '</td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				$labeltype = $langs->trans("PaymentType".$objp->type_code) != "PaymentType".$objp->type_code ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
				print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
				if (isModEnabled("bank")) {
					$bankaccountstatic->id = $objp->baid;
					$bankaccountstatic->ref = $objp->baref;
					$bankaccountstatic->label = $objp->baref;
					$bankaccountstatic->number = $objp->banumber;
					$bankaccountstatic->currency_code = $objp->bacurrency_code;

					if (isModEnabled('accounting')) {
						$bankaccountstatic->account_number = $objp->account_number;

						$accountingjournal = new AccountingJournal($db);
						$accountingjournal->fetch($objp->fk_accountancy_journal);
						$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
					}

					print '<td class="right">';
					if ($bankaccountstatic->id) {
						print $bankaccountstatic->getNomUrl(1, 'transactions');
					}
					print '</td>';
				}
				print '<td class="right"><span class="amount">'.price($objp->amount)."</span></td>\n";
				print "</tr>";
				$totalpaid += $objp->amount;
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '<td></td><td></td><td></td><td></td>';
			print '</tr>';
		}

		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid")." :</td><td class=\"right\">".price($totalpaid)."</td></tr>\n";
		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected")." :</td><td class=\"right\">".price($object->amount)."</td></tr>\n";

		$resteapayer = $object->amount - $totalpaid;
		$cssforamountpaymentcomplete = 'amountpaymentcomplete';

		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
		print '<td class="right'.($resteapayer ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayer)."</td></tr>\n";

		print "</table>";
		print '</div>';

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	if ($action == 'edit') {
		print $form->buttonsSaveCancel();

		print "</form>\n";
	}


	// Buttons for actions

	print '<div class="tabsAction">'."\n";

	if ($action != 'edit') {
		// Reopen
		if ($object->paye && $user->hasRight('tax', 'charges', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen")."</a></div>";
		}

		// Edit
		if ($object->paye == 0 && $user->hasRight('tax', 'charges', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify")."</a></div>";
		}

		// Emit payment
		if ($object->paye == 0 && ((price2num($object->amount) < 0 && price2num($resteapayer, 'MT') < 0) || (price2num($object->amount) > 0 && price2num($resteapayer, 'MT') > 0)) && $user->hasRight('tax', 'charges', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/paiement_vat.php?id='.$object->id.'&action=create&token='.newToken().'">'.$langs->trans("DoPayment").'</a></div>';
		}

		// Classify 'paid'
		if ($object->paye == 0
		&& (
			(round($resteapayer) <= 0 && $object->amount > 0)
			|| (round($resteapayer) >= 0 && $object->amount < 0)
		)
		&& $user->hasRight('tax', 'charges', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$object->id.'&token='.newToken().'&action=paid">'.$langs->trans("ClassifyPaid")."</a></div>";
		}

		// Clone
		if ($user->hasRight('tax', 'charges', 'creer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$object->id.'&token='.newToken().'&action=clone">'.$langs->trans("ToClone")."</a></div>";
		}

		if ($user->hasRight('tax', 'charges', 'supprimer') && empty($totalpaid)) {
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("DisabledBecausePayments"))).'">'.$langs->trans("Delete").'</a></div>';
		}
	}
	print '</div>'."\n";



	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->tax->dir_output.'/vat/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = 0;
			$delallowed = $user->hasRight('tax', 'charges', 'creer'); // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('tax-vat', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		//$tmparray = $form->showLinkToObjectBlock($object, null, array('myobject'), 1);
		//$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		/*
		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);
		*/

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'vat';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->tax->dir_output;
	$trackid = 'vat'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

llxFooter();
$db->close();
