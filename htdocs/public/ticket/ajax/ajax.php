<?php
/**
 * Copyright (C) 2020 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\file       htdocs/public/ticket/ajax/ajax.php
 *	\brief      Ajax component for Ticket.
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

include_once '../../../main.inc.php'; // Load $user and permissions

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$email = GETPOST('email', 'alphanohtml');


/*
 * View
 */

top_httphead();

if ($action == 'getContacts') {
	$return = array(
		'contacts' => array(),
		'error' => '',
	);

	if (!empty($email)) {
		require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';

		$ticket = new Ticket($db);
		$contacts = $ticket->searchContactByEmail($email);
		if (is_array($contacts)) {
			$return['contacts'] = $contacts;
		} else {
			$return['error'] = $ticket->errorsToString();
		}
	}

	echo json_encode($return);
	exit();
}
