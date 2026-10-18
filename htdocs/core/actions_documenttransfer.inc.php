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
 * \file       htdocs/core/actions_documenttransfer.inc.php
 * \brief      Order document transfer actions shared by both order document pages
 */

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 * @var CommonObject $object
 * @var string $action
 * @var string $documentTransferOrderType
 */

require_once DOL_DOCUMENT_ROOT.'/ecm/class/documenttransfer.class.php';

$documentTransferPreview = array();
if ($action === 'previewtransfer' || $action === 'confirmtransfer') {
	$langs->load('ecm');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		setEventMessages($langs->trans('ECMTransferInvalidInput'), null, 'errors');
	} else {
		$transfer = new DocumentTransfer($db);
		$filename = GETPOST('transfer_filename', 'alpha');
		$invoiceId = GETPOSTINT('transfer_invoice_id');
		$invoiceRef = GETPOST('transfer_invoice_ref', 'alpha');
		if ($action === 'previewtransfer') {
			if ($documentTransferOrderType === 'customer') {
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$invoice = new Facture($db);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$invoice = new FactureFournisseur($db);
			}
			$invoiceId = ($invoiceRef !== '' && $invoice->fetch(0, $invoiceRef) > 0 ? (int) $invoice->id : 0);
		}
		if ($action === 'confirmtransfer' && $invoiceRef === '') {
			setEventMessages($langs->trans('ECMTransferInvalidInput'), null, 'errors');
		} elseif ($invoiceId <= 0) {
			setEventMessages($langs->trans('ECMTransferNotFound'), null, 'errors');
		} elseif ($action === 'previewtransfer') {
			$checked = $transfer->inspectOrderToInvoice($documentTransferOrderType, (int) $object->id, $invoiceId, $filename, $user);
			if ($checked === false) {
				setEventMessages($langs->trans($transfer->error), null, 'errors');
			} else {
				$documentTransferPreview = $checked;
			}
		} else {
			$expectedHash = GETPOST('transfer_sha256', 'alpha');
			$result = $transfer->moveOrderToInvoice($documentTransferOrderType, (int) $object->id, $invoiceId, $filename, $user, $expectedHash, $invoiceRef);
			if ($result === false) {
				setEventMessages($langs->trans($transfer->error), null, 'errors');
			} else {
				setEventMessages($langs->trans('ECMTransferDone', $result['filename'], $result['invoice_ref']), null);
			}
		}
	}
	$action = '';
}
