<?php
/* Copyright (C) 2011-2015 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2021      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file       htdocs/core/ajax/constantonoff.php
 *       \brief      File to set or del an on/off constant
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
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09'); // set or del
$name = GETPOST('name', 'alpha');
$entity = GETPOSTINT('entity');
$value = (GETPOST('value', 'aZ09') != '' ? GETPOST('value', 'aZ09') : 1);
$userconst = GETPOSTINT('userconst');


// Security check
if (empty($user->admin) && empty($userconst)) {
	httponly_accessforbidden('This ajax component can be called by admin user only');
}


/*
 * Actions
 */

// Registering the new value of constant
if (!empty($action) && !empty($name)) {
	if ($userconst) {
		$tmpuser = new User($db);
		$tmpuser->id = $userconst;
		if ($tmpuser->id == $user->id || $user->hasRight('user', 'user', 'creer')) {
			if ($action == 'set') {			// Test on permission not required here. Already done into test on user->admin in header.
				dol_set_user_param($db, $conf, $tmpuser, array($name => $value));
			} elseif ($action == 'del') {	// Test on permission not required here. Already done into test on user->admin in header.
				dol_set_user_param($db, $conf, $tmpuser, array($name => ''));
			}
		}
	} else {
		if ($action == 'set') {			// Test on permission not required here. Already done into test on user->admin in header.
			dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $entity);
		} elseif ($action == 'del') {	// Test on permission not required here. Already done into test on user->admin in header.
			dolibarr_del_const($db, $name, $entity);
			if ($entity == 1) {	// Sometimes the param was saved in both entity 0 and 1. When we work on master entity, we should clean also if entity is 0
				dolibarr_del_const($db, $name, 0);
			}
		}
	}
} else {
	httponly_accessforbidden('Param action and name is required', 403);
}


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";
