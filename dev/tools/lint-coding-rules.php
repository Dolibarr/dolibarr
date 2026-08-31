#!/usr/bin/env php
<?php
/* Copyright (C) 2026 Frédéric France <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    dev/tools/lint-coding-rules.php
 * \brief   Run the static coding-convention checks once (used by the CI "coding-rules" job).
 *
 * These checks are pure file scanning: they depend neither on the PHP version
 * nor on the DB engine, so there is no point re-running them on every PHPUnit
 * matrix leg. This script runs them a single time per pipeline.
 *
 * Currently covers the SQL file rules previously enforced by
 * test/phpunit/CodingSqlTest::testSql() and ::testInitData().
 *
 * Usage:  php dev/tools/lint-coding-rules.php [-v|--verbose]
 * Exit:   0 = no violation, 1 = at least one violation (all are printed)
 */

if (php_sapi_name() !== 'cli') {
	fwrite(STDERR, "Error: ".basename(__FILE__)." must be run from the command line (PHP CLI).\n");
	exit(1);
}

require_once __DIR__.'/CodingRulesLint.class.php';

$docroot = __DIR__.'/../../htdocs';

$lint = new CodingRulesLint($docroot);
$lint->verbose = in_array('-v', $argv, true) || in_array('--verbose', $argv, true);

$lint->checkInstallSqlFiles();
$lint->checkInitDemoFiles();

if (!empty($lint->violations)) {
	fwrite(STDERR, "\nCoding rule violation(s) found:\n\n");
	foreach ($lint->violations as $violation) {
		fwrite(STDERR, " - ".$violation."\n");
	}
	fwrite(STDERR, "\n".count($lint->violations)." violation(s).\n");
	exit(1);
}

print "Coding rules: OK (no violation).\n";
exit(0);
