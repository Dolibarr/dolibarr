<?php
/* Copyright (C) 2012 Regis Houssin	<regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/install/lib/repair.lib.php
 *  \brief			Library of repair functions
 */

/**
 *  Check if an element exist
 *
 *  @param	int		$id			Element id
 *  @param	string	$table		Table of Element
 *  @return	boolean				True if child exists
 */
function checkElementExist($id, $table)
{
	global $db;

	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$table;
	$sql .= " WHERE rowid = ".((int) $id);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num > 0) {
			return true;
		} else {
			return false;
		}
	} else {
		return true; // for security
	}
}

/**
 * Check linked elements and delete if invalid
 *
 * @param	string	$sourcetype		Source element type
 * @param	string	$targettype		Target element type
 * @return	string
 */
function checkLinkedElements($sourcetype, $targettype)
{
	global $db, $langs;

	$elements = array();
	$deleted = 0;

	$sourcetable = $sourcetype;
	$targettable = $targettype;

	if ($sourcetype == 'shipping') {
		$sourcetable = 'expedition';
	} elseif ($targettype == 'shipping') {
		$targettable = 'expedition';
	}
	if ($sourcetype == 'delivery') {
		$sourcetable = 'livraison';
	} elseif ($targettype == 'delivery') {
		$targettable = 'livraison';
	}
	if ($sourcetype == 'order_supplier') {
		$sourcetable = 'commande_fournisseur';
	} elseif ($targettype == 'order_supplier') {
		$targettable = 'commande_fournisseur';
	}
	if ($sourcetype == 'invoice_supplier') {
		$sourcetable = 'facture_fourn';
	} elseif ($targettype == 'invoice_supplier') {
		$targettable = 'facture_fourn';
	}

	$out = $langs->trans('SourceType').': '.$sourcetype.' => '.$langs->trans('TargetType').': '.$targettype.' ';

	$sql = "SELECT rowid, fk_source, fk_target FROM ".MAIN_DB_PREFIX."element_element";
	$sql .= " WHERE sourcetype = '".$db->escape($sourcetype)."' AND targettype = '".$db->escape($targettype)."'";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$elements[$obj->rowid] = array($sourcetype => $obj->fk_source, $targettype => $obj->fk_target);
				$i++;
			}
		}
	}

	if (!empty($elements)) {
		foreach ($elements as $key => $element) {
			if (!checkElementExist($element[$sourcetype], $sourcetable) || !checkElementExist($element[$targettype], $targettable)) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'element_element';
				$sql .= " WHERE rowid = ".((int) $key);
				$resql = $db->query($sql);
				$deleted++;
			}
		}
	}

	if ($deleted) {
		$out .= '('.$langs->trans('LinkedElementsInvalidDeleted', $deleted).')<br>';
	} else {
		$out .= '('.$langs->trans('NothingToDelete').')<br>';
	}

	return $out;
}

/**
 * Clean data into ecm_directories table
 *
 * @return	int			Return integer <0 if KO, >0 if OK
 */
function clean_data_ecm_directories()
{
	global $db, $langs;

	// Clean data from ecm_directories
	$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."ecm_directories";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$id = $obj->rowid;
			$label = $obj->label;
			$newlabel = dol_sanitizeFileName($label);
			if ($label != $newlabel) {
				$sqlupdate = "UPDATE ".MAIN_DB_PREFIX."ecm_directories set label = '".$db->escape($newlabel)."' WHERE rowid = ".((int) $id);
				print '<tr><td>'.$sqlupdate."</td></tr>\n";
				$resqlupdate = $db->query($sqlupdate);
				if (!$resqlupdate) {
					dol_print_error($db, 'Failed to update');
					return -1;
				}
			}
		}
	} else {
		dol_print_error($db, 'Failed to run request');
		return -1;
	}

	return 1;
}

/**
 * Check an entity's ECM index against the filesystem, optionally removing missing-file entries.
 * The caller must validate the entity and, for confirmed requests, the installer CSRF token.
 *
 * @param DoliDB $db Database handler
 * @param int $entity Entity to repair (without shared entities)
 * @param string $documentroot Global documents directory; ECM paths are already relative to it
 * @param bool $confirmed Remove missing-file index entries
 * @param int $maxdelete Maximum number of entries to remove
 * @return array{processed:int,missing:int,deleted:int,skipped:int,limitreached:bool,entries:list<array{id:int,path:string}>,error:string}
 */
