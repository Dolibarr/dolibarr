<?php
/* Copyright (C) 2011-2019  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *  \file       htdocs/salaries/card.php
 *  \ingroup    salaries
 *  \brief      Page of salaries payments
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "salaries", "hrm", "trips"));
if (!empty($conf->projet->enabled)) {
	$langs->load("projects");
}

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$accountid = GETPOST('accountid', 'int') > 0 ? GETPOST('accountid', 'int') : 0;
$projectid = (GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : GETPOST('fk_project', 'int'));
$confirm = GETPOST('confirm');
if (GETPOSTISSET('auto_create_paiement') || $action === 'add') {
	$auto_create_paiement = GETPOST("auto_create_paiement", "int");
} else {
	$auto_create_paiement = empty($conf->global->CREATE_NEW_SALARY_WITHOUT_AUTO_PAYMENT);
}

$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));
$label = GETPOST('label', 'alphanohtml');
$fk_user = GETPOSTINT('userid');

$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('salarycard', 'globalcard'));

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);

	// Check current user can read this salary
	$canread = 0;
	if (!empty($user->rights->salaries->readall)) {
		$canread = 1;
	} elseif (!empty($user->rights->salaries->read) && $object->fk_user > 0 && in_array($object->fk_user, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}

restrictedArea($user, 'salaries', $object->id, 'salary', '');


/**
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/salaries/list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/salaries/card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		/*var_dump($cancel);
		 var_dump($backtopage);exit;*/
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}
}

// Link to a project
if ($action == 'classin' && $user->rights->banque->modifier) {
	$object->fetch($id);
	$object->setProject($projectid);
}

// set label
if ($action == 'setlabel' && $user->rights->salaries->write) {
	$object->fetch($id);
	$object->label = $label;
	$object->update($user);
}

// Classify paid
if ($action == 'confirm_paid' && $user->rights->salaries->write && $confirm == 'yes') {
	$object->fetch($id);
	$result = $object->set_paid($user);
}

if ($action == 'setfk_user' && $user->rights->salaries->write) {
	$result = $object->fetch($id);
	if ($result > 0) {
		$object->fk_user = $fk_user;
		$object->update($user);
	} else {
		dol_print_error($db);
		exit;
	}
}

