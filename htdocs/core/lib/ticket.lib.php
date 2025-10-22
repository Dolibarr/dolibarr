<?php
/* Copyright (C) 2013-2018	Jean-François FERRY	<hello@librethic.io>
 * Copyright (C) 2016		Christophe Battarel	<christophe@altairis.fr>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
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
 * \file       core/lib/ticket.lib.php
 * \ingroup    ticket
 * \brief      This file is a library for Ticket module
 */

/**
 * Build tabs for admin page
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function ticketAdminPrepareHead()
{
	global $langs, $conf, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('ticket');

	$langs->load("ticket");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/ticket.php';
	$head[$h][1] = $langs->trans("TicketSettings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/ticket_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsTicket");
	$nbExtrafields = $extrafields->attributes['ticket']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/ticket_public.php';
	$head[$h][1] = $langs->trans("PublicInterface");
	$head[$h][2] = 'public';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//    'entity:+tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//    'entity:-tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'ticketadmin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ticketadmin', 'remove');

	return $head;
}

/**
 *  Build tabs for a Ticket object
 *
 *  @param	Ticket	  $object		Object Ticket
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function ticket_prepare_head($object)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();
	$head[$h][0] = DOL_URL_ROOT.'/ticket/card.php?track_id='.$object->track_id;
	$head[$h][1] = $langs->trans("Ticket");
	$head[$h][2] = 'tabTicket';
	$h++;

	if (!getDolGlobalInt('MAIN_DISABLE_CONTACTS_TAB') && empty($user->socid) && isModEnabled("societe")) {
		$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
		$head[$h][0] = DOL_URL_ROOT.'/ticket/contact.php?track_id='.$object->track_id;
		$head[$h][1] = $langs->trans('ContactsAddresses');
		if ($nbContact > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
		}
		$head[$h][2] = 'contact';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket', 'add', 'core');

	// Attached files
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->ticket->dir_output."/".$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files'));
	/* disabled. Too many bugs. All file of a ticket must be stored into ticket. File must be linked to an event by column agenda_id into llx_ecmfiles.
	$sql = 'SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm';
	$sql .= " WHERE fk_element = ".(int) $object->id." AND elementtype = 'ticket'";
	$resql = $db->query($sql);
	if ($resql) {
		$numrows = $db->num_rows($resql);
		for ($i=0; $i < $numrows; $i++) {
			$upload_msg_dir = $conf->agenda->dir_output.'/'.$db->fetch_row($resql)[0];
			$nbFiles += count(dol_dir_list($upload_msg_dir, "files"));
		}
	}
	*/
	$head[$h][0] = DOL_URL_ROOT.'/ticket/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if ($nbFiles > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFiles.'</span>';
	}

	$head[$h][2] = 'tabTicketDocument';
	$h++;


	// History
	$ticketViewType = "messaging";
	if (empty($_SESSION['ticket-view-type'])) {
		$_SESSION['ticket-view-type'] = $ticketViewType;
	} else {
		$ticketViewType = $_SESSION['ticket-view-type'];
	}

	if ($ticketViewType == "messaging") {
		$head[$h][0] = DOL_URL_ROOT.'/ticket/messaging.php?track_id='.$object->track_id;
	} else {
		// $ticketViewType == "list"
		$head[$h][0] = DOL_URL_ROOT.'/ticket/agenda.php?track_id='.$object->track_id;
	}
	$head[$h][1] = $langs->trans('Events');
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'tabTicketLogs';
	$h++;


	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket', 'remove');

	return $head;
}

/**
 * Return string with full Url. The file qualified is the one defined by relative path in $object->last_main_doc
 *
 * @param   Object	$object				Object
 * @return	string						Url string
 */
function showDirectPublicLink($object)
{
	global $conf, $langs;

	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	$email = CMailFile::getValidAddress($object->origin_email, 2);
	$url = '';
	if ($email) {
		$url = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', dol_buildpath('/public/ticket/', 3)).'view.php?track_id='.$object->track_id.'&email='.$email;
	}

	$out = '';
	if (!getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE')) {
		$langs->load('errors');
		$out .= '<span class="opacitymedium">'.$langs->trans("ErrorPublicInterfaceNotEnabled").'</span>';
	} else {
		$out .= img_picto('', 'object_globe.png').' <span class="opacitymedium">'.$langs->trans("TicketPublicAccess").'</span><br>';
		if ($url) {
			$out .= '<div class="urllink">';
			$out .= '<input type="text" id="directpubliclink" class="quatrevingtpercentminusx" spellcheck="false" value="'.$url.'">';
			$out .= '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'object_globe.png', 'class="paddingleft"').'</a>';
			$out .= '</div>';
			$out .= ajax_autoselect("directpubliclink", '');
		} else {
			$out .= '<span class="opacitymedium">'.$langs->trans("TicketNotCreatedFromPublicInterface").'</span>';
		}
	}

	return $out;
}

/**
 *  Generate a random id
 *
 *  @param  int 	$car 	Length of string to generate key
 *  @return string
 */
function generate_random_id($car = 16)
{
	$string = "";
	$chaine = "abcdefghijklmnopqrstuvwxyz123456789";
	mt_srand((int) ((float) microtime() * 1000000));
	for ($i = 0; $i < $car; $i++) {
		$string .= $chaine[mt_rand() % strlen($chaine)];
	}
	return $string;
}

/**
 * Show http header, open body tag and show HTML header banner for public pages for tickets
 *
 * @param  string		$title       Title
 * @param  string		$head        Head array
 * @param  int<0,1>		$disablejs   More content into html header
 * @param  int<0,1>		$disablehead More content into html header
 * @param  string[]		$arrayofjs   Array of complementary js files
 * @param  string[]		$arrayofcss  Array of complementary css files
 * @return void
 */
function llxHeaderTicket($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, 1); // Show html headers

	print '<body id="mainbody" class="publicnewticketform">';
	print '<div class="publicnewticketform2 centpercent" style="min-height: 100%;">';

	htmlPrintOnlineHeader($mysoc, $langs, (getDolGlobalInt('TICKET_SHOW_COMPANY_LOGO') ? getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', dol_buildpath('/public/ticket/index.php?entity='.$conf->entity, 1)) : '0'), getDolGlobalString('TICKET_PUBLIC_INTERFACE_TOPIC', $langs->trans("TicketSystem")), 'TICKET_IMAGE_PUBLIC_INTERFACE');
}