function repairEcmFiles($db, $entity, $documentroot, $confirmed = false, $maxdelete = 100)
{
	$report = array('processed' => 0, 'missing' => 0, 'deleted' => 0, 'skipped' => 0, 'limitreached' => false, 'entries' => array(), 'error' => '');
	if ($entity <= 0 || $maxdelete <= 0) {
		$report['error'] = 'ErrorWrongParameters';
		return $report;
	}
	$realroot = realpath(dol_osencode($documentroot));
	if ($realroot === false || !dol_is_dir($documentroot) || !is_readable(dol_osencode($documentroot))) {
		$report['error'] = 'RepairEcmDirectoryUnavailable';
		return $report;
	}
	$rootprefix = rtrim(str_replace('\\', '/', $realroot), '/').'/';
	$sql = "SELECT rowid, filename, filepath FROM ".$db->prefix()."ecm_files";
	$sql .= " WHERE entity = ".((int) $entity)." ORDER BY rowid ASC";
	$resql = $db->query($sql);
	if (!$resql) {
		$report['error'] = 'RepairEcmDatabaseError';
		return $report;
	}
	$candidates = array();
	while (is_object($obj = $db->fetch_object($resql))) {
		$report['processed']++;
		$relativepath = str_replace('\\', '/', (string) $obj->filepath);
		$filename = (string) $obj->filename;
		// Never interpret an unsafe or inaccessible indexed path as a missing file.
		if ($relativepath === '' || preg_match('~^/|(^|/)\.\.?(/|$)|[\x00-\x1f:]~', $relativepath)
			|| $filename === '' || $filename === '.' || $filename === '..' || preg_match('~[\\\\/\x00-\x1f:]~', $filename)) {
			$report['skipped']++;
			continue;
		}
		$directory = $documentroot.'/'.$relativepath;
		$realdirectory = realpath(dol_osencode($directory));
		if ($realdirectory === false || !is_readable(dol_osencode($directory))
			|| strpos(rtrim(str_replace('\\', '/', $realdirectory), '/').'/', $rootprefix) !== 0) {
			$report['skipped']++;
			continue;
		}
		$filetocheck = $directory.'/'.$filename;
		if (dol_is_file($filetocheck) || dol_is_file($filetocheck.'.noexe')) {
			continue;
		}
		if (dol_is_dir($filetocheck) || dol_is_link($filetocheck) || dol_is_link($filetocheck.'.noexe')) {
			$report['skipped']++;
			continue;
		}
		$report['missing']++;
		if (count($candidates) >= $maxdelete) {
			$report['limitreached'] = true;
			break;
		}
		$candidates[] = $obj;
		$report['entries'][] = array('id' => (int) $obj->rowid, 'path' => $relativepath.'/'.$filename);
	}
	$db->free($resql);
	if (!$confirmed || !$candidates) {
		return $report;
	}
	if (!$db->begin()) {
		$report['error'] = 'RepairEcmDatabaseError';
		return $report;
	}
	foreach ($candidates as $candidate) {
		// Keep an entry if another process has restored its file or changed its indexed path.
		$filetocheck = $documentroot.'/'.$candidate->filepath.'/'.$candidate->filename;
		$directory = dirname($filetocheck);
		$realdirectory = realpath(dol_osencode($directory));
		if ($realdirectory === false || !is_readable(dol_osencode($directory))
			|| strpos(rtrim(str_replace('\\', '/', $realdirectory), '/').'/', $rootprefix) !== 0
			|| dol_is_link($filetocheck) || dol_is_link($filetocheck.'.noexe') || dol_is_dir($filetocheck)) {
			$report['skipped']++;
			continue;
		}
		if (dol_is_file($filetocheck) || dol_is_file($filetocheck.'.noexe')) {
			continue;
		}
		$sql = "DELETE FROM ".$db->prefix()."ecm_files WHERE rowid = ".((int) $candidate->rowid);
		$sql .= " AND entity = ".((int) $entity);
		$sql .= " AND filepath = '".$db->escape($candidate->filepath)."' AND filename = '".$db->escape($candidate->filename)."'";
		$result = $db->query($sql);
		if (!$result) {
			$db->rollback();
			$report['deleted'] = 0;
			$report['error'] = 'RepairEcmDatabaseError';
			return $report;
		}
		$report['deleted'] += $db->affected_rows($result);
	}
	if (!$db->commit()) {
		$db->rollback();
		$report['deleted'] = 0;
		$report['error'] = 'RepairEcmDatabaseError';
	}
	return $report;
}

/**
 * Check an image repair path, including a missing destination, against its owner's directory.
 *
 * @param string $path File or directory to read or write
 * @param string $directory Owner's module directory
 * @param string $documentroot Installation's documents directory
 * @return bool True when no path component redirects the operation outside its expected location
 */
