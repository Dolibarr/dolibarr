<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/compta/prelevement/card.php
 *	\ingroup    prelevement
 *	\brief      Card of a direct debit
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'bills', 'companies', 'withdrawals'));

// Get supervariables
$action = GETPOST('action', 'aZ09');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$type = GETPOST('type', 'aZ09');
$date_trans = dol_mktime(GETPOST('date_transhour', 'int'), GETPOST('date_transmin', 'int'), GETPOST('date_transsec', 'int'), GETPOST('date_transmonth', 'int'), GETPOST('date_transday', 'int'), GETPOST('date_transyear', 'int'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = 'pl.rowid';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$object = new BonPrelevement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

$hookmanager->initHooks(array('directdebitprevcard', 'globalcard', 'directdebitprevlist'));

$type = $object->type;
// chek if salary pl
$salaryBonPl = $object->checkIfSalaryBonPrelevement();
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');

	$permissiontoadd = $user->hasRight('paymentbybanktransfer', 'read');
	$permissiontosend = $user->hasRight('paymentbybanktransfer', 'send');
	$permissiontocreditdebit = $user->hasRight('paymentbybanktransfer', 'debit');
	$permissiontodelete = $user->hasRight('paymentbybanktransfer', 'read');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');

	$permissiontoadd = $user->hasRight('prelevement', 'bons', 'read');
	$permissiontosend = $user->hasRight('prelevement', 'bons', 'send');
	$permissiontocreditdebit = $user->hasRight('prelevement', 'bons', 'credit');
	$permissiontodelete = $user->hasRight('prelevement', 'bons', 'read');
}



/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'setbankaccount' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);
		$object->fk_bank_account = GETPOST('fk_bank_account', 'int');
		$object->update($user);
	}

	// date of upload
	if ($action == 'setdate_trans' && $permissiontoadd) {
		$result = $object->setValueFrom('date_trans', $date_trans, '', null, 'date');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'infotrans' && $permissiontosend) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$dt = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

		/*
		if ($_FILES['userfile']['name'] && basename($_FILES['userfile']['name'],".ps") == $object->ref)
		{
			$dir = $conf->prelevement->dir_output.'/receipts';

			if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $dir . "/" . dol_unescapefile($_FILES['userfile']['name']),1) > 0)
			{
				$object->set_infotrans($user, $dt, GETPOST('methode','alpha'));
			}

			header("Location: card.php?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("Fichier invalide",LOG_WARNING);
			$mesg='BadFile';
		}*/

		$error = $object->set_infotrans($user, $dt, GETPOST('methode', 'alpha'));

		if ($error) {
			header("Location: card.php?id=".$id."&error=$error");
			exit;
		}
	}

	// Set direct debit order to credited, create payment and close invoices
	if ($action == 'setinfocredit' && $permissiontocreditdebit) {
		$dt = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

		if (($object->type != 'bank-transfer' && $object->statut == BonPrelevement::STATUS_CREDITED) || ($object->type == 'bank-transfer' && $object->statut == BonPrelevement::STATUS_DEBITED)) {
			$error = 1;
			setEventMessages('WithdrawalCantBeCreditedTwice', array(), 'errors');
		} else {
			$error = $object->set_infocredit($user, $dt, ($salaryBonPl ? 'salary' : ''));
		}

		if ($error) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'reopen' && $permissiontocreditdebit) {
		$savtype = $object->type;
		$res = $object->setStatut(BonPrelevement::STATUS_TRANSFERED);
		if ($res <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_delete' && $permissiontodelete) {
		$savtype = $object->type;
		$res = $object->delete($user);
		if ($res > 0) {
			if ($savtype == 'bank-transfer') {
				header("Location: ".DOL_URL_ROOT.'/compta/paymentbybanktransfer/index.php');
			} else {
				header("Location: ".DOL_URL_ROOT.'/compta/prelevement/index.php');
			}
			exit;
		}
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("WithdrawalsReceipts"));

if ($id > 0 || $ref) {
	$head = prelevement_prepare_head($object);
	print dol_get_fiche_head($head, 'prelevement', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

	if (GETPOST('error', 'alpha') != '') {
		print '<div class="error">'.$object->getErrorString(GETPOST('error', 'alpha')).'</div>';
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/orders_list.php?restore_lastsearch_values=1'.($object->type != 'bank-transfer' ? '' : '&type=bank-transfer').'">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Date for payment
	print '<tr><td class="titlefieldcreate">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec, 'day').'</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($object->amount).'</span></td></tr>';

	// Upload file
	if (!empty($object->date_trans)) {
		$muser = new User($db);
		$muser->fetch($object->user_trans);

		// Date upload
		print '<tr><td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('TransData');
		print '</td>';
		if ($action != 'editdate_trans') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_trans&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetTransDate'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdate_trans') {
			print '<form name="setdate_trans" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setdate_trans">';
			print $form->selectDate($object->date_trans ? $object->date_trans : -1, 'date_trans', 0, '', "setdate_trans");
			print '<input type="submit" class="button button-edit smallpaddingimp valign middle" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print $object->date_trans ? dol_print_date($object->date_trans, 'day') : '';
			print ' &nbsp; <span class="opacitymedium">'.$langs->trans("By").'</span> '.$muser->getNomUrl(-1).'</td>';
		}
		print '</td>';
		print '</tr>';

		// Method upload
		print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
		print $object->methodes_trans[$object->method_trans];
		print '</td></tr>';
	}

	// Date real payment
	if (!empty($object->date_credit)) {
		print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
		print dol_print_date($object->date_credit, 'day');
		print '</td></tr>';
	}

	print '</table>';

	print '<br>';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Get bank account for the payment
	$acc = new Account($db);
	$fk_bank_account = $object->fk_bank_account;
	if (empty($fk_bank_account)) {
		$fk_bank_account = ($object->type == 'bank-transfer' ? getDolGlobalInt('PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT') : getDolGlobalInt('PRELEVEMENT_ID_BANKACCOUNT'));
	}
	if ($fk_bank_account > 0) {
		$result = $acc->fetch($fk_bank_account);
	}

	// Bank account
	$labelofbankfield = "BankToReceiveWithdraw";
	if ($object->type == 'bank-transfer') {
		$labelofbankfield = 'BankToPayCreditTransfer';
	}
	//print $langs->trans($labelofbankfield);
	$caneditbank = $permissiontoadd;
	if ($object->status != $object::STATUS_DRAFT) {
		$caneditbank = 0;
	}
	/*
	print '<tr><td class="titlefieldcreate">';
	print $form->editfieldkey($langs->trans($labelofbankfield), 'fk_bank_account', $acc->id, $object, $caneditbank);
	print '</td>';
	print '<td>';
	print $form->editfieldval($langs->trans($labelofbankfield), 'fk_bank_account', $acc->id, $acc, $caneditbank, 'string', '', null, null, '', 1, 'getNomUrl');
	print '</td>';
	print '</tr>';
	*/
	print '<tr><td class="titlefieldcreate">';
	print '<table class="nobordernopadding centpercent"><tr><td class="nowrap">';
	print $form->textwithpicto($langs->trans("BankAccount"), $langs->trans($labelofbankfield));
	print '<td>';
	if (($action != 'editbankaccount') && $caneditbank) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editfkbankaccount&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td>';
	if ($action == 'editfkbankaccount') {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $fk_bank_account, 'fk_bank_account', 0);
	} else {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $fk_bank_account, 'none');
	}
	print "</td>";
	print '</tr>';

	// Donwload file
	print '<tr><td class="titlefieldcreate">';
	$labelfororderfield = 'WithdrawalFile';
	if ($object->type == 'bank-transfer') {
		$labelfororderfield = 'CreditTransferFile';
	}
	print $langs->trans($labelfororderfield).'</td><td>';

	$modulepart = 'prelevement';
	if ($object->type == 'bank-transfer') {
		$modulepart = 'paymentbybanktransfer';
	}

	if (isModEnabled('multicompany')) {
		$labelentity = $conf->entity;
		$relativepath = 'receipts/'.$object->ref.'-'.$labelentity.'.xml';

		if ($type != 'bank-transfer') {
			$dir = $conf->prelevement->dir_output;
		} else {
			$dir = $conf->paymentbybanktransfer->dir_output;
		}
		if (!dol_is_file($dir.'/'.$relativepath)) {	// For backward compatibility
			$relativepath = 'receipts/'.$object->ref.'.xml';
		}
	} else {
		$relativepath = 'receipts/'.$object->ref.'.xml';
	}

	print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">'.$relativepath;
	print img_picto('', 'download', 'class="paddingleft"');
	print '</a>';
	print '</td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();


	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Delete'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Call Hook formConfirm
	/*$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;*/

	// Print form confirm
	print $formconfirm;


	if (empty($object->date_trans) && (($user->hasRight('prelevement', 'bons', 'send') && $object->type != 'bank-transfer') || ($user->hasRight('paymentbybanktransfer', 'send') && $object->type == 'bank-transfer')) && $action == 'settransmitted') {
		print '<form method="post" name="userfile" action="card.php?id='.$object->id.'" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="infotrans">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("NotifyTransmision").'</td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans("TransData").'</td><td>';
		print $form->selectDate('', '', '', '', '', "userfile", 1, 1);
		print '</td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans("TransMetod").'</td><td>';
		print $form->selectarray("methode", $object->methodes_trans);
		print '</td></tr>';
		print '</table>';
		print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("SetToStatusSent")).'"></div>';
		print '</form>';
		print '<br>';
	}

	if ($object->status == BonPrelevement::STATUS_TRANSFERED && (($user->hasRight('prelevement', 'bons', 'credit') && $object->type != 'bank-transfer') || ($user->hasRight('paymentbybanktransfer', 'debit') && $object->type == 'bank-transfer')) && $action == 'setcredited') {
		$btnLabel = ($object->type == 'bank-transfer') ? $langs->trans("ClassDebited") : $langs->trans("ClassCredited");
		print '<form name="infocredit" method="post" action="card.php?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setinfocredit">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("NotifyCredit").'</td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans('CreditDate').'</td><td>';
		print $form->selectDate(-1, '', '', '', '', "infocredit", 1, 1);
		print '</td></tr>';
		print '</table>';
		print '<br><div class="center"><span class="opacitymedium">'.$langs->trans("ThisWillAlsoAddPaymentOnInvoice").'</span></div>';
		print '<div class="center"><input type="submit" class="button" value="'.dol_escape_htmltag($btnLabel).'"></div>';
		print '</form>';
		print '<br>';
	}

	// Actions
	if ($action != 'settransmitted' && $action != 'setcredited') {
		print "\n".'<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			if (empty($object->date_trans)) {
				if ($object->type == 'bank-transfer') {
					print dolGetButtonAction($langs->trans("SetToStatusSent"), '', 'default', 'card.php?action=settransmitted&token='.newToken().'&id='.$object->id, '', $user->hasRight('paymentbybanktransfer', 'send'));
				} else {
					print dolGetButtonAction($langs->trans("SetToStatusSent"), '', 'default', 'card.php?action=settransmitted&token='.newToken().'&id='.$object->id, '', $user->hasRight('prelevement', 'bons', 'send'));
				}
			}

			if ($object->status == BonPrelevement::STATUS_TRANSFERED) {
				if ($object->type == 'bank-transfer') {
					print dolGetButtonAction($langs->trans("ClassDebited"), '', 'default', 'card.php?action=setcredited&token='.newToken().'&id='.$object->id, '', $user->hasRight('paymentbybanktransfer', 'debit'));
				} else {
					print dolGetButtonAction($langs->trans("ClassCredited"), '', 'default', 'card.php?action=setcredited&token='.newToken().'&id='.$object->id, '', $user->hasRight('prelevement', 'bons', 'credit'));
				}
			}

			if (getDolGlobalString('BANK_CAN_REOPEN_DIRECT_DEBIT_OR_CREDIT_TRANSFER')) {
				if ($object->status == BonPrelevement::STATUS_DEBITED || $object->status == BonPrelevement::STATUS_CREDITED) {
					if ($object->type == 'bank-transfer') {
						print dolGetButtonAction($langs->trans("ReOpen"), '', 'default', 'card.php?action=reopen&token='.newToken().'&id='.$object->id, '', $user->hasRight('paymentbybanktransfer', 'debit'));
					} else {
						print dolGetButtonAction($langs->trans("ReOpen"), '', 'default', 'card.php?action=reopen&token='.newToken().'&id='.$object->id, '', $user->hasRight('prelevement', 'bons', 'credit'));
					}
				}
			}

			if ($object->type == 'bank-transfer') {
				print dolGetButtonAction($langs->trans("Delete"), '', 'delete', 'card.php?action=delete&token='.newToken().'&id='.$object->id, '', $user->hasRight('paymentbybanktransfer', 'create'));
			} else {
				print dolGetButtonAction($langs->trans("Delete"), '', 'delete', 'card.php?action=delete&token='.newToken().'&id='.$object->id, '', $user->hasRight('prelevement', 'bons', 'creer'));
			}
		}
		print '</div>';
	}


	$ligne = new LignePrelevement($db);

	// Lines into withdraw request
	if ($salaryBonPl) {
		$sql = "SELECT pl.rowid, pl.statut, pl.amount, pl.fk_user,";
		$sql .= " u.rowid as socid, u.login as name";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
		$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
		$sql .= ", ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $id);
		$sql .= " AND pl.fk_prelevement_bons = pb.rowid";
		$sql .= " AND pb.entity = ".((int) $conf->entity);	// No sharing of entity here
		$sql .= " AND pl.fk_user = u.rowid";
		if ($socid) {
			$sql .= " AND u.rowid = ".((int) $socid);
		}
		$sql .= $db->order($sortfield, $sortorder);
	} else {
		$sql = "SELECT pl.rowid, pl.statut, pl.amount,";
		$sql .= " s.rowid as socid, s.nom as name";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
		$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
		$sql .= ", ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $id);
		$sql .= " AND pl.fk_prelevement_bons = pb.rowid";
		$sql .= " AND pb.entity = ".((int) $conf->entity);	// No sharing of entity here
		$sql .= " AND pl.fk_soc = s.rowid";
		if ($socid) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		$sql .= $db->order($sortfield, $sortorder);
	}
	// Count total nb of records
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

	$result = $db->query($sql);

	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;

		$urladd = "&id=".urlencode($id);
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$urladd .= '&limit='.((int) $limit);
		}

		print '<form method="POST" action="'.$_SERVER ['PHP_SELF'].'" name="search_form">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'"/>';
		print '<input type="hidden" name="id" value="'.$id.'"/>';
		print '<input type="hidden" name="socid" value="'.$socid.'"/>';
		if (!empty($page)) {
			print '<input type="hidden" name="page" value="'.$page.'"/>';
		}
		if (!empty($limit)) {
			print '<input type="hidden" name="limit" value="'.$limit.'"/>';
		}
		if (!empty($sortfield)) {
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
		}
		if (!empty($sortorder)) {
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
		}
		print_barre_liste($langs->trans("Lines"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '', 0, '', '', $limit);

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print '<table class="noborder liste centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("Lines", $_SERVER["PHP_SELF"], "pl.rowid", '', $urladd, '', $sortfield, $sortorder);
		print_liste_field_titre((!$salaryBonPl ? "ThirdParty" : "Employee"), $_SERVER["PHP_SELF"], "s.nom", '', $urladd, '', $sortfield, $sortorder);
		print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "pl.amount", "", $urladd, 'class="right"', $sortfield, $sortorder);
		print_liste_field_titre('');
		print "</tr>\n";

		$total = 0;

		while ($i < min($num, $limit)) {
			$obj = $db->fetch_object($result);

			print '<tr class="oddeven">';

			// Status of line
			print "<td>";
			print '<a class="valignmiddle" href="'.DOL_URL_ROOT.'/compta/prelevement/line.php?id='.$obj->rowid.'&type='.$object->type.'&token='.newToken().'">';
			print $ligne->LibStatut($obj->statut, 2);
			print '<span class="paddingleft">'.$obj->rowid.'</span>';
			print '</a></td>';
			if (!$salaryBonPl) {
				$thirdparty = new Societe($db);
				$thirdparty->fetch($obj->socid);
			} else {
				$userSalary = new User($db);
				$userSalary->fetch($obj->fk_user);
			}
			print '<td>';
			print(!$salaryBonPl ? $thirdparty->getNomUrl(1) : $userSalary->getNomUrl(-1));
			print "</td>\n";

			print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";

			print '<td class="right">';

			if ($obj->statut == 3) {
				print '<span class="error">'.$langs->trans("StatusRefused").'</span>';
			} else {
				if ($object->statut == BonPrelevement::STATUS_CREDITED) {
					if ($obj->statut == LignePrelevement::STATUS_CREDITED) {
						if ($user->hasRight('prelevement', 'bons', 'credit')) {
							//print '<a class="butActionDelete" href="line.php?action=rejet&id='.$obj->rowid.'">'.$langs->trans("StandingOrderReject").'</a>';
							print '<a href="line.php?action=rejet&type='.$object->type.'&id='.$obj->rowid.'&token='.newToken().'">'.$langs->trans("StandingOrderReject").'</a>';
						} else {
							//print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("StandingOrderReject").'</a>';
						}
					}
				} else {
					//print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotPossibleForThisStatusOfWithdrawReceiptORLine").'">'.$langs->trans("StandingOrderReject").'</a>';
				}
			}

			print '</td></tr>';

			$total += $obj->amount;

			$i++;
		}

		if ($num > 0) {
			$total = price2num($total, 'MT');

			print '<tr class="liste_total">';
			print '<td>'.$langs->trans("Total").'</td>';
			print '<td>&nbsp;</td>';
			print '<td class="right">';
			if (empty($offset) && $num <= $limit) {
				// If we have all record on same page, then the following test/warning can be done
				if ($total != $object->amount) {
					print img_warning($langs->trans("TotalAmountOfdirectDebitOrderDiffersFromSumOfLines"));
				}
			}
			print price($total);
			print "</td>\n";
			print '<td>&nbsp;</td>';
			print "</tr>\n";
		}

		print "</table>";
		print '</div>';
		print '</form>';

		$db->free($result);
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
