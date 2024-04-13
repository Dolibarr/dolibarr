<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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
 *      \file       htdocs/salaries/paiement_salary.php
 *      \ingroup    salary
 *      \brief      Page to add payment of a salary
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills"));

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$amounts = array();

$object = new Salary($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}

// Security check
$socid = GETPOSTINT("socid");
if ($user->socid > 0) {
	$socid = $user->socid;
}
restrictedArea($user, 'salaries', $object->id, 'salary', '');


/*
 * Actions
 */

if (($action == 'add_payment' || ($action == 'confirm_paiement' && $confirm == 'yes')) && $user->hasRight('salaries', 'write')) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/salaries/card.php?id='.$id;
		header("Location: ".$loc);
		exit;
	}

	$datepaye = dol_mktime(GETPOSTINT("rehour"), GETPOSTINT("remin"), GETPOSTINT("resec"), GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"), 'tzuserrel');

	if (!(GETPOSTINT("paiementtype") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if ($datepaye == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
		$action = 'create';
	}
	if (isModEnabled("bank") && !(GETPOSTINT("accountid") > 0)) {
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
			$paiement = new PaymentSalary($db);
			$paiement->fk_salary    = $id;
			$paiement->chid         = $id;	// deprecated
			$paiement->datep        = $datepaye;
			$paiement->amounts      = $amounts; // Tableau de montant
			$paiement->fk_typepayment = GETPOSTINT("paiementtype");
			$paiement->num_payment  = GETPOST("num_payment", 'alphanohtml');
			$paiement->note         = GETPOST("note", 'restricthtml');
			$paiement->note_private = GETPOST("note", 'restricthtml');

			if (!$error) {
				$paymentid = $paiement->create($user, (GETPOST('closepaidsalary') == 'on' ? 1 : 0));
				if ($paymentid < 0) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$result = $paiement->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', GETPOSTINT('accountid'), '', '');

				if (!($result > 0)) {
					$error++;
					setEventMessages($paiement->error, null, 'errors');
					$action = 'create';
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/salaries/card.php?id='.$id;
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

$form = new Form($db);

$help_url = '';

llxHeader('', '', $help_url);

$salary = $object;

// Formulaire de creation d'un paiement de charge
if ($action == 'create') {
	$salary->accountid = $salary->fk_account ? $salary->fk_account : $salary->accountid;
	$salary->paiementtype = $salary->mode_reglement_id ? $salary->mode_reglement_id : $salary->paiementtype;

	$total = $salary->amount;
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';

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
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="chid" value="'.$id.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td><a href="'.DOL_URL_ROOT.'/salaries/card.php?id='.$id.'">'.$id.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("DateStart")."</td><td>".dol_print_date($salary->datesp, 'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("DateEnd")."</td><td>".dol_print_date($salary->dateep, 'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$salary->label."</td></tr>\n";
	/*print '<tr><td>'.$langs->trans("DateDue")."</td><td>".dol_print_date($salary->date_ech,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Amount")."</td><td>".price($salary->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	$sql = "SELECT sum(p.amount) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as p";
	$sql .= " WHERE p.fk_salary = ".((int) $id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free($resql);
	}
	/*print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td>'.price($sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td>'.price($total-$sumpaid,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';*/

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$datepaye = dol_mktime(GETPOSTINT("rehour"), GETPOSTINT("remin"), GETPOSTINT("resec"), GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
	$datepayment = !getDolGlobalString('MAIN_AUTOFILL_DATE') ? (GETPOST("remonth") ? $datepaye : -1) : '';
	print $form->selectDate($datepayment, '', 1, 1, 0, "add_payment", 1, 1, 0, '', '', $salary->dateep, '', 1, $langs->trans("DateEnd"));
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	$form->select_types_paiements(GETPOSTISSET("paiementtype") ? GETPOST("paiementtype") : $salary->type_payment, "paiementtype");
	print "</td>\n";
	print '</tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
	print '<td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	$form->select_comptes(GETPOSTISSET("accountid") ? GETPOSTINT("accountid") : $salary->accountid, "accountid", 0, '', 1); // Show opend bank account list
	print '</td></tr>';

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td><input name="num_payment" type="text" value="'.GETPOST('num_payment', 'alphanohtml').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_3.'">';
	print GETPOST('note');
	print '</textarea></td>';
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
	print '<td class="left">'.$langs->trans("DateEnd").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total = 0;
	$total_ttc = 0.;
	$totalrecu = 0;

	while ($i < $num) {
		$objp = $salary;

		print '<tr class="oddeven">';

		if ($objp->dateep > 0) {
			print '<td class="left">'.dol_print_date($objp->dateep, 'day').'</td>'."\n";
		} else {
			print '<td align="center"><b>!!!</b></td>'."\n";
		}

		print '<td class="right">'.price($objp->amount)."</td>";

		print '<td class="right">'.price($sumpaid)."</td>";

		print '<td class="right">'.price($objp->amount - $sumpaid)."</td>";

		print '<td class="center">';
		if ($sumpaid < $objp->amount) {
			$namef = "amount_".$objp->id;
			$nameRemain = "remain_".$objp->id;
			/* Disabled, we autofil the amount with remain to pay by default
			if (!empty($conf->use_javascript_ajax)) {
				print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowid='".$namef."' data-value='".($objp->amount - $sumpaid)."'");
			} */
			$valuetoshow = GETPOSTISSET($namef) ? GETPOST($namef) : ($objp->amount - $sumpaid);

			print '<input type=hidden class="sum_remain" name="'.$nameRemain.'" value="'.$valuetoshow.'">';
			print '<input type="text" class="right width75" name="'.$namef.'" id="'.$namef.'" value="'.$valuetoshow.'">';
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

	print '<br>';

	// Bouton Save payment
	print '<div class="center">';
	print '<div class="paddingbottom"><input type="checkbox" checked name="closepaidsalary" id="closepaidsalary"><label for="closepaidsalary">'.$langs->trans("ClosePaidSalaryAutomatically").'</label></div>';
	print $form->buttonsSaveCancel("ToMakePayment", "Cancel", '', true);
	print '</div>';


	print "</form>\n";
}

llxFooter();
$db->close();
