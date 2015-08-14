<?php
/**
 * Restler 1 compatibility mode enabler
 */
use Luracast\Restler\Defaults;

//changes in iAuthenticate
Defaults::$authenticationMethod = 'isAuthenticated';
include __DIR__ . '/iAuthenticate.php';

//changes in routing
Defaults::$autoRoutingEnabled = false;
Defaults::$smartParameterParsing = false;
Defaults::$autoValidationEnabled = false;