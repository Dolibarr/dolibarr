<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
	 * The rules themselves live in dev/tools/CodingRulesLint.class.php so they can
	 * also be run once per pipeline by dev/tools/lint-coding-rules.php (CI job
	 * "coding-rules") instead of once per PHPUnit matrix leg - they are pure file
	 * scanning and depend neither on the PHP version nor on the DB engine.
	 *
	 * @group lint
	 * @return void
	 */
	public function testSql()
	{
		require_once dirname(__FILE__).'/../../dev/tools/CodingRulesLint.class.php';

		$lint = new CodingRulesLint(DOL_DOCUMENT_ROOT);
		$lint->checkInstallSqlFiles();

		$this->assertSame(array(), $lint->violations, "\n".implode("\n", $lint->violations));
	}

	/**
	 * testInitData
	 *
	 * @group lint
	 * @return void
	 */
	public function testInitData()
	{
		require_once dirname(__FILE__).'/../../dev/tools/CodingRulesLint.class.php';

		$lint = new CodingRulesLint(DOL_DOCUMENT_ROOT);
		$lint->checkInitDemoFiles();

		$this->assertSame(array(), $lint->violations, "\n".implode("\n", $lint->violations));
	}
}
