<?php

// Defined some constants and load Dolibarr env to reduce PHPStan bootstrap that fails to load a lot of things.
//define('DOL_DOCUMENT_ROOT', __DIR__ . '/../../htdocs');
//define('DOL_DATA_ROOT', __DIR__ . '/../../documents');
//define('DOL_URL_ROOT', '/');

// Load the main.inc.php file to have functions env defined
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined("NOHTTPSREDIRECT")) {
	define("NOHTTPSREDIRECT", '1');
}
global $conf, $langs, $user, $db;
include_once __DIR__ . '/../../htdocs/main.inc.php';
