<?php

/*
 * Bootstrap the library.
 */

namespace Egulias;

require_once __DIR__ . '/egulias/email-validator/AutoLoader.php';

$autoloader = new EguliasAutoLoader(__NAMESPACE__, dirname(__DIR__));

$autoloader->register();
