<?php
/* Copyright (C) 2026 braito4 <braito4@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/tpl/document_transfer_order_invoice.tpl.php
 * \brief      Transfer an order attachment to a related invoice
 */

/**
 * @var Translate $langs
 * @var User $user
 * @var CommonObject $object
 * @var array<int,array<string,mixed>> $filearray
 * @var array<string,mixed> $documentTransferPreview
 * @var string $documentTransferOrderType
 */

$canTransfer = $documentTransferOrderType === 'customer'
	? ($user->hasRight('commande', 'creer') && $user->hasRight('facture', 'lire') && $user->hasRight('facture', 'creer'))
	: ($user->hasRight('fournisseur', 'commande', 'creer')
		&& $user->hasRight('fournisseur', 'facture', 'lire') && $user->hasRight('fournisseur', 'facture', 'creer'));
$transferableFiles = array();
foreach ($filearray as $file) {
	if (!empty($file['name']) && $file['name'] !== dol_sanitizeFileName($object->ref).'.pdf') {
		$transferableFiles[] = $file['name'];
	}
}
if ($canTransfer && !empty($transferableFiles)) {
	$langs->load('ecm');
	print load_fiche_titre($langs->trans('ECMTransferToInvoice'), '', 'file');
	print '<p class="opacitymedium">'.$langs->trans('ECMTransferExplanation').'</p>';

	if (!empty($documentTransferPreview)) {
		print '<div class="info">';
		print $langs->trans('ECMTransferPreview',
			dol_escape_htmltag($documentTransferPreview['filename']),
			dol_escape_htmltag($documentTransferPreview['order_ref']),
			dol_escape_htmltag($documentTransferPreview['invoice_ref']));
		print '</div>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirmtransfer">';
		print '<input type="hidden" name="transfer_filename" value="'.dolPrintHTMLForAttribute($documentTransferPreview['filename']).'">';
		print '<input type="hidden" name="transfer_invoice_id" value="'.((int) $documentTransferPreview['invoice_id']).'">';
		print '<input type="hidden" name="transfer_invoice_ref" value="'.dolPrintHTMLForAttribute($documentTransferPreview['invoice_ref']).'">';
		print '<input type="hidden" name="transfer_sha256" value="'.dolPrintHTMLForAttribute($documentTransferPreview['sha256']).'">';
		print '<div class="center"><input type="submit" class="button button-save" value="'.dolPrintHTMLForAttribute($langs->trans('ECMTransferConfirm')).'"></div>';
		print '</form>';
	}

	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="previewtransfer">';
	print '<table class="border centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans('ECMTransferChooseFile').'</td><td>';
	print '<select class="flat minwidth300" name="transfer_filename" required>';
	print '<option value=""></option>';
	foreach ($transferableFiles as $filename) {
		print '<option value="'.dolPrintHTMLForAttribute($filename).'">'.dol_escape_htmltag($filename).'</option>';
	}
	print '</select></td></tr>';
	print '<tr><td>'.$langs->trans('ECMTransferInvoiceRef').'</td><td>';
	print '<input class="flat minwidth300" type="text" name="transfer_invoice_ref" required>';
	print '</td></tr>';
	print '</table>';
	print '<div class="center"><input type="submit" class="button" value="'.dolPrintHTMLForAttribute($langs->trans('ECMTransferCheck')).'"></div>';
	print '</form>';
}
