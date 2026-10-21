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
 * \brief      Document transfer actions shared by document pages
 */

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 * @var CommonObject $object
 * @var string $action
 * @var string $documentTransferSourceType
 */

require_once DOL_DOCUMENT_ROOT.'/ecm/class/documenttransfer.class.php';

$documentTransferSourceType = (string) ($documentTransferSourceType ?? '');
$documentTransferPreview = array();
if ($documentTransferSourceType === '') {
	return;
}
if ($action === 'previewtransfer' || $action === 'confirmtransfer') {
	$langs->load('ecm');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		setEventMessages($langs->trans('ECMTransferInvalidInput'), null, 'errors');
	} else {
		$transfer = new DocumentTransfer($db);
		$filename = GETPOST('transfer_filename', 'alpha');
		$targetType = GETPOST('transfer_target_type', 'aZ09arobase');
		$targetId = GETPOSTINT('transfer_target_id');
		if ($action === 'previewtransfer') {
			$destination = GETPOST('transfer_destination', 'alpha');
			if (preg_match('/^([a-z][a-z0-9_]*):([1-9][0-9]*)$/', $destination, $matches)) {
				$targetType = $matches[1];
				$targetId = (int) $matches[2];
			} else {
				$targetId = 0;
			}
		}
		$targetRef = GETPOST('transfer_target_ref', 'alpha');
		$sourceType = $documentTransferSourceType;
		if ($targetId <= 0) {
			setEventMessages($langs->trans('ECMTransferNotFound'), null, 'errors');
		} elseif ($action === 'previewtransfer') {
			$checked = $transfer->inspect($sourceType, (int) $object->id, $targetType, $targetId, $filename, $user);
			if ($checked === false) {
				setEventMessages($langs->trans($transfer->error), null, 'errors');
			} else {
				$documentTransferPreview = $checked;
			}
		} else {
			$expectedHash = GETPOST('transfer_sha256', 'alpha');
			$result = $transfer->move($sourceType, (int) $object->id, $targetType, $targetId, $filename, $user, $expectedHash, $targetRef);
			if ($result === false) {
				setEventMessages($langs->trans($transfer->error), null, 'errors');
			} else {
				setEventMessages($langs->trans('ECMTransferDone', $result['filename'], $result['target_ref']), null);
			}
		}
	}
	$action = '';
}
