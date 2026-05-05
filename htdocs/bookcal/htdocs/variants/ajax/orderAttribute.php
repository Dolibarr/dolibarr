<?php
/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
 * Copyright (C) 2022   Open-Dsi		<support@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disable token renewal
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
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require DOL_DOCUMENT_ROOT . '/variants/class/ProductAttribute.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!isModEnabled('variants')) {
	accessforbidden('Module not enabled');
}
if ($user->socid > 0) { // Protection if external user
	accessforbidden();
}
$result = restrictedArea($user, 'variants');


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering the location of boxes
if (GETPOST('roworder', 'alpha', 3)) {
	$roworder = GETPOST('roworder', 'alpha', 3);

	dol_syslog("AjaxOrderAttribute roworder=" . $roworder, LOG_DEBUG);

	$rowordertab = explode(',', $roworder);
	$newrowordertab = array();
	foreach ($rowordertab as $value) {
		if (!empty($value)) {
			$newrowordertab[] = $value;
		}
	}

	$row = new ProductAttribute($db);

	$row->attributesAjaxOrder($newrowordertab); // This update field rank or position in table row->table_element_line
} else {
	print 'Bad parameters for orderAttribute.php';
}
