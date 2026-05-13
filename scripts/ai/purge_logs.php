<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file htdocs/ai/scripts/purge_logs.php
 * \ingroup ai
 * \brief Script to delete mcp ai assistant log records
 */

if (php_sapi_name() === 'cli') {
	if (!defined('NOLOGIN')) {
		define('NOLOGIN', 1);
	}
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}

require __DIR__ . '/../../main.inc.php';

/** @var DoliDB $db */

// Security check for web access
if (php_sapi_name() !== 'cli') {
	if (!isModEnabled('ai')) {
		accessforbidden('Module not allowed');
	}
	if (empty($user->admin)) {
		accessforbidden('Admin access required');
	}
}

$retention = getDolGlobalInt('AI_LOG_RETENTION');

if ($retention > 0) {
	$limitDate = dol_now() - ($retention * 86400);
	$chunkSize = 1000; // Delete 1000 rows at a time
	$totalDeleted = 0;

	$db->begin();

	while (true) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "ai_request_log";
		$sql .= " WHERE date_request < '" . $db->idate($limitDate) . "'";
		$sql .= " AND entity IN (" . getEntity('airequestlog') . ")";
		$sql .= " LIMIT " . ((int) $chunkSize);

		$resql = $db->query($sql);
		if (!$resql) {
			$db->rollback();
			print "ERROR: " . $db->lasterror() . "\n";
			exit(1);
		}

		$rowsAffected = $db->affected_rows($resql);
		$totalDeleted += $rowsAffected;

		// If no rows were deleted, we are done
		if ($rowsAffected < 1) {
			break;
		}

		// Sleep briefly to reduce server load
		usleep(50000); // 0.05 seconds
	}

	$db->commit();
	print "OK: Purged {$totalDeleted} log(s) older than {$retention} days.\n";
}
