<?php
/* Copyright (C) 2005-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2017-2019  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/salaries/info.php
 *	\ingroup    salaries
 *	\brief      Page with info about salaries contribution
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
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "users", "salaries", "hrm", "withdrawals"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$type = 'salaire';

$label = GETPOST('label', 'alphanohtml');
$projectid = (GETPOSTINT('projectid') ? GETPOSTINT('projectid') : GETPOSTINT('fk_project'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}


$object = new Salary($db);
$extrafields = new ExtraFields($db);

$childids = $user->getAllChildIds(1);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('salaryinfo', 'globalcard'));

$object = new Salary($db);
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

$permissiontoread = $user->hasRight('salaries', 'read');
$permissiontoadd = $user->hasRight('salaries', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles
$permissiontodelete = $user->hasRight('salaries', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_UNPAID);

$moreparam = '';
if ($type == 'bank-transfer') {
	$obj = new FactureFournisseur($db);
	$moreparam = '&type='.$type;
} else {
	$obj = new Facture($db);
}

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	$isdraft = (($obj->status == FactureFournisseur::STATUS_DRAFT) ? 1 : 0);
	if ($ret > 0) {
		$object->fetch_thirdparty();
	}
}

$hookmanager->initHooks(array('directdebitcard', 'globalcard'));

restrictedArea($user, 'salaries', $object->id, 'salary', '');


/*
 * Actions
 */

// Link to a project
if ($action == 'classin' && $user->hasRight('banque', 'modifier')) {
	$object->fetch($id);
	$object->setProject($projectid);
}

// set label
if ($action == 'setlabel' && $user->hasRight('salaries', 'write')) {
	$object->fetch($id);
	$object->label = $label;
	$object->update($user);
}

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $obj, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


if ($action == "add" && $permissiontoadd) {
	//var_dump($object);exit;
	if ($object->id > 0) {
		$db->begin();

		$sourcetype = 'salaire';
		$newtype = 'salaire';
		$paymentservice = GETPOST('paymentservice');
		$result = $object->demande_prelevement($user, price2num(GETPOST('request_transfer', 'alpha')), $newtype, $sourcetype);

		if ($result > 0) {
			$db->commit();

			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		} else {
			dol_print_error($db, $error);
			$db->rollback();
			setEventMessages($obj->error, $obj->errors, 'errors');
		}
	}
	$action = '';
}

