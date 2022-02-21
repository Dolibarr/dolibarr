<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 */

/**
 * \file    lib/handson_label.lib.php
 * \ingroup handson
 * \brief   Library files with common functions for Label
 */

/**
 * Prepare array of tabs for Label
 *
 * @param Label $object Label
 * @return    array                    Array of tabs
 */
function labelPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("handson@handson");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/handson/label_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/handson/label_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->handson->dir_output . "/label/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/handson/label_document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/handson/label_agenda.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'label@handson');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'label@handson', 'remove');

	return $head;
}

function labelIsEditable($date_creation)
{
	$date_creation = explode(' ', $date_creation);
	$day = new DateTime($date_creation[0], new DateTimeZone('Europe/Berlin'));
	$time = new DateTime($date_creation[1], new DateTimeZone('Europe/Berlin'));
	$evening = new DateTime('18:00', new DateTimeZone('Europe/Berlin'));

	if (date('Y-m-d') == $day->format('Y-m-d') && (date('H:i') < $evening->format('H:i') || $time->format('H:i') >= $evening->format('H:i'))) {
		return true;
	} elseif (date('Y-m-d') == $day->add(DateInterval::createFromDateString('1 day'))->format('Y-m-d') && $time->format('H:i') >= $evening->format('H:i') && date('H:i') < $evening->format('H:i')) {
		return true;
	} else {
		return false;
	}
}

function printDate($tms)
{
	$date = new DateTime($tms, new DateTimeZone('Europe/Berlin'));
	return $date->format('d.m.Y H:i');
}

function sendShipmentConfirmation($subs_arr, $to, $cc = '', $bcc = '', $from = 'HANDS on TECHNOLOGY e.V.<info@hands-on-technology.org>')
{

	global $db, $langs, $conf;

	dol_include_once('/core/class/CMailFile.class.php');

	$sql = "SELECT topic, content, joinfiles FROM llx_c_email_templates WHERE label='AutoMailDhlShipment'";
	$result = $db->query($sql)->fetch_array(MYSQLI_ASSOC);

	if ($result['joinfiles'] == '1') {
		$file = '';
		$mime = '';
		$filenames = '';
		//$file = ($foerd == "1") ? array() : array(DOL_DATA_ROOT . '/commande/' . $order->ref . '/' . $order->ref . '.pdf');
		//$mime = ($foerd == "1") ? array() : array('application/pdf');
		//$filenames = ($foerd == "1") ? array() : array($order->ref . '.pdf');
	} else {
		$file = '';
		$mime = '';
		$filenames = '';
	}

	$content = $result['content'];
	getCommonSubstitutionArray();
	/*$content = str_replace('__NAME__', $subs_arr['name'], $content);
	$content = str_replace('__SHIPNUMLINK__', 'https://www.dhl.de/de/privatkunden.html?piececode=' . $subs_arr['shipnum'], $content);
	$content = str_replace('__SHIPNUM__', $subs_arr['shipnum'], $content);*/

	$mailfile = new CMailFile($result['topic'], $to, $from, $content, $file, $mime, $filenames, $cc, $bcc, 0, 1, '', '', '', '', 'mail');

	if ($mailfile->sendfile()) {
		$sql = "UPDATE llx_handson_label SET mail_sent = 1 WHERE ref LIKE '" . $subs_arr['shipnum'] . "'";
		$db->query($sql);
	} else {
		$error = $langs->trans("ErrorFailedToSendMail", $from, $this->email) . '. ' . $mailfile->error;
	}
}
