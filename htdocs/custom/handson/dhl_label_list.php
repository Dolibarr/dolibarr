<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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

/**
 *    \file       handson/handsonindex.php
 *    \ingroup    handson
 *    \brief      Home page of handson top menu
 */


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


// Security check
if (!$user->rights->handson->createdhllabel) {
	accessforbidden();
}

dol_include_once('/custom/handson/class/label.class.php');
dol_include_once('/custom/handson/lib/handson_label.lib.php');
dol_include_once('/core/class/form.class.php');
$labelstatic = new Label($db);
$form = new Form($db);

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$rowid = GETPOST('rowid', 'alpha');

llxHeader();

// Actions
if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
	$soapxmlstring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:cis=\"http://dhl.de/webservice/cisbase\" xmlns:ns=\"http://dhl.de/webservices/businesscustomershipping/3.0\">
   <soapenv:Header>
      <cis:Authentification>
         <cis:user>".$conf->global->DHL_USER."</cis:user>
         <cis:signature>".$conf->global->DHL_SIGNATURE."</cis:signature>
      </cis:Authentification>
   </soapenv:Header>
   <soapenv:Body>
      <ns:DeleteShipmentOrderRequest>
   		<ns:Version>
            <majorRelease>3</majorRelease>
            <minorRelease>2</minorRelease>
        </ns:Version>
		<cis:shipmentNumber>" . GETPOST('shipnum', 'int') . "</cis:shipmentNumber>
      </ns:DeleteShipmentOrderRequest>
   </soapenv:Body>
</soapenv:Envelope>";

	$auth = base64_encode($conf->global->DHL_APP_ID . ':' . $conf->global->DHL_APP_TOKEN);

	$curl = curl_init();
	curl_setopt_array($curl, array(
			CURLOPT_URL => $conf->global->DHL_SEND_SOAP_ENDPOINT,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $soapxmlstring,
			CURLOPT_HTTPHEADER => array(
				"content-type: text/xml; charset=utf-8",
				'authorization: Basic ' . $auth,
				"SOAPAction: urn:deleteShipmentOrder",
			)
		)
	);

	$response = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);

	$statusCode[0] = -1;
	$statusCode = explode('<statusCode>', $response);
	$statusCode = explode('</statusCode>', $statusCode[1]);

	if($statusCode[0] == '0') {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "handson_label WHERE rowid=" . ((int)$rowid);

		dol_syslog("delete", LOG_DEBUG);
		$result = $db->query($sql);
		if (!$result) {
			if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	} else {
		print $form->formconfirm('dhl_label_list.php', 'Fehler', 'Versandlabel konnte nicht storniert werden. Bitte im Geschäftskundenportal prüfen.', '', '', '', 1);
	}
}



if ($action == 'delete') {
	$page = 'custom/handson/dhl_label_list.php';
	$shipnum = GETPOST('shipnum', 'int');
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?' . ($page ? 'page=' . $page . '&' : '') . 'shipnum=' . $shipnum . '&rowid=' . $rowid, $langs->trans('Versandlabel löschen'), $langs->trans('Möchtest du dieses Versandlabel wirklich löschen?'), 'confirm_delete', '', 0, 1);
}

if ($action == 'sendConf') {
	$shipnum = GETPOST('shipnum', 'int');
	print $form->formconfirm('createshipment.php?shipnum=' . $shipnum, $langs->trans('Bestätigungsmail'), $langs->trans('Bestätigungsmail jetzt senden?'), 'sendConf', '', 0, 1);
}



print load_fiche_titre($langs->trans("DHL Versandlabel Verwaltung"), '', 'stock');

print "<script>function openPrintLabel(shipnum) {
    window.open('createshipment.php?action=printLabel&shipnum=' + shipnum, 'targetWindow',
		`toolbar=no,
		location=no,
		status=no,
		menubar=no,
		scrollbars=no,
		resizable=no,
		width=800,
		height=800`);
		}</script>";
print '<div class="fichecenter"><div class="fichehalfleft">';

if (!empty($conf->handson->enabled) && $user->rights->handson->label->read) {

	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "handson_label as c ORDER BY tms DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>' . $langs->trans("Sendungsnummer") . '</th>';
		print '<th>' . $langs->trans("EmpfängerIn") . '</th>';
		print '<th colspan="4">' . $langs->trans("Erstellungsdatum") . '</th>';
		//print '<th class="center">' . $langs->trans("Bestätigung") . '</th>';
		//print '<th>' . $langs->trans("Label erneut herunterladen") . '</th>';
		print '</tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);

				$url = $_SERVER["PHP_SELF"] . '?' . ($page ? 'page=' . $page . '&' : '') . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
				if ($param) $url .= '&' . $param;
				$url .= '&';

				print '<tr class="oddeven"><td class="nowrap">';

				$labelstatic->id = $obj->rowid;
				$labelstatic->ref = $obj->ref;
				$obj->total_ttc = 25;

				print '<a href="https://www.dhl.com/de-de/home/sendungsverfolgung.html?tracking-id='.$labelstatic->ref.'" target="_blank">'.$labelstatic->ref.'</a>';
				print '</td>';
				print '<td class="nowrap">';
				print $labelstatic->showOutputField($labelstatic->fields['contact'], 'contact', $obj->contact);
				print '</td>';
				print '<td class="nowrap">';
				print printDate($obj->tms);
				print '</td>';
				print '<td class="center">';
				print $obj->mail_sent == 1 ? img_picto('Bestätigungs E-Mail gesendet', 'check') : '<a href="dhl_label_list.php?action=sendConf&shipnum='.$obj->ref.'">'.img_picto('Bestätigungs E-Mail jetzt senden', 'envelope').'</a>';
				print '</td>';
				print '<td class="center">';
				print '<a href="#" onclick="openPrintLabel(\''.$labelstatic->ref.'\')">'.img_picto('Label erneut herunterladen', 'download').'</a>';
				print '</td>';
				print '<td class="center">';
				if (labelIsEditable($obj->tms)) {
					//print '<a class="reposition editfielda" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
					print '<a href="' . $url . 'action=delete&shipnum=' . $labelstatic->ref . '&token=' . newToken() . '">' . img_delete('Sendung löschen/stornieren') . '</a>';
				} else {
					print img_edit_remove('zu spät für Storno');
				}
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div><div class="fichehalfright">';


llxFooter();
?>
