<?php
/* Copyright (C) 2006      	Andre Cianfarani     	<acianfa@free.fr>
 * Copyright (C) 2005-2012 	Regis Houssin        	<regis.houssin@inodbox.com>
 * Copyright (C) 2007-2019 	Laurent Destailleur  	<eldy@users.sourceforge.net>
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
 *       \file       htdocs/contact/ajax/contact.php
 *       \brief      File to return Ajax response on contact list request. Used by the combo list of contacts, for example into page list of projects
 *       			 Search done on name, firstname...
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
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
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$htmlname = GETPOST('htmlname', 'aZ09');
$outjson = (GETPOSTINT('outjson') ? GETPOSTINT('outjson') : 0);
$action = GETPOST('action', 'aZ09');

$id = GETPOSTINT('id');
$socid = GETPOSTINT('socid');
$exclude = GETPOST('exclude', 'intcomma');
$showsoc = GETPOSTINT('showsoc');

$object = new Contact($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
if ($user->socid > 0) {
	unset($action);
	$socid = $user->socid;
	$object->id = $socid;
}
restrictedArea($user, 'societe', $object->id, '&societe');

$permissiontoread = $user->hasRight('societe', 'lire');


/*
 * View
 */

top_httphead('application/json');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if ($action == 'fetch' && !empty($id) && $permissiontoread) {
	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

	$outjson = array();

	if ($object->id > 0) {
		$outref = $object->ref;
		$outfirstname = $object->firstname;
		$outlastname = $object->lastname;
		$outdesc = '';

		$outjson = array('ref' => $outref, 'firstname' => $outfirstname, 'lastname' => $outlastname, 'desc' => $outdesc);
	}

	echo json_encode($outjson);
} elseif ($permissiontoread) {		// $action can be 'getContacts'
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	if (empty($htmlname)) {
		return 'Error value for parameter htmlname';
	}

	// The filter on the company to search for can be:
	// Into an array with key $htmlname123 (we take first one found). Which page use this ?
	// Into a var with name $htmlname can be 'elemid', ...
	$match = preg_grep('/('.preg_quote($htmlname, '/').'[0-9]+)/', array_keys($_GET));
	sort($match);

	$id = (!empty($match[0]) ? $match[0] : '');		// Take first key found into GET array with matching $htmlname123

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($id && GETPOST($id, 'alpha')) ? GETPOST($id, 'alpha') : (($htmlname && GETPOST($htmlname, 'alpha')) ? GETPOST($htmlname, 'alpha') : ''));
	if (!$searchkey) {
		return;
	}

	if (empty($form) || !is_object($form)) {
		$form = new Form($db);
	}

	$limitto = '';
	$showfunction = 0;
	$morecss = 'minwidth100';
	$options_only = 2;
	$forcecombo = 0;
	$events = array();
	$moreparam = '';
	$htmlid = '';
	$multiple = 0;
	$disableifempty = 0;

	$prefix = getDolGlobalString('CONTACT_DONOTSEARCH_ANYWHERE') ? '' : '%'; // Can use index if CONTACT_DONOTSEARCH_ANYWHERE is on

	$nbchar = 0;
	$filter = '';
	$listofsearchkey = preg_split('/\s+/', $searchkey);
	foreach ($listofsearchkey as $searchkey) {
		$nbchar += strlen($searchkey);

		$filter .= ($filter ? ' AND ' : '');
		$filter .= '(';
		$filter .= "(lastname:like:'".$prefix.$searchkey."%') OR (firstname:like:'".$prefix.$searchkey."%')";
		if ($showsoc) {
			$filter .= " OR (s.nom:like:'".$prefix.$searchkey."%')";
		}
		$filter .= ')';
	}

	// If CONTACT_USE_SEARCH_TO_SELECT is set, check that nb of chars in $filter is >= to avoid DOS attack
	if (getDolGlobalInt('CONTACT_USE_SEARCH_TO_SELECT') && $nbchar < getDolGlobalInt('CONTACT_USE_SEARCH_TO_SELECT')) {
		print json_encode(array());
	} else {
		$arrayresult = $form->selectcontacts($socid, array(), $htmlname, 1, $exclude, $limitto, $showfunction, $morecss, $options_only, $showsoc, $forcecombo, $events, $moreparam, $htmlid, $multiple, $disableifempty, $filter);

		print json_encode($arrayresult);
	}
}

$db->close();
