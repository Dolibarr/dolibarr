<?php
/* Copyright (C) 2013-2024  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2026       Frédéric France      <frederic.france@free.fr>
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
 * \file    dev/tools/CodingRulesLint.class.php
 * \brief   Static coding-convention checks, runnable outside PHPUnit.
 *
 * The rules implemented here were extracted, unchanged, from
 * test/phpunit/CodingSqlTest so that they can run *once per pipeline* from a
 * plain CLI script (dev/tools/lint-coding-rules.php, wired to the CI
 * "coding-rules" job) instead of *once per PHPUnit matrix leg* - they are pure
 * file scanning and depend neither on the PHP version nor on the DB engine.
 *
 * test/phpunit/CodingSqlTest now only calls into this class (thin wrappers,
 * @group lint).
 */
class CodingRulesLint
{
	/**
	 * @var string[] Human readable message for every rule violation found so far.
	 */
	public $violations = array();

	/**
	 * @var bool When true, print the "Process dir ... / Check sql file ..." progress lines.
	 */
	public $verbose = false;

	/**
	 * @var string Absolute path to the htdocs/ directory to scan.
	 */
	private $docroot;

	/**
	 * Constructor
	 *
	 * @param string|null $docroot Path to the htdocs/ directory (defaults to the one of this repository)
	 */
	public function __construct($docroot = null)
	{
		$this->docroot = rtrim($docroot ?: dirname(__DIR__, 2).'/htdocs', '/');
	}

	/**
	 * Record a rule violation when $ok is false.
	 *
	 * Drop-in replacement for the $this->assertTrue($ok, $message) calls of the
	 * original PHPUnit test: same semantics (the message only matters on
	 * failure) but collected in $this->violations instead of thrown.
	 *
	 * @param bool   $ok      Condition that must hold
	 * @param string $message Message describing the violation
	 * @return void
	 */
	private function report($ok, $message)
	{
		if (!$ok) {
			$this->violations[] = $message;
		}
	}

	/**
	 * Print a progress line when verbose mode is on.
	 *
	 * @param string $line Line to print (no trailing newline needed)
	 * @return void
	 */
	private function trace($line)
	{
		if ($this->verbose) {
			print $line."\n";
		}
	}

