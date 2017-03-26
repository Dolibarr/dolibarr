<?php
/* Copyright (C) 2017		Alexandre Spangaro	<aspangaro@zendsi.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/compta/bank/various_expenses/card.php
 *      \ingroup    bank
 *		\brief      Page of various expenses
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

$langs->load("compta");
$langs->load("banks");
$langs->load("bills");
$langs->load("users");
$langs->load("accountancy");

$id=GETPOST("id",'int');
$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$accountid=GETPOST("accountid") > 0 ? GETPOST("accountid","int") : 0;
$label=GETPOST("label","alpha");
$sens=GETPOST("sens","int");
$amount=GETPOST("amount");
$paymenttype=GETPOST("paymenttype");
$accountancy_code=GETPOST("accountancy_code","int");

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '', '');

$object = new PaymentVarious($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('variouscard','globalcard'));



/**
 * Actions
 */

if (! empty($cancel))
{
	header("Location: index.php");
	exit;
}

if ($action == 'add' && empty($cancel))
{
	$error=0;

	$datep=dol_mktime(12,0,0, GETPOST("datepmonth"), GETPOST("datepday"), GETPOST("datepyear"));
	$datev=dol_mktime(12,0,0, GETPOST("datevmonth"), GETPOST("datevday"), GETPOST("datevyear"));
	if (empty($datev)) $datev=$datep;
	
	$object->accountid=GETPOST("accountid") > 0 ? GETPOST("accountid","int") : 0;
	$object->datev=$datev;
	$object->datep=$datep;
	$object->amount=price2num(GETPOST("amount"));
	$object->label=GETPOST("label");
	$object->note=GETPOST("note");
	$object->type_payment=GETPOST("paymenttype") > 0 ? GETPOST("paymenttype", "int") : 0;
	$object->num_payment=GETPOST("num_payment");
	$object->fk_user_author=$user->id;
	$object->accountancy_code=GETPOST("accountancy_code") > 0 ? GETPOST("accountancy_code","int") : "";

	if (empty($datep) || empty($datev))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	if (empty($object->type_payment) || $object->type_payment < 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
		$error++;
	}
	if (empty($object->amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$error++;
	}
	if (! empty($conf->banque->enabled) && ! $object->accountid > 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$db->begin();

		$ret=$object->create($user);
		if ($ret > 0)
		{
			$db->commit();
			header("Location: index.php");
			exit;
		}
		else
		{
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action="create";
		}
	}

	$action='create';
}

if ($action == 'delete')
{
	$result=$object->fetch($id);

	if ($object->rappro == 0)
	{
		$db->begin();

		$ret=$object->delete($user);
		if ($ret > 0)
		{
			if ($object->fk_bank)
			{
				$accountline=new AccountLine($db);
				$result=$accountline->fetch($object->fk_bank);
				if ($result > 0) $result=$accountline->delete($user);	// $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
			}

			if ($result >= 0)
			{
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/salaries/index.php');
				exit;
			}
			else
			{
				$object->error=$accountline->error;
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		else
		{
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else
	{
		setEventMessages('Error try do delete a line linked to a conciliated bank transaction', null, 'errors');
	}
}


/*
 *	View
 */

llxHeader("",$langs->trans("VariousPayment"));

$form = new Form($db);
if (! empty($conf->accounting->enabled)) $formaccountancy = New FormVentilation($db);

if ($id)
{
	$object = new PaymentVarious($db);
	$result = $object->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}

/* ************************************************************************** */
/*                                                                            */
/* Create mode                                                                */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	print '<form name="salary" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print load_fiche_titre($langs->trans("NewVariousPayment"),'', 'title_accountancy.png');

	dol_fiche_head('', '');
	
	print '<table class="border" width="100%">';

	// Date payment
	print '<tr><td>';
	print fieldLabel('DatePayment','datep',1).'</td><td>';
	print $form->select_date((empty($datep)?-1:$datep),"datep",'','','','add',1,1);
	print '</td></tr>';

	// Date value for bank
	print '<tr><td>';
	print fieldLabel('DateValue','datev',0).'</td><td>';
	print $form->select_date((empty($datev)?-1:$datev),"datev",'','','','add',1,1);
	print '</td></tr>';

	// Label
	print '<tr><td>';
	print fieldLabel('Label','label',1).'</td><td>';
	print '<input name="label" id="label" class="minwidth300" value="'.($label?$label:$langs->trans("VariousPayment")).'">';
	print '</td></tr>';

	// Sens
	print '<tr><td>';
	print fieldLabel('Sens','sens',1).'</td><td>';
    $sensarray=array( '0' => $langs->trans("Debit"), '1' => $langs->trans("Credit"));
    print $form->selectarray('sens',$sensarray,$sens);
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print fieldLabel('Amount','amount',1).'</td><td>';
	print '<input name="amount" id="amount" class="minwidth100" value="'.$amount.'">';
	print '</td></tr>';

	// Bank
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td>';
		print fieldLabel('BankAccount','selectaccountid',1).'</td><td>';
		$form->select_comptes($accountid,"accountid",0,'',1);  // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td>';
	print fieldLabel('PaymentMode','selectpaymenttype',1).'</td><td>';
	$form->select_types_paiements($paymenttype, "paymenttype");
	print '</td></tr>';

	// Number
	if (! empty($conf->banque->enabled))
	{
		// Number
		print '<tr><td><label for="num_payment">'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</label></td>';
		print '<td><input name="num_payment" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
	}

	// Accountancy account
	if (! empty($conf->accounting->enabled))
	{
		print '<tr><td>'.$langs->trans("AccountAccounting").'</td>';
        print '<td>';
		print $formaccountancy->select_account($accountancy_code, 'accountancy_code', 1, null, 1, 1, '');
        print '</td></tr>';
	}			
	else // For external software 
	{
		print '<tr><td>'.$langs->trans("AccountAccounting").'</td>';
		print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="accountancy_code" value="'.$accountancy_code.'">';
		print '</td></tr>';
	}

	// Other attributes
	$parameters=array('colspan' => ' colspan="1"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{

	$head=various_payment_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("VariousPayment"), 0, 'payment');

	print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/index.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
	
    print "<tr>";
	print '<td class="titlefield">'.$langs->trans("Ref").'</td><td>';
	print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($object->datev,'day');
	print '</td></tr>';

	// Debit / Credit
	if ($object->sens == '1') $sens = $langs->trans("Credit"); else $sens = $langs->trans("Debit");
	print '<tr><td>'.$langs->trans("Sens").'</td><td>'.$sens.'</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	// Accountancy code
	print '<tr><td class="nowrap">';
	print $langs->trans("AccountAccounting");
	print '</td><td>';
	if (! empty($conf->accounting->enabled))
	{
		$accountancyaccount = new AccountingAccount($db);
		$accountancyaccount->fetch('',$object->accountancy_code);

		print $accountancyaccount->getNomUrl(1);
		// print length_accountg($object->accountancy_code);
	} else {
		print $object->accountancy_code;
	}
	print '</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($object->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($object->fk_bank);

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showall');
			print '</td>';
			print '</tr>';
		}
	}

	// Other attributes
	$parameters=array('colspan' => ' colspan="1"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	print '</table>';

	dol_fiche_end();

	
	/*
	 * Action buttons
	 */
	print '<div class="tabsAction">'."\n";
	if ($object->rappro == 0)
	{
		if (! empty($user->rights->banque->delete))
		{
			print '<a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("LinkedToAConciliatedTransaction").'">'.$langs->trans("Delete").'</a>';
	}
	print "</div>";
}



llxFooter();

$db->close();
