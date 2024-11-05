<?php
/* Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/core/ajax/box.php
 *       \brief      File to return Ajax response on a Box move or close
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$boxid = GETPOSTINT('boxid');
$boxorder = GETPOST('boxorder');
$zone = GETPOST('zone');		// Can be key for zone
if ($zone !== '') {
	$zone = (int) $zone;
}
$userid = GETPOSTINT('userid');

// Security check
if ($userid != $user->id) {
	httponly_accessforbidden('Bad userid parameter. Must match logged user.');
}


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Add a box
if ($boxid > 0 && $zone != '' && $userid > 0) {
	$tmp = explode('-', $boxorder);
	$nbboxonleft = substr_count($tmp[0], ',');
	$nbboxonright = substr_count($tmp[1], ',');
	print $nbboxonleft.'-'.$nbboxonright;
	if ($nbboxonleft > $nbboxonright) {
		$boxorder = preg_replace('/B:/', 'B:'.$boxid.',', $boxorder); // Insert id of new box into list
	} else {
		$boxorder = preg_replace('/^A:/', 'A:'.$boxid.',', $boxorder); // Insert id of new box into list
	}
}

// Registering the location of boxes after a move
if ($boxorder && $zone != '' && $userid > 0) {
	// boxorder value is the target order: "A:idboxA1,idboxA2,A-B:idboxB1,idboxB2,B"
	dol_syslog("AjaxBox boxorder=".$boxorder." zone=".$zone." userid=".$userid, LOG_DEBUG);

	$result = InfoBox::saveboxorder($db, (int) $zone, $boxorder, $userid);
	if ($result > 0) {
		$langs->load("boxes");
		if (!GETPOST('closing')) {
			setEventMessages($langs->trans("BoxAdded"), null);
		}
	}
}