if ($action == "delete" && $permissiontodelete) {
	if ($object->id > 0) {
		$result = $object->demande_prelevement_delete($user, GETPOSTINT('did'));
		if ($result == 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}
}


/*
 * View
 */

if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $langs->trans('Salary')." - ".$langs->trans('Info');
$help_url = "";
llxHeader("", $title, $help_url);

$object->fetch($id);
$object->info($id);

$head = salaries_prepare_head($object);

print dol_get_fiche_head($head, 'request_virement', $langs->trans("SalaryPayment"), -1, 'salary');

$linkback = '<a href="'.DOL_URL_ROOT.'/salaries/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';

$userstatic = new User($db);
$userstatic->fetch($object->fk_user);


// Label
if ($action != 'editlabel') {
	$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->hasRight('salaries', 'write'), 'string', '', 0, 1);
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

$morehtmlref .= '<br>'.$langs->trans('Employee').' : '.$userstatic->getNomUrl(-1);

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
		$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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
if (isModEnabled("bank")) {
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('DefaultBankAccount');
	print '<td>';
	if ($action != 'editbankaccount' && $user->hasRight('salaries', 'write')) {
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

$user_perms = $user->hasRight('virement', 'bons', 'creer');
$buttonlabel = $langs->trans("MakeTransferRequest");
$user_perms = $user->hasRight('paymentbybanktransfer', 'create');

print '<div class="fichehalfright">';
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
$sql .= ", ".MAIN_DB_PREFIX."salary as s";
$sql .= " WHERE p.fk_salary = ".((int) $id);
$sql .= " AND p.fk_salary = s.rowid";
$sql .= " AND s.entity IN (".getEntity('tax').")";
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
			print '<a href="'.DOL_URL_ROOT.'/salaries/payment_salary/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($objp->dp), 'dayhour', 'tzuserrel')."</td>\n";
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

	// print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="right nowrap amountcard">'.price($totalpaid)."</td></tr>\n";
	// print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected").' :</td><td class="right nowrap amountcard">'.price($object->amount)."</td></tr>\n";

	$resteapayer = (float) $object->amount - $totalpaid;
	// $cssforamountpaymentcomplete = 'amountpaymentcomplete';

	// print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
	// print '<td class="right nowrap'.($resteapayer ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayer)."</td></tr>\n";

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

/**button  */
print '<div class="tabsAction">'."\n";

$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande,";
$sql .= " pfd.date_traite as date_traite, pfd.amount, pfd.fk_prelevement_bons,";
$sql .= " pb.ref, pb.date_trans, pb.method_trans, pb.credite, pb.date_credit, pb.datec, pb.statut as status, pb.amount as pb_amount,";
$sql .= " u.rowid as user_id, u.email, u.lastname, u.firstname, u.login, u.statut as user_status";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
if ($type == 'salaire') {
	$sql .= " WHERE pfd.fk_salary = ".((int) $object->id);
} else {
	$sql .= " WHERE fk_facture = ".((int) $object->id);
}
$sql .= " AND pfd.traite = 0";
$sql .= " AND pfd.type = 'ban'";
$sql .= " ORDER BY pfd.date_demande DESC";
$resql = $db->query($sql);

$hadRequest = $db->num_rows($resql);
if ($object->paye == 0 && $hadRequest == 0) {
	if ($resteapayer > 0) {
		if ($user_perms) {
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			print '<input type="hidden" name="token" value="'.newToken().'" />';
			print '<input type="hidden" name="id" value="'.$object->id.'" />';
			print '<input type="hidden" name="type" value="'.$type.'" />';
			print '<input type="hidden" name="action" value="add" />';
			print '<label for="withdraw_request_amount">'.$langs->trans('BankTransferAmount').' </label>';
			print '<input type="text" id="withdraw_request_amount" name="request_transfer" value="'.price($resteapayer, 0, $langs, 1, -1, -1).'" size="9" />';
			print '<input type="submit" class="butAction" value="'.$buttonlabel.'" />';
			print '</form>';

			if (getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT_SHOW_OLD_BUTTON')) {	// This is hidden, prefer to use mode enabled with STRIPE_SEPA_DIRECT_DEBIT
				// TODO Replace this with a checkbox for each payment mode: "Send request to XXX immediately..."
				print "<br>";
				//add stripe sepa button
				$buttonlabel = $langs->trans("MakeWithdrawRequestStripe");
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				print '<input type="hidden" name="token" value="'.newToken().'" />';
				print '<input type="hidden" name="id" value="'.$object->id.'" />';
				print '<input type="hidden" name="type" value="'.$type.'" />';
				print '<input type="hidden" name="action" value="add" />';
				print '<input type="hidden" name="paymenservice" value="stripesepa" />';
				print '<label for="withdraw_request_amount">'.$langs->trans('BankTransferAmount').' </label>';
				print '<input type="text" id="withdraw_request_amount" name="request_transfer" value="'.price($resteapayer, 0, $langs, 1, -1, -1).'" size="9" />';
				print '<input type="submit" class="butAction" value="'.$buttonlabel.'" />';
				print '</form>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$buttonlabel.'</a>';
		}
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AmountMustBePositive")).'">'.$buttonlabel.'</a>';
	}
} else {
	if ($hadRequest == 0) {
		if ($object->paye > 0) {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("AlreadyPaid")).'">'.$buttonlabel.'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("Draft")).'">'.$buttonlabel.'</a>';
		}
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("RequestAlreadyDone")).'">'.$buttonlabel.'</a>';
	}
}

print '</div>';

print '<div>';


$bprev = new BonPrelevement($db);


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td>&nbsp;</td>';
}
print '<td class="left">'.$langs->trans("DateRequest").'</td>';
print '<td>'.$langs->trans("User").'</td>';
print '<td class="center">'.$langs->trans("Amount").'</td>';
print '<td class="center">'.$langs->trans("DateProcess").'</td>';
if ($type == 'bank-transfer') {
	print '<td class="center">'.$langs->trans("BankTransferReceipt").'</td>';
} else {
	print '<td class="center">'.$langs->trans("WithdrawalReceipt").'</td>';
}
print '<td>&nbsp;</td>';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td>&nbsp;</td>';
}
print '</tr>';

