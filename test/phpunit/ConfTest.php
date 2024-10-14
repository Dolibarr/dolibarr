<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/FactureRecTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
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
class ConfTest extends CommonClassTest
{
	/**
	 * Provider for moduleMap test.
	 *
	 * TODO: extend to help test expected dir_output path which depends
	 * on module name, so extra list is needed
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function moduleMapProvider()
	{
		$tests = [];
		foreach (self::DEPRECATED_MODULE_MAPPING as $old => $new) {
			$tests[$old] = [$old, $new];
		}
		return $tests;
	}

	/**
	 * Provider for all modules (name -> className)
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function moduleProvider()
	{
		$tests = [];
		foreach (self::VALID_MODULE_MAPPING as $modName => $className) {
			$tests[$modName] = [$modName, $className];
		}
		return $tests;
	}

	/**
	 * testOldNewModulePaths
	 *
	 * @dataProvider moduleMapProvider
	 *
	 * @param string $old Module
	 * @param string $new New Module
	 * @return void
	 */
	public function testOldNewModulePaths($old, $new)
	{
		global $conf,$user,$langs,$db;

		// print "DIR_OUTPUT for $old is {$conf->$old->dir_output}".PHP_EOL;
		// print "DIR_OUTPUT for $new is {$conf->$new->dir_output}".PHP_EOL;
		$this->assertEquals($conf->$new->dir_output, $conf->$old->dir_output, "Old ($old/expected) and new dir_output ($new/result) dir_output must be equal");
		$this->assertEquals($conf->$new->multidir_output[1], $conf->$old->multidir_output[1], "Old ($old/expected) and new  ($new/result) multidir_output must be equal");
		$this->assertEquals($conf->$new->multidir_output[1], $conf->$old->dir_output, "Old ($old/expected) and new  ($new/result) dir_output and multidir_output must be equal");
	}

	/**
	 * testModulePaths
	 *
	 * @dataProvider moduleProvider
	 *
	 * @param string $modName Module name
	 * @param string $className Module class name
	 * @return void
	 */
	public function testModulePaths($modName, $className)
	{
		global $conf,$user,$langs,$db;

		$this->assertEquals($conf->$modName->multidir_output[1], $conf->$modName->dir_output, "dir_output and multidir_output must be equal");
	}
}
