<?php
/* Copyright (C) 2021 admin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');


/**
 * \file    maillinkgenerator/js/maillinkgenerator.js.php
 * \ingroup maillinkgenerator
 * \brief   JavaScript file for module MailLinkGenerator.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>

/* Javascript library of module MailLinkGenerator */
var addresses = new Array();
function collectMailAddress(mailAddr) {
	addresses.push(mailAddr);
}

function closeMailExp() {
	$('#mailExp').remove();
	$('#overlay').remove();
}

function inZwAbl() {
	$('#addresses').select();
	document.execCommand('copy');
}

function exportMailAddresses() {

	$('#mainbody')
		.append('<div role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" id="mailExp">')
		.append('<div id="overlay" style="z-index: 100; width: 100vw; height: 100vh; background: rgba(255,255,255,.5); top: 0; left: 0; position: fixed;"></div>');
	$('#mailExp')
		.append('<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix" id="mailExpTitle">')
		.append('<div class="ui-dialog-content ui-widget-content" id="mailExpContent">')
		.append('<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix" id="mailExpButtons">');

	$('#mailExpTitle')
		.append('<span id="ui-id-1" class="ui-dialog-title">E-Mailadressen</span>')
		.append('<button type="button" onclick="closeMailExp();" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close"><span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"> </span>Close</button>');
	let addrShow = '';
	for(let e of addresses) {
		addrShow += e + '; ';
	}
	$('#mailExpContent')
		.append('<textarea id="addresses">' + addrShow + '</textarea>')
	$('#mailExpButtons')
		.append('<br>')
		.append('<a class="button" href="#" onclick="inZwAbl()">in Zwischenablage</a>')
		.append('<a class="button" href="mailto:' + addrShow + '">Empfänger</a>')
		.append('<a class="button" href="mailto:?cc=' + addrShow + '">als CC</a>')
		.append('<a class="button" href="mailto:?bcc=' + addrShow + '">als BCC</a>');

	$('#overlay').click(e => {
		$('#mailExp').remove();
		$('#overlay').remove();
	});

	$('#mailExpTitle').on('mousedown', function() {
		$('#mailExp').draggable({disabled: false});
	});
	$('#mailExpTitle').on('mouseleave', function() {
		$('#mailExp').draggable({disabled: true});
	});
}


//$(document).ready(function () {
function addMailButton() {
	$('td.nobordernopadding.valignmiddle.center').append('<input type="button" class="button" value="Mail Link generieren" onclick="exportMailAddresses();">');
}