$num = 0;
if ($resql) {
	$i = 0;

	$tmpuser = new User($db);

	$num = $db->num_rows($result);
	if ($num > 0) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$tmpuser->id = $obj->user_id;
			$tmpuser->login = $obj->login;
			$tmpuser->ref = $obj->login;
			$tmpuser->email = $obj->email;
			$tmpuser->lastname = $obj->lastname;
			$tmpuser->firstname = $obj->firstname;
			$tmpuser->statut = $obj->user_status;
			$tmpuser->status = $obj->user_status;

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="right">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'&did='.$obj->rowid.'&type='.urlencode($type).'">';
				print img_delete();
				print '</a></td>';
			}

			// Date
			print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'dayhour')."</td>\n";

			// User
			print '<td class="tdoverflowmax125">';
			print $tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login');
			print '</td>';

			// Amount
			print '<td class="center"><span class="amount">'.price($obj->amount).'</span></td>';

			// Date process
			print '<td class="center"><span class="opacitymedium">'.$langs->trans("OrderWaiting").'</span></td>';

			// Link to make payment now
			print '<td class="minwidth75">';
			if ($obj->fk_prelevement_bons > 0) {
				$withdrawreceipt = new BonPrelevement($db);
				$withdrawreceipt->id = $obj->fk_prelevement_bons;
				$withdrawreceipt->ref = $obj->ref;
				$withdrawreceipt->date_trans = $db->jdate($obj->date_trans);
				$withdrawreceipt->date_credit = $db->jdate($obj->date_credit);
				$withdrawreceipt->date_creation = $db->jdate($obj->datec);
				$withdrawreceipt->statut = $obj->status;
				$withdrawreceipt->status = $obj->status;
				$withdrawreceipt->amount = $obj->pb_amount;
				//$withdrawreceipt->credite = $db->jdate($obj->credite);

				print $withdrawreceipt->getNomUrl(1);
			}

			if (!in_array($type, array('bank-transfer', 'salaire', 'salary'))) {
				if (getDolGlobalString('STRIPE_SEPA_DIRECT_DEBIT')) {
					$langs->load("stripe");
					if ($obj->fk_prelevement_bons > 0) {
						print ' &nbsp; ';
					}
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=sepastripedirectdebit&paymentservice=stripesepa&token='.newToken().'&did='.$obj->rowid.'&id='.$object->id.'&type='.urlencode($type).'">'.img_picto('', 'stripe', 'class="pictofixedwidth"').$langs->trans("RequestDirectDebitWithStripe").'</a>';
				}
			} else {
				if (getDolGlobalString('STRIPE_SEPA_CREDIT_TRANSFER')) {
					$langs->load("stripe");
					if ($obj->fk_prelevement_bons > 0) {
						print ' &nbsp; ';
					}
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=sepastripecredittransfer&paymentservice=stripesepa&token='.newToken().'&did='.$obj->rowid.'&id='.$object->id.'&type='.urlencode($type).'">'.img_picto('', 'stripe', 'class="pictofixedwidth"').$langs->trans("RequesCreditTransferWithStripe").'</a>';
				}
			}
			print '</td>';

			//
			print '<td class="center">-</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="right">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().'&did='.$obj->rowid.'&type='.urlencode($type).'">';
				print img_delete();
				print '</a></td>';
			}

			print "</tr>\n";
			$i++;
		}
	}

	$db->free($resql);
} else {
	dol_print_error($db);
}

