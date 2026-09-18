<?php
/* Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/expensereport/payment/payment.php
 *  \ingroup    Expense Report
 *  \brief      Page to add payment of an expense report
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'trips'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$amounts = array();
$accountid = GETPOSTINT('accountid');
$cancel = GETPOST('cancel');

$object = new PaymentExpenseReport($db);

if ($id > 0) {
	$result = $object->fetch($id);
	if (!$result) {
		dol_print_error($db, 'Failed to get payment id '.$id);
	}
}

// Security check
$socid = 0;
if ($user->isExternalUser()) {
	$socid = $user->isExternalUser();
}

$result = restrictedArea($user, 'expensereport', $object->fk_expensereport, 'expensereport');

$permissiontoadd = $user->hasRight('expensereport', 'creer');


/*
 * Actions
 */

if ($action == 'add_payment' && $permissiontoadd) {
	$error = 0;

	if ($cancel) {
		$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
		header("Location: ".$loc);
		exit;
	}

	$expensereport = new ExpenseReport($db);
	$result = $expensereport->fetch($id, $ref);
	if (!$result) {
		$error++;
		setEventMessages($expensereport->error, $expensereport->errors, 'errors');
	}

	$datepaid = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));

	if (!(GETPOSTINT("fk_typepayment") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), null, 'errors');
		$error++;
	}
	if ($datepaid == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$error++;
	}

	if (isModEnabled("bank") && !($accountid > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountToDebit")), null, 'errors');
		$error++;
	}

	if (!$error) {
		$paymentid = 0;
		// $total = 0;

		// Read possible payments.
		// The key of $amounts is the expense report ID.
		foreach ($_POST as $postkey => $value) {
			if (preg_match('/^amount_([0-9]+)$/', $postkey, $matches)) {
				$expensereportid = (int) $matches[1];

				if (GETPOST($postkey) === '') {
					continue;
				}

				$amount = (float) price2num(GETPOST($postkey), 'MT');

				if ($amount < 0) {
					$error++;
					setEventMessages(
						$langs->trans("ErrorBadValueForParameter", $postkey),
						null,
						'errors'
					);
					continue;
				}

				if ($amount != 0) {
					$amounts[$expensereportid] = $amount;
				}
			}
		}

		if (count($amounts) <= 0) {
			$error++;
			setEventMessages('ErrorNoPaymentDefined', null, 'errors');
		}

		$expensereportsToPay = array();

		/*
		 * Server-side validation of every expense report submitted.
		 */
		if (!$error) {
			foreach ($amounts as $expensereportid => $amount) {
				$tmpExpenseReport = new ExpenseReport($db);

				$result = $tmpExpenseReport->fetch((int) $expensereportid);

				if ($result <= 0) {
					$error++;
					setEventMessages(
						'Expense report '.$expensereportid.' not found',
						null,
						'errors'
					);
					break;
				}

				// All reports of one payment must belong to the same user.
				if ((int) $tmpExpenseReport->fk_user_author !== (int) $expensereport->fk_user_author) {
					$error++;
					setEventMessages(
						'Expense report '.$tmpExpenseReport->ref.' belongs to another user',
						null,
						'errors'
					);
					break;
				}

				// Keep the payment inside the same entity.
				if ((int) $tmpExpenseReport->entity !== (int) $expensereport->entity) {
					$error++;
					setEventMessages(
						'Expense report '.$tmpExpenseReport->ref.' belongs to another entity',
						null,
						'errors'
					);
					break;
				}

				// Only approved, unpaid reports can receive a payment here.
				if (
					(int) $tmpExpenseReport->status !== ExpenseReport::STATUS_APPROVED
					|| !empty($tmpExpenseReport->paid)
				) {
					$error++;
					setEventMessages(
						'Expense report '.$tmpExpenseReport->ref.' is not available for payment',
						null,
						'errors'
					);
					break;
				}

				$sumpaidtmp = $tmpExpenseReport->getSumPayments();

				if ($sumpaidtmp < 0) {
					$error++;
					setEventMessages(
						$tmpExpenseReport->error,
						$tmpExpenseReport->errors,
						'errors'
					);
					break;
				}

				$remaintopay = price2num(
					$tmpExpenseReport->total_ttc - $sumpaidtmp,
					'MT'
				);

				if ($remaintopay <= 0) {
					$error++;
					setEventMessages(
						'Expense report '.$tmpExpenseReport->ref.' has no amount remaining to pay',
						null,
						'errors'
					);
					break;
				}

				if ((float) $amount > (float) $remaintopay) {
					$error++;
					setEventMessages(
						$langs->trans("PaymentHigherThanReminderToPay"),
						null,
						'errors'
					);
					break;
				}

				$expensereportsToPay[$expensereportid] = $tmpExpenseReport;
			}
		}

		if (!$error) {
			$db->begin();

			// Create a line of payments
			$payment = new PaymentExpenseReport($db);
			$payment->fk_expensereport = $expensereport->id;
			$payment->datep       	 = $datepaid;
			$payment->amounts		 = $amounts; // array of amounts
			// total is calculated in class
			// $payment->total          = $total;
			$payment->fk_typepayment = GETPOSTINT("fk_typepayment");
			$payment->num_payment    = GETPOST("num_payment", 'alphanohtml');
			$payment->note_public    = GETPOST("note_public", 'restricthtml');
			$payment->fk_bank        = $accountid;

			if (!$error) {
				$paymentid = $payment->create($user);
				if ($paymentid < 0) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$result = $payment->addPaymentToBank($user, 'payment_expensereport', '(ExpenseReportPayment)', $accountid, '', '');
				if ($result <= 0) {
					setEventMessages($payment->error, $payment->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				foreach ($expensereportsToPay as $expensereportid => $tmpExpenseReport) {
					// Recalculate after inserting the new payment relation.
					$sumpaidtmp = $tmpExpenseReport->getSumPayments();

					if ($sumpaidtmp < 0) {
						setEventMessages(
							$tmpExpenseReport->error,
							$tmpExpenseReport->errors,
							'errors'
						);
						$error++;
						break;
					}

					$remaintopay = price2num(
						$tmpExpenseReport->total_ttc - $sumpaidtmp,
						'MT'
					);

					if ($remaintopay == 0) {
						$result = $tmpExpenseReport->setPaid(
							$tmpExpenseReport->id,
							$user
						);

						if ($result <= 0) {
							setEventMessages(
								$tmpExpenseReport->error,
								$tmpExpenseReport->errors,
								'errors'
							);
							$error++;
							break;
						}
					}
				}
			}

			if (!$error) {
				$db->commit();
				$loc = DOL_URL_ROOT.'/expensereport/card.php?id='.$id;
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

llxHeader();

$form = new Form($db);


// Form to create expense report payment
if ($action == 'create' || empty($action)) {
	$expensereport = new ExpenseReport($db);
	$expensereport->fetch($id, $ref);

	$total = $expensereport->total_ttc;

	// autofill remainder amount
	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		//Add js for AutoFill
		print ' $(document).ready(function () {';
		print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(){
                        var amount = $(this).data("value");
						document.getElementById($(this).data(\'rowid\')).value = amount ;
					});';
		print "\t});\n";
		print "</script>\n";
	}

	print load_fiche_titre($langs->trans("DoPayment"));

	print '<form name="add_payment" action="'.$_SERVER['PHP_SELF'].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="id" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="chid" value="'.$expensereport->id.'">';
	print '<input type="hidden" name="action" value="add_payment">';

	print dol_get_fiche_head([], '0', '', -1);

	$linkback = '';
	// $linkback = '<a href="' . DOL_URL_ROOT . '/expensereport/payment/list.php">' . $langs->trans("BackToList") . '</a>';

	dol_banner_tab($expensereport, 'ref', $linkback, 1, 'ref', 'ref', '');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">'."\n";

	print '<tr><td class="titlefield">'.$langs->trans("Period").'</td><td>'.get_date_range($expensereport->date_debut, $expensereport->date_fin, "", $langs, 0).'</td></tr>';
	print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($expensereport->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';


	$sumpaid = $expensereport->getSumPayments();

	if ($sumpaid < 0) {
		dol_print_error($db, $expensereport->error);
		$sumpaid = 0;
	}

	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td><span class="amount">'.price($sumpaid, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans("RemainderToPay").'</td><td><span class="amount">'.price($total - $sumpaid, 0, $langs, 1, -1, -1, $conf->currency).'</span></td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	print '<br>';

	print dol_get_fiche_head();

	print '<table class="border centpercent">'."\n";

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Date").'</td><td colspan="2">';
	$datepaid = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
	$datepayment = ($datepaid == '' ? (getDolGlobalString('MAIN_AUTOFILL_DATE') ? '' : -1) : $datepaid);
	print $form->selectDate($datepayment, '', 0, 0, 0, "add_payment", 1, 1);
	print "</td>";
	print '</tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td colspan="2">';
	$form->select_types_paiements(GETPOSTISSET("fk_typepayment") ? GETPOST("fk_typepayment", 'alpha') : $expensereport->fk_c_paiement, "fk_typepayment");
	print "</td>\n";
	print '</tr>';

	if (isModEnabled("bank")) {
		print '<tr>';
		print '<td class="fieldrequired">'.$langs->trans('AccountToDebit').'</td>';
		print '<td colspan="2">';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		$form->select_comptes(GETPOSTISSET("accountid") ? GETPOSTINT("accountid") : 0, "accountid", 0, '', 2); // Show open bank account list
		print '</td></tr>';
	}

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '</td>';
	print '<td colspan="2"><input name="num_payment" type="text" value="'.GETPOST('num_payment').'"></td></tr>'."\n";

	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td valign="top" colspan="2"><textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_3.'"></textarea></td>';
	print '</tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '<br>';

	// List of expense reports of the same user not already paid completely
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ExpenseReport").'</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td class="center">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$total_ttc = 0;
	$totalrecu = 0;

	/*
	 * Get all approved and unpaid expense reports for the same user.
	 *
	 * The expense report used to enter this page is shown first.
	 */
	$sql = "SELECT";
	$sql .= " e.rowid,";
	$sql .= " e.ref,";
	$sql .= " e.total_ttc,";
	$sql .= " e.date_debut,";
	$sql .= " COALESCE(SUM(per.amount), 0) as total_paid";
	$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paymentexpensereport_expensereport as per";
	$sql .= " ON per.fk_expensereport = e.rowid";
	$sql .= " WHERE e.fk_user_author = ".((int) $expensereport->fk_user_author);
	$sql .= " AND e.entity = ".((int) $expensereport->entity);
	$sql .= " AND e.fk_statut = ".ExpenseReport::STATUS_APPROVED;
	$sql .= " AND e.paid = 0";
	$sql .= " GROUP BY e.rowid, e.ref, e.total_ttc, e.date_debut";
	$sql .= " HAVING (e.total_ttc - COALESCE(SUM(per.amount), 0)) > 0";
	$sql .= " ORDER BY";
	$sql .= " CASE WHEN e.rowid = ".((int) $expensereport->id)." THEN 0 ELSE 1 END,";
	$sql .= " e.date_debut DESC,";
	$sql .= " e.rowid DESC";

	$resql = $db->query($sql);

	if ($resql) {
		while ($objp = $db->fetch_object($resql)) {
			$tmpExpenseReport = new ExpenseReport($db);
			$tmpExpenseReport->id = $objp->rowid;
			$tmpExpenseReport->ref = $objp->ref;

			$sumpaidrow = (float) $objp->total_paid;
			$remaintopay = price2num($objp->total_ttc - $sumpaidrow, 'MT');

			print '<tr class="oddeven'.($objp->rowid == $expensereport->id ? ' highlight' : '').'">';

			print '<td>'.$tmpExpenseReport->getNomUrl(1).'</td>';

			print '<td class="right">'.price($objp->total_ttc).'</td>';

			print '<td class="right">'.price($sumpaidrow).'</td>';

			print '<td class="right">'.price($remaintopay).'</td>';

			print '<td class="center nowraponall">';

			$namef = 'amount_'.$objp->rowid;
			$nameRemain = 'remain_'.$objp->rowid;

			if ($remaintopay > 0) {
				if (!empty($conf->use_javascript_ajax)) {
					print img_picto(
						"Auto fill",
						'rightarrow.png',
						"class='AutoFillAmount' data-rowid='".$namef."' data-value='".$remaintopay."'"
					);
				}

				print '<input type="hidden" class="sum_remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';

				print '<input type="text" class="width75"';
				print ' name="'.$namef.'"';
				print ' id="'.$namef.'"';
				print ' value="'.dol_escape_htmltag(GETPOST($namef)).'">';
			} else {
				print '-';
			}

			print '</td>';

			print "</tr>\n";

			$total_ttc += (float) $objp->total_ttc;
			$totalrecu += $sumpaidrow;
			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	if ($i > 1) {
		print '<tr class="liste_total">';
		print '<td>'.$langs->trans("Total").'</td>';
		print '<td class="right"><b>'.price($total_ttc).'</b></td>';
		print '<td class="right"><b>'.price($totalrecu).'</b></td>';
		print '<td class="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
	}

	print '</table>';

	print $form->buttonsSaveCancel();

	print "</form>\n";
}

// End of page
llxFooter();
$db->close();
