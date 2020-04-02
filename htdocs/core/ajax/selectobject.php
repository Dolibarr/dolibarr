<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/selectobject.php
 *       \brief      File to return Ajax response on a selection list request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');

require '../../main.inc.php';

$objectdesc=GETPOST('objectdesc', 'alpha');
$htmlname=GETPOST('htmlname', 'aZ09');
$sqlfilter=GETPOST('sqlfilter', 'alpha');
$outjson=(GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');


/*
 * View
 */

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_GET));
//print_r($_GET);


require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$form = new Form($db);

//$langs->load("companies");

top_httphead();

if (empty($htmlname)) return;


$InfoFieldList = explode(":", $objectdesc);
$classname=$InfoFieldList[0];
$classpath=$InfoFieldList[1];
if (! empty($classpath))
{
	dol_include_once($classpath);
	if ($classname && class_exists($classname))
	{
		$objecttmp = new $classname($db);
	}
}
if (! is_object($objecttmp))
{
	dol_syslog('Error bad param objectdesc', LOG_WARNING);
	print 'Error bad param objectdesc';
}

// When used from jQuery, the search term is added as GET param "term".
$searchkey=(($id && GETPOST($id, 'alpha'))?GETPOST($id, 'alpha'):(($htmlname && GETPOST($htmlname, 'alpha'))?GETPOST($htmlname, 'alpha'):''));

// TODO Add a security test to avoid to get content of all tables

$arrayresult=$form->selectForFormsList($objecttmp, $htmlname, '', 0, $searchkey, '', '', '', 0, 1);

$db->close();

if ($outjson) print json_encode($arrayresult);
