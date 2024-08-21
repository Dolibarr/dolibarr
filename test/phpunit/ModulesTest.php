<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/ModulesTest.php
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


use PHPUnit\Framework\TestCase;

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ModulesTest extends CommonClassTest // TestCase //CommonClassTest
{
	/**
	 * Return list of modules for which to test initialisation
	 *
	 * @return array<array{0:string}> List of module labels to test (class is mod<module_label>)
	 */
	public function moduleInitListProvider()
	{
		$full_list = self::VALID_MODULE_MAPPING;
		$filtered_list = array_map(function ($value) {
			return array($value);
		}, array_filter($full_list, function ($value) {
			return $value !== null;
		}));
		return $filtered_list;
	}

	/**
	 * testModulesInit
	 *
	 * @param string	$modlabel	Module label (class is mod<modlabel>)
	 *
	 * @return int
	 *
	 * @dataProvider moduleInitListProvider
	 */
	public function testModulesInit(string $modlabel)
	{
		global $conf,$user,$langs,$db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->nbLinesToShow = 0; // Only 3 lines of the log.

		require_once DOL_DOCUMENT_ROOT.'/core/modules/mod'.$modlabel.'.class.php';
		$class = 'mod'.$modlabel;
		$mod = new $class($db);

		$result = $mod->remove();
		$result = $mod->init();

		$this->assertLessThan($result, 0, $modlabel." ".$mod->error);
		print __METHOD__." test remove/init for module ".$modlabel.", result=".$result."\n";

		if (in_array($modlabel, array('Ldap', 'MailmanSpip'))) {
			$result = $mod->remove();
		}

		return 0;
	}
}
