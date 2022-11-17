<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Cyrille de Lambert   <info@auguria.net>
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
 *       \file       htdocs/salaries/ajax/ajaxsalaries.php
 *       \brief      File to return Ajax response on salary request
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
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';

restrictedArea($user, 'salaries');


/*
 * View
 */

$fk_user = GETPOST('fk_user', 'int');
$return_arr = array();

if (!empty(GETPOST('fk_user', 'int'))) {
	$sql = "SELECT s.amount, s.rowid FROM ".MAIN_DB_PREFIX."salary as s";
	$sql .= " WHERE s.fk_user = ".((int) $fk_user);
	$sql .= " AND s.paye = 1";
	$sql .= $db->order("s.dateep", "DESC");

	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$label = "Salary amount";
		$row_array['label'] = $label;
		$row_array['value'] = price2num($obj->amount, 'MT');
		$row_array['key'] = "Amount";

		array_push($return_arr, $row_array);
		echo json_encode($return_arr);
	} else {
		echo json_encode(array('nom'=>'Error', 'label'=>'Error', 'key'=>'Error', 'value'=>'Error'));
	}
} else {
	echo json_encode(array('nom'=>'ErrorBadParameter', 'label'=>'ErrorBadParameter', 'key'=>'ErrorBadParameter', 'value'=>'ErrorBadParameter'));
}
