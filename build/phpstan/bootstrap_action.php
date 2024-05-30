<?php

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

// Defined some constants and load Dolibarr env to reduce PHPStan bootstrap that fails to load a lot of things.
define('DOL_DOCUMENT_ROOT', __DIR__ . '/../../htdocs');
define('DOL_DATA_ROOT', __DIR__ . '/../../documents');
define('DOL_URL_ROOT', '/');
define('DOL_MAIN_URL_ROOT', '/');
define('MAIN_DB_PREFIX', 'llx_');

global $conf, $db, $langs, $user;
// include_once DOL_DOCUMENT_ROOT . '/../../htdocs/main.inc.php';