if ($action == 'reopen' && $user->rights->salaries->write) {
	$result = $object->fetch($id);
	if ($object->paye) {
		$result = $object->set_unpaid($user);
		if ($result > 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// payment mode
if ($action == 'setmode' && $user->rights->salaries->write) {
	$object->fetch($id);
	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	if ($result < 0)
		setEventMessages($object->error, $object->errors, 'errors');
}

// bank account
if ($action == 'setbankaccount' && $user->rights->salaries->write) {
	$object->fetch($id);
	$result = $object->setBankAccount(GETPOST('fk_account', 'int'));
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'add' && empty($cancel)) {
	$error = 0;

	if (empty($datev)) $datev = $datep;

	$type_payment = GETPOST("paymenttype", 'alpha');
	$amount = price2num(GETPOST("amount", 'alpha'), 'MT', 2);

	$object->accountid = GETPOST("accountid", 'int') > 0 ? GETPOST("accountid", "int") : 0;
	$object->fk_user = GETPOST("fk_user", 'int') > 0 ? GETPOST("fk_user", "int") : 0;
	$object->datev = $datev;
	$object->datep = $datep;
	$object->amount = $amount;
	$object->label = GETPOST("label", 'alphanohtml');
	$object->datesp = $datesp;
	$object->dateep = $dateep;
	$object->note = GETPOST("note", 'restricthtml');
	$object->type_payment = ($type_payment > 0 ? $type_payment : 0);
	$object->fk_user_author = $user->id;
	$object->fk_project = $projectid;

	// Set user current salary as ref salary for the payment
	$fuser = new User($db);
	$fuser->fetch(GETPOST("fk_user", "int"));
	$object->salary = $fuser->salary;

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) {
		$error++;
	}

	if (!empty($auto_create_paiement) && empty($datep)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DATE_PAIEMENT")), null, 'errors');
		$error++;
	}
	if (empty($datesp) || empty($dateep)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	if (empty($object->fk_user) || $object->fk_user < 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Employee")), null, 'errors');
		$error++;
	}
	if (!empty($auto_create_paiement) && (empty($type_payment) || $type_payment < 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
		$error++;
	}
	if (empty($object->amount)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$error++;
	}
	if (!empty($conf->banque->enabled) && !empty($auto_create_paiement) && !$object->accountid > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$db->begin();

		$ret = $object->create($user);
		if ($ret < 0) $error++;
		if (!empty($auto_create_paiement) && !$error) {
			// Create a line of payments
			$paiement = new PaymentSalary($db);
			$paiement->chid         = $object->id;
			$paiement->datepaye     = $datep;
			$paiement->datev		= $datev;
			$paiement->amounts      = array($object->id=>$amount); // Tableau de montant
			$paiement->paiementtype = $type_payment;
			$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$paiement->note = GETPOST("note", 'none');

			if (!$error) {
				$paymentid = $paiement->create($user, (int) GETPOST('closepaidsalary'));
				if ($paymentid < 0) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$result = $paiement->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', GETPOST('accountid', 'int'), '', '');
				if (!($result > 0)) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
				}
			}
		}

		if (empty($error)) {
			$db->commit();

			if (GETPOST('saveandnew', 'alpha')) {
				setEventMessages($langs->trans("RecordSaved"), '', 'mesgs');
				header("Location: card.php?action=create&fk_project=" . urlencode($projectid) . "&accountid=" . urlencode($accountid) . '&paymenttype=' . urlencode(GETPOST('paymenttype', 'az09')) . '&datepday=' . GETPOST("datepday", 'int') . '&datepmonth=' . GETPOST("datepmonth", 'int') . '&datepyear=' . GETPOST("datepyear", 'int'));
				exit;
			} else {
				header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
				exit;
			}
		} else {
			$db->rollback();
		}
	}

	$action = 'create';
}

if ($action == 'confirm_delete') {
	$result = $object->fetch($id);
	$totalpaye = $object->getSommePaiement();

	if (empty($totalpaye)) {
		$db->begin();

		$ret = $object->delete($user);
		if ($ret > 0) {
			$db->commit();
			header("Location: ".DOL_URL_ROOT.'/salaries/list.php');
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans('DisabledBecausePayments'), null, 'errors');
	}
}


if ($action == 'update' && !GETPOST("cancel") && $user->rights->salaries->write) {
	$amount = price2num(GETPOST('amount'), 'MT', 2);

	if (empty($amount)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'edit';
	} elseif (!is_numeric($amount)) {
		setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'create';
	} else {
		$result = $object->fetch($id);

		$object->amount = price2num($amount);
		$object->datesp = price2num($datesp);
		$object->dateep = price2num($dateep);

		$result = $object->update($user);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

if ($action == 'confirm_clone' && $confirm != 'yes') { $action = ''; }

if ($action == 'confirm_clone' && $confirm == 'yes' && ($user->rights->salaries->write)) {
	$db->begin();

	$originalId = $id;

	$object->fetch($id);

	if ($object->id > 0) {
		$object->paye = 0;
		$object->id = $object->ref = null;

		if (GETPOST('clone_label', 'alphanohtml')) {
			$object->label = GETPOST('clone_label', 'alphanohtml');
		} else {
			$object->label = $langs->trans("CopyOf").' '.$object->label;
		}

		$newdatestart = dol_mktime(0, 0, 0, GETPOST('clone_date_startmonth', 'int'), GETPOST('clone_date_startday', 'int'), GETPOST('clone_date_startyear', 'int'));
		$newdateend = dol_mktime(0, 0, 0, GETPOST('clone_date_endmonth', 'int'), GETPOST('clone_date_endday', 'int'), GETPOST('clone_date_endyear', 'int'));

		if ($newdatestart) $object->datesp = $newdatestart;
		if ($newdateend) $object->dateep = $newdateend;

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
		$db->rollback();
		dol_print_error($db, $object->error);
	}
}

// Action to update one extrafield
if ($action == "update_extras" && !empty($user->rights->salaries->read)) {
	$object->fetch(GETPOST('id', 'int'));

	$attributekey = GETPOST('attribute', 'alpha');
	$attributekeylong = 'options_'.$attributekey;

	if (GETPOSTISSET($attributekeylong.'day') && GETPOSTISSET($attributekeylong.'month') && GETPOSTISSET($attributekeylong.'year')) {
		// This is properties of a date
		$object->array_options['options_'.$attributekey] = dol_mktime(GETPOST($attributekeylong.'hour', 'int'), GETPOST($attributekeylong.'min', 'int'), GETPOST($attributekeylong.'sec', 'int'), GETPOST($attributekeylong.'month', 'int'), GETPOST($attributekeylong.'day', 'int'), GETPOST($attributekeylong.'year', 'int'));
		//var_dump(dol_print_date($object->array_options['options_'.$attributekey]));exit;
	} else {
		$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, 'alpha');
	}

	$result = $object->insertExtraFields(empty($triggermodname) ? '' : $triggermodname, $user);
	if ($result > 0) {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = 'view';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'edit_extras';
	}
}

/*
 *	View
 */

$title = $langs->trans('Salary')." - ".$langs->trans('Card');
$help_url = "";
llxHeader("", $title, $help_url);

$form = new Form($db);
if (!empty($conf->projet->enabled)) $formproject = new FormProjets($db);

if ($id > 0) {
	$result = $object->fetch($id);
	if ($result <= 0) {
		dol_print_error($db);
		exit;
	}
}

// Create
if ($action == 'create') {
	$year_current = strftime("%Y", dol_now());
	$pastmonth = strftime("%m", dol_now()) - 1;
	$pastmonthyear = $year_current;
	if ($pastmonth == 0) {
		$pastmonth = 12;
		$pastmonthyear--;
	}

	$datespmonth = GETPOST('datespmonth', 'int');
	$datespday = GETPOST('datespday', 'int');
	$datespyear = GETPOST('datespyear', 'int');
	$dateepmonth = GETPOST('dateepmonth', 'int');
	$dateepday = GETPOST('dateepday', 'int');
	$dateepyear = GETPOST('dateepyear', 'int');
	$datesp = dol_mktime(0, 0, 0, $datespmonth, $datespday, $datespyear);
	$dateep = dol_mktime(23, 59, 59, $dateepmonth, $dateepday, $dateepyear);

	if (empty($datesp) || empty($dateep)) { // We define date_start and date_end
		$datesp = dol_get_first_day($pastmonthyear, $pastmonth, false); $dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
	}

	print '<form name="salary" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print load_fiche_titre($langs->trans("NewSalary"), '', 'salary');

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript" language="javascript">';
		print /** @lang JavaScript */'
			$(document).ready(function () {
				let onAutoCreatePaiementChange = function () {
					if($("#auto_create_paiement").is(":checked")) {
						$("#label_fk_account").find("span").addClass("fieldrequired");
						$("#label_type_payment").find("span").addClass("fieldrequired");
						$(".hide_if_no_auto_create_payment").show();
					} else {
						$("#label_fk_account").find("span").removeClass("fieldrequired");
						$("#label_type_payment").find("span").removeClass("fieldrequired");
						$(".hide_if_no_auto_create_payment").hide();
					}
				};
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

	print dol_get_fiche_head('', '');

	print '<table class="border centpercent">';

	// Employee
	print '<tr><td class="titlefieldcreate">';
	print $form->editfieldkey('Employee', 'fk_user', '', $object, 0, 'string', '', 1).'</td><td>';
	$noactive = 0; // We keep active and unactive users
	print img_picto('', 'user', 'class="paddingrighonly"').$form->select_dolusers(GETPOST('fk_user', 'int'), 'fk_user', 1, '', 0, '', '', 0, 0, 0, 'AND employee=1', 0, '', 'maxwidth300', $noactive);
	print '</td></tr>';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="label" id="label" class="minwidth300" value="'.(GETPOST("label") ?GETPOST("label") : $langs->trans("Salary")).'">';
	print '</td></tr>';

	// Date start period
	print '<tr><td>';
	print $form->editfieldkey('DateStartPeriod', 'datesp', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate($datesp, "datesp", '', '', '', 'add');
	print '</td></tr>';

	// Date end period
	print '<tr><td>';
	print $form->editfieldkey('DateEndPeriod', 'dateep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate($dateep, "dateep", '', '', '', 'add');
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="amount" id="amount" class="minwidth75 maxwidth100" value="'.GETPOST("amount").'">';
	print '</td></tr>';

	// Project
	if (!empty($conf->projet->enabled)) {
		$formproject = new FormProjets($db);

		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		$formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1);
		print '</td></tr>';
	}

	// Comments
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'">'.GETPOST('note', 'restricthtml').'</textarea></td>';
	print '</tr>';

	print '<tr><td colspan="2"><hr></td></tr>';

	// Auto create payment
	print '<tr><td>'.$langs->trans('AutomaticCreationPayment').'</td>';
	print '<td><input id="auto_create_paiement" name="auto_create_paiement" type="checkbox" ' . (empty($auto_create_paiement) ? '' : 'checked="checked"') . ' value="1"></td></tr>'."\n";	// Date payment

	// Bank
	if (!empty($conf->banque->enabled)) {
		print '<tr><td id="label_fk_account">';
		print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
		print img_picto('', 'bank_account', 'class="paddingrighonly"');
		$form->select_comptes($accountid, "accountid", 0, '', 1); // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td id="label_type_payment">';
	print $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1).'</td><td>';
	$form->select_types_paiements(GETPOST("paymenttype", 'aZ09'), "paymenttype", '');
	print '</td></tr>';

	// Date payment
	print '<tr class="hide_if_no_auto_create_payment"><td>';
	print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate((empty($datep) ? '' : $datep), "datep", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Date value for bank
	print '<tr class="hide_if_no_auto_create_payment"><td>';
	print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
	print $form->selectDate((empty($datev) ?-1 : $datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Number
	if (!empty($conf->banque->enabled)) {
		// Number
		print '<tr class="hide_if_no_auto_create_payment"><td><label for="num_payment">'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</label></td>';
		print '<td><input name="num_payment" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
	}

	// Bouton Save payment
	/*
	print '<tr class="hide_if_no_auto_create_payment"><td>';
	print $langs->trans("ClosePaidSalaryAutomatically");
	print '</td><td><input type="checkbox" checked value="1" name="closepaidsalary"></td></tr>';
	*/

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create');
	}

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';

	print '<div class="hide_if_no_auto_create_payment paddingbottom">';
	print '<input type="checkbox" checked value="1" name="closepaidsalary">'.$langs->trans("ClosePaidSalaryAutomatically");
	print '<br>';
	print '</div>';

	print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp; &nbsp;&nbsp;';
	print '<input type="submit" class="button" name="saveandnew" value="'.$langs->trans("SaveAndNew").'">';
	print '&nbsp;&nbsp; &nbsp;&nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */

if ($id) {
	$head = salaries_prepare_head($object);

	if ($action === 'clone') {
		$formquestion = array(
			array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
		);

		//$formquestion[] = array('type' => 'date', 'name' => 'clone_date_ech', 'label' => $langs->trans("Date"), 'value' => -1);
		$formquestion[] = array('type' => 'date', 'name' => 'clone_date_start', 'label' => $langs->trans("DateStart"), 'value' => -1);
		$formquestion[] = array('type' => 'date', 'name' => 'clone_date_end', 'label' => $langs->trans("DateEnd"), 'value' => -1);

		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneSalary', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 240);
	}

	if ($action == 'paid') {
		$text = $langs->trans('ConfirmPaySalary');
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PaySalary'), $text, "confirm_paid", '', '', 2);
	}

	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteSalary');
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteSalary'), $text, 'confirm_delete', '', '', 2);
	}

	if ($action == 'edit') {
		print "<form name=\"charge\" action=\"".$_SERVER["PHP_SELF"]."?id=$object->id&amp;action=update\" method=\"post\">";
		print '<input type="hidden" name="token" value="'.newToken().'">';
	}

	print dol_get_fiche_head($head, 'card', $langs->trans("SalaryPayment"), -1, 'salary');

	$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	// Label
	if ($action != 'editlabel') {
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->salaries->write, 'string', '', 0, 1);
		$morehtmlref .= $object->label;
	} else {
		$morehtmlref .= '<br>'.$langs->trans('Label').' :&nbsp;';
		$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		$morehtmlref .= '<input type="hidden" name="action" value="setlabel">';
		$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
		$morehtmlref .= '<input type="text" name="label" value="'.$object->label.'"/>';
		$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		$morehtmlref .= '</form>';
	}

	//Employee
	if ($action != 'editfk_user') {
		if ($object->getSommePaiement() > 0 && !empty($object->fk_user)) {
			$userstatic = new User($db);
			$result = $userstatic->fetch($object->fk_user);
			if ($result > 0) {
				$morehtmlref .= '<br>' .$langs->trans('Employee').' : '.$userstatic->getNomUrl(1);
			}
		} else {
			$morehtmlref .= '<br>' . $form->editfieldkey("Employee", 'fk_user', $object->label, $object, $user->rights->salaries->write, 'string', '', 0, 1);

			if (!empty($object->fk_user)) {
				$userstatic = new User($db);
				$result = $userstatic->fetch($object->fk_user);
				if ($result > 0) {
					$morehtmlref .= $userstatic->getNomUrl(1);
				} else {
					dol_print_error($db);
					exit();
				}
			}
		}
	} else {
		$morehtmlref .= '<br>'.$langs->trans('Employee').' :&nbsp;';
		$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		$morehtmlref .= '<input type="hidden" name="action" value="setfk_user">';
		$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
		$morehtmlref .= $form->select_dolusers($object->fk_user, 'userid', 1);
		$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		$morehtmlref .= '</form>';
	}

	// Project
	if (!empty($conf->projet->enabled)) {
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($user->rights->salaries->write) {
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects(-1, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				$morehtmlref .= $proj->ref;
				$morehtmlref .= '</a>';
			} else {
				$morehtmlref .= '';
			}
		}
	}

	$morehtmlref .= '</div>';

	$totalpaye = $object->getSommePaiement();
	$object->totalpaye = $totalpaye;

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	if ($action == 'edit') {
		print '<tr><td class="titlefield">'.$langs->trans("DateStartPeriod")."</td><td>";
		print $form->selectDate($object->datesp, 'datesp', 0, 0, 0, 'datesp', 1);
		print "</td></tr>";
	} else {
		print "<tr>";
		print '<td class="titlefield">' . $langs->trans("DateStartPeriod") . '</td><td>';
		print dol_print_date($object->datesp, 'day');
		print '</td></tr>';
	}

	if ($action == 'edit') {
		print '<tr><td>'.$langs->trans("DateEndPeriod")."</td><td>";
		print $form->selectDate($object->dateep, 'dateep', 0, 0, 0, 'dateep', 1);
		print "</td></tr>";
	} else {
		print "<tr>";
		print '<td>' . $langs->trans("DateEndPeriod") . '</td><td>';
		print dol_print_date($object->dateep, 'day');
		print '</td></tr>';
	}

	/*print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($object->datev, 'day');
	print '</td></tr>';*/

	if ($action == 'edit') {
		print '<tr><td class="fieldrequired">' . $langs->trans("Amount") . '</td><td><input name="amount" size="10" value="' . price($object->amount) . '"></td></tr>';
	} else {
		print '<tr><td>' . $langs->trans("Amount") . '</td><td><span class="amount">' . price($object->amount, 0, $langs, 1, -1, -1, $conf->currency) . '</span></td></tr>';
	}

	// Default mode of payment
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DefaultPaymentMode');
	print '</td>';
	if ($action != 'editmode')
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
	print '</tr></table>';
	print '</td><td>';

	if ($action == 'editmode') {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->type_payment, 'mode_reglement_id');
	} else {
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->type_payment, 'none');
	}
	print '</td></tr>';

	// Default Bank Account
	if (!empty($conf->banque->enabled)) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('DefaultBankAccount');
		print '<td>';
		if ($action != 'editbankaccount' && $user->rights->salaries->write) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
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
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';

	$nbcols = 3;
	if (!empty($conf->banque->enabled)) {
		$nbcols++;
	}

	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_payment as num_payment, p.datep as dp, p.amount,";
	$sql .= " c.code as type_code,c.libelle as paiement_type,";
	$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.currency_code as bacurrency_code, ba.fk_accountancy_journal';
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepayment = c.id";
	$sql .= ", ".MAIN_DB_PREFIX."salary as salaire";
	$sql .= " WHERE p.fk_salary = ".((int) $id);
	$sql .= " AND p.fk_salary = salaire.rowid";
	$sql .= " AND salaire.entity IN (".getEntity('tax').")";
	$sql .= " ORDER BY dp DESC";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		$totalpaye = 0;

		$num = $db->num_rows($resql);
		$i = 0; $total = 0;

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder paymenttable">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		if (!empty($conf->banque->enabled)) {
			print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
		}
		print '<td class="right">'.$langs->trans("Amount").'</td>';
		print '</tr>';

		if ($num > 0) {
			$bankaccountstatic = new Account($db);
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print '<a href="'.DOL_URL_ROOT.'/salaries/payment_salary/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				$labeltype = $langs->trans("PaymentType".$objp->type_code) != ("PaymentType".$objp->type_code) ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
				print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
				if (!empty($conf->banque->enabled)) {
					$bankaccountstatic->id = $objp->baid;
					$bankaccountstatic->ref = $objp->baref;
					$bankaccountstatic->label = $objp->baref;
					$bankaccountstatic->number = $objp->banumber;
					$bankaccountstatic->currency_code = $objp->bacurrency_code;

					if (!empty($conf->accounting->enabled)) {
						$bankaccountstatic->account_number = $objp->account_number;

						$accountingjournal = new AccountingJournal($db);
						$accountingjournal->fetch($objp->fk_accountancy_journal);
						$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
					}

					print '<td class="right">';
					if ($bankaccountstatic->id)
						print $bankaccountstatic->getNomUrl(1, 'transactions');
					print '</td>';
				}
				print '<td class="right">'.price($objp->amount)."</td>\n";
				print "</tr>";
				$totalpaye += $objp->amount;
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '<td></td><td></td><td></td><td></td>';
			print '</tr>';
		}

		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid")." :</td><td class=\"right\">".price($totalpaye)."</td></tr>\n";
		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected")." :</td><td class=\"right\">".price($object->amount)."</td></tr>\n";

		$resteapayer = $object->amount - $totalpaye;
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
	print '</div>';

	print '<div class="clearboth"></div>';


	if ($action == 'edit') {
		print '<div align="center">';
		print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print "</form>\n";
	}

	print dol_get_fiche_end();


	/*
	 * Action bar
	 */

	print '<div class="tabsAction">'."\n";
	if ($action != 'edit') {
		// Reopen
		if ($object->paye && $user->rights->salaries->write) {
			print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".dol_buildpath("/salaries/card.php", 1)."?id=".$object->id.'&action=reopen&token='.newToken().'">'.$langs->trans("ReOpen")."</a></div>";
		}

		// Edit
		if ($object->paye == 0 && $user->rights->salaries->write) {
			print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".DOL_URL_ROOT."/salaries/card.php?id=".$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify")."</a></div>";
		}

		// Emit payment
		if ($object->paye == 0 && ((price2num($object->amount) < 0 && price2num($resteapayer, 'MT') < 0) || (price2num($object->amount) > 0 && price2num($resteapayer, 'MT') > 0)) && $user->rights->salaries->write) {
			print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".DOL_URL_ROOT."/salaries/paiement_salary.php?id=".$object->id.'&action=create&token='.newToken().'">'.$langs->trans("DoPayment")."</a></div>";
		}

		// Classify 'paid'
		if ($object->paye == 0
			&& (
				(round($resteapayer) <= 0 && $object->amount > 0)
				|| (round($resteapayer) >= 0 && $object->amount < 0)
			)
			&& $user->rights->salaries->write) {
			print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".DOL_URL_ROOT."/salaries/card.php?id=".$object->id.'&action=paid&token='.newToken().'">'.$langs->trans("ClassifyPaid")."</a></div>";
		}

		// Clone
		if ($user->rights->salaries->write) {
			print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".DOL_URL_ROOT."/salaries/card.php?id=".$object->id.'&action=clone&token='.newToken().'">'.$langs->trans("ToClone")."</a></div>";
		}

		if (!empty($user->rights->salaries->delete) && empty($totalpaye)) {
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("DisabledBecausePayments"))).'">'.$langs->trans("Delete").'</a></div>';
		}
	}
	print "</div>";
}

// End of page
llxFooter();
$db->close();
