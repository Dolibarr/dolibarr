<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2017 Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/compta/bank/line.php
 *	\ingroup    bank
 *	\brief      Page to edit a bank transaction record
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'compta', 'bills', 'other'));
if (!empty($conf->adherent->enabled)) {
	$langs->load("members");
}
if (!empty($conf->don->enabled)) {
	$langs->load("donations");
}
if (!empty($conf->loan->enabled)) {
	$langs->load("loan");
}
if (!empty($conf->salaries->enabled)) {
	$langs->load("salaries");
}


$id = GETPOST('rowid', 'int');
$rowid = GETPOST("rowid", 'int');
$accountoldid = GETPOST('account', 'int');		// GETPOST('account') is old account id
$accountid = GETPOST('accountid', 'int');		// GETPOST('accountid') is new account id
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$orig_account = GETPOST("orig_account");
$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
$socid = 0;
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'banque', $accountoldid, 'bank_account');
if (empty($user->rights->banque->lire) && empty($user->rights->banque->consolidate)) {
	accessforbidden();
}

$hookmanager->initHooks(array('bankline'));


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if ($cancel) {
	if ($backtopage) {
		header("Location: ".$backtopage);
		exit;
	}
}


if ($user->rights->banque->consolidate && $action == 'donext') {
	$al = new AccountLine($db);
	$al->dateo_next(GETPOST("rowid", 'int'));
} elseif ($user->rights->banque->consolidate && $action == 'doprev') {
	$al = new AccountLine($db);
	$al->dateo_previous(GETPOST("rowid", 'int'));
} elseif ($user->rights->banque->consolidate && $action == 'dvnext') {
	$al = new AccountLine($db);
	$al->datev_next(GETPOST("rowid", 'int'));
} elseif ($user->rights->banque->consolidate && $action == 'dvprev') {
	$al = new AccountLine($db);
	$al->datev_previous(GETPOST("rowid", 'int'));
}

if ($action == 'confirm_delete_categ' && $confirm == "yes" && $user->rights->banque->modifier) {
	$cat1 = GETPOST("cat1", 'int');
	if (!empty($rowid) && !empty($cat1)) {
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = ".((int) $rowid)." AND fk_categ = ".((int) $cat1);
		if (!$db->query($sql)) {
			dol_print_error($db);
		}
	} else {
		setEventMessages($langs->trans("MissingIds"), null, 'errors');
	}
}

