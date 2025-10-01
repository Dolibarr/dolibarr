<?php
/* Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Defined some constants and load Dolibarr env to reduce PHPStan bootstrap that fails to load a lot of things.
//define('DOL_DOCUMENT_ROOT', __DIR__ . '/../../htdocs');
//define('DOL_DATA_ROOT', __DIR__ . '/../../documents');
//define('DOL_URL_ROOT', '/');

// Load the main.inc.php file to have functions env defined
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}
if (!defined("NOHTTPSREDIRECT")) {
	define("NOHTTPSREDIRECT", '1');
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

global $conf, $db, $hookmanager, $langs, $mysoc, $user;

include_once __DIR__ . '/../../../htdocs/main.inc.php';
