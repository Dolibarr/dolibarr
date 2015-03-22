<?php
/* Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
$action=GETPOST('action');
$amounts = array();
$cancel=GETPOST('cancel','alpha');

// Security check
$socid=0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}

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

	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

	if (! $_POST["paymenttype"] > 0)
	{
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
		$error++;
	}
	if ($datepaid == '')
	{
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Date"));
		$error++;
	}
    if (! empty($conf->banque->enabled) && ! $_POST["accountid"] > 0)
    {
        $mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("AccountToCredit"));
        $error++;
    }

	if (! $error)
	{
		$paymentid = 0;

		// Read possible payments
		foreach ($_POST as $key => $value)
		{
			if (substr($key,0,7) == 'amount_')
			{
				$other_chid = substr($key,7);
				$amounts[$other_chid] = price2num($_POST[$key]);
			}
		}

        if (count($amounts) <= 0)
        {
            $error++;
            $errmsg='ErrorNoPaymentDefined';
        }

        if (! $error)
        {
    		$db->begin();

    		// Create a line of payments
    		$payment = new PaymentLoan($db);
    		$payment->chid				= $chid;
    		$payment->datepaid			= $datepaid;
    		$payment->amounts			= $amounts;   // Tableau de montant
			$payment->amount_capital	= $_POST["amount_capital"];
			$payment->amount_insurance	= $_POST["amount_insurance"];
			$payment->amount_interest	= $_POST["amount_interest"];
			$payment->paymenttype		= $_POST["paymenttype"];
    		$payment->num_payment		= $_POST["num_payment"];
    		$payment->note				= $_POST["note"];

    		if (! $error)
    		{
    		    $paymentid = $payment->create($user);
                if ($paymentid < 0)
                {
                    $errmsg=$payment->error;
                    $error++;
                }
    		}

            if (! $error)
            {
                $result=$payment->addPaymentToBank($user,'payment_loan','(LoanPayment)',$_POST['accountid'],'','');
                if (! $result > 0)
                {
                    $errmsg=$payment->error;
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

	$_GET["action"]='create';
}


/*
 * View
 */

llxHeader();

$form=new Form($db);


// Form to create loan's payment
if ($_GET["action"] == 'create')
{

	$loan = new Loan($db);
	$loan->fetch($chid);

	$total = $loan->capital;

	print_fiche_titre($langs->trans("DoPayment"));
	print "<br>\n";

	if ($mesg)
	{
		print "<div class=\"error\">$mesg</div>";
	}

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Loan").'</td>';

	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/loan/card.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("DateStart").'</td><td colspan="2">'.dol_print_date($loan->datestart,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$loan->label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">'.price($loan->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	$sql = "SELECT sum(p.amount) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_loan as p";
	$sql.= " WHERE p.fk_loan = ".$chid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td valign="top">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '</tr>';

	print '</table>';
	
	print '<br>';
	
	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("Payment").'</td>';
	print '</tr>';

	print '<tr><td  width="25%" class="fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	$datepayment = empty($conf->global->MAIN_AUTOFILL_DATE)?(empty($_POST["remonth"])?-1:$datepaye):0;
	$form->select_date($datepayment,'','','','',"add_payment",1,1);
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
	print '<tr><td>'.$langs->trans('Number');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td valign="top">'.$langs->trans("Comments").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

	print '<br>';

	/*
 	 * Other loan unpaid
	 */
	$num = 1;
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="left">'.$langs->trans("DateDue").'</td>';
	print '<td align="right">'.$langs->trans("Capital").'</td>';
	print '<td align="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td align="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$var=True;
	$total=0;
	$totalrecu=0;

	while ($i < $num)
	{
		$objp = $loan;

		$var=!$var;

		print "<tr ".$bc[$var].">";

		if ($objp->datestart > 0)
		{
			print '<td align="left" valign="center">'.dol_print_date($objp->datestart,'day').'</td>';
		}
		else
		{
			print '<td align="center" valign="center"><b>!!!</b></td>';
		}

		print '<td align="right" valign="center">'.price($objp->capital)."</td>";

		print '<td align="right" valign="center">'.price($sumpaid)."</td>";

		print '<td align="right" valign="center">'.price($objp->capital - $sumpaid)."</td>";

		print '<td align="right">';
		if ($sumpaid < $objp->capital)
		{
			$namec = "amount_capital_".$objp->id;
			print $langs->trans("Capital") .': <input type="text" size="8" name="'.$namec.'">';
		}
		else
		{
			print '-';
		}
		print '<br>';		
		if ($sumpaid < $objp->capital)
		{
			$namea = "amount_insurance_".$objp->id;
			print $langs->trans("Insurance") .': <input type="text" size="8" name="'.$namea.'">';
		}
		else
		{
			print '-';
		}
		print '<br>';		
		if ($sumpaid < $objp->capital)
		{
			$namei = "amount_interest_".$objp->id;
			print $langs->trans("Interest") .': <input type="text" size="8" name="'.$namei.'">';
		}
		else
		{
			print '-';
		}
		print "</td>";

		print "</tr>\n";
		$total+=$objp->total;
		$total_ttc+=$objp->total_ttc;
		$totalrecu+=$objp->am;
		$i++;
	}
	if ($i > 1)
	{
		// Print total
		print "<tr ".$bc[!$var].">";
		print '<td colspan="2" align="left">'.$langs->trans("Total").':</td>';
		print '<td align="right"><b>"'.price($total_ttc).'"</b></td>';
		print '<td align="right"><b>"'.price($totalrecu).'"</b></td>';
		print '<td align="right"><b>"'.price($total_ttc - $totalrecu).'"</b></td>';
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";

	print '<br><center>';

	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp; &nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';

	print '</center>';

	print "</form>\n";
}


$db->close();

llxFooter();
