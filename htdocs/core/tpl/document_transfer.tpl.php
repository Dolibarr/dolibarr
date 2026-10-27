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
 * \file       htdocs/core/tpl/document_transfer.tpl.php
 * \brief      Transfer an attachment to another document-bearing object
 */

/**
 * @var Translate $langs
 * @var User $user
 * @var CommonObject $object
 * @var array<int,array<string,mixed>> $filearray
 * @var array<string,mixed> $documentTransferPreview
 * @var string $documentTransferSourceType
 */

$documentTransferSourceType = (string) ($documentTransferSourceType ?? '');
$canTransfer = !empty($permissiontoadd);
$targetTypes = array(
	'invoice' => 'Invoice',
	'supplier_invoice' => 'SupplierInvoice',
	'order' => 'Order',
	'supplier_order' => 'SupplierOrder',
	'propal' => 'Proposal',
	'supplier_proposal' => 'SupplierProposal',
	'project' => 'Project',
	'project_task' => 'Task',
	'contract' => 'Contract',
	'shipment' => 'Shipment',
	'ticket' => 'Ticket'
);
$transferableFiles = array();
foreach ((array) ($filearray ?? array()) as $file) {
	if (!empty($file['name']) && $file['name'] !== dol_sanitizeFileName($object->ref).'.pdf') {
		$transferableFiles[] = $file['name'];
	}
}
if ($canTransfer && !empty($transferableFiles)) {
	$langs->loadLangs(array('ecm', 'propal', 'supplier_proposal', 'projects', 'contracts', 'sendings', 'ticket'));
	$transfer = new DocumentTransfer($object->db);
	$relatedDestinations = $transfer->getRelatedDestinations($object, $transferableFiles[0], $user);
	print load_fiche_titre($langs->trans('ECMTransferToObject'), '', 'file');
	print '<p class="opacitymedium">'.$langs->trans('ECMTransferExplanation').'</p>';

	if (!empty($documentTransferPreview)) {
		$sourceLabel = $langs->transnoentitiesnoconv($targetTypes[$documentTransferSourceType] ?? 'Object');
		$targetLabel = $langs->transnoentitiesnoconv($targetTypes[$documentTransferPreview['target_type']] ?? 'Object');
		print '<div class="info">';
		print dol_escape_htmltag($langs->transnoentitiesnoconv('ECMTransferPreview',
			$documentTransferPreview['filename'],
			$sourceLabel,
			$documentTransferPreview['source_ref'],
			$targetLabel,
			$documentTransferPreview['target_ref']));
		print '</div>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?id='.((int) $object->id).'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirmtransfer">';
		print '<input type="hidden" name="transfer_filename" value="'.dolPrintHTMLForAttribute($documentTransferPreview['filename']).'">';
		print '<input type="hidden" name="transfer_target_type" value="'.dolPrintHTMLForAttribute($documentTransferPreview['target_type']).'">';
		print '<input type="hidden" name="transfer_target_id" value="'.((int) $documentTransferPreview['target_id']).'">';
		print '<input type="hidden" name="transfer_target_ref" value="'.dolPrintHTMLForAttribute($documentTransferPreview['target_ref']).'">';
		print '<input type="hidden" name="transfer_sha256" value="'.dolPrintHTMLForAttribute($documentTransferPreview['sha256']).'">';
		print '<div class="center"><input type="submit" class="button button-save" value="'.dolPrintHTMLForAttribute($langs->trans('ECMTransferConfirm')).'"></div>';
		print '</form>';
	}

	if ($relatedDestinations === false) {
		print '<div class="error">'.$langs->trans($transfer->error).'</div>';
	} elseif (empty($relatedDestinations)) {
		print '<div class="opacitymedium">'.$langs->trans('ECMTransferNoRelatedDestination').'</div>';
	} else {
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
		print '<tr><td>'.$langs->trans('ECMTransferRelatedDestination').'</td><td>';
		print '<select class="flat minwidth300" name="transfer_destination" required>';
		print '<option value=""></option>';
		foreach ($relatedDestinations as $destination) {
			$typeLabel = $langs->trans($targetTypes[$destination['type']] ?? 'Object');
			print '<option value="'.dolPrintHTMLForAttribute($destination['type'].':'.$destination['id']).'">';
			print dol_escape_htmltag($typeLabel.' '.$destination['ref']);
			print '</option>';
		}
		print '</select></td></tr>';
		print '</table>';
		print '<div class="center"><input type="submit" class="button" value="'.dolPrintHTMLForAttribute($langs->trans('ECMTransferCheck')).'"></div>';
		print '</form>';
	}
}
