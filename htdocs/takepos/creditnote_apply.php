<?php
/* Copyright (C) 2026  Dolibarr contributors
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
 * \file       htdocs/takepos/creditnote_apply.php
 * \ingroup    takepos
 * \brief      Page (colorbox iframe) to apply an available customer credit to the current POS invoice.
 *             Supports automatic split when the credit exceeds the remaining amount to pay.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->loadLangs(array('main', 'bills', 'cashdesk', 'banks'));

$action = GETPOST('action', 'aZ09');
$invoiceid = GETPOSTINT('invoiceid');
$discountid = GETPOSTINT('discountid');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0');

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden();
}


/*
 * Actions
 */

$error = 0;
$successmsg = '';

if ($action == 'confirm_applycredit' && $discountid > 0 && !empty($_POST['token'])) {
	// Apply the selected credit to the invoice (with auto-split if needed)
	$db->begin();

	$invoice = new Facture($db);
	$resf = $invoice->fetch($invoiceid);
	if ($resf <= 0) {
		$error++;
		setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
	}

	$discount = new DiscountAbsolute($db);
	$resd = $discount->fetch($discountid);
	if ($resd <= 0 || $discount->fk_soc != $invoice->socid) {
		// Discount not found or does not belong to this customer
		$error++;
		setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
	}

	if (!$error && $discount->fk_facture > 0) {
		// Already consumed
		$error++;
		setEventMessages($langs->trans('ErrorDiscountAlreadyUsed'), null, 'errors');
	}

	// No validation needed for insert_discount. We add the discount as a line to the draft invoice.

	if (!$error) {
		$remaintopay = (float) price2num($invoice->getRemainToPay(0), 'MT');
		$creditamount = (float) price2num($discount->amount_ttc, 'MT');

		if ($remaintopay <= 0) {
			$error++;
			setEventMessages($langs->trans('TakeposNoRemainToPay'), null, 'errors');
		} elseif ($creditamount > $remaintopay) {
			// Auto split: credit is larger than remain_to_pay
			$remainder = (float) price2num($creditamount - $remaintopay, 'MT');

			$newDiscounts = $discount->splitAmount($remaintopay, $remainder);
			/** @var DiscountAbsolute $newDiscount1 */
			$newDiscount1 = $newDiscounts[0];
			/** @var DiscountAbsolute $newDiscount2 */
			$newDiscount2 = $newDiscounts[1];

			// Delete the original before creating the two parts (same pattern as remx.php)
			$discount->fk_facture_source = 0;
			$discount->fk_invoice_supplier_source = 0;
			$res = $discount->delete($user);
			if ($res <= 0) {
				$error++;
				setEventMessages($discount->error, $discount->errors, 'errors');
			}

			if (!$error) {
				$newid1 = $newDiscount1->create($user);
				if ($newid1 <= 0) {
					$error++;
					setEventMessages($newDiscount1->error, $newDiscount1->errors, 'errors');
				}
			}

			if (!$error) {
				$newid2 = $newDiscount2->create($user);
				if ($newid2 <= 0) {
					$error++;
					setEventMessages($newDiscount2->error, $newDiscount2->errors, 'errors');
				}
			}

			if (!$error) {
				// Apply the first part (= remaintopay) to the invoice as a line
				$result = $invoice->insert_discount($newid1);
				if ($result < 0) {
					$error++;
					setEventMessages($invoice->error, $invoice->errors, 'errors');
				}
			}
		} else {
			// Apply directly without split as a line
			$result = $invoice->insert_discount($discountid);
			if ($result < 0) {
				$error++;
				setEventMessages($invoice->error, $invoice->errors, 'errors');
			}
		}
	}

	if (!$error) {
		$db->commit();
		$successmsg = $langs->trans('TakeposCreditApplied');
		dol_syslog('creditnote_apply.php: credit '.$discountid.' applied to invoice '.$invoiceid, LOG_DEBUG);
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$head = '<link rel="stylesheet" href="css/pos.css.php">';
if (getDolGlobalInt('TAKEPOS_COLOR_THEME') == 1) {
	$head .= '<link rel="stylesheet" href="css/colorful.css">';
}

top_htmlhead($head, $langs->trans('TakeposApplyAvailableCredit'), 0, 0, array(), array('/takepos/css/pos.css.php'));
?>
<body>
<?php

// Load current invoice to get customer
$invoice = new Facture($db);
if ($invoiceid > 0) {
	$invoice->fetch($invoiceid);
}

// Show flash messages
dol_htmloutput_events();

// If credit was applied successfully, close colorbox and reload parent #poslines
if ($successmsg) {
	?>
	<div style="padding:20px; text-align:center;">
		<p class="ok"><?php echo dol_escape_htmltag($successmsg); ?></p>
	</div>
	<script>
	// Reload parent invoice view and close this colorbox
	parent.$("#poslines").load("invoice.php?place=<?php echo dol_escape_js($place); ?>&invoiceid=<?php echo $invoiceid; ?>&token=<?php echo currentToken(); ?>", function() {
		parent.$.colorbox.close();
	});
	</script>
	<?php
	llxFooter();
	exit;
}

// List available credits for this customer
$availableDiscounts = array();
if ($invoice->id > 0 && $invoice->socid > 0) {
	$sql = 'SELECT rowid, amount_ht, amount_tva, amount_ttc, description, fk_facture_source, datec, tva_tx';
	$sql .= ' FROM '.$db->prefix().'societe_remise_except';
	$sql .= ' WHERE entity = '.((int) $conf->entity);
	$sql .= ' AND discount_type = 0';
	$sql .= ' AND fk_soc = '.((int) $invoice->socid);
	$sql .= ' AND fk_facture IS NULL';
	$sql .= ' AND fk_facture_line IS NULL';
	$sql .= ' ORDER BY datec ASC';

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$availableDiscounts[] = $obj;
		}
	} else {
		dol_print_error($db);
	}
}

