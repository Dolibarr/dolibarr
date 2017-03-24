<?php
/* Copyright (C) 2015       Alexandre Spangaro	 <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/expensereport/payment/payment.php
 *  \ingroup    Expense Report
 *  \brief      Page to add payment of an expense report
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("bills");
$langs->load("banks");

$chid=GETPOST("id");
$action=GETPOST('action');
$amounts = array();
$accountid=GETPOST('accountid','int');

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

	if ($_POST["cancel"])
	{
		$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($chid);

	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

	if (! ($_POST["fk_typepayment"] > 0))
	{
		setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("Date")), null, 'errors');
		$error++;
	}
    if (! empty($conf->banque->enabled) && ! ($accountid > 0))
    {
        setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("AccountToDebit")), null, 'errors');
        $error++;
    }
    
	if (! $error)
	{
		$paymentid = 0;
		$total = 0;

		// Read possible payments
		foreach ($_POST as $key => $value)
		{
			if (substr($key,0,7) == 'amount_')
			{
				$amounts[$expensereport->fk_user_author] = price2num($_POST[$key]);
				$total += price2num($_POST[$key]);
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
    		$payment = new PaymentExpenseReport($db);
    		$payment->chid           = $chid;
    		$payment->datepaid       = $datepaid;
    		$payment->amounts        = $amounts;   // Tableau de montant
    		$payment->total          = $total;
    		$payment->fk_typepayment = $_POST["fk_typepayment"];
    		$payment->num_payment    = $_POST["num_payment"];
    		$payment->note           = $_POST["note"];

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
                $result=$payment->addPaymentToBank($user,'payment_expensereport','(ExpenseReportPayment)',$accountid,'','');
                if (! $result > 0)
                {
                    $errmsg=$payment->error;
                    $error++;
                }
            }

    	    if (! $error)
            {
                $db->commit();
                $loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$chid;
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


// Form to create expense report payment
if (GETPOST("action") == 'create')
{
	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($chid);

	$total = $expensereport->total_ttc;

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';
	
    dol_fiche_head();

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ExpenseReport").'</td>';

	print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/expensereport/card.php?id='.$chid.'">'.$expensereport->ref.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("Period").'</td><td colspan="2">'.get_date_range($expensereport->date_debut,$expensereport->date_fin,"",$langs,0).'</td></tr>';
	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">'.price($expensereport->total_ttc,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	$sql = "SELECT sum(p.amount) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_expensereport as p, ".MAIN_DB_PREFIX."expensereport as e";
	$sql.= " WHERE p.fk_expensereport = e.rowid AND p.fk_expensereport = ".$chid;
    $sql.= ' AND e.entity IN ('.getEntity('expensereport', 1).')';
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	print '<tr class="liste_titre">';
	print "<td colspan=\"3\">".$langs->trans("Payment").'</td>';
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	$datepayment=empty($conf->global->MAIN_AUTOFILL_DATE)?(empty($_POST["remonth"])?-1:$datepaid):0;
	$form->select_date($datepayment,'','','','',"add_payment",1,1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST["fk_typepayment"])?$_POST["fk_typepayment"]:$expensereport->fk_typepayment, "fk_typepayment");
	print "</td>\n";
	print '</tr>';

	if (! empty($conf->banque->enabled))
	{
    	print '<tr>';
    	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
    	print '<td colspan="2">';
    	$form->select_comptes(isset($_POST["accountid"])?$_POST["accountid"]:$expensereport->accountid, "accountid", 0, '',1);  // Show open bank account list
    	print '</td></tr>';
	}
	
	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

    dol_fiche_end();

	// List of expenses ereport not already paid completely
	$num = 1;
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print '<td align="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td align="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$var=true;
	$total=0;
	$totalrecu=0;

	while ($i < $num)
	{
		$objp = $expensereport;

		$var=!$var;

		print "<tr ".$bc[$var].">";

		print '<td align="right">'.price($objp->total_ttc)."</td>";

		print '<td align="right">'.price($sumpaid)."</td>";

		print '<td align="right">'.price($objp->total_ttc - $sumpaid)."</td>";

		print '<td align="center">';
		if ($sumpaid < $objp->total_ttc)
		{
			$namef = "amount_".$objp->id;
			print '<input type="text" size="8" name="'.$namef.'">';
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
		print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
		print "<td align=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";

	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print "</form>\n";
}

llxFooter();
$db->close();
