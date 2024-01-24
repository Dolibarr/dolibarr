<?php
/* Copyright (C) 2011-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2014-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023       Maxime Nicolas          <maxime@oarces.com>
 * Copyright (C) 2023       Benjamin GREMBI         <benjamin@oarces.com>
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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "salaries", "hrm", "trips"));
if (isModEnabled('project')) {
	$langs->load("projects");
}

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$confirm = GETPOST('confirm');

$label = GETPOST('label', 'alphanohtml');
$projectid = (GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : GETPOST('fk_project', 'int'));
$accountid = GETPOST('accountid', 'int') > 0 ? GETPOST('accountid', 'int') : 0;
if (GETPOSTISSET('auto_create_paiement') || $action === 'add') {
	$auto_create_paiement = GETPOST("auto_create_paiement", "int");
} else {
	$auto_create_paiement = !getDolGlobalString('CREATE_NEW_SALARY_WITHOUT_AUTO_PAYMENT');
}

$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
$datesp = dol_mktime(12, 0, 0, GETPOST("datespmonth", 'int'), GETPOST("datespday", 'int'), GETPOST("datespyear", 'int'));
$dateep = dol_mktime(12, 0, 0, GETPOST("dateepmonth", 'int'), GETPOST("dateepday", 'int'), GETPOST("dateepyear", 'int'));
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
	if ($user->hasRight('salaries', 'readall')) {
		$canread = 1;
	}
	if ($user->hasRight('salaries', 'read') && $object->fk_user > 0 && in_array($object->fk_user, $childids)) {
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

$permissiontoread = $user->hasRight('salaries', 'read');
$permissiontoadd = $user->hasRight('salaries', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('salaries', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);

$upload_dir = $conf->salaries->multidir_output[$conf->entity];


/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may be modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = DOL_URL_ROOT.'/salaries/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/salaries/card.php?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		//var_dump($cancel);
		//var_dump($backtopage);exit;
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Actions to send emails
	$triggersendname = 'COMPANY_SENTBYMAIL';
	$paramname = 'id';
	$mode = 'emailfromthirdparty';
	$trackid = 'sal'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	//var_dump($upload_dir);var_dump($permissiontoadd);var_dump($action);exit;
	// Actions to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}

// Link to a project
if ($action == 'classin' && $permissiontoadd) {
	$object->fetch($id);
	$object->setProject($projectid);
}

// set label
if ($action == 'setlabel' && $permissiontoadd) {
	$object->fetch($id);
	$object->label = $label;
	$object->update($user);
}

// Classify paid
if ($action == 'confirm_paid' && $permissiontoadd && $confirm == 'yes') {
	$object->fetch($id);
	$result = $object->setPaid($user);
}

if ($action == 'setfk_user' && $permissiontoadd) {
	$result = $object->fetch($id);
	if ($result > 0) {
		$object->fk_user = $fk_user;
		$object->update($user);
	} else {
		dol_print_error($db);
		exit;
	}
}

if ($action == 'reopen' && $permissiontoadd) {
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
if ($action == 'setmode' && $permissiontoadd) {
	$object->fetch($id);
	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// bank account
if ($action == 'setbankaccount' && $permissiontoadd) {
	$object->fetch($id);
	$result = $object->setBankAccount(GETPOST('fk_account', 'int'));
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'add' && empty($cancel)) {
	$error = 0;

	if (empty($datev)) {
		$datev = $datep;
	}

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
	if (isModEnabled("banque") && !empty($auto_create_paiement) && !$object->accountid > 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$db->begin();

		$ret = $object->create($user);
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
		if (!empty($auto_create_paiement) && !$error) {
			// Create a line of payments
			$paiement = new PaymentSalary($db);
			$paiement->fk_salary    = $object->id;
			$paiement->chid         = $object->id;	// deprecated
			$paiement->datep        = $datep;
			$paiement->datev		= $datev;
			$paiement->amounts      = array($object->id=>$amount); // Tableau de montant
			$paiement->fk_typepayment = $type_payment;
			$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$paiement->note_private = GETPOST("note", 'restricthtml');

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
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				header("Location: card.php?action=create&fk_project=" . urlencode($projectid) . "&accountid=" . urlencode($accountid) . '&paymenttype=' . urlencode(GETPOST('paymenttype', 'aZ09')) . '&datepday=' . GETPOST("datepday", 'int') . '&datepmonth=' . GETPOST("datepmonth", 'int') . '&datepyear=' . GETPOST("datepyear", 'int'));
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
	$totalpaid = $object->getSommePaiement();

	if (empty($totalpaid)) {
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


if ($action == 'update' && !GETPOST("cancel") && $permissiontoadd) {
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

if ($action == 'confirm_clone' && $confirm != 'yes') {
	$action = '';
}

if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontoadd) {
	$db->begin();

	$originalId = $id;

	$object->fetch($id);

	if ($object->id > 0) {
		$object->paye = 0;
		$object->id = $object->ref = null;

		if (GETPOST('amount', 'alphanohtml')) {
			$object->amount = price2num(GETPOST('amount', 'alphanohtml'), 'MT', 2);
		}

		if (GETPOST('clone_label', 'alphanohtml')) {
			$object->label = GETPOST('clone_label', 'alphanohtml');
		} else {
			$object->label = $langs->trans("CopyOf").' '.$object->label;
		}

		$newdatestart = dol_mktime(0, 0, 0, GETPOST('clone_date_startmonth', 'int'), GETPOST('clone_date_startday', 'int'), GETPOST('clone_date_startyear', 'int'));
		$newdateend = dol_mktime(0, 0, 0, GETPOST('clone_date_endmonth', 'int'), GETPOST('clone_date_endday', 'int'), GETPOST('clone_date_endyear', 'int'));

		if ($newdatestart) {
			$object->datesp = $newdatestart;
		}
		if ($newdateend) {
			$object->dateep = $newdateend;
		}

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
if ($action == "update_extras" && $permissiontoadd) {
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

$form = new Form($db);
$formfile = new FormFile($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $langs->trans('Salary')." - ".$object->ref;
if ($action == 'create') {
	$title = $langs->trans("NewSalary");
}
$help_url = "";
llxHeader('', $title, $help_url);


if ($id > 0) {
	$result = $object->fetch($id);
	if ($result <= 0) {
		dol_print_error($db);
		exit;
	}
}

// Create
if ($action == 'create' && $permissiontoadd) {
	$year_current = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
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
		$datesp = dol_get_first_day($pastmonthyear, $pastmonth, false);
		$dateep = dol_get_last_day($pastmonthyear, $pastmonth, false);
	}

	print '<form name="salary" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
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
		print "\n".'<script type="text/javascript">';
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

	print dol_get_fiche_head('');

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
	print '<input name="label" id="label" class="minwidth300" value="'.(GETPOST("label") ? GETPOST("label") : $langs->trans("Salary")).'">';
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
	print '<input name="amount" id="amount" class="minwidth75 maxwidth100" value="'.GETPOST("amount").'"> &nbsp;';
	print ' <button class="dpInvisibleButtons datenowlink" id="updateAmountWithLastSalary" name="_useless" type="button">'.$langs->trans('UpdateAmountWithLastSalary').'</a>';
	print '</td>';
	print '</tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print img_picto('', 'project', 'class="pictofixedwidth"');
		print $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 1);
		print '</td></tr>';
	}

	// Comments
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'">'.GETPOST('note', 'restricthtml').'</textarea></td>';
	print '</tr>';


	print '<tr><td colspan="2"><hr></td></tr>';


	// Auto create payment
	print '<tr><td><label for="auto_create_paiement">'.$langs->trans('AutomaticCreationPayment').'</label></td>';
	print '<td><input id="auto_create_paiement" name="auto_create_paiement" type="checkbox" ' . (empty($auto_create_paiement) ? '' : 'checked="checked"') . ' value="1"></td></tr>'."\n";	// Date payment

	// Bank
	if (isModEnabled("banque")) {
		print '<tr><td id="label_fk_account">';
		print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
		print img_picto('', 'bank_account', 'class="paddingrighonly"');
		$form->select_comptes($accountid, "accountid", 0, '', 1); // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td id="label_type_payment">';
	print $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1).'</td><td>';
	print img_picto('', 'bank', 'class="pictofixedwidth"');
	print $form->select_types_paiements(GETPOST("paymenttype", 'aZ09'), "paymenttype", '');
	print '</td></tr>';

	// Date payment
	print '<tr class="hide_if_no_auto_create_payment"><td>';
	print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate((empty($datep) ? '' : $datep), "datep", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Date value for bank
	print '<tr class="hide_if_no_auto_create_payment"><td>';
	print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
	print $form->selectDate((empty($datev) ? -1 : $datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Number
	if (isModEnabled("banque")) {
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
	print '</div>';

	print '</div>';

	$addition_button = array(
		'name' => 'saveandnew',
		'label_key' => 'SaveAndNew',
	);
	print $form->buttonsSaveCancel("Save", "Cancel", $addition_button);

	print '</form>';
	print '<script>';
	print '$( document ).ready(function() {';
	print '$("#updateAmountWithLastSalary").on("click", function updateAmountWithLastSalary() {
					var fk_user = $("#fk_user").val()
					var url = "'.DOL_URL_ROOT.'/salaries/ajax/ajaxsalaries.php?fk_user="+fk_user;
					console.log("We click on link to autofill salary amount url="+url);

					if (fk_user != -1) {
						$.get(
							url,
							function( data ) {
								console.log("Data returned: "+data);
								if (data != null) {
									if (typeof data == "object") {
										console.log("data is already type object, no need to parse it");
										item = data;
									} else {
										console.log("data is type "+(typeof data));
										item = JSON.parse(data);
									}
									if (item[0].key == "Amount") {
										value = item[0].value;
										console.log("amount returned = "+value);
										if (value != null) {
											$("#amount").val(item[0].value);
										} else {
											console.error("Error: Ajax url "+url+" has returned a null value.");
										}
									} else {
										console.error("Error: Ajax url "+url+" has returned the wrong key.");
									}
								} else {
									console.error("Error: Ajax url "+url+" has returned an empty page.");
								}
							}
						);

					} else {
						alert("'.dol_escape_js($langs->transnoentitiesnoconv("FillFieldFirst")).'");
					}
		});

	})';
	print '</script>';
}

// View mode
if ($id > 0) {
	$head = salaries_prepare_head($object);
	$formconfirm = '';

	if ($action === 'clone') {
		$formquestion = array(
			array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
		);

		//$formquestion[] = array('type' => 'date', 'name' => 'clone_date_ech', 'label' => $langs->trans("Date"), 'value' => -1);
		$formquestion[] = array('type' => 'date', 'name' => 'clone_date_start', 'label' => $langs->trans("DateStart"), 'value' => -1);
		$formquestion[] = array('type' => 'date', 'name' => 'clone_date_end', 'label' => $langs->trans("DateEnd"), 'value' => -1);
		$formquestion[] = array('type' => 'text', 'name' => 'amount', 'label' => $langs->trans("Amount"), 'value' => price($object->amount), 'morecss' => 'width100 right');

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneSalary', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 280);

		//Add fill with end of month button
		$formconfirm .= "<script>
			$('#clone_date_end').after($('<button id=\"fill_end_of_month\" class=\"dpInvisibleButtons\" style=\"color: var(--colortextlink);font-size: 0.8em;opacity: 0.7;margin-left:4px;\" type=\"button\">".$langs->trans('FillEndOfMonth')."</button>'));
			$('#fill_end_of_month').click(function(){
				var clone_date_startmonth = +$('#clone_date_startmonth').val();
				var clone_date_startyear = +$('#clone_date_startyear').val();
				var end_date = new Date(clone_date_startyear, clone_date_startmonth, 0);
				end_date.setMonth(clone_date_startmonth - 1);
				$('#clone_date_end').val(formatDate(end_date,'".$langs->trans("FormatDateShortJavaInput")."'));
				$('#clone_date_endday').val(end_date.getDate());
				$('#clone_date_endmonth').val(end_date.getMonth() + 1);
				$('#clone_date_endyear').val(end_date.getFullYear());
			});
		</script>";
	}

	if ($action == 'paid') {
		$text = $langs->trans('ConfirmPaySalary');
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PaySalary'), $text, "confirm_paid", '', '', 2);
	}

	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteSalary');
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteSalary'), $text, 'confirm_delete', '', '', 2);
	}

	if ($action == 'edit') {
		print "<form name=\"charge\" action=\"".$_SERVER["PHP_SELF"]."?id=$object->id&amp;action=update\" method=\"post\">";
		print '<input type="hidden" name="token" value="'.newToken().'">';
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	print dol_get_fiche_head($head, 'card', $langs->trans("SalaryPayment"), -1, 'salary', 0, '', '', 0, '', 1);

	$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	// Label
	if ($action != 'editlabel') {
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $permissiontoadd, 'string', '', 0, 1);
		$morehtmlref .= $object->label;
	} else {
		$morehtmlref .= $langs->trans('Label').' :&nbsp;';
		$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		$morehtmlref .= '<input type="hidden" name="action" value="setlabel">';
		$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
		$morehtmlref .= '<input type="text" name="label" value="'.$object->label.'"/>';
		$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
		$morehtmlref .= '</form>';
	}

	// Employee
	if ($action != 'editfk_user') {
		if ($object->getSommePaiement() > 0 && !empty($object->fk_user)) {
			$userstatic = new User($db);
			$result = $userstatic->fetch($object->fk_user);
			if ($result > 0) {
				$morehtmlref .= '<br>' .$langs->trans('Employee').' : '.$userstatic->getNomUrl(-1);
			}
		} else {
			$morehtmlref .= '<br>' . $form->editfieldkey("Employee", 'fk_user', $object->label, $object, $permissiontoadd, 'string', '', 0, 1);

			if (!empty($object->fk_user)) {
				$userstatic = new User($db);
				$result = $userstatic->fetch($object->fk_user);
				if ($result > 0) {
					$morehtmlref .= $userstatic->getNomUrl(-1);
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

	$usercancreate = $permissiontoadd;

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if ($usercancreate) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, -1, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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

	$totalpaid = $object->getSommePaiement();
	$object->alreadypaid = $totalpaid;
	$object->totalpaid = $totalpaid;

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

	// Default Bank Account
	if (isModEnabled("banque")) {
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('DefaultBankAccount');
		print '<td>';
		if ($action != 'editbankaccount' && $permissiontoadd) {
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
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	print '<div class="fichehalfright">';

	$nbcols = 3;
	if (isModEnabled("banque")) {
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
		$totalpaid = 0;

		$num = $db->num_rows($resql);
		$i = 0;
		$total = 0;

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder paymenttable">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		if (isModEnabled("banque")) {
			print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
		}
		print '<td class="right">'.$langs->trans("Amount").'</td>';
		print '</tr>';

		$paymentsalarytemp = new PaymentSalary($db);

		if ($num > 0) {
			$bankaccountstatic = new Account($db);
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$paymentsalarytemp->id = $objp->rowid;
				$paymentsalarytemp->ref = $objp->rowid;
				$paymentsalarytemp->num_payment = $objp->num_payment;
				$paymentsalarytemp->datep = $objp->dp;

				print '<tr class="oddeven"><td>';
				print $paymentsalarytemp->getNomUrl(1);
				print '</td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp), 'dayhour', 'tzuserrel')."</td>\n";
				$labeltype = $langs->trans("PaymentType".$objp->type_code) != "PaymentType".$objp->type_code ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
				print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
				if (isModEnabled("banque")) {
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
				print '<td class="right nowrap amountcard">'.price($objp->amount)."</td>\n";
				print "</tr>";
				$totalpaid += $objp->amount;
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '<td></td><td></td><td></td><td></td>';
			print '</tr>';
		}

		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="right nowrap amountcard">'.price($totalpaid)."</td></tr>\n";
		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected").' :</td><td class="right nowrap amountcard">'.price($object->amount)."</td></tr>\n";

		$resteapayer = $object->amount - $totalpaid;
		$cssforamountpaymentcomplete = 'amountpaymentcomplete';

		print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
		print '<td class="right nowrap'.($resteapayer ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayer)."</td></tr>\n";

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
		print "</form>";
	}

	$resteapayer = price2num($resteapayer, 'MT');


	/*
	 * Action bar
	 */

	print '<div class="tabsAction">'."\n";
	if ($action != 'edit') {
		// Dynamic send mail button
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			if (empty($user->socid)) {
				$canSendMail = true;

				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&token='.newToken().'&mode=init#formmailbeforetitle', '', $canSendMail);
			}
		}

		// Reopen
		if ($object->paye && $permissiontoadd) {
			print dolGetButtonAction('', $langs->trans('ReOpen'), 'default', $_SERVER["PHP_SELF"].'?action=reopen&token='.newToken().'&id='.$object->id, '');
		}

		// Edit
		if ($object->paye == 0 && $permissiontoadd) {
			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id, '');
		}

		// Emit payment
		if ($object->paye == 0 && ((price2num($object->amount) < 0 && $resteapayer < 0) || (price2num($object->amount) > 0 && $resteapayer > 0)) && $permissiontoadd) {
			print dolGetButtonAction('', $langs->trans('DoPayment'), 'default', DOL_URL_ROOT.'/salaries/paiement_salary.php?action=create&token='.newToken().'&id='. $object->id, '');
		}

		// Classify 'paid'
		// If payment complete $resteapayer <= 0 on a positive salary, or if amount is negative, we allow to classify as paid.
		if ($object->paye == 0 && (($resteapayer <= 0 && $object->amount > 0) || ($object->amount <= 0)) && $permissiontoadd) {
			print dolGetButtonAction('', $langs->trans('ClassifyPaid'), 'default', $_SERVER["PHP_SELF"].'?action=paid&token='.newToken().'&id='.$object->id, '');
		}

		// Transfer request
		print dolGetButtonAction('', $langs->trans('MakeTransferRequest'), 'default', DOL_URL_ROOT.'/salaries/virement_request.php?id='.$object->id, '');

		// Clone
		if ($permissiontoadd) {
			print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER["PHP_SELF"].'?action=clone&token='.newToken().'&id='.$object->id, '');
		}

		if ($permissiontodelete && empty($totalpaid)) {
			print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id, '');
		} else {
			print dolGetButtonAction($langs->trans('DisabledBecausePayments'), $langs->trans('Delete'), 'default', $_SERVER['PHP_SELF'].'#', '', false);
		}
	}
	print "</div>";



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
			$filedir = $conf->salaries->dir_output.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			//$genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
			$genallowed = 0; // If you can read, you can build the PDF to read content
			$delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('salaries', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		/*
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('salaries'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
		*/

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_agenda.php', 1).'?id='.$object->id);

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		//$somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'salary';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->salaries->dir_output;
	$trackid = 'salary'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

	// Hook to add more things on page
	$parameters = array();
	$reshook = $hookmanager->executeHooks('salaryCardTabAddMore', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
}

// End of page
llxFooter();
$db->close();
