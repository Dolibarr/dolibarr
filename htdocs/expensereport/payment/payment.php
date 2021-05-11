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

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'trips'));

$id=GETPOST("id",'int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','aZ09');
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
		$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
		header("Location: ".$loc);
		exit;
	}

	$expensereport = new ExpenseReport($db);
	$result = $expensereport->fetch($id, $ref);
	if (! $result)
	{
		$error++;
		setEventMessages($expensereport->error, $expensereport->errors, 'errors');
	}

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
    		$payment->chid           = $expensereport->id;
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
                	setEventMessages($payment->error, $payment->errors, 'errors');
                    $error++;
                }
    		}

            if (! $error)
            {
                $result=$payment->addPaymentToBank($user,'payment_expensereport','(ExpenseReportPayment)',$accountid,'','');
                if (! $result > 0)
                {
                	setEventMessages($payment->error, $payment->errors, 'errors');
                    $error++;
                }
            }

            if (!$error) {
                $payment->fetch($paymentid);
                if ($expensereport->total_ttc - $payment->amount == 0) {
                    $result = $expensereport->set_paid($expensereport->id, $user);
                    if (!$result > 0) {
                    	setEventMessages($payment->error, $payment->errors, 'errors');
                        $error++;
                    }
                }

            }

    	    if (! $error)
            {
                $db->commit();
                $loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
                header('Location: '.$loc);
                exit;
            }
            else
            {
                $db->rollback();
            }
        }
	}

	$action='create';
}


/*
 * View
 */

llxHeader();

$form=new Form($db);


// Form to create expense report payment
if ($action == 'create' || empty($action))
{
	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($id, $ref);

	$total = $expensereport->total_ttc;

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="id" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="chid" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="action" value="add_payment">';

    dol_fiche_head(null, '0', '', -1);

    $linkback = '';
    // $linkback = '<a href="' . DOL_URL_ROOT . '/expensereport/payment/list.php">' . $langs->trans("BackToList") . '</a>';

    dol_banner_tab($expensereport, 'ref', $linkback, 1, 'ref', 'ref', '');

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">'."\n";

	print '<tr><td class="titlefield">'.$langs->trans("Period").'</td><td>'.get_date_range($expensereport->date_debut,$expensereport->date_fin,"",$langs,0).'</td></tr>';
	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($expensereport->total_ttc,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	$sql = "SELECT sum(p.amount) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_expensereport as p, ".MAIN_DB_PREFIX."expensereport as e";
	$sql.= " WHERE p.fk_expensereport = e.rowid AND p.fk_expensereport = ".$id;
    $sql.= ' AND e.entity IN ('.getEntity('expensereport').')';
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td>'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td>'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

    print '</table>';

    print '<br>';

    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent">'."\n";

    print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
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

	print '</div>';

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

	$total=0;
	$totalrecu=0;

	while ($i < $num)
	{
		$objp = $expensereport;

		print '<tr class="oddeven">';

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
		print '<tr class="oddeven">';
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
