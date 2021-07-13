<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/contact/ajax/contact.php
 *       \brief      File to return Ajax response on contact list request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../../main.inc.php';

$htmlname=GETPOST('htmlname', 'alpha');
$outjson=(GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
$socid=GETPOST('socid', 'int');
$showempty=GETPOST('showempty', 'int');
$showfunction=GETPOST('showfunction', 'int');
$showsoc=GETPOST('showsoc', 'int');
$exclude=GETPOST('exclude', 'array');
$page=GETPOST('page', 'int');
$limit=GETPOST('limit', 'int');


/*
 * View
 */

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', array_merge($_GET, $_POST)));
//print_r($_GET);

if (! empty($action) && $action == 'fetch' && ! empty($id)) {
	require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

	$outjson = array();

	$object = new Contact($db);
	$ret = $object->fetch($id);
	if ($ret > 0) {
		$outname = $object->getFullName($langs);
		$outref = '';
		$outlabel = '';
		$outdesc = '';
		$outtype = '';

		$outjson = array('ref' => $outref, 'name' => $outname, 'desc' => $outdesc, 'type' => $outtype);
	}

	echo json_encode($outjson);
} else {
	$langs->load("companies");

	top_httphead();

	if ($socid === '') $socid = 0;
	$page = $page > 0 ? $page : 0;
	$limit = $limit > 0 ? $limit + 1 : 0;

	if (GETPOST('_type', 'aZ') == "query" && (GETPOSTISSET("term") || GETPOSTISSET("q"))) {
		$searchkey = GETPOSTISSET("term") ? GETPOST("term", 'alpha') : GETPOST("q", 'alpha');
	} elseif (GETPOSTISSET("q")) {
		$searchkey = GETPOST("q", 'alpha');
	} else {
		if (empty($htmlname)) return;

		$match = preg_grep('/(' . $htmlname . '[0-9]+)/', array_keys($_GET));
		sort($match);
		$id = (!empty($match[0]) ? $match[0] : '');

		// When used from jQuery, the search term is added as GET param "term".
		$searchkey = (($id && GETPOST($id, 'alpha')) ? GETPOST($id, 'alpha') : (($htmlname && GETPOST($htmlname, 'alpha')) ? GETPOST($htmlname, 'alpha') : ''));
	}

	if (!$searchkey) return;

	if (!is_object($form)) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$form = new Form($db);
	}
	$arrayresult = $form->selectcontactlist($socid, 0, $htmlname, $showempty, $exclude, '', $showfunction,
		$showsoc, 0, array(), $searchkey, $outjson, false, $page, $limit);

	$db->close();

	if ($outjson) {
		header("Content-Type: application/json;");
		print json_encode($arrayresult);
	}
}
