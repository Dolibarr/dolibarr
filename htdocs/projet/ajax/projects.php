<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
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
 *       \file       htdocs/projet/ajax/projects.php
 *       \brief      File to return Ajax response on product list request
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');

require '../../main.inc.php';

$htmlname = GETPOST('htmlname', 'aZ09');
$socid = GETPOST('socid', 'int');
$discard_closed = GETPOST('discardclosed', 'int');


/*
 * View
 */

dol_syslog("Call ajax projet/ajax/projects.php");
//dol_syslog(join(',', $_GET));

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

top_httphead();

if (empty($htmlname) && !GETPOST('mode', 'aZ09')) return;

// Mode to get list of projects
if (!GETPOST('mode', 'aZ09') || GETPOST('mode', 'aZ09') != 'gettasks') {
	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (GETPOSTISSET($htmlname) ? GETPOST($htmlname, 'aZ09') : '');

	$formproject = new FormProjets($db);
	$arrayresult = $formproject->select_projects_list($socid, '', $htmlname, 0, 0, 1, $discard_closed, 0, 0, 1, $searchkey);
}

// Mode to get list of tasks
if (GETPOST('mode', 'aZ09') == 'gettasks') {
	$formproject = new FormProjets($db);
	$formproject->selectTasks((!empty($$socid) ? $socid : -1), 0, 'taskid', 24, 1, '1', 1, 0, 0, 'maxwidth500', GETPOST('projectid', 'int'), '');
	return;
}


$db->close();

print json_encode($arrayresult);
