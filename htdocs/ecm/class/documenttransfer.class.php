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
 * \file       htdocs/ecm/class/documenttransfer.class.php
 * \ingroup    ecm
 * \brief      Checked transfer of an order attachment to a related invoice
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

/**
 * Transfer one attachment while retaining its ECM metadata.
 */
class DocumentTransfer
{
	/** @var DoliDB */
	private $db;

	/** @var string Translation key for the last error */
	public $error = '';

	/** @var string Machine-readable reason for the last error */
	public $reason = '';

	/** @var int HTTP status suitable for a future REST adapter */
	public $status = 0;

	/**
	 * @param DoliDB $db Database connection
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @param int $status HTTP status
	 * @param string $key Translation key
	 * @param string $reason Stable reason
	 * @return false
	 */
	private function fail($status, $key, $reason)
	{
		$this->status = $status;
		$this->error = $key;
		$this->reason = $reason;
		dol_syslog(__METHOD__.' '.$reason, LOG_WARNING);
		return false;
	}

	/**
	 * Remove an uncommitted invoice copy after a failed transfer.
	 *
	 * @param string $targetPath Invoice copy
	 * @param string $reason Failure reason
	 * @param int $status HTTP status when cleanup succeeds
	 * @param string $key Translation key when cleanup succeeds
	 * @return false
	 */
	private function discardCopy($targetPath, $reason, $status = 500, $key = 'ECMTransferFailed')
	{
		dol_delete_file($targetPath, 1, 1, 1, null, false, 0);
		return $this->fail(dol_is_file($targetPath) ? 500 : $status, dol_is_file($targetPath) ? 'ECMTransferManualRecovery' : $key, $reason);
	}

	/**
	 * Check a transfer without changing files or database rows.
	 *
	 * @param string $orderType 'customer' or 'supplier'
	 * @param int $orderId Order rowid
	 * @param int $invoiceId Related invoice rowid
	 * @param string $filename Exact filename in the order directory
	 * @param User $user Acting user
	 * @return array<string,mixed>|false Checked transfer context
	 */
	public function inspectOrderToInvoice($orderType, $orderId, $invoiceId, $filename, User $user)
	{
		global $conf;

		$this->error = '';
		$this->reason = '';
		$this->status = 0;
		$orderId = (int) $orderId;
		$invoiceId = (int) $invoiceId;
		$filename = (string) $filename;
		if (!in_array($orderType, array('customer', 'supplier'), true) || $orderId <= 0 || $invoiceId <= 0
			|| $filename === '' || $filename !== dol_basename($filename) || $filename !== dol_sanitizeFileName($filename)
			|| $filename[0] === '.' || preg_match('/[<>|\\\\\/\x00-\x1f]/', $filename)) {
			return $this->fail(400, 'ECMTransferInvalidInput', 'invalid_input');
		}

		if ($orderType === 'customer') {
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$order = new Commande($this->db);
			$invoice = new Facture($this->db);
			$sourcepart = 'order';
			$targetpart = 'invoice';
			$hasRights = $user->hasRight('commande', 'lire') && $user->hasRight('commande', 'creer')
				&& $user->hasRight('facture', 'lire') && $user->hasRight('facture', 'creer');
		} else {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$order = new CommandeFournisseur($this->db);
			$invoice = new FactureFournisseur($this->db);
			$sourcepart = 'supplier_order';
			$targetpart = 'supplier_invoice';
			$hasRights = $user->hasRight('fournisseur', 'commande', 'lire') && $user->hasRight('fournisseur', 'commande', 'creer')
				&& $user->hasRight('fournisseur', 'facture', 'lire') && $user->hasRight('fournisseur', 'facture', 'creer');
		}
		if (!$hasRights) {
			return $this->fail(403, 'ECMTransferDenied', 'document_rights_required');
		}
		if ($order->fetch($orderId) <= 0 || $invoice->fetch($invoiceId) <= 0) {
			return $this->fail(404, 'ECMTransferNotFound', 'order_or_invoice_missing');
		}
		if ((int) $order->entity !== (int) $invoice->entity || (int) $order->entity !== (int) $conf->entity
			|| (int) $order->socid !== (int) $invoice->socid
			|| !checkUserAccessToObject($user, array($order->element), $order, $order->table_element, '')
			|| !checkUserAccessToObject($user, array($invoice->element), $invoice, $invoice->table_element, '')) {
			return $this->fail(403, 'ECMTransferDenied', 'order_invoice_mismatch_or_inaccessible');
		}

		$sql = 'SELECT rowid FROM '.$this->db->prefix().'element_element';
		$sql .= ' WHERE (fk_source = '.$orderId." AND sourcetype = '".$this->db->escape($order->element)."'"
			.' AND fk_target = '.$invoiceId." AND targettype = '".$this->db->escape($invoice->element)."')"
			.' OR (fk_target = '.$orderId." AND targettype = '".$this->db->escape($order->element)."'"
			.' AND fk_source = '.$invoiceId." AND sourcetype = '".$this->db->escape($invoice->element)."') LIMIT 1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $this->fail(500, 'ECMTransferFailed', 'link_check_failed');
		}
		if (!$this->db->fetch_object($resql)) {
			return $this->fail(409, 'ECMTransferConflict', 'order_invoice_not_linked');
		}

