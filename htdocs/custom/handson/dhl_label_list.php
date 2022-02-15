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

// Actions

if ($action == 'confirm_delete' && $confirm == 'yes')       // delete
{
	$soapxmlstring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:cis=\"http://dhl.de/webservice/cisbase\" xmlns:ns=\"http://dhl.de/webservices/businesscustomershipping/3.0\">
   <soapenv:Header>
      <cis:Authentification>
         <cis:user>2222222222_01</cis:user>
         <cis:signature>pass</cis:signature>
      </cis:Authentification>
   </soapenv:Header>
   <soapenv:Body>
      <ns:DeleteShipmentOrderRequest>
   		<ns:Version>
            <majorRelease>3</majorRelease>
            <minorRelease>2</minorRelease>
        </ns:Version>
		<shipmentNumber>" . GETPOST('shipnum', 'int') . "</shipmentNumber>
      </ns:DeleteShipmentOrderRequest>
   </soapenv:Body>
</soapenv:Envelope>";

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
}

llxHeader();

if ($action == 'delete') {
	$page = 'custom/handson&/dhl_label_list.php';
	$shipnum = GETPOST('shipnum', 'int');
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?' . ($page ? 'page=' . $page . '&' : '') . '&shipnum=' . $shipnum . '&rowid=' . $rowid, $langs->trans('Versandlabel löschen'), $langs->trans('Möchtest du dieses Versandlabel wirklich löschen?'), 'confirm_delete', '', 0, 1);
}


print load_fiche_titre($langs->trans("DHL Versandlabel Verwaltung"), '', 'stock');


print '<div class="fichecenter"><div class="fichehalfleft">';

if (!empty($conf->handson->enabled) && $user->rights->handson->label->read) {

	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "handson_label as c ORDER BY date_creation DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="1">' . $langs->trans("Sendungsnummer") . '</th>';
		print '<th colspan="1">' . $langs->trans("EmpfängerIn") . '</th>';
		print '<th colspan="1">' . $langs->trans("Erstellungsdatum") . '</th>';
		print '<th colspan="1">' . $langs->trans("Bestätigungsmail") . '</th>';
		print '<th></th></tr>';


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


				print $labelstatic->ref;
				print '</td>';
				print '<td class="nowrap">';
				print $labelstatic->showOutputField($labelstatic->fields['contact'], 'contact', $obj->contact);
				print '</td>';
				print '<td class="nowrap">';
				print $labelstatic->showOutputField($labelstatic->fields['date_creation'], 'date_creation', $obj->date_creation);
				print '</td>';
				print '<td>';
				print $obj->mail_sent == 1 ? img_picto('gesendet', 'check') : img_picto('nicht gesendet', 'envelope');
				print '</td>';
				print '<td>';
				if (labelIsEditable($obj->date_creation)) {
					print '<a class="reposition editfielda" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
					print '<a class="marginleftonly" href="' . $url . 'action=delete&shipnum=' . $labelstatic->ref . '&token=' . newToken() . '">' . img_delete() . '</a>';
				} else {
					print img_edit_remove();
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
