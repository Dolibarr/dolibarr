<?php
/* Copyright (C) 2026 Marcel
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// Standalone, database-free runner for the same scenarios registered with PHPUnit.
if (PHP_SAPI !== 'cli') {
	exit(1);
}
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
	throw new ErrorException($message, 0, $severity, $file, $line);
});
define('DOL_DOCUMENT_ROOT', dirname(__DIR__, 2).'/htdocs');
define('DOL_URL_ROOT', '');
require __DIR__.'/../phpunit/fixtures/subscriptionoptions.php';
subscriptionOptionsTestEnvironment();
$failed = 0;
$scenarios = subscriptionOptionsScenarios();
foreach ($scenarios as $name => $scenario) {
	try {
		subscriptionOptionsTestEnvironment();
		if (!$scenario()) {
			throw new RuntimeException('Assertion failed');
		}
		print 'PASS '.$name."\n";
	} catch (Throwable $e) {
		$failed++;
		print 'FAIL '.$name.': '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n";
	}
}
print count($scenarios).' scenarios, '.$failed." failures. No application database or mail transport used.\n";
exit($failed ? 1 : 0);
