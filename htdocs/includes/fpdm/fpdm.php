<?php

/**
 * Entry point for legacy calls
 *
 * Devs not using composer autoload will have included this file directly.
 * Keeping it as a wrapper allows to retain compatibility with legacy projects
 * while allowing adjustments to the source to improve composer integration.
 */

define('FPDM_DIRECT', true);

// @CHANGE DOL use __DIR__ instead of relative paths, which depend on the include_path/CWD and are not reliable from Dolibarr's entry points
require_once(__DIR__."/src/fpdm.php");

require_once(__DIR__."/src/filters/FilterASCIIHex.php");
require_once(__DIR__."/src/filters/FilterASCII85.php");
require_once(__DIR__."/src/filters/FilterFlate.php");
require_once(__DIR__."/src/filters/FilterLZW.php");
require_once(__DIR__."/src/filters/FilterStandard.php");
