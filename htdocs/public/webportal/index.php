<?php

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists('./webportal.main.inc.php')) $res = @include './webportal.main.inc.php';                // to work if your module directory is into dolibarr root htdocs directory
if (!$res) die('Include of WebPortal main fails');

/** @var Context $context */

/*
 * Action
 */


$context->controllerInstance->action();

/*
 * View
 */

$context->controllerInstance->display();