function repairImagePathIsSafe($path, $directory, $documentroot)
{
	$path = rtrim(str_replace('\\', '/', $path), '/');
	$directory = rtrim(str_replace('\\', '/', $directory), '/');
	$documentroot = rtrim(str_replace('\\', '/', $documentroot), '/');
	$windows = stripos(PHP_OS, 'WIN') === 0;
	$documentrootlength = dol_strlen($documentroot, 'UTF-8', 1);
	$directorylength = dol_strlen($directory, 'UTF-8', 1);
	if ($path === '' || $directory === '' || $documentroot === '' || preg_match('~(?:^|/)\.\.?(?:/|$)~', $path)) {
		return false;
	}
	if (($windows ? strncasecmp($directory.'/', $documentroot.'/', $documentrootlength + 1) : strncmp($directory.'/', $documentroot.'/', $documentrootlength + 1)) !== 0
		|| ($windows ? strncasecmp($path.'/', $directory.'/', $directorylength + 1) : strncmp($path.'/', $directory.'/', $directorylength + 1)) !== 0) {
		return false;
	}
	$realroot = realpath(dol_osencode($documentroot));
	$realdirectory = realpath(dol_osencode($directory));
	if ($realroot === false || $realdirectory === false || !dol_is_dir($directory)) {
		return false;
	}
	$realroot = rtrim(str_replace('\\', '/', $realroot), '/');
	$expected = $realroot.dol_substr($directory, $documentrootlength, null, 'UTF-8', 1);
	$realdirectory = str_replace('\\', '/', $realdirectory);
	if (($windows ? strcasecmp($realdirectory, $expected) : strcmp($realdirectory, $expected)) !== 0) {
		return false;
	}
	$parent = $path;
	// lstat also detects broken links and Windows junctions that is_link() may miss.
	while (@lstat(dol_osencode($parent)) === false) {
		$nextparent = dirname($parent);
		if ($nextparent === $parent) {
			return false;
		}
		$parent = $nextparent;
	}
	$realparent = realpath(dol_osencode($parent));
	if ($realparent === false) {
		return false;
	}
	$expected = $realroot.dol_substr($parent, $documentrootlength, null, 'UTF-8', 1);
	$realparent = str_replace('\\', '/', $realparent);
	return ($windows ? strcasecmp($realparent, $expected) : strcmp($realparent, $expected)) === 0;
}

/**
 * Find or delete document files whose object no longer exists in any entity.
 *
 * @param DoliDB $db Database handler
 * @param string $modulepart Supported native document module
 * @param string $directory One owner's native module directory
 * @param string $documentroot Installation's documents directory
 * @param bool $confirmed Delete candidates; false only reports them
 * @return array{files:list<string>,deleted:int,error:string}
 */
