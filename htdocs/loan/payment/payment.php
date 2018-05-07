<?php
/* Copyright (C) 2014-2016	Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *	    \file       htdocs/loan/payment/payment.php
 *		\ingroup    Loan
 *		\brief      Page to add payment of a loan
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("bills");
$langs->load("loan");

$chid=GETPOST('id','int');
$action=GETPOST('action','aZ09');
$cancel=GETPOST('cancel','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}

$loan = new Loan($db);
$loan->fetch($chid);

/*
 * Actions
 */
if ($action == 'add_payment')
{
	$error=0;

	if ($cancel)
	{
		$loc = DOL_URL_ROOT.'/loan/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$datepaid = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

	if (! GETPOST('paymenttype', 'int') > 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}
    if (! empty($conf->banque->enabled) && ! GETPOST('accountid', 'int') > 0)
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit")), null, 'errors');
        $error++;
    }

	if (! $error)
	{
		$paymentid = 0;

        $amount = GETPOST('amount_capital') + GETPOST('amount_insurance') + GETPOST('amount_interest');
        if ($amount == 0)
        {
            setEventMessages($langs->trans('ErrorNoPaymentDefined'), null, 'errors');
            $error++;
        }

        if (! $error)
        {
    		$db->begin();

    		// Create a line of payments
    		$payment = new PaymentLoan($db);
    		$payment->chid				= $chid;
    		$payment->datepaid			= $datepaid;
            $payment->label             = $loan->label;
			$payment->amount_capital	= GETPOST('amount_capital');
			$payment->amount_insurance	= GETPOST('amount_insurance');
			$payment->amount_interest	= GETPOST('amount_interest');
			$payment->paymenttype		= GETPOST('paymenttype');
    		$payment->num_payment		= GETPOST('num_payment');
    		$payment->note_private      = GETPOST('note_private','none');
    		$payment->note_public       = GETPOST('note_public','none');

    		if (! $error)
    		{
    		    $paymentid = $payment->create($user);
                if ($paymentid < 0)
                {
                    setEventMessages($payment->error, $payment->errors, 'errors');
                    $error++;
                }
    		}

            if (! $error)
            {
                $result = $payment->addPaymentToBank($user, $chid, 'payment_loan', '(LoanPayment)', GETPOST('accountid', 'int'), '', '');
                if (! $result > 0)
                {
                    setEventMessages($payment->error, $payment->errors, 'errors');
                    $error++;
                }
            }

    	    if (! $error)
            {
                $db->commit();
                $loc = DOL_URL_ROOT.'/loan/card.php?id='.$chid;
                header('Location: '.$loc);
                exit;
            }
            else
            {
                $db->rollback();
            }
        }
	}

	$action = 'create';
}


/*
 * View
 */

llxHeader();

$form=new Form($db);


// Form to create loan's payment
if ($action == 'create')
{
	$total = $loan->capital;

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';

    dol_fiche_head();

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Loan").'</td>';

	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/loan/card.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="2">'.dol_print_date($loan->datestart,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$loan->label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">'.price($loan->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	$sql = "SELECT SUM(amount_capital) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_loan";
	$sql.= " WHERE fk_loan = ".$chid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total-$sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
	print '</tr>';

	print '</table>';

	print '<br>';

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("Payment").'</td>';
	print '</tr>';

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
	$datepayment = empty($conf->global->MAIN_AUTOFILL_DATE)?(empty($_POST["remonth"])?-1:$datepaye):0;
	$form->select_date($datepayment, '', '', '', '', "add_payment", 1, 1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST["paymenttype"])?$_POST["paymenttype"]:$loan->paymenttype, "paymenttype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
	print '<td colspan="2">';
	$form->select_comptes(isset($_POST["accountid"])?$_POST["accountid"]:$loan->accountid, "accountid", 0, '',1);  // Show opend bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("NotePrivate").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("NotePublic").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

    dol_fiche_end();

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="left">'.$langs->trans("DateDue").'</td>';
	print '<td align="right">'.$langs->trans("LoanCapital").'</td>';
	print '<td align="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td align="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$var=True;


	print '<tr class="oddeven">';

	if ($loan->datestart > 0)
	{
		print '<td align="left" valign="center">'.dol_print_date($loan->datestart,'day').'</td>';
	}
	else
	{
		print '<td align="center" valign="center"><b>!!!</b></td>';
	}

	print '<td align="right" valign="center">'.price($loan->capital)."</td>";

	print '<td align="right" valign="center">'.price($sumpaid)."</td>";

	print '<td align="right" valign="center">'.price($loan->capital - $sumpaid)."</td>";

	print '<td align="right">';
	if ($sumpaid < $loan->capital)
	{
		print $langs->trans("LoanCapital") .': <input type="text" size="8" name="amount_capital">';
	}
	else
	{
		print '-';
	}
	print '<br>';
	if ($sumpaid < $loan->capital)
	{
		print $langs->trans("Insurance") .': <input type="text" size="8" name="amount_insurance">';
	}
	else
	{
		print '-';
	}
	print '<br>';
	if ($sumpaid < $loan->capital)
	{
		print $langs->trans("Interest") .': <input type="text" size="8" name="amount_interest">';
	}
	else
	{
		print '-';
	}
	print "</td>";

	print "</tr>\n";

	print '</table>';

	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp; &nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print "</form>\n";
}

llxFooter();
$db->close();