if ($user->rights->banque->modifier && $action == "update") {
	$error = 0;

	$acline = new AccountLine($db);
	$result = $acline->fetch($rowid);
	if ($result <= 0) {
		dol_syslog('Failed to read bank line with id '.$rowid, LOG_WARNING);	// This happens due to old bug that has set fk_account to null.
		$acline->id = $rowid;
	}

	$acsource = new Account($db);
	$acsource->fetch($accountoldid);

	$actarget = new Account($db);
	if (GETPOST('accountid', 'int') > 0 && !$acline->rappro && !$acline->getVentilExportCompta()) {	// We ask to change bank account
		$actarget->fetch(GETPOST('accountid', 'int'));
	} else {
		$actarget->fetch($accountoldid);
	}

	if (!($actarget->id > 0)) {
		setEventMessages($langs->trans("ErrorFailedToLoadBankAccount"), null, 'errors');
		$error++;
	}
	if ($actarget->courant == Account::TYPE_CASH && GETPOST('value', 'alpha') != 'LIQ') {
		setEventMessages($langs->trans("ErrorCashAccountAcceptsOnlyCashMoney"), null, 'errors');
		$error++;
	}

	if (!$error) {
		$db->begin();

		$amount = price2num(GETPOST('amount'));
		$dateop = dol_mktime(12, 0, 0, GETPOST("dateomonth"), GETPOST("dateoday"), GETPOST("dateoyear"));
		$dateval = dol_mktime(12, 0, 0, GETPOST("datevmonth"), GETPOST("datevday"), GETPOST("datevyear"));
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
		$sql .= " SET ";
		// Always opened
		if (GETPOSTISSET('value')) {
			$sql .= " fk_type='".$db->escape(GETPOST('value'))."',";
		}
		if (GETPOSTISSET('num_chq')) {
			$sql .= " num_chq='".$db->escape(GETPOST("num_chq"))."',";
		}
		if (GETPOSTISSET('banque')) {
			$sql .= " banque='".$db->escape(GETPOST("banque"))."',";
		}
		if (GETPOSTISSET('emetteur')) {
			$sql .= " emetteur='".$db->escape(GETPOST("emetteur"))."',";
		}
		// Blocked when conciliated
		if (!$acline->rappro) {
			if (GETPOSTISSET('label')) {
				$sql .= " label = '".$db->escape(GETPOST("label"))."',";
			}
			if (GETPOSTISSET('amount')) {
				$sql .= " amount= '".$db->escape($amount)."',";
			}
			if (GETPOSTISSET('dateomonth')) {
				$sql .= " dateo = '".$db->idate($dateop)."',";
			}
			if (GETPOSTISSET('datevmonth')) {
				$sql .= " datev = '".$db->idate($dateval)."',";
			}
		}
		$sql .= " fk_account = ".((int) $actarget->id);
		$sql .= " WHERE rowid = ".((int) $acline->id);

		$result = $db->query($sql);
		if (!$result) {
			$error++;
		}

		if (!$error) {
			$arrayofcategs = GETPOST('custcats', 'array');
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = ".((int) $rowid);
			if (!$db->query($sql)) {
				$error++;
				dol_print_error($db);
			}
			if (count($arrayofcategs)) {
				foreach ($arrayofcategs as $val) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES (".((int) $rowid).", ".((int) $val).")";
					if (!$db->query($sql)) {
						$error++;
						dol_print_error($db);
					}
				}
				// $arrayselected will be loaded after in page output
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$db->commit();
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}

// Reconcile
if ($user->rights->banque->consolidate && ($action == 'num_releve' || $action == 'setreconcile')) {
	$num_rel = trim(GETPOST("num_rel"));
	$rappro = GETPOST('reconciled') ? 1 : 0;

	// Check parameters
	if ($rappro && empty($num_rel)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountStatement")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
		$sql .= " SET num_releve = ".($num_rel ? "'".$db->escape($num_rel)."'" : "null");
		if (empty($num_rel)) {
			$sql .= ", rappro = 0";
		} else {
			$sql .= ", rappro = ".((int) $rappro);
		}
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog("line.php", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$db->commit();
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("BankTransaction"));

$arrayselected = array();

$c = new Categorie($db);
$cats = $c->containing($rowid, Categorie::TYPE_BANK_LINE);
if (is_array($cats)) {
	foreach ($cats as $cat) {
		$arrayselected[] = $cat->id;
	}
}

$head = bankline_prepare_head($rowid);


$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro,";
$sql .= " b.num_releve, b.fk_user_author, b.num_chq, b.fk_type, b.fk_account, b.fk_bordereau as receiptid,";
$sql .= " b.emetteur,b.banque";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= " WHERE rowid=".((int) $rowid);
$sql .= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result) {
	$i = 0;
	$total = 0;
	if ($db->num_rows($result)) {
		$objp = $db->fetch_object($result);

		$total = $total + $objp->amount;

		$acct = new Account($db);
		$acct->fetch($objp->fk_account);
		$account = $acct->id;

		$bankline = new AccountLine($db);
		$bankline->fetch($rowid, $ref);

		$links = $acct->get_url($rowid);
		$bankline->load_previous_next_ref('', 'rowid');

		// Confirmations
		if ($action == 'delete_categ') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".urlencode($rowid)."&cat1=".urlencode(GETPOST("fk_categ", 'int'))."&orig_account=".urlencode($orig_account), $langs->trans("RemoveFromRubrique"), $langs->trans("RemoveFromRubriqueConfirm"), "confirm_delete_categ", '', 'yes', 1);
		}

		print '<form name="update" method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="orig_account" value="'.$orig_account.'">';
		print '<input type="hidden" name="account" value="'.$acct->id.'">';

		print dol_get_fiche_head($head, 'bankline', $langs->trans('LineRecord'), 0, 'accountline', 0);

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';


		dol_banner_tab($bankline, 'rowid', $linkback);

		print '<div class="fichecenter2">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		$i++;

		// Bank account
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Account").'</td>';
		print '<td>';
		// $objp->fk_account may be not > 0 if data was lost by an old bug. In such a case, we let a chance to user to fix it.
		if (($objp->rappro || $bankline->getVentilExportCompta()) && $objp->fk_account > 0) {
			print $acct->getNomUrl(1, 'transactions', 'reflabel');
		} else {
			print img_picto('', 'bank_account', 'class="paddingright"');
			print $form->select_comptes($acct->id, 'accountid', 0, '', ($acct->id > 0 ? $acct->id : 1), '', 0, '', 1);
		}
		print '</td>';
		print '</tr>';

		// Show links of bank transactions
		if (count($links)) {
			print '<tr><td class="tdtop">'.$langs->trans("Links").'</td>';
			print '<td>';
			foreach ($links as $key => $val) {
				if ($key) {
					print '<br>';
				}
				if ($links[$key]['type'] == 'payment') {
					require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
					$paymenttmp = new Paiement($db);
					$paymenttmp->fetch($links[$key]['url_id']);
					$paymenttmp->ref = $langs->trans("Payment").' '.$paymenttmp->ref;
					/*print '<a href="'.DOL_URL_ROOT.'/compta/paiement/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Payment'),'payment').' ';
					print $langs->trans("Payment");
					print '</a>';*/
					print $paymenttmp->getNomUrl(1);
				} elseif ($links[$key]['type'] == 'payment_supplier') {
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
					$paymenttmp = new PaiementFourn($db);
					$paymenttmp->fetch($links[$key]['url_id']);
					$paymenttmp->ref = $langs->trans("Payment").' '.$paymenttmp->ref;
					/*print '<a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Payment'),'payment').' ';
					print $langs->trans("Payment");
					print '</a>';*/
					print $paymenttmp->getNomUrl(1);
				} elseif ($links[$key]['type'] == 'company') {
					$societe = new Societe($db);
					$societe->fetch($links[$key]['url_id']);
					print $societe->getNomUrl(1);
				} elseif ($links[$key]['type'] == 'sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('SocialContribution'), 'bill').' ';
					print $langs->trans("SocialContribution").($links[$key]['label'] ? ' - '.$links[$key]['label'] : '');
					print '</a>';
				} elseif ($links[$key]['type'] == 'vat') {
					print '<a href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('VATDeclaration'), 'bill').' ';
					print $langs->trans("VATDeclaration").($links[$key]['label'] ? '&nbsp;'.$links[$key]['label'] : '');
					print '</a>';
				} elseif ($links[$key]['type'] == 'salary') {
					print '<a href="'.DOL_URL_ROOT.'/salaries/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Salary'), 'bill').' ';
					print $langs->trans("Salary").($links[$key]['label'] ? ' - '.$links[$key]['label'] : '');
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Payment'), 'payment').' ';
					print $langs->trans("SocialContributionPayment");
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_vat') {
					print '<a href="'.DOL_URL_ROOT.'/compta/payment_vat/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('VATPayment'), 'payment').' ';
					print $langs->trans("VATPayment");
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_salary') {
					print '<a href="'.DOL_URL_ROOT.'/salaries/payment_salary/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('PaymentSalary'), 'payment').' ';
					print $langs->trans("SalaryPayment");
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_loan') {
					print '<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('LoanPayment'), 'payment').' ';
					print $langs->trans("PaymentLoan");
					print '</a>';
				} elseif ($links[$key]['type'] == 'loan') {
					print '<a href="'.DOL_URL_ROOT.'/loan/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Loan'), 'bill').' ';
					print $langs->trans("Loan");
					print '</a>';
				} elseif ($links[$key]['type'] == 'member') {
					print '<a href="'.DOL_URL_ROOT.'/adherents/card.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Member'), 'user').' ';
					print $links[$key]['label'];
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_donation') {
					print '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Donation'), 'payment').' ';
					print $langs->trans("DonationPayment");
					print '</a>';
				} elseif ($links[$key]['type'] == 'banktransfert') {
					print '<a href="'.DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('Transaction'), 'payment').' ';
					print $langs->trans("TransactionOnTheOtherAccount");
					print '</a>';
				} elseif ($links[$key]['type'] == 'user') {
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('User'), 'user').' ';
					print $langs->trans("User");
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_various') {
					print '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('VariousPayment'), 'payment').' ';
					print $langs->trans("VariousPayment");
					print '</a>';
				} else {
					print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
					print img_object('', 'generic').' ';
					print $links[$key]['label'];
					print '</a>';
				}
			}
			print '</td></tr>';
		}

		//$user->rights->banque->modifier=false;
		//$user->rights->banque->consolidate=true;

		// Type of payment / Number
		print "<tr><td>".$langs->trans("Type")." / ".$langs->trans("Numero");
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print "</td>";
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			$form->select_types_paiements($objp->fk_type, "value", '', 2);
			print '<input type="text" class="flat" name="num_chq" value="'.(empty($objp->num_chq) ? '' : $objp->num_chq).'">';
			if ($objp->receiptid) {
				include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
				$receipt = new RemiseCheque($db);
				$receipt->fetch($objp->receiptid);
				print ' &nbsp; &nbsp; '.$langs->trans("CheckReceipt").': '.$receipt->getNomUrl(2);
			}
			print '</td>';
		} else {
			print '<td>'.$objp->fk_type.' '.dol_escape_htmltag($objp->num_chq).'</td>';
		}
		print "</tr>";

		// Transmitter
		print "<tr><td>".$langs->trans("CheckTransmitter");
		print ' <em>('.$langs->trans("ChequeMaker").')</em>';
		print "</td>";
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			print '<input type="text" class="flat minwidth200" name="emetteur" value="'.(empty($objp->emetteur) ? '' : dol_escape_htmltag($objp->emetteur)).'">';
			print '</td>';
		} else {
			print '<td>'.$objp->emetteur.'</td>';
		}
		print "</tr>";

		// Bank of cheque
		print "<tr><td>".$langs->trans("Bank");
		print ' <em>('.$langs->trans("ChequeBank").')</em>';
		print "</td>";
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			print '<input type="text" class="flat minwidth200" name="banque" value="'.(empty($objp->banque) ? '' : dol_escape_htmltag($objp->banque)).'">';
			print '</td>';
		} else {
			print '<td>'.dol_escape_htmltag($objp->banque).'</td>';
		}
		print "</tr>";

		// Date ope
		print '<tr><td>'.$langs->trans("DateOperation").'</td>';
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			print $form->selectDate($db->jdate($objp->do), 'dateo', '', '', '', 'update', 1, 0, $objp->rappro);
			if (!$objp->rappro) {
				print ' &nbsp; ';
				print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=doprev&id='.$objp->fk_account.'&rowid='.$objp->rowid.'&token='.newToken().'">';
				print img_edit_remove()."</a> ";
				print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=donext&id='.$objp->fk_account.'&rowid='.$objp->rowid.'&token='.newToken().'">';
				print img_edit_add()."</a>";
			}
			print '</td>';
		} else {
			print '<td>';
			print dol_print_date($db->jdate($objp->do), "day");
			print '</td>';
		}
		print '</tr>';

		// Value date
		print "<tr><td>".$langs->trans("DateValue")."</td>";
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			print $form->selectDate($db->jdate($objp->dv), 'datev', '', '', '', 'update', 1, 0, $objp->rappro);
			if (!$objp->rappro) {
				print ' &nbsp; ';
				print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=dvprev&id='.$objp->fk_account.'&rowid='.$objp->rowid.'&token='.newToken().'">';
				print img_edit_remove()."</a> ";
				print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=dvnext&id='.$objp->fk_account.'&rowid='.$objp->rowid.'&token='.newToken().'">';
				print img_edit_add()."</a>";
			}
			print '</td>';
		} else {
			print '<td>';
			print dol_print_date($db->jdate($objp->dv), "day");
			print '</td>';
		}
		print "</tr>";

		// Description
		$reg = array();
		print "<tr><td>".$langs->trans("Label")."</td>";
		if ($user->rights->banque->modifier || $user->rights->banque->consolidate) {
			print '<td>';
			print '<input name="label" class="flat minwidth300" '.($objp->rappro ? ' disabled' : '').' value="';
			if (preg_match('/^\((.*)\)$/i', $objp->label, $reg)) {
				// Label generique car entre parentheses. On l'affiche en le traduisant
				print $langs->trans($reg[1]);
			} else {
				print dol_escape_htmltag($objp->label);
			}
			print '">';
			print '</td>';
		} else {
			print '<td>';
			if (preg_match('/^\((.*)\)$/i', $objp->label, $reg)) {
				// Label generique car entre parentheses. On l'affiche en le traduisant
				print $langs->trans($reg[1]);
			} else {
				print dol_escape_htmltag($objp->label);
			}
			print '</td>';
		}
		print '</tr>';

		// Amount
		print "<tr><td>".$langs->trans("Amount")."</td>";
		if ($user->rights->banque->modifier) {
			print '<td>';
			print '<input name="amount" class="flat maxwidth100" '.($objp->rappro ? ' disabled' : '').' value="'.price($objp->amount).'"> '.$langs->trans("Currency".$acct->currency_code);
			print '</td>';
		} else {
			print '<td>';
			print price($objp->amount);
			print '</td>';
		}
		print "</tr>";

		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire)) {
			$langs->load('categories');

			// Bank line
			print '<tr><td class="toptd">'.$form->editfieldkey('RubriquesTransactions', 'custcats', '', $object, 0).'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_BANK_LINE, null, 'parent', null, null, 1);

			$arrayselected = array();

			$c = new Categorie($db);
			$cats = $c->containing($bankline->id, Categorie::TYPE_BANK_LINE);
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}
			print img_picto('', 'category', 'class="paddingright"').$form->multiselectarray('custcats', $cate_arbo, $arrayselected, null, null, null, null, "90%");
			print "</td></tr>";
		}

		print "</table>";

		// Code to adjust value date with plus and less picto using an Ajax call instead of a full reload of page
		/* Not yet ready. We must manage inline replacemet of input date field
		$urlajax = DOL_URL_ROOT.'/core/ajax/bankconciliate.php?token='.currentToken();
		print '
			<script type="text/javascript">
			$(function() {
				$("a.ajaxforbankoperationchange").each(function(){
					var current = $(this);
					current.click(function()
					{
						var url = "'.$urlajax.'&"+current.attr("href").split("?")[1];
						$.get(url, function(data)
						{
							console.log(url)
							console.log(data)
							current.parent().prev().replaceWith(data);
						});
						return false;
					});
				});
			});
			</script>
			';
		*/
		print '</div>';

		print dol_get_fiche_end();


		print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Update").'"></div><br>';

		print "</form>";



		// Releve rappro
		if ($acct->canBeConciliated() > 0) {  // Si compte rapprochable
			print load_fiche_titre($langs->trans("Reconciliation"), '', 'bank_account');
			print '<hr>'."\n";

			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$objp->rowid.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setreconcile">';
			print '<input type="hidden" name="orig_account" value="'.$orig_account.'">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

			print '<div class="fichecenter">';

			print '<table class="border centpercent">';

			print '<tr><td class="titlefieldcreate">'.$form->textwithpicto($langs->trans("AccountStatement"), $langs->trans("InputReceiptNumber"))."</td>";
			if ($user->rights->banque->consolidate) {
				print '<td>';
				if ($objp->rappro) {
					print '<input name="num_rel_bis" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro ? ' disabled' : '').'>';
					print '<input name="num_rel" type="hidden" value="'.$objp->num_releve.'">';
				} else {
					print '<input name="num_rel" class="flat" value="'.$objp->num_releve.'"'.($objp->rappro ? ' disabled' : '').'>';
				}
				if ($objp->num_releve) {
					print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/bank/releve.php?num='.$objp->num_releve.'&account='.$acct->id.'">'.$langs->trans("AccountStatement").' '.$objp->num_releve.')</a>';
				}
				print '</td>';
			} else {
				print '<td>'.$objp->num_releve.'</td>';
			}
			print '</tr>';

			print '<tr><td><label for="reconciled">'.$langs->trans("BankLineConciliated").'</label></td>';
			if ($user->rights->banque->consolidate) {
				print '<td>';
				print '<input type="checkbox" id="reconciled" name="reconciled" class="flat" '.(GETPOSTISSET("reconciled") ? (GETPOST("reconciled") ? ' checked="checked"' : '') : ($objp->rappro ? ' checked="checked"' : '')).'">';
				print '</td>';
			} else {
				print '<td>'.yn($objp->rappro).'</td>';
			}
			print '</tr>';
			print '</table>';

			print '</div>';

			print '<div class="center">';

			print '<input type="submit" class="button" value="'.$langs->trans("Update").'">';
			if ($backtopage) {
				print ' &nbsp; ';
				print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			}
			print '</div>';

			print '</form>';
		}
	}

	$db->free($result);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