		$sourceRef = dol_sanitizeFileName((string) $order->ref);
		$targetRef = dol_sanitizeFileName((string) $invoice->ref);
		if ($filename === $sourceRef.'.pdf') {
			return $this->fail(409, 'ECMTransferConflict', 'generated_order_pdf');
		}
		$sourceRelative = $sourceRef.'/'.$filename;
		$targetRelative = ($orderType === 'supplier' ? get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier') : '').$targetRef.'/'.$filename;
		$sourceAccess = dol_check_secure_access_document($sourcepart, $sourceRelative, (int) $order->entity, $user, (string) $order->ref, 'write');
		$targetAccess = dol_check_secure_access_document($targetpart, $targetRelative, (int) $invoice->entity, $user, (string) $invoice->ref, 'write');
		if (!is_array($sourceAccess) || !is_array($targetAccess)
			|| empty($sourceAccess['accessallowed']) || empty($targetAccess['accessallowed'])
			|| !checkUserAccessToDocumentObject($user, $sourcepart, $sourceRelative, 'write')
			|| !checkUserAccessToDocumentObject($user, $targetpart, $targetRelative, 'write')) {
			return $this->fail(403, 'ECMTransferDenied', 'attachment_access_denied');
		}
		$sourcePath = (string) $sourceAccess['original_file'];
		$targetPath = (string) $targetAccess['original_file'];
		if (strpos($sourcePath, rtrim(DOL_DATA_ROOT, '/').'/') !== 0
			|| strpos($targetPath, rtrim(DOL_DATA_ROOT, '/').'/') !== 0) {
			return $this->fail(409, 'ECMTransferConflict', 'storage_not_in_ecm');
		}
		if (!dol_is_file($sourcePath)) {
			return $this->fail(404, 'ECMTransferNotFound', 'source_file_missing');
		}
		if (dol_is_file($targetPath)) {
			return $this->fail(409, 'ECMTransferConflict', 'target_file_exists');
		}

		$sourceHash = hash_file('sha256', dol_osencode($sourcePath));
		if ($sourceHash === false) {
			return $this->fail(500, 'ECMTransferFailed', 'source_hash_failed');
		}
		$sourceEcmPath = ltrim(dol_substr($sourcePath, dol_strlen(DOL_DATA_ROOT)), '/');
		$targetEcmPath = ltrim(dol_substr($targetPath, dol_strlen(DOL_DATA_ROOT)), '/');
		$ecm = new EcmFiles($this->db);
		$found = $ecm->fetch(0, '', $sourceEcmPath, '', '', '', 0, (int) $order->entity);
		if ($found < 0) {
			return $this->fail(500, 'ECMTransferFailed', 'source_index_lookup_failed');
		}
		if ($found > 0) {
			$boundToOrder = (int) $ecm->src_object_id === $orderId && $ecm->src_object_type === $order->table_element;
			$unbound = empty($ecm->src_object_id) && empty($ecm->src_object_type);
			if ((int) $ecm->entity !== (int) $order->entity || (!$boundToOrder && !$unbound)) {
				return $this->fail(409, 'ECMTransferConflict', 'source_index_wrong_order');
			}
			if (!empty($ecm->share)) {
				return $this->fail(409, 'ECMTransferConflict', 'shared_attachment_review_required');
			}
			if ($ecm->gen_or_uploaded === 'generated') {
				return $this->fail(409, 'ECMTransferConflict', 'generated_order_document');
			}
		} elseif (getDolGlobalString('MAIN_FORCE_SHARING_ON_ANY_UPLOADED_FILE')) {
			return $this->fail(409, 'ECMTransferConflict', 'unindexed_file_would_be_shared');
		}
		$targetEcm = new EcmFiles($this->db);
		if ($targetEcm->fetch(0, '', $targetEcmPath, '', '', '', 0, (int) $invoice->entity) !== 0) {
			return $this->fail(409, 'ECMTransferConflict', 'target_index_exists_or_failed');
		}

		return array(
			'order_type' => $orderType,
			'order_id' => $orderId,
			'order_ref' => (string) $order->ref,
			'invoice_id' => $invoiceId,
			'invoice_ref' => (string) $invoice->ref,
			'filename' => $filename,
			'ecm_file_id' => ($found > 0 ? (int) $ecm->id : 0),
			'sha256' => $sourceHash,
			'source_path' => $sourcePath,
			'target_path' => $targetPath,
			'source_ecm_path' => $sourceEcmPath,
			'target_ecm_path' => $targetEcmPath,
			'source_type' => $order->table_element,
			'target_type' => $invoice->table_element,
			'order' => $order
		);
	}

