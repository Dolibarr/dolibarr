<?php
/* Copyright (C) 2019	Laurent Destailleur (eldy)	<eldy@users.sourceforge.net>
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
 *	\file       htdocs/mrp/ajax/ajax_bom.php
 *	\brief      Ajax search component for Mrp. It get BOM content.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (!defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';

$idbom = GETPOST('idbom', 'alpha');
$action = GETPOST('action', 'aZ09');


/*
 * View
 */

$object = new BOM($db);
$result = $object->fetch($idbom);
if ($result > 0)
{
	// We remove properties we don't need in answer
	unset($object->fields);
	unset($object->db);
	echo json_encode($object);
} else {
   	echo 'Failed to load category with id='.$idbom;
}
