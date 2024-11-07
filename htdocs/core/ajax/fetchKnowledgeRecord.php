<?php
/* Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 */
/*
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

/**
 *	\file       /htdocs/core/ajax/fetchKnowledgeRecord.php
 *	\brief      File to make Ajax action on Knowledge Management
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If we need access without being logged.
if (!empty($_GET['public'])) {	// Keep $_GET here. GETPOST() is not yet defined so we use $_GET
	if (!defined("NOLOGIN")) {
		define("NOLOGIN", '1');
	}
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
include '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$lang = GETPOST('lang', 'aZ09');

// Security check
if (!defined("NOLOGIN")) {	// No need of restrictedArea if not logged: Later the select will filter on public articles only if not logged.
	restrictedArea($user, 'knowledgemanagement', 0, 'knowledgemanagement_knowledgerecord', 'knowledgerecord');
}


/*
 * Actions
 */

// None


/*
 * View
 */

top_httphead('application/json');

if ($action == "getKnowledgeRecord") {
	$response = '';
	$sql = "SELECT kr.rowid, kr.ref, kr.question, kr.answer,kr.url,ctc.code";
	$sql .= " FROM ".MAIN_DB_PREFIX."knowledgemanagement_knowledgerecord as kr ";
	$sql .= " JOIN ".MAIN_DB_PREFIX."c_ticket_category as ctc ON ctc.rowid = kr.fk_c_ticket_category";
	$sql .= " WHERE ctc.code = '".$db->escape($idticketgroup)."'";
	$sql .= " AND ctc.active = 1";
	if (defined("NOLOGIN")) {
		$sql .= " AND ctc.public = 1";
	}
	$sql .= " AND (kr.lang = '".$db->escape($lang)."' OR kr.lang = 0 OR kr.lang IS NULL)";
	$sql .= " AND kr.status = 1 AND (kr.answer IS NOT NULL AND kr.answer <> '')";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$response = array();
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$response[] = array('title'=>$obj->question,'ref'=>$obj->ref,'answer'=>dol_escape_htmltag(preg_replace('/\\r|\\r\\n|\\n/', "", $obj->answer)),'url'=>$obj->url);
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	$response =json_encode($response);
	echo $response;
}
