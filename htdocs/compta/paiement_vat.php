<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *      \file       htdocs/compta/paiement_charge.php
 *      \ingroup    tax
 *      \brief      Page to add payment of a tax
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills"));

$chid = GETPOST("id", 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel');

$amounts = array();

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}


/*
 * Actions
 */

if ($action == 'add_payment' || ($action == 'confirm_paiement' && $confirm == 'yes')) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/compta/tva/card.php?id='.$chid;
		header("Location: ".$loc);
		exit;
	}

	$datepaye = dol_mktime(12, 0, 0, GETPOST("remonth", 'int'), GETPOST("reday", 'int'), GETPOST("reyear", 'int'));

	if (!(GETPOST("paiementtype", 'int') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if ($datepaye == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if (!empty($conf->banque->enabled) && !(GETPOST("accountid", 'int') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToDebit")), null, 'errors');
		$error++;
		$action = 'create';
	}

	// Read possible payments
	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 7) == 'amount_') {
			$other_chid = substr($key, 7);
			$amounts[$other_chid] = price2num(GETPOST($key));
		}
	}

	if ($amounts[key($amounts)] <= 0) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'create';
	}

	if (!$error) {
		$paymentid = 0;

		if (!$error) {
			$db->begin();

			// Create a line of payments
			$paiement = new PaymentVAT($db);
			$paiement->chid         = $chid;
			$paiement->datepaye     = $datepaye;
			$paiement->amounts      = $amounts; // Tableau de montant
			$paiement->paiementtype = GETPOST("paiementtype", 'alphanohtml');
			$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$paiement->note         = (string) GETPOST("note", 'restricthtml');
			$paiement->note_private = (string) GETPOST("note", 'restricthtml');

			if (!$error) {
				$paymentid = $paiement->create($user, (GETPOST('closepaidvat') == 'on' ? 1 : 0));
				if ($paymentid < 0) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$result = $paiement->addPaymentToBank($user, 'payment_vat', '(VATPayment)', GETPOST('accountid', 'int'), '', '');
				if (!($result > 0)) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/compta/tva/card.php?id='.$chid;
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);


// Formulaire de creation d'un paiement de charge
if ($action == 'create') {
	$tva = new Tva($db);
	$tva->fetch($chid);
	$tva->accountid = $tva->fk_account ? $tva->fk_account : $tva->accountid;
	$tva->paiementtype = $tva->type_payment;

	$total = $tva->amount;
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript" language="javascript">';

		//Add js for AutoFill
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(){
                        var amount = $(this).data("value");
						document.getElementById($(this).data(\'rowid\')).value = amount ;
					});';
		print '	});'."\n";

		print '	</script>'."\n";
	}

	print load_fiche_titre($langs->trans("DoPayment"));
	print "<br>\n";

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$chid.'">';
	print '<input type="hidden" name="chid" value="'.$chid.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head('', '');

	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td><a href="'.DOL_URL_ROOT.'/compta/tva/card.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	//print '<tr><td>'.$langs->trans("Type")."</td><td>".$tva->type_label."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Period")."</td><td>".dol_print_date($tva->datev, 'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$tva->label."</td></tr>\n";
	/*print '<tr><td>'.$langs->trans("DateDue")."</td><td>".dol_print_date($tva->date_ech,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount")."</td><td>".price($tva->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	$sql = "SELECT sum(p.amount) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_vat as p";
	$sql .= " WHERE p.fk_tva = ".((int) $chid);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	/*print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td>'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td>'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$datepaye = dol_mktime(12, 0, 0, GETPOST("remonth", 'int'), GETPOST("reday", 'int'), GETPOST("reyear", 'int'));
	$datepayment = empty($conf->global->MAIN_AUTOFILL_DATE) ? (GETPOST("remonth", 'int') ? $datepaye : -1) : 0;
	print $form->selectDate($datepayment, '', '', '', '', "add_payment", 1, 1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	$form->select_types_paiements(GETPOSTISSET("paiementtype") ? GETPOST("paiementtype", "int") : $tva->paiementtype, "paiementtype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
	print '<td>';
	$form->select_comptes(GETPOST("accountid") ? GETPOST("accountid", "int") : $tva->accountid, "accountid", 0, '', 1); // Show opend bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td><input name="num_payment" type="text" value="'.GETPOST('num_payment', 'alphanohtml').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	/*
	  * Autres charges impayees
	 */
	$num = 1;
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	//print '<td>'.$langs->trans("SocialContribution").'</td>';
	print '<td class="left">'.$langs->trans("DateDue").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total = 0;
	$totalrecu = 0;

	while ($i < $num) {
		$objp = $tva;

		print '<tr class="oddeven">';

		if ($objp->datev > 0) {
			print '<td class="left">'.dol_print_date($objp->datev, 'day').'</td>'."\n";
		} else {
			print "<td align=\"center\"><b>!!!</b></td>\n";
		}

		print '<td class="right"><span class="amount">'.price($objp->amount)."</span></td>";

		print '<td class="right"><span class="amount">'.price($sumpaid)."</span></td>";

		print '<td class="right"><span class="amount">'.price($objp->amount - $sumpaid)."</span></td>";

		print '<td class="center">';

		if ($sumpaid <> $objp->amount) {
			$namef = "amount_".$objp->id;
			$nameRemain = "remain_".$objp->id;
			/* Disabled, we autofil the amount with remain to pay by default
			if (!empty($conf->use_javascript_ajax)) {
					print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowid='".$namef."' data-value='".($objp->amount - $sumpaid)."'");
			} */
			$remaintopay = $objp->amount - $sumpaid;
			print '<input type=hidden class="sum_remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
			print '<input type="text" class="right width100" name="'.$namef.'" id="'.$namef.'" value="'.$remaintopay.'">';
		} else {
			print '-';
		}
		print "</td>";

		print "</tr>\n";
		$total += $objp->total;
		$total_ttc += $objp->total_ttc;
		$totalrecu += $objp->am;
		$i++;
	}
	if ($i > 1) {
		// Print total
		print '<tr class="oddeven">';
		print '<td colspan="2" class="left">'.$langs->trans("Total").':</td>';
		print '<td class="right"><b>'.price($total_ttc).'</b></td>';
		print '<td class="right"><b>'.price($totalrecu).'</b></td>';
		print '<td class="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";

	// Bouton Save payment
	print '<br><div class="center"><input type="checkbox" checked name="closepaidvat"> '.$langs->trans("ClosePaidVATAutomatically");
	print '<br><input type="submit" class="button" name="save" value="'.$langs->trans('ToMakePayment').'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print "</form>\n";
}

llxFooter();
$db->close();
