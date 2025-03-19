<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/CodingSqlTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CodingSqlTest extends CommonClassTest
{
	/**
	 * testEscape
	 *
	 * @return string
	 */
	public function testEscape()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if ($db->type == 'mysqli') {
			$a = 'abc"\'def';	// string is abc"'def
			print $a;
			$result = $db->escape($a);	// $result must be abc\"\'def
			$this->assertEquals('abc\"\\\'def', $result);
		}
		if ($db->type == 'pgsql') {
			$a = 'abc"\'def';	// string is abc"'def
			print $a;
			$result = $db->escape($a);	// $result must be abc"''def
			$this->assertEquals('abc"\'\'def', $result);
		}
	}

	/**
	 * testEscapeForLike
	 *
	 * @return string
	 */
	public function testEscapeForLike()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$a = 'abc"\'def_ghi%klm\\nop';
		//print $a;
		$result = $db->escapeforlike($a);	// $result must be abc"'def\_ghi\%klm\\nop with mysql
		$this->assertEquals('abc"\'def\_ghi\%klm\\\\nop', $result);
	}

	/**
	 * testSql
	 *
	 * @return string
	 */
	public function testSql()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$listofsqldir = array(DOL_DOCUMENT_ROOT.'/install/mysql/data', DOL_DOCUMENT_ROOT.'/install/mysql/tables', DOL_DOCUMENT_ROOT.'/install/mysql/migration');

		foreach ($listofsqldir as $dir) {
			print 'Process dir '.$dir."\n";
			$filesarray = scandir($dir);

			foreach ($filesarray as $key => $file) {
				if (! preg_match('/\.sql$/', $file)) {
					continue;
				}

				print 'Check sql file '.$file."\n";
				$filecontent = file_get_contents($dir.'/'.$file);

				// Allow ` for 'rank' column name only
				$filecontent = str_replace('`rank`', '_rank_', $filecontent);

				$filecontent = str_replace(array('["', '"]', '{"', '"}', '("', '")'), '__OKSTRING__', $filecontent);
				// To accept " after the comment tag
				//$filecontent = preg_replace('/^--.*$/', '', $filecontent);
				$filecontent = preg_replace('/--.*?\n/', '', $filecontent);

				$result = strpos($filecontent, '`');
				//print __METHOD__." Result for checking we don't have back quote = ".$result."\n";
				$this->assertTrue($result === false, 'Found back quote into '.$file.'. Bad.');

				$result = strpos($filecontent, '"');
				//print __METHOD__." Result for checking we don't have double quote = ".$result."\n";
				$this->assertTrue($result === false, 'Found double quote that is not [" neither {" (used for json content) neither (" (used for content with string like isModEnabled("")) into '.$file.'. Bad.');

				$result = strpos($filecontent, 'int(');
				//print __METHOD__." Result for checking we don't have 'int(' instead of 'integer' = ".$result."\n";
				$this->assertTrue($result === false, 'Found int(x) or tinyint(x) instead of integer or tinyint into '.$file.'. Bad.');

				$result = strpos($filecontent, 'ON DELETE CASCADE');
				//print __METHOD__." Result for checking we don't have 'ON DELETE CASCADE' = ".$result."\n";
				$this->assertTrue($result === false, 'Found ON DELETE CASCADE into '.$file.'. Bad.');

				$result = strpos($filecontent, 'NUMERIC(');
				//print __METHOD__." Result for checking we don't have 'NUMERIC(' = ".$result."\n";
				$this->assertTrue($result === false, 'Found NUMERIC( into '.$file.'. Bad.');

				$result = strpos($filecontent, 'NUMERIC(');
				//print __METHOD__." Result for checking we don't have 'curdate(' = ".$result."\n";
				$this->assertTrue($result === false, 'Found curdate( into '.$file.'. Bad. Current date must be generated with PHP.');

				$result = strpos($filecontent, 'integer(');
				//print __METHOD__." Result for checking we don't have 'integer(' = ".$result."\n";
				$this->assertTrue($result === false, 'Found value in parenthesis after the integer. It must be integer not integer(x) into '.$file.'. Bad.');

				$result = strpos($filecontent, 'timestamp,');
				//print __METHOD__." Result for checking we don't have 'NUMERIC(' = ".$result."\n";
				$this->assertTrue($result === false, 'Found type timestamp without option DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP after into '.$file.'. Bad.');

				if ($dir == DOL_DOCUMENT_ROOT.'/install/mysql/migration') {
					// Test for migration files only
				} elseif ($dir == DOL_DOCUMENT_ROOT.'/install/mysql/data') {
					// Test for data files only
				} else {
					if (preg_match('/\.key\.sql$/', $file)) {
						// Test for key files only
					} else {
						// Test for non key files only
						$result = (strpos($filecontent, 'KEY ') && strpos($filecontent, 'PRIMARY KEY') == 0);
						//print __METHOD__." Result for checking we don't have ' KEY ' instead of a sql file to create index = ".$result."\n";
						$this->assertTrue($result === false, 'Found KEY into '.$file.'. Bad.');

						$result = stripos($filecontent, 'ENGINE=innodb');
						//print __METHOD__." Result for checking we have the ENGINE=innodb string = ".$result."\n";
						$this->assertGreaterThan(0, $result, 'The ENGINE=innodb was not found into '.$file.'. Add it or just fix syntax to match case.');
					}
				}
			}
		}

		return;
	}

	/**
	 * testInitData
	 *
	 * @return string
	 */
	public function testInitData()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$filesarray = scandir(DOL_DOCUMENT_ROOT.'/../dev/initdemo');
		foreach ($filesarray as $key => $file) {
			if (! preg_match('/\.sql$/', $file)) {
				continue;
			}

			print 'Check sql file '.$file."\n";
			$filecontent = file_get_contents(DOL_DOCUMENT_ROOT.'/../dev/initdemo/'.$file);

			$result = strpos($filecontent, '@gmail.com');
			print __METHOD__." Result for checking we don't have personal data = ".$result."\n";
			$this->assertTrue($result === false, 'Found a bad key @gmail into file '.$file);

			$result = strpos($filecontent, 'eldy@');
			print __METHOD__." Result for checking we don't have personal data = ".$result."\n";
			$this->assertTrue($result === false, 'Found a bad key eldy@ into file '.$file);

			$result = strpos($filecontent, 'INSERT INTO `llx_oauth_token`');
			print __METHOD__." Result for checking we don't have data into llx_oauth_token = ".$result."\n";
			$this->assertTrue($result === false, 'Found a non expected insert into file '.$file);
		}

		return;
	}
}