// Confirmation step: a credit was selected, show details before executing
if ($action == 'applycredit' && $discountid > 0 && empty($error)) {
	$discountToConfirm = new DiscountAbsolute($db);
	$discountToConfirm->fetch($discountid);

	$remaintopay = ($invoice->id > 0) ? (float) price2num($invoice->getRemainToPay(0), 'MT') : 0;
	$creditamount = (float) price2num($discountToConfirm->amount_ttc, 'MT');

	$willSplit = ($creditamount > $remaintopay);

	?>
	<div style="padding:20px;">
		<h3><?php echo $langs->trans('TakeposConfirmApplyCredit'); ?></h3>

		<?php if ($remaintopay <= 0) { ?>
			<p class="error"><?php echo $langs->trans('TakeposNoRemainToPay'); ?></p>
			<div style="text-align:center; margin-top:20px;">
				<button type="button" class="butActionDelete" onclick="parent.$.colorbox.close();">
					<?php echo $langs->trans('Close'); ?>
				</button>
			</div>
		<?php } else { ?>
			<?php if ($willSplit) {
				$remainder = price2num($creditamount - $remaintopay, 'MT');
				echo '<p class="warning">'.sprintf(
					$langs->transnoentities('TakeposCreditSplitAuto'),
					'<strong>'.price($remaintopay, 1, $langs, 1, -1, -1, $conf->currency).'</strong>',
					'<strong>'.price($remainder, 1, $langs, 1, -1, -1, $conf->currency).'</strong>'
				).'</p>';
			} ?>

		<table class="noborder" style="width:100%;">
			<tr class="liste_titre">
				<td><?php echo $langs->trans('TakeposCreditOrigin'); ?></td>
				<td class="right"><?php echo $langs->trans('TakeposCreditAmount'); ?></td>
				<td class="center"><?php echo $langs->trans('RemainToPay'); ?></td>
			</tr>
			<tr class="oddeven">
				<td>
					<?php
					if ($discountToConfirm->description == '(CREDIT_NOTE)') {
						echo $langs->trans('CreditNote');
					} elseif ($discountToConfirm->description == '(DEPOSIT)') {
						echo $langs->trans('Deposit');
					} else {
						echo dol_escape_htmltag($discountToConfirm->description);
					}
					if ($discountToConfirm->fk_facture_source > 0) {
						$srcInv = new Facture($db);
						$srcInv->fetch($discountToConfirm->fk_facture_source);
						echo ' — '.dol_escape_htmltag($srcInv->ref);
					}
					?>
				</td>
				<td class="right"><strong><?php echo price($creditamount, 1, $langs, 1, -1, -1, $conf->currency); ?></strong></td>
				<td class="center"><?php echo price($remaintopay, 1, $langs, 1, -1, -1, $conf->currency); ?></td>
			</tr>
		</table>

		<br>

		<form method="POST" action="creditnote_apply.php">
			<input type="hidden" name="token" value="<?php echo newToken(); ?>">
			<input type="hidden" name="action" value="confirm_applycredit">
			<input type="hidden" name="invoiceid" value="<?php echo $invoiceid; ?>">
			<input type="hidden" name="discountid" value="<?php echo $discountid; ?>">
			<input type="hidden" name="place" value="<?php echo dol_escape_htmltag($place); ?>">

			<div style="text-align:center; margin-top:20px;">
				<button type="submit" class="butAction">
					<?php echo $langs->trans('Confirm'); ?>
				</button>
				&nbsp;
				<button type="button" class="butActionDelete" onclick="parent.$.colorbox.close();">
					<?php echo $langs->trans('Cancel'); ?>
				</button>
			</div>
		</form>
		<?php } ?>
	</div>
	<?php

	llxFooter();
	exit;
}

