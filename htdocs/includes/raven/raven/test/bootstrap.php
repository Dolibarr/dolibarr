<?php

/*
 * This file is part of Raven.
 *
 * (c) Sentry Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

error_reporting(E_ALL | E_STRICT);

session_start();

require_once dirname(__FILE__).'/../lib/Raven/Autoloader.php';
Raven_Autoloader::register();
