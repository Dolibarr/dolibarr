<?php
/* Copyright (C) 2024  Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * $usercancreate			Permission to edit object
 * $action
 * $form
 * $conf
 * $langs
 */

print '<!-- BEGIN object_currency_amount.tpl.php -->'."\n";

// Multicurrency
if (isModEnabled('multicurrency')) {
	$colspan = 1;
	if (isModEnabled("multicurrency") && ($object->multicurrency_code && $object->multicurrency_code != $conf->currency)) {
		$colspan = 2;
	}
	if ($object instanceof FactureFournisseurRec || $object instanceof FactureRec) {
		$currencyIsEditable = ($object->suspended == $object::STATUS_SUSPENDED);
		$colspan = 1;
	} else {
		$currencyIsEditable = ($object->status == $object::STATUS_DRAFT);
	}

	// Multicurrency code
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $form->editfieldkey('Currency', 'multicurrency_code', '', $object, 0);
	print '</td>';
	if ($usercancreate && $action != 'editmulticurrencycode' && $currencyIsEditable) {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencycode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td'.($colspan == 2 ? ' colspan="2"' : '').'>';
	$htmlname = (($usercancreate && $action == 'editmulticurrencycode' && $currencyIsEditable) ? 'multicurrency_code' : 'none');
	$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, $htmlname);
	print '</td></tr>';

	// Multicurrency rate
	if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1) {
		print '<tr>';
		print '<td>';
		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $form->editfieldkey('CurrencyRate', 'multicurrency_tx', '', $object, 0);
		print '</td>';
		if ($usercancreate && $action != 'editmulticurrencyrate' && $currencyIsEditable && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmulticurrencyrate&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td'.($colspan == 2 ? ' colspan="2"' : '').'>';
		if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
			if ($action == 'actualizemulticurrencyrate') {
				list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
			}
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, ($usercancreate ? 'multicurrency_tx' : 'none'), $object->multicurrency_code);
		} else {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
			if ($object->status == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
				print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate&token='.newToken().'" title="'.$langs->trans("ActualizeCurrency").'">'.$langs->trans("ActualizeCurrency").'</a>';
				print '</div>';
			}
		}
		print '</td></tr>';
	}
}

print '<!-- END template -->';
