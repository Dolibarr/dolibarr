<?php
/* Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/don/payment/payment.php
 *  \ingroup    donations
 *  \brief      Page to add payment of a donation
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("bills");

$chid = GETPOST("rowid", 'int');
$action = GETPOST('action', 'aZ09');
$amounts = array();
$cancel = GETPOST('cancel');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$object = new Don($db);


/*
 * Actions
 */

if ($action == 'add_payment') {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/don/card.php?rowid='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$datepaid = dol_mktime(12, 0, 0, GETPOST("remonth"), GETPOST("reday"), GETPOST("reyear"));

	if (!(GETPOST("paymenttype") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}
	if (isModEnabled("banque") && !(GETPOST("accountid", 'int') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToCredit")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$paymentid = 0;

		// Read possible payments
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 7) == 'amount_') {
				$other_chid = substr($key, 7);
				$amounts[$other_chid] = price2num(GETPOST($key));
			}
		}

		if (count($amounts) <= 0) {
			$error++;
			$errmsg = 'ErrorNoPaymentDefined';
			setEventMessages($errmsg, null, 'errors');
		}

		if (!$error) {
			$db->begin();

			// Create a line of payments
			$payment = new PaymentDonation($db);
			$payment->chid         = $chid;
			$payment->datep     = $datepaid;
			$payment->amounts      = $amounts; // Tableau de montant
			$payment->paymenttype  = GETPOST("paymenttype", 'int');
			$payment->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$payment->note_public  = GETPOST("note_public", 'restricthtml');

			if (!$error) {
				$paymentid = $payment->create($user);
				if ($paymentid < 0) {
					$errmsg = $payment->error;
					setEventMessages($errmsg, null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$result = $payment->addPaymentToBank($user, 'payment_donation', '(DonationPayment)', GETPOST('accountid', 'int'), '', '');
				if (!($result > 0)) {
					$errmsg = $payment->error;
					setEventMessages($errmsg, null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/don/card.php?rowid='.$chid;
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}

	$action = 'create';
}


/*
 * View
 */

$form = new Form($db);

llxHeader();


$sql = "SELECT sum(p.amount) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."payment_donation as p";
$sql .= " WHERE p.fk_donation = ".((int) $chid);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$sumpaid = $obj->total;
	$db->free($resql);
}


// Form to create donation payment
if ($action == 'create') {
	$object->fetch($chid);

	$total = $object->amount;

	print load_fiche_titre($langs->trans("DoPayment"));

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		//Add js for AutoFill
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmout").on(\'click touchstart\', function(){
							$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
						});';
		print '	});'."\n";

		print '	</script>'."\n";
	}

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="rowid" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate">';

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, GETPOST("remonth"), GETPOST("reday"), GETPOST("reyear"));
	$datepayment = !getDolGlobalString('MAIN_AUTOFILL_DATE') ? (GETPOST("remonth") ? $datepaid : -1) : 0;
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_payment", 1, 1, 0, '', '', $object->date, '', 1, $langs->trans("DonationDate"));
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(GETPOSTISSET("paymenttype") ? GETPOST("paymenttype") : $object->fk_typepayment, "paymenttype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToCredit').'</td>';
	print '<td colspan="2">';
	$form->select_comptes(GETPOSTISSET("accountid") ? GETPOST("accountid") : "0", "accountid", 0, '', 2); // Show open bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop" colspan="2"><textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	/*
	  * List of payments on donation
	 */

	$num = 1;
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Donation").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total = 0;
	$totalrecu = 0;

	while ($i < $num) {
		$objp = $object;

		print '<tr class="oddeven">';

		print '<td>'.$object->getNomUrl(1)."</td>";

		print '<td class="right">'.price($objp->amount)."</td>";

		print '<td class="right">'.price($sumpaid)."</td>";

		print '<td class="right">'.price($objp->amount - $sumpaid)."</td>";

		print '<td class="center">';
		if ($sumpaid < $objp->amount) {
			$namef = "amount_".$objp->id;
			if (!empty($conf->use_javascript_ajax)) {
				print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".price($objp->amount - $sumpaid)."'");
			}
			print '<input type="text" size="8" name="'.$namef.'">';
		} else {
			print '-';
		}
		print "</td>";

		print "</tr>\n";
		/*$total+=$objp->total;
		$total_ttc+=$objp->total_ttc;
		$totalrecu+=$objp->am;*/	//Useless code ?
		$i++;
	}
	/*if ($i > 1)
	{
		// Print total
		print '<tr class="oddeven">';
		print '<td colspan="2" class="left">'.$langs->trans("Total").':</td>';
		print "<td class=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td class=\"right\"><b>".price($totalrecu)."</b></td>";
		print "<td class=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		print '<td class="center">&nbsp;</td>';
		print "</tr>\n";
	}*/	//Useless code ?

	print "</table>";

	print $form->buttonsSaveCancel();

	print "</form>\n";
}

llxFooter();
$db->close();
