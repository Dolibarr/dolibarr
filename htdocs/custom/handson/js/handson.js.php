<?php
/* Copyright (C) 2021 Kuba admin <js@hands-on-technology.org>
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

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) define('NOREQUIREDB', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');


/**
 * \file    handson/js/handson.js.php
 * \ingroup handson
 * \brief   JavaScript file for module HandsOn.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
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
let root, context;
function addExportButton(dol_doc_root, cont) {
	$('td.nobordernopadding.valignmiddle.center').append('<input type="button" class="button" value="Exportieren" onclick="showExportWindow();">');
	root = dol_doc_root;
	context = cont;
}

function exportListExcel() {
	window.open(root + 'excelexport.php?query=' + $('#query').val() + '&columns=' + $('#columns').val() + '&context=' + context, 'targetWindow',
		`toolbar=no,
		location=no,
		status=no,
		menubar=no,
		scrollbars=no,
		resizable=no,
		width=800,
		height=800`);
}

function exportListDHL() {
	window.open(root + 'dhlexport.php?query=' + $('#query').val() + '&context=' + context, 'targetWindow',
		`toolbar=no,
		location=no,
		status=no,
		menubar=no,
		scrollbars=no,
		resizable=no,
		width=800,
		height=800`);
}

function getDataFromForm (data, address) {
	$('#shipConfForm input, #shipConfForm select').change(function (e) {
		switch(e.target.id) {
			case 'name':
				let name = $(e.target).val().split(' ');
				address[0] = name[0];
				address[1] = name[1];
				break;
			case 'street':
				address[2] = $(e.target).val();
				break;
			case 'number':
				address[3] = $(e.target).val();
				break;
			case 'street2':
				address[4] = $(e.target).val();
				break;
			case 'street3':
				address[5] = $(e.target).val();
				break;
			case 'zip':
				address[6] = $(e.target).val();
				break;
			case 'city':
				address[7] = $(e.target).val();
				break;
			case 'email':
				address[8] = $(e.target).val();
				break;
			case 'weight':
				data[1] = $(e.target).val();
				break;
			case 'width':
				data[2] = $(e.target).val();
				break;
			case 'height':
				data[3] = $(e.target).val();
				break;
			case 'depth':
				data[4] = $(e.target).val();
				break;
			case 'costcenter':
				data[5] = $(e.target).val();
				break;
			case 'reference':
				data[6] = $(e.target).val();
				break;
			case 'shipdate':
				data[7] = $(e.target).val();
				break;
			case 'sendmail':
				data[8] = $(e.target).val();
				break;
		}
		addButtons(data, address);
	});
	addButtons(data, address);
}

function createShipmentLabel(data, address) {
	window.open('/custom/handson/createshipment.php?action=create&data=' + data + '&address=' + address, 'targetWindow',
		`toolbar=no,
		location=no,
		status=no,
		menubar=no,
		scrollbars=no,
		resizable=no,
		width=800,
		height=800`);
}

function validateLabelCreation(data, address) {
	console.log(address);
	$.ajax({
		url: '/custom/handson/createshipment.php?action=validate&data=' + data + '&address=' + address,
		type: 'GET',
		success: function (response) {
			let arr = response.split(';');
			$('#statusMessage').empty();
			if(arr[0] == 0) {
				$('#statusMessage').append('Das sieht gut aus!');
			} else {
				$('#statusMessage').append('DHL sagt: ' + arr[1]);
			}
		}
	});
}

function fromBinary(encoded) {
	binary = atob(encoded)
	const bytes = new Uint8Array(binary.length);
	for (let i = 0; i < bytes.length; i++) {
		bytes[i] = binary.charCodeAt(i);
	}
	return String.fromCharCode(...new Uint16Array(bytes.buffer));
}

function addButtons(data, address) {
	$('#shipConfButtons')
		.empty()
		.append('<br>')
		.append('<a class="butAction" href="#" onclick="validateLabelCreation(\'' + btoa(data) + '\', \'' + btoa(address) + '\')">Eingaben prüfen</a>')
		.append('<a class="butAction" href="#" onclick="createShipmentLabel(\'' + btoa(data) + '\', \'' + btoa(address) + '\')">Label erzeugen</a>')
		.append('<a class="butActionDelete" href="#" onclick="closePopUp(\'#shipLabelConfirm\')">Abbrechen</a>');
}

function checkCreateShipmentLabel(datastring, addrstring) {
	let data = atob(datastring).split(';');
	let address = atob(addrstring).split(';');
	console.log(address);

	$('#mainbody')
		.append('<div role="dialog" class="hotPopUp ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" id="shipLabelConfirm">')
		.append('<div id="overlay" style="z-index: 100; width: 100vw; height: 100vh; background: rgba(255,255,255,.5); top: 0; left: 0; position: fixed;"></div>');

	$('#shipLabelConfirm')
		.append('<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix" id="shipConfTitle">')
		.append('<div class="hotPopUpContent ui-dialog-content ui-widget-content" id="shipConfContent">')
		.append('<div class="hotPopUpButtons ui-dialog-buttonpane ui-widget-content ui-helper-clearfix" id="shipConfButtons">');

	$('#shipConfTitle')
		.append('<span id="ui-id-1" class="ui-dialog-title">Versandlabel erstellen</span>')
		.append('<button type="button" onclick="closePopUp(\'#shipLabelConfirm\');" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close"><span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"> </span>Close</button>');

	$('#shipConfContent')
		.append('<form action="action" id="shipConfForm" method="POST">' +
			'<div>Vor- und Zuname <input type="text" id="name" value="' + address[0] + ' ' + address[1] + '" size="2" ></div>' +
			'<div>Straße <span><input type="text" id="street" value="' + address[2] + '" size="20" > Nr. <input type="text" id="number" value="' + address[3] + '" size="2"></span></div>' +
			'<div>Zusatz 2 <input type="text" id="street2" value="' + address[4] + '" size="2" ></div>' +
			'<div>Zusatz 3 <input type="text" id="street3" value="' + address[5] + '" size="2" ></div>' +
			'<div>PLZ <span><input type="text" id="zip" value="' + address[6] + '" size="4" > Stadt <input type="text" id="city" value="' + address[7] + '" size="12"></span></div>' +
			'<div>Empf. E-Mail: <input type="text" id="email" value="' + address[8] + '" size="2" ></div>' +
			'<div>Gewicht <input type="text" id="weight" value="' + data[1] + '" size="1" > kg</div>' +
			'<div>Maße (BxHxT) <span><input type="text" id="width" value="' + data[2] + '" size="2" > x <input type="text" id="height" value="' + data[3] + '" size="2"> x <input type="text" id="depth" value="' + data[4] + '" size="2"> cm</span></div>' +
			'<div>Kostenstelle <select id="costcenter">' +
			'<option value="error" selected>Bitte wählen!</option>' +
			'<option value="61812053340101">FLL Challenge</option>' +
			'<option value="61812053340102">FLL Explore</option>' +
			'<option value="61812053340103">HoT e.V.</option>' +
			'<option value="61812053340701">DHL Retoure Online</option>' +
			'<option value="61812053345301">FLL Challenge International</option>' +
			'<option value="61812053345302">FLL Explore International</option>' +
			'<option value="61812053345303">HoT e.V. International</option>' +
			'<option value="61812053345304">DHL Retoure int. A</option>' +
			'<option value="61812053345305">DHL Retoure int. B</option>' +
			'</select>' +
			'</div>' +
			'<div>Referenz <input type="text" id="reference" value="' + data[6] + '" size="6" ></div>' +
			'<div>Versanddatum <input type="text" id="shipdate" value="' + data[7] + '" size="6" ></div>' +
			'<div>Automatische E-Mail an Empfänger <input type="checkbox" id="sendmail"></div>' +
			'<div id="statusMessage"></div>' +
			'</form>');

	getDataFromForm(data, address);

	$('#shipConfForm').css({'display': 'flex', 'flex-direction': 'column'});
	$('#shipConfForm>div').css({'display': 'grid', 'grid-template-columns': '10em auto auto'});

	$('#overlay').click(e => {
		$('#shipLabelConfirm').remove();
		$('#overlay').remove();
	});

	$('#shipLabelTitle').on('mousedown', function () {
		$('#shipLabelConfirm').draggable({disabled: false});
	});
	$('#shipLabelTitle').on('mouseleave', function () {
		$('#shipLabelConfirm').draggable({disabled: true});
	});
}

function closePopUp(id) {
	$(id).remove();
	$('#overlay').remove();
}

function showExportWindow() {
	$('#mainbody')
		.append('<div role="dialog" class="ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-draggable" aria-describedby="dialog-confirm" aria-labelledby="ui-id-1" id="mailExp">')
		.append('<div id="overlay" style="z-index: 100; width: 100vw; height: 100vh; background: rgba(255,255,255,.5); top: 0; left: 0; position: fixed;"></div>');
	$('#mailExp')
		.append('<div class="ui-dialog-titlebar ui-corner-all ui-widget-header ui-helper-clearfix" id="mailExpTitle">')
		.append('<div class="ui-dialog-content ui-widget-content" id="mailExpContent">')
		.append('<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix" id="mailExpButtons">');

	$('#mailExpTitle')
		.append('<span id="ui-id-1" class="ui-dialog-title">Exportieren</span>')
		.append('<button type="button" onclick="closeMailExp();" class="ui-button ui-corner-all ui-widget ui-button-icon-only ui-dialog-titlebar-close" title="Close"><span class="ui-button-icon ui-icon ui-icon-closethick"></span><span class="ui-button-icon-space"> </span>Close</button>');

	$('#mailExpContent').append("<p>In welches Format soll exportiert werden?</p>");
	$('#mailExpContent').append("<ul>" +
		"<li>Für den Excel Export werden die gewählten Spalten und Filter übernommen.</li>" +
		"<li>Für den DHL- und Post-Export werden nur die Filter übernommen; die Spalten sind fix (Vorlage).</li>" +
		"</ul>" +
		"<h3>Beachte, dass bei vielen ausgewählten Kontakten das Exportieren der Datei länger dauert.</h3>");
	//$('#mailExpContent').append("<br><p>Einstellungen für DHL/Post Export");
	//$('#mailExpContent').append('<select name="socinst"><option>Geschäftspartner</option><option>Institution</option></select></p>');

	$('#mailExpButtons')
		.append('<br>')
		.append('<a class="button" href="#" onclick="exportListExcel()">als Excel-Datei</a>')
		.append('<a class="button" href="#" onclick="exportListDHL()">als CSV für DHL</a>')
		.append('<a class="button" href="#" onclick="" style="pointer-events: none; cursor: default; color: lightgrey !important;">als CSV für die Post</a>');

	$('#overlay').click(e => {
		$('#mailExp').remove();
		$('#overlay').remove();
	});

	$('#mailExpTitle').on('mousedown', function () {
		$('#mailExp').draggable({disabled: false});
	});
	$('#mailExpTitle').on('mouseleave', function () {
		$('#mailExp').draggable({disabled: true});
	});
}
