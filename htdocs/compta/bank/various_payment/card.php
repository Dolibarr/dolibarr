<?php
/* Copyright (C) 2017-2021  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Joachim Kueter     		<git-jk@bloxera.com>
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
 *  \file       htdocs/compta/bank/various_payment/card.php
 *  \ingroup    bank
 *  \brief      Page of various expenses
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "accountancy", "categories"));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$accountid = GETPOST("accountid") > 0 ? GETPOST("accountid", "int") : 0;
$label = GETPOST("label", "alpha");
$sens = GETPOST("sens", "int");
$amount = price2num(GETPOST("amount", "alpha"));
$paymenttype = GETPOST("paymenttype", "aZ09");
$accountancy_code = GETPOST("accountancy_code", "alpha");
$projectid = (GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : GETPOST('fk_project', 'int'));
if (isModEnabled('accounting') && getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
	$subledger_account = GETPOST("subledger_account", "alpha") > 0 ? GETPOST("subledger_account", "alpha") : '';
} else {
	$subledger_account = GETPOST("subledger_account", "alpha");
}

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'banque', '', '', '');

$object = new PaymentVarious($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('variouscard', 'globalcard'));

$permissiontoadd = $user->hasRight('banque', 'modifier');


/**
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if ($action != 'addlink' && $action != 'setaccountancy_code' && $action != 'setsubledger_account') {
			$urltogo = $backtopage ? $backtopage : dol_buildpath('/compta/bank/various_payment/list.php', 1);
			header("Location: ".$urltogo);
			exit;
		}
		if ($id > 0 || !empty($ref)) {
			$ret = $object->fetch($id, $ref);
		}
		$action = '';
	}

	// Link to a project
	if ($action == 'classin' && $permissiontoadd) {
		$object->fetch($id);
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'add') {
		$error = 0;

		$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
		$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
		if (empty($datev)) {
			$datev = $datep;
		}

		$object->ref = ''; // TODO
		$object->accountid = GETPOST("accountid", 'int') > 0 ? GETPOST("accountid", "int") : 0;
		$object->datev = $datev;
		$object->datep = $datep;
		$object->amount = price2num(GETPOST("amount", 'alpha'));
		$object->label = GETPOST("label", 'restricthtml');
		$object->note = GETPOST("note", 'restricthtml');
		$object->type_payment = dol_getIdFromCode($db, GETPOST('paymenttype'), 'c_paiement', 'code', 'id', 1);
		$object->num_payment = GETPOST("num_payment", 'alpha');
		$object->chqemetteur = GETPOST("chqemetteur", 'alpha');
		$object->chqbank = GETPOST("chqbank", 'alpha');
		$object->fk_user_author = $user->id;
		$object->category_transaction = GETPOST("category_transaction", 'alpha');

		$object->accountancy_code = GETPOST("accountancy_code") > 0 ? GETPOST("accountancy_code", "alpha") : "";
		$object->subledger_account = $subledger_account;

		$object->sens = GETPOSTINT('sens');
		$object->fk_project = GETPOSTINT('fk_project');

		if (empty($datep) || empty($datev)) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$error++;
		}
		if (empty($object->amount)) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$error++;
		}
		if (isModEnabled("banque") && !$object->accountid > 0) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
			$error++;
		}
		if (empty($object->type_payment) || $object->type_payment < 0) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
			$error++;
		}
		if (isModEnabled('accounting') && !$object->accountancy_code) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
			$error++;
		}
		if ($object->sens < 0) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Sens")), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->begin();

			$ret = $object->create($user);
			if ($ret > 0) {
				$db->commit();
				$urltogo = ($backtopage ? $backtopage : DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
				header("Location: ".$urltogo);
				exit;
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
				$action = "create";
			}
		}

		$action = 'create';
	}

	if ($action == 'confirm_delete' && $confirm == 'yes') {
		$result = $object->fetch($id);

		if ($object->rappro == 0) {
			$db->begin();

			$ret = $object->delete($user);
			if ($ret > 0) {
				if ($object->fk_bank) {
					$accountline = new AccountLine($db);
					$result = $accountline->fetch($object->fk_bank);
					if ($result > 0) {
						$result = $accountline->delete($user); // $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
					}
				}

				if ($result >= 0) {
					$db->commit();
					header("Location: ".DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
					exit;
				} else {
					$object->error = $accountline->error;
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages('Error try do delete a line linked to a conciliated bank transaction', null, 'errors');
		}
	}

	if ($action == 'setaccountancy_code') {
		$db->begin();

		$result = $object->fetch($id);

		$object->accountancy_code = GETPOST('accountancy_code', 'alphanohtml');

		$res = $object->update($user);
		if ($res > 0) {
			$db->commit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setsubledger_account') {
		$db->begin();

		$result = $object->fetch($id);

		$object->subledger_account = $subledger_account;

		$res = $object->update($user);
		if ($res > 0) {
			$db->commit();
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm != 'yes') {
	$action = '';
}

if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
	$db->begin();

	$originalId = $id;

	$object->fetch($id);

	if ($object->id > 0) {
		$object->id = $object->ref = null;

		if (GETPOST('clone_label', 'alphanohtml')) {
			$object->label = GETPOST('clone_label', 'alphanohtml');
		} else {
			$object->label = $langs->trans("CopyOf").' '.$object->label;
		}

		$newdatepayment = dol_mktime(0, 0, 0, GETPOST('clone_date_paymentmonth', 'int'), GETPOST('clone_date_paymentday', 'int'), GETPOST('clone_date_paymentyear', 'int'));
		$newdatevalue = dol_mktime(0, 0, 0, GETPOST('clone_date_valuemonth', 'int'), GETPOST('clone_date_valueday', 'int'), GETPOST('clone_date_valueyear', 'int'));
		if ($newdatepayment) {
			$object->datep = $newdatepayment;
		}
		if (!empty($newdatevalue)) {
			$object->datev = $newdatevalue;
		} else {
			$object->datev = $newdatepayment;
		}

		if (GETPOSTISSET("clone_sens")) {
			$object->sens = GETPOST("clone_sens", 'int');
		} else {
			$object->sens = $object->sens;
		}

		if (GETPOST("clone_amount", "alpha")) {
			$object->amount = price2num(GETPOST("clone_amount", "alpha"));
		} else {
			$object->amount = price2num($object->amount);
		}

		if ($object->check()) {
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
		} else {
			$id = $originalId;
			$db->rollback();

			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		$db->rollback();
		dol_print_error($db, $object->error);
	}
}


/*
 *	View
 */