function repairOrphanFiles($db, $modulepart, $directory, $documentroot, $confirmed = false)
{
	$report = array('files' => array(), 'deleted' => 0, 'error' => '');
	$tables = array('company' => 'societe', 'invoice' => 'facture', 'invoice_supplier' => 'facture_fourn', 'propal' => 'propal', 'order' => 'commande', 'order_supplier' => 'commande_fournisseur', 'contract' => 'contrat', 'tax' => 'chargesociales');
	if (!isset($tables[$modulepart]) || !repairImagePathIsSafe($directory.'/', $directory, $documentroot) || !is_readable(dol_osencode($directory))) {
		$report['error'] = 'RepairOrphanFilesFailed';
		return $report;
	}
	// This maintenance inventory is deliberately global: an inaccessible or shared object is not an orphan.
	$sql = "SELECT rowid".($modulepart === 'company' ? '' : ', ref')." FROM ".$db->prefix().$db->sanitize($tables[$modulepart]);
	$resql = $db->query($sql);
	if (!$resql) {
		$report['error'] = 'RepairOrphanFilesFailed';
		return $report;
	}
	$existingdirectories = array();
	while (is_object($obj = $db->fetch_object($resql))) {
		$id = (int) $obj->rowid;
		$ref = $modulepart === 'company' || $modulepart === 'tax' ? (string) $id : dol_sanitizeFileName((string) $obj->ref);
		if ($modulepart === 'invoice_supplier') {
			$prefix = get_exdir($id, 2, 0, 0, null, 'invoice_supplier');
			$existingdirectories[dol_strtolower(dol_string_unaccent($prefix.$ref))] = true;
			$existingdirectories[$prefix.$id] = true; // Historical numeric object directory.
			$existingdirectories[(string) $id] = true;
		} elseif ($ref !== '') {
			$existingdirectories[dol_strtolower(dol_string_unaccent($ref))] = true;
		}
	}
	$db->free($resql);
	$files = array();
	$pendingdirectories = array($directory);
	while (!empty($pendingdirectories)) {
		$currentdirectory = array_pop($pendingdirectories);
		if (!repairImagePathIsSafe($currentdirectory, $directory, $documentroot) || !is_readable(dol_osencode($currentdirectory))) {
			$report['error'] = 'RepairOrphanFilesFailed';
			return $report;
		}
		// Check each directory before descending, including Windows junctions.
		$entries = dol_dir_list($currentdirectory, 'all', 0, '', array('^SPECIMEN\.pdf$', '^\.', '(\.meta|_preview.*\.png)$', '^temp$', '^payments$', '^CVS$', '^thumbs$'), 'name', SORT_ASC, 0, 1, '', 1);
		foreach ($entries as $entry) {
			if ($entry['type'] === 'dir') {
				$pendingdirectories[] = $entry['fullname'];
			} else {
				$files[] = $entry;
			}
		}
	}
	foreach ($files as $file) {
		$relativefile = substr($file['fullname'], strlen($directory) + 1);
		$parts = explode('/', $relativefile);
		if (count($parts) < 2) {
			continue; // Root files cannot be assigned to an object directory reliably.
		}
		$objectdirectory = $parts[0];
		if ($modulepart === 'invoice_supplier') {
			if (count($parts) >= 4 && preg_match('/^[0-9]$/', $parts[0]) && preg_match('/^[0-9]$/', $parts[1])) {
				$objectdirectory = $parts[0].'/'.$parts[1].'/'.$parts[2];
			} elseif (count($parts) !== 2 || !ctype_digit($parts[0])) {
				continue;
			}
		} elseif ($modulepart === 'company') {
			if (!ctype_digit($parts[0]) || (count($parts) > 2 && $parts[1] !== 'logos')) {
				continue; // Preserve legacy hierarchies and shared courrier/logo directories.
			}
		} elseif ($modulepart === 'tax' && !ctype_digit($parts[0])) {
			continue;
		}
		if (isset($existingdirectories[dol_strtolower(dol_string_unaccent($objectdirectory))])) {
			continue;
		}
		if (!repairImagePathIsSafe($file['fullname'], $directory, $documentroot)) {
			$report['error'] = 'RepairOrphanFilesFailed';
			return $report;
		}
		$report['files'][] = $file['fullname'];
	}
	if ($confirmed) {
		foreach ($report['files'] as $file) {
			if (!dol_delete_file($file, 0, 0, 1)) {
				$report['error'] = 'RepairOrphanFilesFailed';
				break;
			}
			$report['deleted']++;
			dol_delete_dir(dirname($file), 1); // rmdir only: never remove other files recursively.
		}
	}
	return $report;
}

/**
 * Migrate one mailing's legacy files inside its owner's native directory.
 *
 * @param Mailing $mailing Mailing with id, ref and entity loaded
 * @param string $directory Owner's native mailing directory
 * @param string $documentroot Installation's documents directory
 * @param bool $ambiguous Other mailings in this entity use the same legacy bucket
 * @param bool $confirmed Apply moves; false only reports planned moves
 * @return array{files:list<array{source:string,target:string}>,error:string}
 */