// Past requests when bon prelevement

$sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande,";
$sql .= " pfd.date_traite as date_traite, pfd.amount, pfd.fk_prelevement_bons,";
$sql .= " pb.ref, pb.date_trans, pb.method_trans, pb.credite, pb.date_credit, pb.datec, pb.statut as status, pb.amount as pb_amount,";
$sql .= " u.rowid as user_id, u.email, u.lastname, u.firstname, u.login, u.statut as user_status";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on pfd.fk_user_demande = u.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."prelevement_bons as pb ON pb.rowid = pfd.fk_prelevement_bons";
if ($type == 'salaire') {
	$sql .= " WHERE pfd.fk_salary = ".((int) $object->id);
} else {
	$sql .= " WHERE fk_facture = ".((int) $object->id);
}
$sql .= " AND pfd.traite = 1";
$sql .= " AND pfd.type = 'ban'";
$sql .= " ORDER BY pfd.date_demande DESC";

$resql = $db->query($sql);
if ($resql) {
	$numOfBp = $db->num_rows($resql);
	$i = 0;
	$tmpuser = new User($db);
	if ($numOfBp > 0) {
		while ($i < $numOfBp) {
			$obj = $db->fetch_object($resql);

			$tmpuser->id = $obj->user_id;
			$tmpuser->login = $obj->login;
			$tmpuser->ref = $obj->login;
			$tmpuser->email = $obj->email;
			$tmpuser->lastname = $obj->lastname;
			$tmpuser->firstname = $obj->firstname;
			$tmpuser->statut = $obj->user_status;
			$tmpuser->status = $obj->user_status;

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td>&nbsp;</td>';
			}

			// Date
			print '<td class="nowraponall">'.dol_print_date($db->jdate($obj->date_demande), 'dayhour')."</td>\n";

			// User
			print '<td class="tdoverflowmax125">';
			print $tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login');
			print '</td>';

			// Amount
			print '<td class="center"><span class="amount">'.price($obj->amount).'</span></td>';

			// Date process
			print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_traite), 'dayhour', 'tzuserrel')."</td>\n";

			// Link to payment request done
			print '<td class="center minwidth75">';
			if ($obj->fk_prelevement_bons > 0) {
				$withdrawreceipt = new BonPrelevement($db);
				$withdrawreceipt->id = $obj->fk_prelevement_bons;
				$withdrawreceipt->ref = $obj->ref;
				$withdrawreceipt->date_trans = $db->jdate($obj->date_trans);
				$withdrawreceipt->date_credit = $db->jdate($obj->date_credit);
				$withdrawreceipt->date_creation = $db->jdate($obj->datec);
				$withdrawreceipt->statut = $obj->status;
				$withdrawreceipt->status = $obj->status;
				$withdrawreceipt->fk_bank_account = $obj->fk_bank_account;
				$withdrawreceipt->amount = $obj->pb_amount;
				//$withdrawreceipt->credite = $db->jdate($obj->credite);

				print $withdrawreceipt->getNomUrl(1);
				print ' ';
				print $withdrawreceipt->getLibStatut(2);

				// Show the bank account
				$fk_bank_account = $withdrawreceipt->fk_bank_account;
				if (empty($fk_bank_account)) {
					$fk_bank_account = ($object->type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
				}
				if ($fk_bank_account > 0) {
					$bankaccount = new Account($db);
					$result = $bankaccount->fetch($fk_bank_account);
					if ($result > 0) {
						print ' - ';
						print $bankaccount->getNomUrl(1);
					}
				}
			}
			print "</td>\n";

			//
			print '<td>&nbsp;</td>';

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td>&nbsp;</td>';
			}

			print "</tr>\n";
			$i++;
		}
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

if ($num == 0 && $numOfBp == 0) {
	print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
}

print "</table>";
print '</div>';

// End of page
llxFooter();
$db->close();
