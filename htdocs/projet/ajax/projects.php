<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2015 Marcos García       <marcosgdf@gmail.com>
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
 *       \file       htdocs/product/ajax/products.php
 *       \brief      File to return Ajax response on product list request
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (empty($_GET['keysearch']) && ! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');

require '../../main.inc.php';

$htmlname=GETPOST('htmlname','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');
$discard_closed =GETPOST('discardclosed','int');


/*
 * View
 */

dol_syslog(join(',',$_GET));

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Load translation files required by the page
$langs->load("main");

top_httphead();

if (empty($htmlname)) return;

$match = preg_grep('/('.$htmlname.'[0-9]+)/',array_keys($_GET));
sort($match);
$idprod = (! empty($match[0]) ? $match[0] : '');

if (! GETPOST($htmlname) && ! GETPOST($idprod)) return;

// When used from jQuery, the search term is added as GET param "term".
$searchkey=((!empty($idprod) && GETPOST($idprod))?GETPOST($idprod):(GETPOST($htmlname)?GETPOST($htmlname):''));

$form = new FormProjets($db);
$arrayresult=$form->select_projects_list($socid, '', $htmlname, 0, 0, 1, $discard_closed, 0, 0, 1, $searchkey);

$db->close();

print json_encode($arrayresult);