// Default view: list of available credits
?>
<div style="padding:15px;">
	<h3><?php echo $langs->trans('TakeposSelectCreditToApply'); ?></h3>

	<?php if (empty($availableDiscounts)) { ?>
		<p class="opacitymedium"><?php echo $langs->trans('TakeposNoCreditAvailable'); ?></p>
	<?php } else { ?>
		<table class="noborder" style="width:100%;">
			<tr class="liste_titre">
				<td><?php echo $langs->trans('TakeposCreditOrigin'); ?></td>
				<td class="center"><?php echo $langs->trans('TakeposCreditDate'); ?></td>
				<td class="right"><?php echo $langs->trans('TakeposCreditAmount'); ?></td>
				<td class="center"><?php echo $langs->trans('Action'); ?></td>
			</tr>
			<?php foreach ($availableDiscounts as $disc) {
				$originLabel = '';
				if ($disc->description == '(CREDIT_NOTE)') {
					$originLabel = $langs->trans('CreditNote');
				} elseif ($disc->description == '(DEPOSIT)') {
					$originLabel = $langs->trans('Deposit');
				} elseif ($disc->description == '(EXCESS RECEIVED)') {
					$originLabel = $langs->trans('ExcessReceived');
				} else {
					$originLabel = dol_escape_htmltag($disc->description);
				}
				if ($disc->fk_facture_source > 0) {
					$srcInv = new Facture($db);
					$srcInv->fetch($disc->fk_facture_source);
					$originLabel .= ' — '.dol_escape_htmltag($srcInv->ref);
				}
				?>
				<tr class="oddeven">
					<td><?php echo $originLabel; ?></td>
					<td class="center"><?php echo dol_print_date($db->jdate($disc->datec), 'day'); ?></td>
					<td class="right"><strong><?php echo price($disc->amount_ttc, 1, $langs, 1, -1, -1, $conf->currency); ?></strong></td>
					<td class="center">
						<?php
						$remaintopay = ($invoice->id > 0) ? (float) price2num($invoice->getRemainToPay(0), 'MT') : 0;
						if ($remaintopay <= 0) {
							echo '<span class="opacitymedium">'.$langs->trans('TakeposNoRemainToPay').'</span>';
						} else {
							?>
							<form method="POST" action="creditnote_apply.php" style="display:inline;">
								<input type="hidden" name="token" value="<?php echo newToken(); ?>">
								<input type="hidden" name="action" value="applycredit">
								<input type="hidden" name="invoiceid" value="<?php echo $invoiceid; ?>">
								<input type="hidden" name="discountid" value="<?php echo (int) $disc->rowid; ?>">
								<input type="hidden" name="place" value="<?php echo dol_escape_htmltag($place); ?>">
								<button type="submit" class="butAction" style="padding:4px 10px;">
									<?php echo $langs->trans('TakeposApplyThisCredit'); ?>
								</button>
							</form>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
		</table>
	<?php } ?>

	<div style="text-align:center; margin-top:20px;">
		<button type="button" class="butActionDelete" onclick="parent.$.colorbox.close();">
			<?php echo $langs->trans('Close'); ?>
		</button>
	</div>
</div>
</body>
</html>
<?php
llxFooter();