function repairMailingFiles($mailing, $directory, $documentroot, $ambiguous = false, $confirmed = false)
{
	global $conf;
	$report = array('files' => array(), 'error' => '');
	if ($mailing->id <= 0 || $mailing->entity <= 0 || !repairImagePathIsSafe($directory.'/'.$mailing->id, $directory, $documentroot)) {
		$report['error'] = 'RepairMailingFailed';
		return $report;
	}
	$origin = $directory.'/'.get_exdir($mailing->id, 2, 0, 1, $mailing, 'mailing');
	// Calculate the destination without changing the administrator's persisted path setting.
	$hadsetting = isset($conf->global->MAILING_USE_NEW_PATH_FOR_FILES);
	$previoussetting = $hadsetting ? $conf->global->MAILING_USE_NEW_PATH_FOR_FILES : null;
	$conf->global->MAILING_USE_NEW_PATH_FOR_FILES = 1;
	$destination = $directory.'/'.get_exdir($mailing->id, 0, 0, 1, $mailing, 'mailing');
	if ($hadsetting) {
		$conf->global->MAILING_USE_NEW_PATH_FOR_FILES = $previoussetting;
	} else {
		unset($conf->global->MAILING_USE_NEW_PATH_FOR_FILES);
	}
	if (!repairImagePathIsSafe($origin, $directory, $documentroot) || !repairImagePathIsSafe($destination, $directory, $documentroot) || $origin === $destination) {
		$report['error'] = 'RepairMailingFailed';
		return $report;
	}
	if (!dol_is_dir($origin)) {
		return $report;
	}
	if (!is_readable(dol_osencode($origin))) {
		$report['error'] = 'RepairMailingFailed';
		return $report;
	}
	$files = dol_dir_list($origin, 'files', 1, '', null, 'name', SORT_ASC, 0, 1, '', 1);
	if (!empty($files) && $ambiguous) {
		$report['error'] = 'RepairMailingAmbiguous';
		return $report;
	}
	foreach ($files as $file) {
		$source = $file['fullname'];
		$target = $destination.'/'.substr($source, strlen($origin) + 1);
		if (!repairImagePathIsSafe($source, $directory, $documentroot) || !repairImagePathIsSafe($target, $directory, $documentroot) || file_exists(dol_osencode($target))) {
			$report['error'] = 'RepairMailingFailed';
			return $report; // Refuse the entire mailing before moving any file on a collision.
		}
		$report['files'][] = array('source' => $source, 'target' => $target);
	}
	if ($confirmed) {
		foreach ($report['files'] as $file) {
			if (dol_mkdir(dirname($file['target'])) < 0 || !dol_move($file['source'], $file['target'], '0', 0, 0, 1, array(), (int) $mailing->entity)) {
				$report['error'] = 'RepairMailingFailed';
				break;
			}
		}
	}
	return $report;
}

/**
 * Restore a user's missing photo and thumbnails without overwriting existing files.
 *
 * @param int $id User id
 * @param string $photo Stored photo filename
 * @param string $directory Owner's native user directory (entity 1 for global users)
 * @param string $documentroot Installation's documents directory
 * @param bool $confirmed Apply the repair; false only reports planned copies
 * @return array{files:list<array{source:string,target:string}>,error:string}
 */
function repairUserPicture($id, $photo, $directory, $documentroot, $confirmed = false)
{
	$report = array('files' => array(), 'error' => '');
	if ($id <= 0 || $photo === '' || $photo !== dol_sanitizeFileName($photo) || basename($photo) !== $photo) {
		$report['error'] = 'ErrorWrongParameters';
		return $report;
	}
	if (!repairImagePathIsSafe($directory.'/'.$id, $directory, $documentroot)) {
		$report['error'] = 'RepairImagesDirectoryUnavailable';
		return $report;
	}
	$pictureuser = new User(null);
	$pictureuser->id = $id;
	$pictureuser->ref = (string) $id;
	$targetdirectory = $directory.'/'.get_exdir(0, 0, 0, 0, $pictureuser, 'user').'photos';
	$oldhierarchy = substr(sprintf('%08d', $id), -1, 1).'/'.substr(sprintf('%08d', $id), -2, 1);
	// Both historical layouts predate the current <id>/photos directory.
	$sourcedirectories = array($directory.'/'.$id, $directory.'/'.$oldhierarchy);
	$extension = pathinfo($photo, PATHINFO_EXTENSION);
	$stem = $extension !== '' ? substr($photo, 0, -strlen($extension) - 1) : $photo;
	foreach (array($photo, 'thumbs/'.$stem.'_small'.($extension !== '' ? '.'.$extension : ''), 'thumbs/'.$stem.'_mini'.($extension !== '' ? '.'.$extension : '')) as $relativefile) {
		$target = $targetdirectory.'/'.$relativefile;
		if (!repairImagePathIsSafe($target, $directory, $documentroot)) {
			$report['error'] = 'RepairImagesFailed';
			continue;
		}
		if (file_exists(dol_osencode($target))) {
			continue;
		}
		foreach ($sourcedirectories as $sourcedirectory) {
			$source = $sourcedirectory.'/'.$relativefile;
			if (!repairImagePathIsSafe($source, $directory, $documentroot)) {
				$report['error'] = 'RepairImagesFailed';
				break;
			}
			if (!dol_is_file($source)) {
				continue;
			}
			$report['files'][] = array('source' => $source, 'target' => $target);
			if ($confirmed && (dol_mkdir(dirname($target)) < 0 || dol_copy($source, $target, '0', 0) <= 0)) {
				$report['error'] = 'RepairImagesFailed';
			}
			break;
		}
	}
	return $report;
}
