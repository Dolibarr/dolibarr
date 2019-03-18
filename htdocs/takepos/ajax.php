<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
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
 *	\file       htdocs/takepos/ajax.php
 *	\brief      Ajax search component for TakePos. It search products of a category.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$category = GETPOST('category', 'alpha');
$action = GETPOST('action', 'alpha');
$term = GETPOST('term', 'alpha');


/*
 * View
 */

if ($action=="getProducts") {
    $object = new Categorie($db);
    $result=$object->fetch($category);
    $prods = $object->getObjectsInCateg("product");
    echo json_encode($prods);
}

elseif ($action=="search") {
    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product';
    $sql .= ' WHERE entity IN ('.getEntity('product').')';
    $sql .= ' AND tosell = 1';
    $sql .= natural_search(array('label','barcode'), $term);
    $resql = $db->query($sql);
    $rows = array();
    while ($row = $db->fetch_array($resql)) {
        $rows[] = $row;
    }
    echo json_encode($rows);
}
