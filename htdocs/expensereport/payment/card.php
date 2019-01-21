<?php
/* Copyright (C) 2015-2017  Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
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
 *	    \file       htdocs/expensereport/payment/card.php
 *		\ingroup    Expense Report
 *		\brief      Tab payment of an expense report
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
if (! empty($conf->banque->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'companies', 'trips'));

$id=GETPOST('rowid')?GETPOST('rowid','int'):GETPOST('id','int');
$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
// TODO Add rule to restrict access payment
//$result = restrictedArea($user, 'facture', $id,'');

$object = new PaymentExpenseReport($db);

if ($id > 0)
{
	$result=$object->fetch($id);
	if (! $result) dol_print_error($db,'Failed to get payment id '.$id);
}


/*
 * Actions
 */

// Delete payment
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expensereport->supprimer)
{
	$db->begin();

	$result = $object->delete($user);
	if ($result > 0)
	{
        $db->commit();
        header("Location: ".DOL_URL_ROOT."/expensereport/index.php");
        exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
	}
}

// Create payment
if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->expensereport->creer)
{
	$db->begin();

	$result=$object->valide();

	if ($result > 0)
	{
		$db->commit();

		$factures=array();	// TODO Get all id of invoices linked to this payment
		foreach($factures as $invoiceid)
		{
			$fac = new Facture($db);
			$fac->fetch($invoiceid);

			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$fac->generateDocument($fac->modelpdf, $outputlangs);
			}
		}

		header('Location: card.php?id='.$object->id);
		exit;
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("ExpenseReportPayment"));

$form = new Form($db);

$head = payment_expensereport_prepare_head($object);

dol_fiche_head($head, 'payment', $langs->trans("ExpenseReportPayment"), -1, 'payment');

/*
 * Confirm deleting of the payment
 */
if ($action == 'delete')
{
	print $form->formconfirm('card.php?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);

}

/*
 * Confirm validation of the payment
 */
if ($action == 'valide')
{
	$facid = $_GET['facid'];
	print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);

}

$linkback = '';
// $linkback = '<a href="' . DOL_URL_ROOT . '/expensereport/payment/list.php">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">'."\n";

// Date payment
print '<tr><td class="titlefield">'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($object->datep,'day').'</td></tr>';

// Mode
print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$langs->trans("PaymentType".$object->type_code).'</td></tr>';

// Number
print '<tr><td>'.$langs->trans('Numero').'</td><td colspan="3">'.$object->num_payment.'</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

// Note
print '<tr><td class="tdtop">'.$langs->trans('Note').'</td><td colspan="3">'.nl2br($object->note).'</td></tr>';

$disable_delete = 0;
// Bank account
if (! empty($conf->banque->enabled))
{
    if ($object->bank_account)
    {
    	$bankline=new AccountLine($db);
    	$bankline->fetch($object->bank_line);
        if ($bankline->rappro)
        {
            $disable_delete = 1;
            $title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
        }

    	print '<tr>';
    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td colspan="3">';
		print $bankline->getNomUrl(1,0,'showconciliated');
    	print '</td>';
    	print '</tr>';

    	print '<tr>';
    	print '<td>'.$langs->trans('BankAccount').'</td>';
		print '<td colspan="3">';
		$accountstatic=new Account($db);
		$accountstatic->fetch($bankline->fk_account);
        print $accountstatic->getNomUrl(1);
    	print '</td>';
    	print '</tr>';
    }
}

print '</table>';

print '</div>';

dol_fiche_end();


/*
 * List of expense report paid
 */

$sql = 'SELECT er.rowid as eid, er.paid, er.total_ttc, per.amount';
$sql.= ' FROM '.MAIN_DB_PREFIX.'payment_expensereport as per,'.MAIN_DB_PREFIX.'expensereport as er';
$sql.= ' WHERE per.fk_expensereport = er.rowid';
$sql.= ' AND er.entity IN ('.getEntity('expensereport').')';
$sql.= ' AND per.rowid = '.$id;

dol_syslog("expensereport/payment/card.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br>';

	print '<div class="div-table-responsive">';
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('ExpenseReport').'</td>';
	print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
	print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
	print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
	print '<td align="center">'.$langs->trans('Status').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven">';

			$expensereport=new ExpenseReport($db);
			$expensereport->fetch($objp->eid);

			// Expense report
			print '<td>';
			print $expensereport->getNomUrl(1);
			print "</td>\n";

			// Expected to pay
			print '<td align="right">'.price($objp->total_ttc).'</td>';

			// Amount paid
			print '<td align="right">'.price($objp->amount).'</td>';

			// Remain to pay
            print '<td align="right">'.price($remaintopay).'</td>';

			// Status
			print '<td align="center">'.$expensereport->getLibStatut(4,$objp->amount).'</td>';

			print "</tr>\n";

			if ($objp->paid == 1)	// If at least one invoice is paid, disable delete
			{
				$disable_delete = 2;
				$title_button = $langs->trans("CantRemovePaymentWithOneInvoicePaid");
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}


	print "</table>\n";
	print '</div>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';


/*
 * Actions buttons
 */
print '<div class="tabsAction">';

if ($action == '')
{
	if ($user->rights->expensereport->supprimer)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($title_button).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

llxFooter();

$db->close();
