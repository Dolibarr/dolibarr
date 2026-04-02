<?php
/* Copyright (C) 2026 OpenAI
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

/**
 * API class for ECM custom uploads.
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class EcmApi extends DolibarrApi
{
	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * Constructor
	 *
	 * @url GET /
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
	}

	/**
	 * Upload a file into a safe custom ECM directory.
	 *
	 * Example: {
	 *   "filename": "contract.pdf",
	 *   "filecontent": "JVBERi0xLjcK...",
	 *   "fileencoding": "base64",
	 *   "target_dir": "partners/acme/2026",
	 *   "createdirifnotexists": 1,
	 *   "overwriteifexists": 0,
	 *   "description": "Signed contract",
	 *   "keywords": "contract,acme,2026",
	 *   "note_private": "Uploaded from API",
	 *   "array_options": {
	 *     "options_document_source": "portal"
	 *   }
	 * }
	 *
	 * @param string $filename File name to create
	 * @param string $filecontent Raw or base64-encoded file content
	 * @param string $fileencoding '' or 'base64'
	 * @param string $target_dir Relative directory under ECM root
	 * @param string $relative_path Alias of target_dir
	 * @param int $createdirifnotexists Create missing directories
	 * @param int $overwriteifexists Overwrite existing file
	 * @param string $description ECM description
	 * @param string $keywords ECM keywords
	 * @param string $note_public ECM public note
	 * @param string $note_private ECM private note
	 * @param array<mixed> $array_options ECM extrafields array
	 * @return array<string,mixed>
	 *
	 * @url POST /uploadToCustomDir
	 *
	 * @throws RestException 400 Bad request
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 File operation error
	 */
	public function uploadToCustomDir($filename = '', $filecontent = '', $fileencoding = '', $target_dir = '', $relative_path = '', $createdirifnotexists = 1, $overwriteifexists = 0, $description = '', $keywords = '', $note_public = '', $note_private = '', $array_options = array())
	{
		global $conf;

		$this->assertUploadRights();

		if (empty($filename)) {
			throw new RestException(400, 'Parameter filename is required.');
		}
		if ($filecontent === null || $filecontent === '') {
			throw new RestException(400, 'Parameter filecontent is required and must not be empty.');
		}

		$requestedRelativeDir = $target_dir;
		if ($requestedRelativeDir === '') {
			$requestedRelativeDir = $relative_path;
		}
		if ($requestedRelativeDir === null || $requestedRelativeDir === '') {
			throw new RestException(400, 'Parameter target_dir or relative_path is required.');
		}

		if (!is_array($array_options)) {
			throw new RestException(400, 'Parameter array_options must be an object/array.');
		}

		$binaryContent = $this->decodeFileContent((string) $filecontent, (string) $fileencoding);
		$safeFilename = dol_sanitizeFileName((string) $filename);
		if (empty($safeFilename)) {
			throw new RestException(400, 'Filename is invalid after sanitization.');
		}

		$this->assertAllowedExtension($safeFilename);

		$baseDirectory = rtrim(preg_replace('/[\\\\]+/', '/', $conf->ecm->dir_output), '/');
		$cleanRelativeDir = $this->sanitizeRelativeDirectory((string) $requestedRelativeDir);
		$targetDirectoryFullPath = $baseDirectory.($cleanRelativeDir !== '' ? '/'.$cleanRelativeDir : '');
		$targetDirectoryFullPath = preg_replace('/\/+/', '/', $targetDirectoryFullPath);

		$this->assertPathInsideBase($baseDirectory, $targetDirectoryFullPath, false);

		if (!dol_is_dir($targetDirectoryFullPath) && empty($createdirifnotexists)) {
			throw new RestException(400, 'Directory does not exist: '.$cleanRelativeDir);
		}

		$this->ensureEcmDirectoryTree($cleanRelativeDir);

		if (!dol_is_dir($targetDirectoryFullPath)) {
			if (dol_mkdir($targetDirectoryFullPath) < 0) {
				throw new RestException(500, 'Error while creating directory '.$cleanRelativeDir);
			}
		}

		$this->assertPathInsideBase($baseDirectory, $targetDirectoryFullPath, true);

		$destinationFile = $targetDirectoryFullPath.'/'.$safeFilename;
		$destinationFile = preg_replace('/\/+/', '/', $destinationFile);

		if (empty($overwriteifexists) && dol_is_file($destinationFile)) {
			throw new RestException(400, "File '".$safeFilename."' already exists.");
		}

		$tempDirectory = DOL_DATA_ROOT.'/admin/temp';
		if (!dol_is_dir($tempDirectory)) {
			dol_mkdir($tempDirectory);
		}

		$tempFile = $tempDirectory.'/ecmapi_'.dol_hash(uniqid('', true), 1).'_'.$safeFilename;
		$tempHandle = @fopen($tempFile, 'wb');
		if (!$tempHandle) {
			throw new RestException(500, "Failed to open temporary file '".$tempFile."' for write.");
		}

		$bytesWritten = fwrite($tempHandle, $binaryContent);
		fclose($tempHandle);
		dolChmod($tempFile);

		if ($bytesWritten === false || $bytesWritten !== strlen($binaryContent)) {
			dol_delete_file($tempFile);
			throw new RestException(500, 'Failed to write uploaded content into temporary file.');
		}

		$moreinfo = array(
			'gen_or_uploaded' => 'api',
			'description' => (string) $description,
			'keywords' => (string) $keywords,
			'note_public' => (string) $note_public,
			'note_private' => (string) $note_private,
			'array_options' => $array_options,
			'src_object_type' => 'ecm',
			'src_object_id' => 0,
		);

		$result = dol_move($tempFile, $destinationFile, '0', (int) $overwriteifexists, 1, 1, $moreinfo, $this->getCurrentEntity());
		if (!$result) {
			dol_delete_file($tempFile);
			throw new RestException(500, "Failed to move file into '".$destinationFile."'.");
		}

		$fullRelativePath = 'ecm/'.($cleanRelativeDir !== '' ? $cleanRelativeDir.'/' : '').$safeFilename;
		dol_syslog(__METHOD__.' uploaded file '.$fullRelativePath, LOG_INFO);

		return array(
			'success' => true,
			'filename' => $safeFilename,
			'directory' => $cleanRelativeDir,
			'full_relative_path' => $fullRelativePath,
			'ecm_directory_tree_updated' => true,
			'message' => 'File uploaded successfully.'
		);
	}

	/**
	 * @return void
	 *
	 * @throws RestException
	 */
	protected function assertUploadRights()
	{
		global $conf;

		$user = DolibarrApiAccess::$user;
		if (!is_object($user) || empty($user->id)) {
			throw new RestException(403, 'Authenticated API user is required.');
		}
		if (!$user->hasRight('ecm', 'upload')) {
			throw new RestException(403, 'Missing ECM upload permission.');
		}
		if (!isModEnabled('ecm')) {
			throw new RestException(403, 'ECM module must be enabled for this endpoint.');
		}
		if (empty($conf->ecm->dir_output) || !is_dir($conf->ecm->dir_output)) {
			throw new RestException(500, 'ECM storage directory is not configured correctly.');
		}
	}

	/**
	 * @param string $content
	 * @param string $encoding
	 * @return string
	 *
	 * @throws RestException
	 */
	protected function decodeFileContent($content, $encoding)
	{
		if ($encoding === '' || $encoding === null) {
			return $content;
		}
		if ($encoding === 'base64') {
			$decoded = base64_decode($content, true);
			if ($decoded === false) {
				throw new RestException(400, 'filecontent is not valid base64 data.');
			}

			return $decoded;
		}

		throw new RestException(400, "Unsupported fileencoding '".$encoding."'. Supported values are '' and 'base64'.");
	}

	/**
	 * @param string $relativeDirectory
	 * @return string
	 *
	 * @throws RestException
	 */
	protected function sanitizeRelativeDirectory($relativeDirectory)
	{
		$relativeDirectory = trim($relativeDirectory);
		$relativeDirectory = str_replace('\\', '/', $relativeDirectory);
		$relativeDirectory = preg_replace('/\/+/', '/', $relativeDirectory);
		$relativeDirectory = trim($relativeDirectory, '/');

		if ($relativeDirectory === '') {
			return '';
		}
		if (preg_match('/^[a-zA-Z]:\//', $relativeDirectory) || preg_match('/^\//', $relativeDirectory)) {
			throw new RestException(400, 'Absolute target directories are not allowed.');
		}

		$parts = explode('/', $relativeDirectory);
		$cleanParts = array();
		foreach ($parts as $part) {
			$part = trim($part);
			if ($part === '' || $part === '.') {
				continue;
			}
			if ($part === '..') {
				throw new RestException(400, 'Path traversal is not allowed in target_dir.');
			}

			$sanitizedPart = dol_sanitizeFileName($part);
			if ($sanitizedPart === '' || $sanitizedPart !== $part) {
				throw new RestException(400, "Unsafe directory segment '".$part."'.");
			}

			$cleanParts[] = $sanitizedPart;
		}

		return implode('/', $cleanParts);
	}

	/**
	 * @param string $basePath
	 * @param string $targetPath
	 * @param bool $mustExist
	 * @return void
	 *
	 * @throws RestException
	 */
	protected function assertPathInsideBase($basePath, $targetPath, $mustExist = true)
	{
		$baseReal = realpath($basePath);
		if ($baseReal === false) {
			throw new RestException(500, 'Configured base storage directory does not exist.');
		}

		$baseReal = rtrim(str_replace('\\', '/', $baseReal), '/');
		$targetPath = rtrim(str_replace('\\', '/', $targetPath), '/');

		if ($mustExist) {
			$targetReal = realpath($targetPath);
			if ($targetReal === false) {
				throw new RestException(400, 'Resolved target path does not exist.');
			}
			$targetReal = str_replace('\\', '/', $targetReal);
			if ($targetReal !== $baseReal && strpos($targetReal, $baseReal.'/') !== 0) {
				throw new RestException(400, 'Target path escapes the configured base directory.');
			}
			return;
		}

		if ($targetPath !== $baseReal && strpos($targetPath, $baseReal.'/') !== 0) {
			throw new RestException(400, 'Target path escapes the configured base directory.');
		}
	}

	/**
	 * @param string $filename
	 * @return void
	 *
	 * @throws RestException
	 */
	protected function assertAllowedExtension($filename)
	{
		$allowedExtensions = trim(getDolGlobalString('ECM_API_ALLOWED_EXTENSIONS'));
		if ($allowedExtensions === '') {
			return;
		}

		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$allowlist = array();
		foreach (explode(',', $allowedExtensions) as $item) {
			$item = strtolower(trim($item));
			$item = ltrim($item, '.');
			if ($item !== '') {
				$allowlist[$item] = $item;
			}
		}

		if (!empty($allowlist) && ($extension === '' || !isset($allowlist[$extension]))) {
			throw new RestException(400, 'File extension is not allowed.');
		}
	}

	/**
	 * @param string $relativeDirectory
	 * @return void
	 *
	 * @throws RestException
	 */
	protected function ensureEcmDirectoryTree($relativeDirectory)
	{
		if ($relativeDirectory === '') {
			return;
		}

		$user = DolibarrApiAccess::$user;
		$currentParentId = 0;
		$currentPath = '';
		$directoryStatic = new EcmDirectory($this->db);
		$tree = $directoryStatic->get_full_arbo(1);
		if (!is_array($tree)) {
			throw new RestException(500, 'Failed to load ECM directory tree.');
		}

		foreach (explode('/', $relativeDirectory) as $segment) {
			$currentPath = ($currentPath !== '' ? $currentPath.'/' : '').$segment;
			$foundId = 0;

			foreach ($tree as $directoryInfo) {
				if (!empty($directoryInfo['fullrelativename']) && $directoryInfo['fullrelativename'] === $currentPath) {
					$foundId = (int) $directoryInfo['id'];
					break;
				}
			}

			if ($foundId <= 0) {
				$directory = new EcmDirectory($this->db);
				$directory->label = $segment;
				$directory->fk_parent = $currentParentId;
				$directory->description = 'Created by ECM API upload.';

				$result = $directory->create($user);
				if ($result <= 0) {
					throw new RestException(500, 'Failed to create ECM directory '.$currentPath.'. '.$directory->error);
				}

				$foundId = (int) $directory->id;
				$tree = $directoryStatic->get_full_arbo(1);
				if (!is_array($tree)) {
					throw new RestException(500, 'Failed to refresh ECM directory tree.');
				}
			}

			$currentParentId = $foundId;
		}
	}

	/**
	 * @return int
	 */
	protected function getCurrentEntity()
	{
		$entity = (int) DolibarrApiAccess::$user->entity;
		if ($entity <= 0) {
			$entity = 1;
		}

		return $entity;
	}
}
