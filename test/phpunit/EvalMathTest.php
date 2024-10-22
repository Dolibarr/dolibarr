<?php
/* Copyright (C) 2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Quatadah Nasdami <quatadah.nasdami@gmail.com>
 * Copyright (C) 2023 Alexandre Janniaux    <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/InventoryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/evalmath.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class EvalMathTest extends CommonClassTest
{
	/**
	 * test postfix evaluation function
	 * @return void
	 */
	public function testEvaluate()
	{
		$localobject = new EvalMath();
		$result = $localobject->evaluate('1+1');
		$this->assertEquals($result, 2);
		print __METHOD__." result=".$result."\n";

		$result = $localobject->evaluate('10-4/4');
		$this->assertEquals($result, 9);
		print __METHOD__." result=".$result."\n";

		$result = $localobject->evaluate('3^3');
		$this->assertEquals($result, 27);
		print __METHOD__." result=".$result."\n";

		$result = $localobject->evaluate('');
		$this->assertEquals($result, '');
		print __METHOD__." result=".$result."\n";
	}
}
