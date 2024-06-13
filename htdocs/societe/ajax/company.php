<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/societe/ajax/company.php
 *       \brief      File to return Ajax response on thirdparty list request. Used by the combo list of thirdparties.
 *       			 Search done on name, name_alias, barcode, tva_intra, ...
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$htmlname = GETPOST('htmlname', 'aZ09');
$filter = GETPOST('filter', 'alpha');
$outjson = (GETPOSTINT('outjson') ? GETPOSTINT('outjson') : 0);
$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$excludeids = GETPOST('excludeids', 'intcomma');
$showtype = GETPOSTINT('showtype');
$showcode = GETPOSTINT('showcode');

$object = new Societe($db);
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


/*
 * View
 */

top_httphead('application/json');

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if (!empty($action) && $action == 'fetch' && !empty($id)) {
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$outjson = array();

	if ($object->id > 0) {
		$outref = $object->ref;
		$outname = $object->name;
		$outdesc = '';
		$outtype = $object->type;

		$outjson = array('ref' => $outref, 'name' => $outname, 'desc' => $outdesc, 'type' => $outtype);
	}

	echo json_encode($outjson);
} else {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	if (empty($htmlname)) {
		return;
	}

	// The filter on the company to search for can be:
	// Into an array with key $htmlname123 (we take first one found). Which page use this ?
	// Into a var with name $htmlname can be 'prodid', 'productid', ...
	$match = preg_grep('/('.preg_quote($htmlname, '/').'[0-9]+)/', array_keys($_GET));
	sort($match);

	$id = (!empty($match[0]) ? $match[0] : '');		// Take first key found into GET array with matching $htmlname123

	// When used from jQuery, the search term is added as GET param $htmlname.
	$searchkey = (($id && GETPOST($id, 'alpha')) ? GETPOST($id, 'alpha') : (($htmlname && GETPOST($htmlname, 'alpha')) ? GETPOST($htmlname, 'alpha') : ''));
	if (!$searchkey) {
		return;
	}

	if (empty($form) || !is_object($form)) {
		$form = new Form($db);
	}

	if (!empty($excludeids)) {
		$excludeids = explode(',', $excludeids);
	} else {
		$excludeids = array();
	}

	// FIXME
	// If SOCIETE_USE_SEARCH_TO_SELECT is set, check that nb of chars in $filter is >= to avoid DOS attack


	$arrayresult = $form->select_thirdparty_list(0, $htmlname, $filter, 1, $showtype, 0, null, $searchkey, $outjson, 0, 'minwidth100', '', false, $excludeids, $showcode);

	if ($outjson) {
		print json_encode($arrayresult);
	}
}

$db->close();
