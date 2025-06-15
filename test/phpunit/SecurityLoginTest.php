<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/SecurityTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';

if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
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
if (! defined("NOSESSION")) {
	define("NOSESSION", '1');
}

require_once dirname(__FILE__).'/../../htdocs/main.inc.php';	// We force include of main.inc.php instead of master.inc.php even if we are in CLI mode because it contains a lot of security components we want to test.
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

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
class SecurityLoginTest extends CommonClassTest
{
	/**
	 * testCheckLoginPassEntity
	 *
	 * @return	void
	 */
	public function testCheckLoginPassEntity()
	{
		$login = checkLoginPassEntity('loginbidon', 'passwordbidon', 1, array('dolibarr'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, '');

		$login = checkLoginPassEntity('admin', 'passwordbidon', 1, array('dolibarr'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, '');

		$login = checkLoginPassEntity('admin', 'admin', 1, array('dolibarr'));            // Should works because admin/admin exists
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, 'admin', 'The test to check if pass of user "admin" is "admin" has failed');

		$login = checkLoginPassEntity('admin', 'admin', 1, array('http','dolibarr'));    // Should work because of second authentication method
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, 'admin');

		$login = checkLoginPassEntity('admin', 'admin', 1, array('forceuser'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals('', $login, 'Error');    // Expected '' because should failed because login 'auto' does not exists
	}
}
