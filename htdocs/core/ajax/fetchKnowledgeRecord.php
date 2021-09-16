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
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
include '../../main.inc.php';

$action = GETPOST('action', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$idticketgroup = GETPOST('idticketgroup', 'aZ09');
$lang = GETPOST('lang', 'aZ09');
$popupurl = GETPOST('popupurl', 'bool');

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
			if ($popupurl == "false") {
				$url = "<a href=\"".$obj->url. "\" target=\"_blank\">".$obj->url."</a></li>";
				$response[] = array('title'=>$obj->question,'ref'=>$obj->url,'answer'=>$obj->answer,'url'=>$url);
			} else {
				$name = $obj->ref;
				$buttonstring = $obj->url;
				$url = $obj->url;
				$urltoprint = '<a class="button_'.$name.'" style="cursor:pointer">'.$buttonstring.'</a>';
				$urltoprint .= '<!-- Add js code to open dialog popup on dialog -->';
				$urltoprint .= '<script language="javascript">
							jQuery(document).ready(function () {
								jQuery(".button_'.$name.'").click(function () {
									console.log("Open popup with jQuery(...).dialog() on URL '.dol_escape_js($url).'")
									var $dialog = $(\'<div></div>\').html(\'<iframe class="iframedialog" style="border: 0px;" src="'.$url.'" width="100%" height="98%"></iframe>\')
										.dialog({
											autoOpen: false,
											modal: true,
											height: (window.innerHeight - 150),
											width: \'80%\',
											title: "'.dol_escape_js($label).'"
										});
									$dialog.dialog(\'open\');
								});
							});
						</script>';
				$response[] = array('title'=>$obj->question,'ref'=>$obj->url,'answer'=>$obj->answer,'url'=>$urltoprint);
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	$response =json_encode($response);
	echo $response;
}
