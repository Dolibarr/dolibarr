<?php
// Example to use PHPStan
// cd git/dolibarr
// /usr/bin/php7.2 ../phpstan.phar -l1 analyze htdocs/societe/website.php --memory-limit 2G

// Defined some constants and load Dolibarr env to reduce PHPStan bootstrap that fails to load a lot of things.
define('DOL_DOCUMENT_ROOT', __DIR__ . '/../../htdocs');
define('DOL_DATA_ROOT', __DIR__ . '/../../documents');
define('DOL_URL_ROOT', '/');

// Load the main.inc.php file to have functions llx_Header and llx_Footer defined
if (! defined("NOLOGIN")) define("NOLOGIN", '1');
global $conf, $langs, $user, $db;
include_once __DIR__ . '/../../htdocs/main.inc.php';
