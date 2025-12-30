<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/unit/BlockedLogAndLNETest.php
 * \ingroup core
 * \brief   PHPUnit test for the BlockedLog and LNE class.
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/blockedlog/class/blockedlog.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class BlockedLogAndLNETest extends CommonClassTest
{
	/**
	 * testBlockedLogAndLNETest
	 *
	 * #LNE8-QU2507-0048
	 *
	 * @return int
	 */
	public function testBlockedLogAndLNETest()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BlockedLog($db);
		$localobject->action = 'TEST';

		$element = new Facture($db);
		$element->initAsSpecimen();
		$localobject->element = $element->element;
		$localobject->object_data = $element;

		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}


	/**
	 * testGetNextAutoIncrementId
	 * This test must be done after a creation of a first record.
	 *
	 * @return	int
	 */
	public function testGetNextAutoIncrementId()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__.' db->type = '.$db->type."\n";
		$result = $db->getNextAutoIncrementId(MAIN_DB_PREFIX.'blockedlog');
		$this->assertGreaterThan(0, $result);	// Must be strictlyhigher than 0
		print __METHOD__." result=".$result."\n";
	}


	// TODO Add more tests
	// #LNExxx
}
