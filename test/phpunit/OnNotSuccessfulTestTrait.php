<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/OnNotSuccessfulTestTrait.php
 *		\ingroup    test
 *      \brief      Provides CommonClassTest::onNotSuccessfulTest() with a ": void"
 *                  return type (PHPUnit <= 11). PHPUnit >= 12 declares this method
 *                  as ": never", so a matching variant is loaded from
 *                  OnNotSuccessfulTestTraitNever.php instead (see CommonClassTest.class.php).
 *      \remarks    This file must stay parseable on all PHP versions supported by
 *                  the test suite (PHP 7.x+), so it must NOT use the "never" type.
 */

/**
 * Trait injected into CommonClassTest to keep the onNotSuccessfulTest override
 * compatible with PHPUnit versions that declare it with a ": void" return type.
 *
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
/** @phpstan-ignore class.notFound */
trait OnNotSuccessfulTestTrait
{
	/**
	 *	This method is called when a test fails
	 *
	 *  @param	Throwable	$t		Throwable object
	 *  @return void
	 */
	protected function onNotSuccessfulTest(Throwable $t): void
	{
		global $db;

		// Get the lines that were added since the start of the test

		if (file_exists($this->logfile)) {
			$filecontent = (string) @file_get_contents($this->logfile);
		} else {
			$filecontent = '';
		}

		$currentSize = strlen($filecontent);
		if ($currentSize >= $this->logSizeAtSetup) {
			$filecontent = substr($filecontent, $this->logSizeAtSetup);
		}
		$lines = preg_split("/\r?\n/", $filecontent, -1, PREG_SPLIT_NO_EMPTY);


		// Determine the number of lines to show

		$nbLinesToShow = $this->nbLinesToShow;
		// @phan-suppress-next-line PhanUndeclaredClass
		/** @phpstan-ignore comparison.alwaysFalse */
		if (get_class($t) === 'PHPUnit\Framework\Error\Notice') {
			$nbLinesToShow = 3;
		}

		// Determine test information to show

		// @phan-suppress-next-line PhanUndeclaredMethod
		// @phpstan-ignore method.notFound
		$failedTestMethod = $this->getName(false);
		$className = get_called_class();

		// Get the test method's reflection
		$reflectionMethod = new ReflectionMethod($className, $failedTestMethod);

		// Get the test method's data set
		// @phan-suppress-next-line PhanUndeclaredMethod
		// @phpstan-ignore method.notFound
		$argsText = $this->getDataSetAsString(true);

		$totalLines = count($lines);
		$first_line = max(0, $totalLines - $nbLinesToShow);
		// Get the last line of the log
		$last_lines = array_slice($lines, $first_line, $nbLinesToShow);


		// Show log information

		print PHP_EOL;
		// Use GitHub Action compatible group output (:warning: arguments not encoded)
		print "##[group]$className::$failedTestMethod failed - $argsText.".PHP_EOL;
		// @phan-suppress-next-line PhanUndeclaredClassMethod
		print "## ".get_class($t).": {$t->getMessage()}".PHP_EOL;

		// Show some information about where it happened
		// @phan-suppress-next-line PhanUndeclaredClassMethod
		foreach ($t->getTrace() as $idx => $trace) {
			if (isset($trace['file'], $trace['line'])  // Only if we have a file name
				&& !preg_match('/(?:\bphar\b|Framework)/', $trace['file']) // Only if it's not in phpunit
			) {
				print "## backtrace($idx): From {$trace['file']}:{$trace['line']}.".PHP_EOL;
			}
		}


		if ($nbLinesToShow) {
			print "\n";
			print "########## We output the last ".$nbLinesToShow." lines of the file ".basename($this->logfile)." for the failed test ".$failedTestMethod." (file has ".$totalLines." lines) ".PHP_EOL;
			$newLines = count($last_lines);
			if ($newLines > 0) {
				// Show partial log file contents when requested.
				print "## Show last ".count($last_lines)." lines of dolibarr.log file -----".PHP_EOL;
				foreach ($last_lines as $line) {
					print $line.PHP_EOL;
				}
				print "########## end of dolibarr.log for $className::$failedTestMethod".PHP_EOL;
			} else {
				print "## No new lines in 'dolibarr.log' since start of this test.".PHP_EOL;
			}
		}
		print "##[endgroup]".PHP_EOL;

		// Print last line of file /var/log/apache2/travis_error_log (Unix only)
		/* File travis_error_log seems not found on travis
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			$logFile = '/var/log/apache2/travis_error_log';

			if (file_exists($logFile) && is_readable($logFile)) {
				$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				$lastFiveLines = array_slice($lines, -10);
				print "\n";
				echo "Last 10 lines of $logFile:\n";
				foreach ($lastFiveLines as $line) {
					echo $line . "\n";
				}
			} else {
				echo "File $logFile does not exist or is not readable.\n";
			}
		}
		*/

		// Try to output DB info
		/*
		if ($db->type == 'mysqli') {
			print "\n";
			print "########## We try to output some DB info".PHP_EOL;
			$resql = $db->query("SHOW ENGINE INNODB STATUS");
			if ($resql) {
				$obj = $db->fetch_object($resql);
				print $obj->Status.PHP_EOL;
			} else {
				print $db->lasterror().PHP_EOL;
			}
		}
		*/

		print PHP_EOL;

		/** @phpstan-ignore method.notFound */
		parent::onNotSuccessfulTest($t);
	}
}
