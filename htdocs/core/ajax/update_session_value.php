<?php
/* Copyright (C) 2024 Eric Seigne <eric.seigne@cap-rel.fr>
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
 *       \file       htdocs/core/ajax/price.php
 *       \brief      File to get ht and ttc
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';


/*
 * View
 */

top_httphead('application/json');

//list of fields handled by that file
$list_of_fields = ['search_date_startday', 'search_date_startmonth', 'search_date_startyear', 'search_date_endday', 'search_date_endmonth', 'search_date_endyear'];

// Security check
// None. This is only a set value in current user session on non critics values (date)
foreach ($_GET as $key => $value) {
	if (in_array($key, $list_of_fields)) {
		$val = (int) $value;
		if ($val != $_SESSION[$key]) {
			$_SESSION[$key] = $val;
			echo json_encode('success');
			//only one field could be set
			exit;
		}
	}
}
echo json_encode('nothing updated');
