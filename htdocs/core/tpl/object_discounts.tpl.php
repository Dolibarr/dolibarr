<?php
/* Copyright (C) 2018		ATM Consulting		<support@atm-consulting.fr>
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
 *
 * Needs the following variables defined:
 * $object					Proposal, order, invoice (including supplier versions)
 * $thirdparty				Thirdparty of object
 * $absolute_discount		Amount of fixed discounts available
 * $absolute_creditnote		Amount of credit notes available
 * $discount_type			0 => Customer discounts, 1 => Supplier discounts
 * $cannotApplyDiscount		Set it to prevent form to apply discount
 * $backtopage				URL to come back to from discount modification pages
 */

$objclassname = get_class($object);
$isInvoice = in_array($object->element, array('facture', 'invoice', 'facture_fourn', 'invoice_supplier'));
$isNewObject = empty($object->id) && empty($object->rowid);

// Relative and absolute discounts
$addrelativediscount = '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$thirdparty->id.'&backtopage='.$backtopage.'">'.$langs->trans("EditRelativeDiscount").'</a>';
$addabsolutediscount = '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$thirdparty->id.'&backtopage='.$backtopage.'">'.$langs->trans("EditGlobalDiscounts").'</a>';
$viewabsolutediscount = '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$thirdparty->id.'&backtopage='.$backtopage.'">'.$langs->trans("ViewAvailableGlobalDiscounts").'</a>';

$fixedDiscount = $thirdparty->remise_percent;
if (!empty($discount_type)) {
	$fixedDiscount = $thirdparty->remise_supplier_percent;
}

if ($fixedDiscount > 0)
{
	$translationKey = (!empty($discount_type)) ? 'HasRelativeDiscountFromSupplier' : 'CompanyHasRelativeDiscount';
	print $langs->trans($translationKey, $fixedDiscount).'.';
}
else
{
	$translationKey = (!empty($discount_type)) ? 'HasNoRelativeDiscountFromSupplier' : 'CompanyHasNoRelativeDiscount';
	print $langs->trans($translationKey).'.';
}
if ($isNewObject) print ' ('.$addrelativediscount.')';

// Is there is commercial discount or down payment available ?
if ($absolute_discount > 0) {
	if ($cannotApplyDiscount || !$isInvoice || $isNewObject || $object->statut > $objclassname::STATUS_DRAFT || $object->type == $objclassname::TYPE_CREDIT_NOTE || $object->type == $objclassname::TYPE_DEPOSIT) {
		$translationKey = !empty($discount_type) ? 'HasAbsoluteDiscountFromSupplier' : 'CompanyHasAbsoluteDiscount';
		$text = $langs->trans($translationKey, price($absolute_discount), $langs->transnoentities("Currency".$conf->currency)).'.';

		if ($isInvoice && !$isNewObject && $object->statut > $objclassname::STATUS_DRAFT && $object->type != $objclassname::TYPE_CREDIT_NOTE && $object->type != $objclassname::TYPE_DEPOSIT) {
			$text = $form->textwithpicto($text, $langs->trans('AbsoluteDiscountUse'));
		}

		if ($isNewObject) {
			$text .= ' ('.$addabsolutediscount.')';
		}

		print '<br>'.$text;
	} else {
		// Discount available of type fixed amount (not credit note)
		$more = '('.$addabsolutediscount.')';
		$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, GETPOST('discountid'), 'remise_id', $thirdparty->id, $absolute_discount, $filterabsolutediscount, $resteapayer, $more, 0, $discount_type);
	}
}

// Is there credit notes availables ?
if ($absolute_creditnote > 0) {
	// If validated, we show link "add credit note to payment"
	if ($cannotApplyDiscount || !$isInvoice || $isNewObject || $object->statut != $objclassname::STATUS_VALIDATED || $object->type == $objclassname::TYPE_CREDIT_NOTE) {
		$translationKey = !empty($discount_type) ? 'HasCreditNoteFromSupplier' : 'CompanyHasCreditNote';
		$text = $langs->trans($translationKey, price($absolute_creditnote), $langs->transnoentities("Currency".$conf->currency)).'.';

		if ($isInvoice && !$isNewObject && $object->statut == $objclassname::STATUS_DRAFT && $object->type != $objclassname::TYPE_DEPOSIT) {
			$text = $form->textwithpicto($text, $langs->trans('CreditNoteDepositUse'));
		}

		if ($absolute_discount <= 0 || $isNewObject) {
			$text .= '('.$addabsolutediscount.')';
		}

		print '<br>'.$text;
	} else {  // We can add a credit note on a down payment or standard invoice or situation invoice
		// There is credit notes discounts available
		$more = $isInvoice && !$isNewObject ? ' ('.$viewabsolutediscount.')' : '';
		$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $thirdparty->id, $absolute_creditnote, $filtercreditnote, 0, $more, 0, $discount_type); // We allow credit note even if amount is higher
	}
}

if ($absolute_discount <= 0 && $absolute_creditnote <= 0) {
	$translationKey = !empty($discount_type) ? 'HasNoAbsoluteDiscountFromSupplier' : 'CompanyHasNoAbsoluteDiscount';
	print '<br>'.$langs->trans($translationKey).'.';

	if ($isInvoice && $object->statut == $objclassname::STATUS_DRAFT && $object->type != $objclassname::TYPE_CREDIT_NOTE && $object->type != $objclassname::TYPE_DEPOSIT) {
		print ' ('.$addabsolutediscount.')';
	}
}
