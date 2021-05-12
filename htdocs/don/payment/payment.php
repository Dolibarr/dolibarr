<?php
<<<<<<< HEAD
/* Copyright (C) 2015       Alexandre Spangaro	  	<aspangaro.dolibarr@gmail.com>
=======
/* Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/don/payment.php
 *  \ingroup    donations
 *  \brief      Page to add payment of a donation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("bills");

$chid=GETPOST("rowid");
<<<<<<< HEAD
$action=GETPOST('action','aZ09');
=======
$action=GETPOST('action', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$amounts = array();

// Security check
$socid=0;
<<<<<<< HEAD
if ($user->societe_id > 0)
{
=======
if ($user->societe_id > 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
		$loc = DOL_URL_ROOT.'/don/card.php?rowid='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

	if (! $_POST["paymenttype"] > 0)
	{
<<<<<<< HEAD
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
=======
		$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$error++;
	}
	if ($datepaid == '')
	{
<<<<<<< HEAD
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Date"));
=======
		$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Date"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$error++;
	}
    if (! empty($conf->banque->enabled) && ! $_POST["accountid"] > 0)
    {
<<<<<<< HEAD
        $mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("AccountToCredit"));
=======
        $mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $error++;
    }

	if (! $error)
	{
		$paymentid = 0;

		// Read possible payments
		foreach ($_POST as $key => $value)
		{
<<<<<<< HEAD
			if (substr($key,0,7) == 'amount_')
			{
				$other_chid = substr($key,7);
=======
			if (substr($key, 0, 7) == 'amount_')
			{
				$other_chid = substr($key, 7);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
    		$payment = new PaymentDonation($db);
    		$payment->chid         = $chid;
    		$payment->datepaid     = $datepaid;
    		$payment->amounts      = $amounts;   // Tableau de montant
    		$payment->paymenttype  = $_POST["paymenttype"];
    		$payment->num_payment  = $_POST["num_payment"];
    		$payment->note         = $_POST["note"];

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
<<<<<<< HEAD
                $result=$payment->addPaymentToBank($user,'payment_donation','(DonationPayment)',$_POST['accountid'],'','');
=======
                $result=$payment->addPaymentToBank($user, 'payment_donation', '(DonationPayment)', $_POST['accountid'], '', '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                if (! $result > 0)
                {
                    $errmsg=$payment->error;
                    $error++;
                }
            }

    	    if (! $error)
            {
                $db->commit();
                $loc = DOL_URL_ROOT.'/don/card.php?rowid='.$chid;
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


// Form to create donation payment
<<<<<<< HEAD
if (GETPOST('action','aZ09') == 'create')
=======
if (GETPOST('action', 'aZ09') == 'create')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{

	$don = new Don($db);
	$don->fetch($chid);

	$total = $don->amount;

	print load_fiche_titre($langs->trans("DoPayment"));

	if ($mesg)
	{
		print "<div class=\"error\">$mesg</div>";
	}

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="rowid" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';
<<<<<<< HEAD
	
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    dol_fiche_head();

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Donation").'</td>';

	print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2"><a href="'.DOL_URL_ROOT.'/don/card.php?rowid='.$chid.'">'.$chid.'</a></td></tr>';
<<<<<<< HEAD
	print '<tr><td>'.$langs->trans("Date")."</td><td colspan=\"2\">".dol_print_date($don->date,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount")."</td><td colspan=\"2\">".price($don->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
=======
	print '<tr><td>'.$langs->trans("Date")."</td><td colspan=\"2\">".dol_print_date($don->date, 'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount")."</td><td colspan=\"2\">".price($don->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$sql = "SELECT sum(p.amount) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_donation as p";
	$sql.= " WHERE p.fk_donation = ".$chid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
<<<<<<< HEAD
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
=======
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2">'.price($sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td colspan="2">'.price($total-$sumpaid, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	print '<tr class="liste_titre">';
	print "<td colspan=\"3\">".$langs->trans("Payment").'</td>';
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	$datepayment=empty($conf->global->MAIN_AUTOFILL_DATE)?(empty($_POST["remonth"])?-1:$datepaid):0;
<<<<<<< HEAD
	$form->select_date($datepayment,'','','','',"add_payment",1,1);
=======
	print $form->selectDate($datepayment, '', '', '', '', "add_payment", 1, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST["paymenttype"])?$_POST["paymenttype"]:$don->paymenttype, "paymenttype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToCredit').'</td>';
	print '<td colspan="2">';
<<<<<<< HEAD
	$form->select_comptes(isset($_POST["accountid"])?$_POST["accountid"]:$don->accountid, "accountid", 0, '',1);  // Show open bank account list
=======
	$form->select_comptes(isset($_POST["accountid"])?$_POST["accountid"]:$don->accountid, "accountid", 0, '', 1);  // Show open bank account list
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '</td></tr>';

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

	/*
 	 * Autres charges impayees
	 */
	$num = 1;
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
<<<<<<< HEAD
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print '<td align="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td align="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="center">'.$langs->trans("Amount").'</td>';
=======
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "</tr>\n";

	$total=0;
	$totalrecu=0;

	while ($i < $num)
	{
		$objp = $don;

		print '<tr class="oddeven">';

<<<<<<< HEAD
		print '<td align="right">'.price($objp->amount)."</td>";

		print '<td align="right">'.price($sumpaid)."</td>";

		print '<td align="right">'.price($objp->amount - $sumpaid)."</td>";

		print '<td align="center">';
=======
		print '<td class="right">'.price($objp->amount)."</td>";

		print '<td class="right">'.price($sumpaid)."</td>";

		print '<td class="right">'.price($objp->amount - $sumpaid)."</td>";

		print '<td class="center">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($sumpaid < $objp->amount)
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
<<<<<<< HEAD
		print '<td colspan="2" align="left">'.$langs->trans("Total").':</td>';
		print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
		print "<td align=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		print '<td align="center">&nbsp;</td>';
=======
		print '<td colspan="2" class="left">'.$langs->trans("Total").':</td>';
		print "<td class=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td class=\"right\"><b>".price($totalrecu)."</b></td>";
		print "<td class=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		print '<td class="center">&nbsp;</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
