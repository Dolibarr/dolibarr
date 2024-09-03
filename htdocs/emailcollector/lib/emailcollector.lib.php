<?php
/* Copyright (C) 2024       Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file    	emailcollector/lib/emailcollector.lib.php
 * \ingroup 	emailcollector
 * \brief   	Library files with common functions for EmailCollector
 */


/**
 * Prepare array of tabs for EmailCollector
 *
 * @param	EmailCollector	$object		EmailCollector
 * @return 	array						Array of tabs
 */
function emailcollectorPrepareHead($object)
{
	global $langs, $conf;

	$langs->load("emailcollector@emailcollector");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/admin/emailcollector_card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("EmailCollector");
	$head[$h][2] = 'card';
	$h++;

	/*if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath('/emailcollector/emailcollector_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}*/

	/*require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->emailcollector->dir_output . "/emailcollector/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/emailcollector/emailcollector_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= '<span class="badge marginleftonlyshort">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/emailcollector/emailcollector_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@emailcollector:/emailcollector/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@emailcollector:/emailcollector/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'emailcollector');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'emailcollector', 'remove');

	return $head;
}

/**
 * Get parts of a message
 *
 * @param 	object 			$structure 		Structure of message
 * @return 	array|false						Array of parts of the message|false if error
 */
function getParts($structure)
{
	return isset($structure->parts) ? $structure->parts : false;
}

/**
 * Array with joined files
 *
 * @param 	object 			$part 		Part of message
 * @return 	object|boolean 				Definition of message|false in case of error
 */
function getDParameters($part)
{
	return $part->ifdparameters ? $part->dparameters : false;
}

/**
 * Get attachments of a given mail
 *
 * @param 	integer $jk 	Number of email
 * @param 	object 	$mbox 	object connection imap
 * @return 	array<array{type:int,filename:string,pos:int}> 	type, filename, pos
 */
function getAttachments($jk, $mbox)
{
	$structure = imap_fetchstructure($mbox, $jk, FT_UID);  // @phan-suppress-current-line PhanTypeMismatchArgumentInternal
	$parts = getParts($structure);

	$fpos = 2;
	$attachments = array();
	$nb = count($parts);
	if ($nb && !empty($parts)) {
		for ($i = 1; $i < $nb; $i++) {
			$part = $parts[$i];

			if ($part->ifdisposition && strtolower($part->disposition) == "attachment") {
				$ext = $part->subtype;
				$params = getDParameters($part);

				if ($params) {
					$filename = $part->dparameters[0]->value;
					$filename = imap_utf8($filename);
					$attachments[] = array('type' => $part->type, 'filename' => $filename, 'pos' => $fpos);
				}
			}
			$fpos++;
		}
	}
	return $attachments;
}

/**
 * Get content of a joined file from its position into a given email
 *
 * @param integer $jk numéro du mail
 * @param integer $fpos position de la pièce jointe
 * @param integer $type type de la pièce jointe
 * @param object $mbox object connection imaap
 * @return mixed data
 */
function getFileData($jk, $fpos, $type, $mbox)
{
	$merge = imap_fetchbody($mbox, $jk, $fpos, FT_UID);  // @phan-suppress-current-line PhanTypeMismatchArgumentInternal
	$data = getDecodeValue($merge, $type);

	return $data;
}

/**
 * Save the attached file into a directory with a given name
 *
 * @param 	string 		$path 		Path to file
 * @param 	string 		$filename 	Name of file
 * @param 	mixed 		$data 		Content to save
 * @return 	string|-1 				Return the path to the saved file, or -1 if error
 **/
function saveAttachment($path, $filename, $data)
{
	$tmp = explode('.', $filename);
	$ext = array_pop($tmp);
	$filename = implode('.', $tmp);
	if (!file_exists($path)) {
		if (dol_mkdir($path) < 0) {
			return -1;
		}
	}

	$i = 1;
	$filepath = $path . $filename . '.' . $ext;

	while (file_exists($filepath)) {
		$filepath = $path . $filename . '(' . $i . ').' . $ext;
		$i++;
	}
	file_put_contents($filepath, $data);
	return $filepath;
}

/**
 * Decode content of a message
 *
 * @param 	string 		$message 	Message
 * @param 	integer 	$coding 	Type of content
 * @return 	string					Decoded message
 **/
function getDecodeValue($message, $coding)
{
	switch ($coding) {
		case 0: //text
		case 1: //multipart
			$message = imap_8bit($message);
			break;
		case 2: //message
			$message = imap_binary($message);
			break;
		case 3: //application
		case 5: //image
		case 6: //video
		case 7: //other
			$message = imap_base64($message);
			break;
		case 4: //audio
			$message = imap_qprint($message);
			break;
	}

	return $message;
}
