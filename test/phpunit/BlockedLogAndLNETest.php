<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/blockedlog/class/blockedlog.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/modBlockedLog.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$mysoc->country_code = 'BE';

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
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $mysoc;

		self::assertTrue(isModEnabled('invoice'), " module customer invoice must be enabled");
		self::assertFalse(isModEnabled('ecotaxdeee'), " module ecotaxdeee must not be enabled");
		parent::setUpBeforeClass();

		// We disable module blocked log to avoid interference with tests
		global $db;
		$blockedlogmodule = new modBlockedLog($db);

		$moduleiniterror = 0;

		print 'BlockedLogAndLNETest mysoc country_code = '.$mysoc->country_code."\n";

		//$result = $blockedlogmodule->remove();
		$result = $blockedlogmodule->init();
		if ($result <= 0) {
			$moduleiniterror++;
		}

		// Check that entry BLOCKEDLOG_HMAC_KEY exists in llx_const
		$key = 'BLOCKEDLOG_HMAC_KEY';
		$sql = "SELECT rowid, value FROM ".MAIN_DB_PREFIX."const WHERE name = '".$db->escape($key)."'";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num == 0) {
				print "Failed to find entry BLOCKEDLOG_HMAC_KEY in llx_const. We can't start test.\n";
				if ($moduleiniterror) {
					print "May be because of failure to init/load module BlockedLog: ".$blockedlogmodule->error.". We can't start test.\n";
					exit -1;
				}
				exit -1;
			} else {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					print 'The entry key BLOCKEDLOG_HMAC_KEY exists in llx_const with value '.$obj->value.". We can start test.\n";
				}
			}
		} else {
			print "Failed to check if entry BLOCKEDLOG_HMAC_KEY exists in llx_const: ".$db->lasterror().". We can't start test.\n";
			exit -1;
		}
	}

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

		$element = new Facture($db);
		$element->initAsSpecimen();

		// Build a clean object_data (a stdClass) - do not assign the raw
		// business object, whose $db property would be serialized into the
		// immutable log and break the INSERT on PostgreSQL.
		$localobject->setObjectData($element, 'TEST', 0);

		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->error);

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