	/**
	 * Check the install SQL scripts (install/mysql/{data,tables,migration}) against
	 * Dolibarr's SQL coding rules.
	 *
	 * Extracted verbatim from CodingSqlTest::testSql().
	 *
	 * @return void
	 */
	public function checkInstallSqlFiles()
	{
		$listofsqldir = array($this->docroot.'/install/mysql/data', $this->docroot.'/install/mysql/tables', $this->docroot.'/install/mysql/migration');

		foreach ($listofsqldir as $dir) {
			$this->trace('Process dir '.$dir);
			$filesarray = scandir($dir);

			foreach ($filesarray as $key => $file) {
				if (! preg_match('/\.sql$/', $file)) {
					continue;
				}

				$this->trace('Check sql file '.$file);
				$filecontent = file_get_contents($dir.'/'.$file);

				// Allow some string sequences
				$filecontent = str_replace(
					array('`rank`', '["', '"]', '{"', '"}', '("', '")', 'href="', '">'),
					array('_rank_', '__OKSTRING__', '__OKSTRING__', '__OKSTRING__', '__OKSTRING__', '__OKSTRING__', '__OKSTRING__', '__OKSTRING__'),
					$filecontent
				);

				// To accept " after the comment tag
				//$filecontent = preg_replace('/^--.*$/', '', $filecontent);
				$filecontent = preg_replace('/--.*?\n/', '', $filecontent);

				$result = strpos($filecontent, '`');
				//print __METHOD__." Result for checking we don't have back quote = ".$result."\n";
				$this->report($result === false, 'Found back quote into '.$file.'. Bad.');

				$result = strpos($filecontent, '"');
				//print __METHOD__." Result for checking we don't have double quote = ".$result."\n";
				$this->report($result === false, 'Found double quote that is not [" neither {" (used for json content) neither (" (used for content with string like isModEnabled("")) into '.$file.'. Bad.');

				$result = strpos($filecontent, 'int(');
				//print __METHOD__." Result for checking we don't have 'int(' instead of 'integer' = ".$result."\n";
				$this->report($result === false, 'Found int(x) or tinyint(x) instead of integer or tinyint into '.$file.'. Bad.');

				$result = strpos($filecontent, 'ADD UNIQUE KEY');
				//print __METHOD__." Result for checking we don't have 'ON DELETE CASCADE' = ".$result."\n";
				$this->report($result === false, 'Found ADD UNIQUE KEY instead of ADD UNIQUE INDEX into '.$file.'. Bad.');

				$result = strpos($filecontent, 'ON DELETE CASCADE');
				//print __METHOD__." Result for checking we don't have 'ON DELETE CASCADE' = ".$result."\n";
				$this->report($result === false, 'Found ON DELETE CASCADE into '.$file.'. Bad.');

				$result = strpos($filecontent, 'NUMERIC(');
				//print __METHOD__." Result for checking we don't have 'NUMERIC(' = ".$result."\n";
				$this->report($result === false, 'Found NUMERIC( into '.$file.'. Bad.');

				$result = strpos($filecontent, 'NUMERIC(');
				//print __METHOD__." Result for checking we don't have 'curdate(' = ".$result."\n";
				$this->report($result === false, 'Found curdate( into '.$file.'. Bad. Current date must be generated with PHP.');

				$result = strpos($filecontent, 'integer(');
				//print __METHOD__." Result for checking we don't have 'integer(' = ".$result."\n";
				$this->report($result === false, 'Found value in parenthesis after the integer. It must be integer not integer(x) into '.$file.'. Bad.');

				$result = strpos($filecontent, 'timestamp,');
				//print __METHOD__." Result for checking we don't have 'NUMERIC(' = ".$result."\n";
				$this->report($result === false, 'Found type timestamp without option DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP after into '.$file.'. Bad.');

				if ($dir == $this->docroot.'/install/mysql/migration') {
					// Test for migration files only
				} elseif ($dir == $this->docroot.'/install/mysql/data') {
					// Test for data files only
				} else {
					if (preg_match('/\.key\.sql$/', $file)) {
						// Test for key files only
					} else {
						// Test for non key files only
						$result = (strpos($filecontent, 'KEY ') && strpos($filecontent, 'PRIMARY KEY') == 0);
						//print __METHOD__." Result for checking we don't have ' KEY ' instead of a sql file to create index = ".$result."\n";
						$this->report($result === false, 'Found KEY into '.$file.'. Bad.');

						$result = stripos($filecontent, 'ENGINE=innodb');
						//print __METHOD__." Result for checking we have the ENGINE=innodb string = ".$result."\n";
						$this->report($result > 0, 'The ENGINE=innodb was not found into '.$file.'. Add it or just fix syntax to match case.');
					}
				}
			}
		}
	}

	/**
	 * Check the demo init SQL files (dev/initdemo) do not leak secrets or personal data.
	 *
	 * Extracted verbatim from CodingSqlTest::testInitData().
	 *
	 * @return void
	 */
	public function checkInitDemoFiles()
	{
		$initdemodir = dirname($this->docroot).'/dev/initdemo';

		$filesarray = scandir($initdemodir);
		foreach ($filesarray as $key => $file) {
			if (! preg_match('/\.sql$/', $file)) {
				continue;
			}

			$this->trace('Check sql file '.$file);
			$filecontent = file_get_contents($initdemodir.'/'.$file);

			// We protect this string key that is legitimate into the init of demo file
			$filecontent = str_replace("BLOCKEDLOG_HMAC_KEY',0,'dolcrypt:", "__STRINGOK__", $filecontent);

			$result = strpos($filecontent, 'dolcrypt:');
			$this->report($result === false, 'Found a "dolcrypt:" into file '.$file);

			$result = strpos($filecontent, '@gmail.com');
			$this->report($result === false, 'Found a bad key @gmail into file '.$file);

			$result = strpos($filecontent, 'eldy@');
			$this->report($result === false, 'Found a bad key eldy@ into file '.$file);

			$result = strpos($filecontent, 'INSERT INTO `llx_oauth_token`');
			$this->report($result === false, 'Found a non expected insert into file '.$file);
		}
	}
}
