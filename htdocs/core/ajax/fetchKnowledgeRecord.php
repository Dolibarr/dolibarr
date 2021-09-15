<?php
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
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
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

include '../../main.inc.php';

$action = GETPOST('action', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$lang = GETPOST('lang', 'aZ09');

/*
 * Actions
 */

// None


/*
 * View
 */

if ($action == "getKnowledgeRecord") {
	$response = '';
	$sql = "SELECT kr.rowid, kr.ref, kr.question, kr.answer,l.url,ctc.code";
	$sql .= " FROM ".MAIN_DB_PREFIX."links as l";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."knowledgemanagement_knowledgerecord as kr ON kr.rowid = l.objectid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."c_ticket_category as ctc ON ctc.rowid = kr.fk_c_ticket_category";
	$sql .= " WHERE ctc.code = '".$db->escape($idticketgroup)."'";
	$sql .= " AND ctc.active = 1 AND ctc.public = 1 AND (kr.lang = '".$db->escape($lang)."' OR kr.lang = 0)";
	$sql .= " AND kr.status = 1";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$response = array();
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$response[] = array('title'=>$obj->question,'ref'=>$obj->url,'answer'=>$obj->answer,'url'=>$obj->url);
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	$response =json_encode($response);
	echo $response;
}