	/**
	 * Copy and verify the invoice file before removing the order file.
	 *
	 * @param string $orderType 'customer' or 'supplier'
	 * @param int $orderId Order rowid
	 * @param int $invoiceId Invoice rowid
	 * @param string $filename Exact attachment filename
	 * @param User $user Acting user
	 * @param string $expectedHash SHA-256 shown in the confirmation step
	 * @param string $expectedInvoiceRef Invoice reference shown in the confirmation step, if any
	 * @return array<string,mixed>|false Transfer result
	 */
	public function moveOrderToInvoice($orderType, $orderId, $invoiceId, $filename, User $user, $expectedHash, $expectedInvoiceRef = '')
	{
		$checked = $this->inspectOrderToInvoice($orderType, $orderId, $invoiceId, $filename, $user);
		if ($checked === false) {
			return false;
		}
		if ($expectedInvoiceRef !== '' && $checked['invoice_ref'] !== $expectedInvoiceRef) {
			return $this->fail(409, 'ECMTransferConflict', 'invoice_changed_since_confirmation');
		}
		if (!hash_equals((string) $checked['sha256'], (string) $expectedHash)) {
			return $this->fail(409, 'ECMTransferConflict', 'source_changed_since_confirmation');
		}
		$sourcePath = $checked['source_path'];
		$targetPath = $checked['target_path'];
		if (dol_mkdir(dirname($targetPath)) < 0 || dol_copy($sourcePath, $targetPath, '0', 0, 0, 0) <= 0) {
			return $this->fail(500, 'ECMTransferFailed', 'copy_failed');
		}
		$targetHash = hash_file('sha256', dol_osencode($targetPath));
		if ($targetHash === false || !hash_equals($checked['sha256'], $targetHash)) {
			return $this->discardCopy($targetPath, 'copied_hash_mismatch_source_kept');
		}

		$ecm = new EcmFiles($this->db);
		if (!empty($checked['ecm_file_id'])) {
			if ($ecm->fetch((int) $checked['ecm_file_id']) <= 0
				|| $ecm->filepath.'/'.$ecm->filename !== $checked['source_ecm_path']
				|| (int) $ecm->entity !== (int) $checked['order']->entity
				|| !((int) $ecm->src_object_id === (int) $orderId && $ecm->src_object_type === $checked['source_type']
					|| (empty($ecm->src_object_id) && empty($ecm->src_object_type)))
				|| !empty($ecm->share) || $ecm->gen_or_uploaded === 'generated') {
				return $this->discardCopy($targetPath, 'source_index_changed', 409, 'ECMTransferConflict');
			}
		} else {
			$ecm->filepath = dirname($checked['source_ecm_path']);
			$ecm->filename = $filename;
			$ecm->label = md5_file(dol_osencode($sourcePath));
			$ecm->fullpath_orig = $filename;
			$ecm->gen_or_uploaded = 'uploaded';
			$ecm->entity = (int) $checked['order']->entity;
			$ecm->src_object_type = $checked['source_type'];
			$ecm->src_object_id = (int) $orderId;
			if ($ecm->label === false || $ecm->create($user) <= 0) {
				return $this->discardCopy($targetPath, 'source_index_failed');
			}
		}
		$oldPath = $ecm->filepath;
		$oldType = $ecm->src_object_type;
		$oldId = $ecm->src_object_id;
		$ecm->filepath = dirname($checked['target_ecm_path']);
		$ecm->src_object_type = $checked['target_type'];
		$ecm->src_object_id = $invoiceId;
		if ($ecm->update($user) <= 0) {
			return $this->discardCopy($targetPath, 'target_index_update_failed');
		}
		$targetEcm = new EcmFiles($this->db);
		$indexed = $targetEcm->fetch(0, '', $checked['target_ecm_path'], '', '', '', 0, (int) $ecm->entity);
		$sourceHash = dol_is_file($sourcePath) ? hash_file('sha256', dol_osencode($sourcePath)) : false;
		$targetHash = dol_is_file($targetPath) ? hash_file('sha256', dol_osencode($targetPath)) : false;
		if ($indexed <= 0 || (int) $targetEcm->id !== (int) $ecm->id
			|| $targetEcm->src_object_type !== $checked['target_type'] || (int) $targetEcm->src_object_id !== (int) $invoiceId
			|| $sourceHash === false || $targetHash === false
			|| !hash_equals($checked['sha256'], $sourceHash) || !hash_equals($checked['sha256'], $targetHash)) {
			$restored = $this->restore($ecm, $oldPath, $oldType, $oldId, $targetPath, $user);
			return $this->fail(500, $restored ? 'ECMTransferFailed' : 'ECMTransferManualRecovery', 'invoice_verification_failed');
		}

		dol_delete_file($sourcePath, 1, 1, 1, $checked['order'], false, 0);
		if (dol_is_file($sourcePath)) {
			$restored = $this->restore($ecm, $oldPath, $oldType, $oldId, $targetPath, $user);
			return $this->fail(500, $restored ? 'ECMTransferFailed' : 'ECMTransferManualRecovery', 'source_delete_failed');
		}
		return array(
			'order_type' => $orderType,
			'order_id' => (int) $orderId,
			'order_ref' => $checked['order_ref'],
			'invoice_id' => (int) $invoiceId,
			'invoice_ref' => $checked['invoice_ref'],
			'filename' => $filename,
			'ecm_file_id' => (int) $ecm->id,
			'sha256' => $checked['sha256'],
			'complete' => true
		);
	}

	/**
	 * Restore the original association and remove the uncommitted invoice copy.
	 *
	 * @param EcmFiles $ecm Indexed file
	 * @param string $oldPath Original relative directory
	 * @param string $oldType Original object type
	 * @param int $oldId Original object id
	 * @param string $targetPath Invoice copy
	 * @param User $user Acting user
	 * @return bool True when the previous state was restored
	 */
	private function restore(EcmFiles $ecm, $oldPath, $oldType, $oldId, $targetPath, User $user)
	{
		$ecm->filepath = $oldPath;
		$ecm->src_object_type = $oldType;
		$ecm->src_object_id = $oldId;
		if ($ecm->update($user) <= 0) {
			return false;
		}
		dol_delete_file($targetPath, 1, 1, 1, null, false, 0);
		return !dol_is_file($targetPath);
	}
}
