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
 * \brief      Checked transfer of an attachment between document-bearing objects
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

/**
 * Transfer one attachment while retaining its ECM identity and metadata.
 */
class DocumentTransfer
{
	/** @var DoliDB */
	private $db;

	/** @var string Translation key for the last error */
	public $error = '';

	/** @var string Machine-readable reason for the last error */
	public $reason = '';

	/** @var int HTTP status suitable for a REST adapter */
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
	 * Disallow path components that could escape or alias another document tree.
	 *
	 * @param string $path Absolute document path
	 * @return bool True for a path inside the data root without symlink components
	 */
	private function safePath($path)
	{
		$root = rtrim(DOL_DATA_ROOT, '/');
		if (strpos($path, $root.'/') !== 0) {
			return false;
		}
		$cursor = $root;
		foreach (explode('/', ltrim(dol_substr($path, dol_strlen($root)), '/')) as $part) {
			if ($part === '' || $part === '.' || $part === '..') {
				return false;
			}
			$cursor .= '/'.$part;
			if (is_link(dol_osencode($cursor))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Resolve an object and its documented attachment directory. The two core
	 * document access checks are applied to the exact path used for the move.
	 *
	 * @param string $type Document modulepart
	 * @param int $id Object rowid
	 * @param string $filename Plain filename
	 * @param User $user Acting user
	 * @param bool $source True for the source, which also requires read access
	 * @return array<string,mixed>|false Checked object, file path and ECM path
	 */
	private function resolve($type, $id, $filename, User $user, $source)
	{
		global $conf;

		$object = fetchObjectByElement($id, $type);
		if (!($object instanceof CommonObject) || (int) $object->id !== $id
			|| empty($object->element) || empty($object->table_element) || empty($object->ref)) {
			return $this->fail(404, 'ECMTransferNotFound', 'object_missing_or_unsupported');
		}
		if (empty($object->entity) || (int) $object->entity !== (int) $conf->entity
			|| !checkUserAccessToObject($user, array($object->element), $object, $object->table_element, '')) {
			return $this->fail(403, 'ECMTransferDenied', 'object_inaccessible');
		}
		if ($user->socid > 0 && !empty($object->socid) && (int) $object->socid !== (int) $user->socid) {
			return $this->fail(403, 'ECMTransferDenied', 'thirdparty_inaccessible');
		}

		$root = rtrim((string) getMultidirOutput($object), '/');
		$directory = rtrim((string) getMultidirOutput($object, '', 1), '/');
		if ($type === 'project_task' && !empty($conf->project->multidir_output[$object->entity])) {
			$root = rtrim($conf->project->multidir_output[$object->entity], '/');
		}
		if ($type === 'contact' && !empty($conf->societe->multidir_output[$object->entity])) {
			$root = rtrim($conf->societe->multidir_output[$object->entity], '/').'/contact';
			$directory = $root.'/'.dol_sanitizeFileName((string) $object->ref);
		}
		if ($object->element === 'invoice_supplier') {
			$directory = $root.'/'.get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').dol_sanitizeFileName((string) $object->ref);
		}
		if ($root === '' || $root[0] !== '/' || strpos($root, 'error-') === 0
			|| strpos($directory, $root.'/') !== 0 || $directory === $root
			|| strpos($directory, 'error-') === 0) {
			return $this->fail(400, 'ECMTransferUnsupported', 'document_directory_unsupported');
		}
		$relativeFile = ltrim(dol_substr($directory, dol_strlen($root)), '/').'/'.$filename;
		$access = dol_check_secure_access_document($type, $relativeFile, (int) $object->entity, $user, (string) $object->ref, 'write');
		if (!is_array($access) || empty($access['accessallowed'])
			|| (string) ($access['original_file'] ?? '') !== $directory.'/'.$filename
			|| !checkUserAccessToDocumentObject($user, $type, $relativeFile, 'write')) {
			return $this->fail(403, 'ECMTransferDenied', 'document_access_denied');
		}
		if ($source) {
			$read = dol_check_secure_access_document($type, $relativeFile, (int) $object->entity, $user, (string) $object->ref, 'read');
			if (!is_array($read) || empty($read['accessallowed'])
				|| (string) ($read['original_file'] ?? '') !== $directory.'/'.$filename
				|| !checkUserAccessToDocumentObject($user, $type, $relativeFile, 'read')) {
				return $this->fail(403, 'ECMTransferDenied', 'source_read_denied');
			}
		}
		$path = $directory.'/'.$filename;
		if (!$this->safePath($path)) {
			return $this->fail(409, 'ECMTransferConflict', 'storage_not_in_ecm');
		}
		return array(
			'object' => $object,
			'modulepart' => $type,
			'path' => $path,
			'ecm_path' => ltrim(dol_substr($path, dol_strlen(DOL_DATA_ROOT)), '/')
		);
	}

	/**
	 * Check a transfer without changing files or database rows.
	 *
	 * @param string $sourceType Source document modulepart
	 * @param int $sourceId Source object rowid
	 * @param string $targetType Target document modulepart
	 * @param int $targetId Target object rowid
	 * @param string $filename Exact filename in the source directory
	 * @param User $user Acting user
	 * @return array<string,mixed>|false Checked transfer context
	 */
	public function inspect($sourceType, $sourceId, $targetType, $targetId, $filename, User $user)
	{
		$this->error = '';
		$this->reason = '';
		$this->status = 0;
		$sourceId = (int) $sourceId;
		$targetId = (int) $targetId;
		$filename = (string) $filename;
		if (!preg_match('/^[a-z][a-z0-9_]{1,63}(?:@[a-z][a-z0-9_]{1,63})?$/', (string) $sourceType)
			|| !preg_match('/^[a-z][a-z0-9_]{1,63}(?:@[a-z][a-z0-9_]{1,63})?$/', (string) $targetType)
			|| $sourceId <= 0 || $targetId <= 0
			|| ($sourceType === $targetType && $sourceId === $targetId)
			|| $filename === '' || $filename !== dol_basename($filename)
			|| $filename !== dol_sanitizeFileName($filename) || $filename[0] === '.'
			|| preg_match('/[<>|\\\\\/\x00-\x1f]/', $filename)) {
			return $this->fail(400, 'ECMTransferInvalidInput', 'invalid_input');
		}

		$source = $this->resolve($sourceType, $sourceId, $filename, $user, true);
		if ($source === false) {
			return false;
		}
		$target = $this->resolve($targetType, $targetId, $filename, $user, false);
		if ($target === false) {
			return false;
		}
		if ((int) $source['object']->entity !== (int) $target['object']->entity
			|| $source['path'] === $target['path']) {
			return $this->fail(409, 'ECMTransferConflict', 'same_storage_or_cross_entity');
		}
		if ($filename === dol_sanitizeFileName((string) $source['object']->ref).'.pdf') {
			return $this->fail(409, 'ECMTransferConflict', 'generated_document');
		}
		if (!dol_is_file($source['path'])) {
			return $this->fail(404, 'ECMTransferNotFound', 'source_file_missing');
		}
		if (dol_is_file($target['path'])) {
			return $this->fail(409, 'ECMTransferConflict', 'target_file_exists');
		}
		$hash = hash_file('sha256', dol_osencode($source['path']));
		if ($hash === false) {
			return $this->fail(500, 'ECMTransferFailed', 'source_hash_failed');
		}

		$ecm = new EcmFiles($this->db);
		$found = $ecm->fetch(0, '', $source['ecm_path'], '', '', '', 0, (int) $source['object']->entity);
		if ($found < 0) {
			return $this->fail(500, 'ECMTransferFailed', 'source_index_lookup_failed');
		}
		if ($found > 0) {
			$bound = (int) $ecm->src_object_id === $sourceId && $ecm->src_object_type === $source['object']->table_element;
			$unbound = empty($ecm->src_object_id) && empty($ecm->src_object_type);
			if ((int) $ecm->entity !== (int) $source['object']->entity || (!$bound && !$unbound)) {
				return $this->fail(409, 'ECMTransferConflict', 'source_index_wrong_object');
			}
			if (!empty($ecm->share) || $ecm->gen_or_uploaded === 'generated') {
				return $this->fail(409, 'ECMTransferConflict', 'shared_or_generated_file');
			}
		} elseif (getDolGlobalString('MAIN_FORCE_SHARING_ON_ANY_UPLOADED_FILE')) {
			return $this->fail(409, 'ECMTransferConflict', 'unindexed_file_would_be_shared');
		}
		$existing = new EcmFiles($this->db);
		$targetFound = $existing->fetch(0, '', $target['ecm_path'], '', '', '', 0, (int) $target['object']->entity);
		if ($targetFound < 0) {
			return $this->fail(500, 'ECMTransferFailed', 'target_index_lookup_failed');
		}
		if ($targetFound > 0) {
			return $this->fail(409, 'ECMTransferConflict', 'target_index_exists');
		}

		return array(
			'source_type' => $sourceType,
			'source_id' => $sourceId,
			'source_ref' => (string) $source['object']->ref,
			'target_type' => $targetType,
			'target_id' => $targetId,
			'target_ref' => (string) $target['object']->ref,
			'filename' => $filename,
			'ecm_file_id' => ($found > 0 ? (int) $ecm->id : 0),
			'sha256' => $hash,
			'source' => $source,
			'target' => $target
		);
	}

	/**
	 * Copy, verify and rebind the ECM record before removing the source file.
	 *
	 * @param string $sourceType Source document modulepart
	 * @param int $sourceId Source object rowid
	 * @param string $targetType Target document modulepart
	 * @param int $targetId Target object rowid
	 * @param string $filename Exact attachment filename
	 * @param User $user Acting user
	 * @param string $expectedHash SHA-256 returned by inspect()
	 * @param string $expectedTargetRef Target reference returned by inspect()
	 * @return array<string,mixed>|false Public transfer result
	 */
	public function move($sourceType, $sourceId, $targetType, $targetId, $filename, User $user, $expectedHash, $expectedTargetRef)
	{
		$checked = $this->inspect($sourceType, $sourceId, $targetType, $targetId, $filename, $user);
		if ($checked === false) {
			return false;
		}
		if ($expectedTargetRef === '' || !hash_equals((string) $checked['target_ref'], (string) $expectedTargetRef)
			|| !hash_equals((string) $checked['sha256'], (string) $expectedHash)) {
			return $this->fail(409, 'ECMTransferConflict', 'transfer_changed_since_inspection');
		}
		$sourcePath = $checked['source']['path'];
		$targetPath = $checked['target']['path'];
		if (dol_mkdir(dirname($targetPath)) < 0) {
			return $this->fail(500, 'ECMTransferFailed', 'target_directory_failed');
		}
		if (!$this->safePath($sourcePath) || !$this->safePath($targetPath)) {
			return $this->fail(409, 'ECMTransferConflict', 'storage_changed');
		}
		if (dol_copy($sourcePath, $targetPath, '0', 0, 0, 0) <= 0) {
			return $this->fail(500, dol_is_file($targetPath) ? 'ECMTransferManualRecovery' : 'ECMTransferFailed', 'copy_failed');
		}
		$targetHash = hash_file('sha256', dol_osencode($targetPath));
		if ($targetHash === false || !hash_equals($checked['sha256'], $targetHash)) {
			return $this->discardCopy($targetPath, 'copied_hash_mismatch');
		}
		$ecm = new EcmFiles($this->db);
		if ($checked['ecm_file_id'] > 0) {
			if ($ecm->fetch($checked['ecm_file_id']) <= 0
				|| $ecm->filepath.'/'.$ecm->filename !== $checked['source']['ecm_path']
				|| (int) $ecm->entity !== (int) $checked['source']['object']->entity
				|| !((int) $ecm->src_object_id === (int) $sourceId
					&& $ecm->src_object_type === $checked['source']['object']->table_element
					|| (empty($ecm->src_object_id) && empty($ecm->src_object_type)))
				|| !empty($ecm->share) || $ecm->gen_or_uploaded === 'generated') {
				return $this->discardCopy($targetPath, 'source_index_changed', 409, 'ECMTransferConflict');
			}
		} else {
			$ecm->filepath = dirname($checked['source']['ecm_path']);
			$ecm->filename = $filename;
			$ecm->label = md5_file(dol_osencode($sourcePath));
			$ecm->fullpath_orig = $filename;
			$ecm->gen_or_uploaded = 'uploaded';
			$ecm->entity = (int) $checked['source']['object']->entity;
			$ecm->src_object_type = $checked['source']['object']->table_element;
			$ecm->src_object_id = (int) $sourceId;
			if ($ecm->label === false || $ecm->create($user) <= 0) {
				return $this->discardCopy($targetPath, 'source_index_failed');
			}
		}
		$oldPath = $ecm->filepath;
		$oldType = (string) $ecm->src_object_type;
		$oldId = (int) $ecm->src_object_id;
		$ecm->filepath = dirname($checked['target']['ecm_path']);
		$ecm->src_object_type = $checked['target']['object']->table_element;
		$ecm->src_object_id = (int) $targetId;
		if ($ecm->update($user) <= 0) {
			return $this->discardCopy($targetPath, 'target_index_update_failed');
		}
		$indexed = new EcmFiles($this->db);
		$found = $indexed->fetch(0, '', $checked['target']['ecm_path'], '', '', '', 0, (int) $ecm->entity);
		$sourceHash = dol_is_file($sourcePath) ? hash_file('sha256', dol_osencode($sourcePath)) : false;
		$targetHash = dol_is_file($targetPath) ? hash_file('sha256', dol_osencode($targetPath)) : false;
		if ($found <= 0 || (int) $indexed->id !== (int) $ecm->id
			|| $indexed->src_object_type !== $checked['target']['object']->table_element
			|| (int) $indexed->src_object_id !== (int) $targetId
			|| $sourceHash === false || $targetHash === false
			|| !hash_equals($checked['sha256'], $sourceHash) || !hash_equals($checked['sha256'], $targetHash)) {
			$restored = $this->restore($ecm, $oldPath, $oldType, $oldId, $targetPath, $user);
			return $this->fail(500, $restored ? 'ECMTransferFailed' : 'ECMTransferManualRecovery', 'target_verification_failed');
		}
		dol_delete_file($sourcePath, 1, 1, 1, $checked['source']['object'], false, 0);
		if (dol_is_file($sourcePath)) {
			$restored = $this->restore($ecm, $oldPath, $oldType, $oldId, $targetPath, $user);
			return $this->fail(500, $restored ? 'ECMTransferFailed' : 'ECMTransferManualRecovery', 'source_delete_failed');
		}
		return $this->publicResult($checked, (int) $ecm->id, true);
	}

	/**
	 * Return only fields safe to expose through UI or REST.
	 *
	 * @param array<string,mixed> $checked Checked transfer context
	 * @param int $ecmId ECM file ID
	 * @param bool $complete True after the source has been removed
	 * @return array<string,mixed> Public result
	 */
	public function publicResult($checked, $ecmId, $complete)
	{
		return array(
			'source_type' => $checked['source_type'],
			'source_id' => $checked['source_id'],
			'source_ref' => $checked['source_ref'],
			'target_type' => $checked['target_type'],
			'target_id' => $checked['target_id'],
			'target_ref' => $checked['target_ref'],
			'filename' => $checked['filename'],
			'ecm_file_id' => $ecmId,
			'sha256' => $checked['sha256'],
			'complete' => $complete,
			'source_removed' => $complete
		);
	}

	/**
	 * @param string $targetPath Uncommitted copy
	 * @param string $reason Failure reason
	 * @param int $status HTTP status when cleanup succeeds
	 * @param string $key Translation key when cleanup succeeds
	 * @return false
	 */
	private function discardCopy($targetPath, $reason, $status = 500, $key = 'ECMTransferFailed')
	{
		dol_delete_file($targetPath, 1, 1, 1, null, false, 0);
		$notRemoved = dol_is_file($targetPath);
		return $this->fail($notRemoved ? 500 : $status, $notRemoved ? 'ECMTransferManualRecovery' : $key, $reason);
	}

	/**
	 * @param EcmFiles $ecm Indexed file
	 * @param string $oldPath Original relative directory
	 * @param string $oldType Original object type
	 * @param int $oldId Original object ID
	 * @param string $targetPath Uncommitted copy
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
