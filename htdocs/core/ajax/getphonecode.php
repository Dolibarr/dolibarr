<?php
/* Copyright (C) 2026	Open-Dsi	<support@open-dsi.fr>
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
 * \file       htdocs/core/ajax/getphonecode.php
 * \brief      Returns JSON phone_code for a given country_id
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/phone.lib.php';
/**
 * @var DoliDB $db
 * @var User $user
 */

$country_id = GETPOSTINT('country_id');

// Security check - user must be logged in
if (empty($user->id)) {
	http_response_code(403);
	echo json_encode(array('error' => 'Not authorized'));
	exit;
}

/*
 * View
 */

top_httphead('application/json');

$phone_code = dol_get_phone_code_from_country($db, $country_id);

echo json_encode(array('phone_code' => $phone_code));