$form = new Form($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

if ($id) {
	$object = new PaymentVarious($db);
	$result = $object->fetch($id);
	if ($result <= 0) {
		dol_print_error($db);
		exit;
	}
}

$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
	$title = $langs->trans("NewVariousPayment");
}
$help_url = 'EN:Module_Suppliers_Invoices|FR:Module_Fournisseurs_Factures|ES:Módulo_Facturas_de_proveedores|DE:Modul_Lieferantenrechnungen';
llxHeader('', $title, $help_url);

$options = array();

// Load bank groups
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
$bankcateg = new BankCateg($db);

foreach ($bankcateg->fetchAll() as $bankcategory) {
	$options[$bankcategory->id] = $bankcategory->label;
}

// Create mode
if ($action == 'create') {
	// Update fields properties in realtime
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
            			setPaymentType();
            			$("#selectpaymenttype").change(function() {
            				setPaymentType();
            			});
            			function setPaymentType()
            			{
							console.log("setPaymentType");
            				var code = $("#selectpaymenttype option:selected").val();
                            if (code == \'CHQ\' || code == \'VIR\')
            				{
            					if (code == \'CHQ\')
			                    {
			                        $(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
			                    }
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = jQuery(\'#thirdpartylabel\').val();
            						$(\'#fieldchqemetteur\').val(emetteur);
            					}
            				}
            				else
            				{
            					$(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
            					$(\'#fieldchqemetteur\').val(\'\');
            				}
            			}
			';

		print '	});'."\n";

		print '	</script>'."\n";
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="action" value="add">';

	print load_fiche_titre($langs->trans("NewVariousPayment"), '', 'object_payment');

	print dol_get_fiche_head('', '');

	print '<table class="border centpercent">';

	// Date payment
	print '<tr><td class="titlefieldcreate">';
	print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate((empty($datep) ? -1 : $datep), "datep", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Date value for bank
	print '<tr><td>';
	print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
	print $form->selectDate((empty($datev) ? -1 : $datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="label" id="label" class="minwidth300 maxwidth150onsmartphone" value="'.($label ? $label : $langs->trans("VariousPayment")).'">';
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="amount" id="amount" class="minwidth50 maxwidth100" value="'.$amount.'">';
	print '</td></tr>';

	// Bank
	if (isModEnabled("banque")) {
		print '<tr><td>';
		print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		print $form->select_comptes($accountid, "accountid", 0, '', 2, '', 0, '', 1); // Show list of main accounts (comptes courants)
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
	$form->select_types_paiements($paymenttype, 'paymenttype', '', 2);
	print "</td>\n";
	print '</tr>';

	// Number
	if (isModEnabled("banque")) {
		print '<tr><td><label for="num_payment">'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</label></td>';
		print '<td><input name="num_payment" class="maxwidth150onsmartphone" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";

		// Check transmitter
		print '<tr><td class="'.(GETPOST('paymenttype') == 'CHQ' ? 'fieldrequired ' : '').'fieldrequireddyn"><label for="fieldchqemetteur">'.$langs->trans('CheckTransmitter');
		print ' <em>('.$langs->trans("ChequeMaker").')</em>';
		print '</label></td>';
		print '<td><input id="fieldchqemetteur" name="chqemetteur" size="30" type="text" value="'.GETPOST('chqemetteur', 'alphanohtml').'"></td></tr>';

		// Bank name
		print '<tr><td><label for="chqbank">'.$langs->trans('Bank');
		print ' <em>('.$langs->trans("ChequeBank").')</em>';
		print '</label></td>';
		print '<td><input id="chqbank" name="chqbank" size="30" type="text" value="'.GETPOST('chqbank', 'alphanohtml').'"></td></tr>';
	}

	// Accountancy account
	if (isModEnabled('accounting')) {
		// TODO Remove the fieldrequired and allow instead to edit a various payment to enter accounting code
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("AccountAccounting").'</td>';
		print '<td>';
		print $formaccounting->select_account($accountancy_code, 'accountancy_code', 1, null, 1, 1);
		print '</td></tr>';
	} else { // For external software
		print '<tr><td class="titlefieldcreate">'.$langs->trans("AccountAccounting").'</td>';
		print '<td><input class="minwidth100 maxwidthonsmartphone" name="accountancy_code" value="'.$accountancy_code.'">';
		print '</td></tr>';
	}

	// Subledger account
	if (isModEnabled('accounting')) {
		print '<tr><td>'.$langs->trans("SubledgerAccount").'</td>';
		print '<td>';
		if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
			print $formaccounting->select_auxaccount($subledger_account, 'subledger_account', 1, '');
		} else {
			print '<input type="text" class="maxwidth200 maxwidthonsmartphone" name="subledger_account" value="'.$subledger_account.'">';
		}
		print '</td></tr>';
	} else { // For external software
		print '<tr><td>'.$langs->trans("SubledgerAccount").'</td>';
		print '<td><input class="minwidth100 maxwidthonsmartphone" name="subledger_account" value="'.$subledger_account.'">';
		print '</td></tr>';
	}

	// Sens
	print '<tr><td>';
	$labelsens = $form->textwithpicto($langs->trans('Sens'), $langs->trans("AccountingDirectionHelp"));
	print $form->editfieldkey($labelsens, 'sens', '', $object, 0, 'string', '', 1).'</td><td>';
	$sensarray = array('0' => $langs->trans("Debit"), '1' => $langs->trans("Credit"));
	print $form->selectarray('sens', $sensarray, $sens, 1, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
	print '</td></tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		// Associated project
		$langs->load("projects");

		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		print $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Category
	if (is_array($options) && count($options) && $conf->categorie->enabled) {
		print '<tr><td>'.$langs->trans("RubriquesTransactions").'</td><td>';
		print img_picto('', 'category').Form::selectarray('category_transaction', $options, GETPOST('category_transaction'), 1, 0, 0, '', 0, 0, 0, '', 'minwidth300', 1);
		print '</td></tr>';
	}

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */

if ($id) {
	$alreadyaccounted = $object->getVentilExportCompta();

	$head = various_payment_prepare_head($object);

	// Clone confirmation
	if ($action === 'clone') {
		$set_value_help = $form->textwithpicto('', $langs->trans($langs->trans("AccountingDirectionHelp")));
		$sensarray = array('0' => $langs->trans("Debit"), '1' => $langs->trans("Credit"));

		$formquestion = array(
			array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
			array('type' => 'date', 'tdclass'=>'fieldrequired', 'name' => 'clone_date_payment', 'label' => $langs->trans("DatePayment"), 'value' => -1),
			array('type' => 'date', 'name' => 'clone_date_value', 'label' => $langs->trans("DateValue"), 'value' => -1),
			array('type' => 'other', 'tdclass'=>'fieldrequired', 'name' => 'clone_accountid', 'label' => $langs->trans("BankAccount"), 'value' => $form->select_comptes($object->fk_account, "accountid", 0, '', 1, '', 0, 'minwidth200', 1)),
			array('type' => 'text', 'name' => 'clone_amount', 'label' => $langs->trans("Amount"), 'value' => price($object->amount)),
			array('type' => 'select', 'name' => 'clone_sens', 'label' => $langs->trans("Sens").' '.$set_value_help, 'values' => $sensarray, 'default' => $object->sens),
		);

		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneVariousPayment', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 350);
	}

	// Confirmation of the removal of the Various Payment
	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteVariousPayment');
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteVariousPayment'), $text, 'confirm_delete', '', '', 2);
	}

	print dol_get_fiche_head($head, 'card', $langs->trans("VariousPayment"), -1, $object->picto);

	$morehtmlref = '<div class="refidno">';
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		//$morehtmlref .= '<br>';
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects(-1, $object->fk_project, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (property_exists($object, 'socid') ? $object->socid : 0), $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}

	$morehtmlref .= '</div>';
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlright = '';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Label
	print '<tr><td class="titlefield">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	// Payment date
	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';

	// Value date
	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($object->datev, 'day');
	print '</td></tr>';

	// Debit / Credit
	if ($object->sens == '1') {
		$sens = $langs->trans("Credit");
	} else {
		$sens = $langs->trans("Debit");
	}
	print '<tr><td>'.$langs->trans("Sens").'</td><td>'.$sens.'</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($object->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';

	// Account of Chart of account
	$editvalue = '';
	if (isModEnabled('accounting')) {
		print '<tr><td class="nowrap">';
		print $form->editfieldkey('AccountAccounting', 'accountancy_code', $object->accountancy_code, $object, (!$alreadyaccounted && $user->hasRight('banque', 'modifier')), 'string', '', 0);
		print '</td><td>';
		if ($action == 'editaccountancy_code') {
			print $form->editfieldval('AccountAccounting', 'accountancy_code', $object->accountancy_code, $object, (!$alreadyaccounted && $user->hasRight('banque', 'modifier')), 'string', '', 0);
		} else {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('', $object->accountancy_code, 1);

			print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
		}
		print '</td></tr>';
	} else {
		print '<tr><td class="nowrap">';
		print $langs->trans("AccountAccounting");
		print '</td><td>';
		print $object->accountancy_code;
		print '</td></tr>';
	}

	// Subledger account
	print '<tr><td class="nowrap">';
	print $form->editfieldkey('SubledgerAccount', 'subledger_account', $object->subledger_account, $object, (!$alreadyaccounted && $permissiontoadd), 'string', '', 0);
	print '</td><td>';
	print $form->editfieldval('SubledgerAccount', 'subledger_account', $object->subledger_account, $object, (!$alreadyaccounted && $permissiontoadd), 'string', '', 0, null, '', 1, 'lengthAccounta');
	print '</td></tr>';

	$bankaccountnotfound = 0;

	if (isModEnabled('banque')) {
		print '<tr>';
		print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td colspan="3">';
		if ($object->fk_bank > 0) {
			$bankline = new AccountLine($db);
			$result = $bankline->fetch($object->fk_bank);

			if ($result <= 0) {
				$bankaccountnotfound = 1;
			} else {
				print $bankline->getNomUrl(1, 0, 'showall');
			}
		} else {
			$bankaccountnotfound = 1;

			print '<span class="opacitymedium">'.$langs->trans("NoRecordfound").'</span>';
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$parameters = array('socid'=>$object->id);
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Action bar
	 */
	print '<div class="tabsAction">'."\n";

	// TODO
	// Add button modify

	// Clone
	if ($permissiontoadd) {
		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath("/compta/bank/various_payment/card.php", 1).'?id='.$object->id.'&amp;action=clone">'.$langs->trans("ToClone")."</a></div>";
	}

	// Delete
	if (empty($object->rappro) || $bankaccountnotfound) {
		if ($permissiontoadd) {
			if ($alreadyaccounted) {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("Accounted").'">'.$langs->trans("Delete").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
			}
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a></div>';
		}
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("LinkedToAConciliatedTransaction").'">'.$langs->trans("Delete").'</a></div>';
	}

	print "</div>";
}

// End of page
llxFooter();
$db->close();
