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
 *       \file       htdocs/core/ajax/ajaxstatusprospect.php
 *       \brief      File to return Ajax response on third parties request
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

// Load Dolibarr environment
require '../../main.inc.php';

$idstatus = GETPOST('id', 'int');
$idprospect = GETPOST('prospectid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid > 0) {
	if ($idprospect != $user->socid) {
		accessforbidden('Not allowed on this thirdparty');
	}
}

// var_dump(	$user, 'societe', $idprospect, '&societe');
$result = restrictedArea($user, 'societe', $idprospect, '&societe');

$permisstiontoupdate = $user->hasRight('societe', 'creer');

/*
 * View
 */

top_httphead();


if ($action === "updatestatusprospect" && $permisstiontoupdate) {
	$response = '';

	$sql  = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
	$sql .= "fk_stcomm=".(int) $db->escape($idstatus);
	$sql .= " WHERE rowid = ".(int) $db->escape($idprospect);

	$resql = $db->query($sql);

	if (!$resql) {
		dol_print_error($db);
	} else {
		$num = $db->affected_rows($resql);
		$response = http_response_code(200);
	}

	$response =json_encode($response);
	echo $response;
}
