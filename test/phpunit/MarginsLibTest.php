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
 *      \file       test/phpunit/MarginLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/margin/lib/margins.lib.php';
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
class MarginsLibTest extends CommonClassTest
{
	/**
	 * testGetMarginInfos
	 *
	 * @return	void
	 */
	public function testGetMarginInfos()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = getMarginInfos(10, 0, 19.6, 0, 0, 0, 8);
		//var_dump($result);
		print __METHOD__." result[0]=".$result[0]."\n";
		$this->assertEquals(8, $result[0]);
		print __METHOD__." result[1]=".$result[1]."\n";
		$this->assertEquals(25, $result[1]);
		print __METHOD__." result[2]=".$result[2]."\n";
		$this->assertEquals(20, $result[2]);

		$result = getMarginInfos(10, 10, 19.6, 0, 0, 0, 8);
		print __METHOD__." result[0]=".$result[0]."\n";
		$this->assertEquals(8, $result[0]);
		print __METHOD__." result[1]=".$result[1]."\n";
		$this->assertEquals(12.5, $result[1]);
		print __METHOD__." result[2]=".$result[2]."\n";
		$this->assertEquals(1 / 9 * 100, $result[2]);

		return 0;
	}
}
