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
 *      \file       test/phpunit/ChargeSociales.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/sociales/class/chargesociales.class.php';
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
class ChargeSocialesTest extends CommonClassTest
{
	/**
	 * testChargeSocialesCreate
	 *
	 * @return	void
	 */
	public function testChargeSocialesCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new ChargeSociales($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user, $langs, $conf);
		print __METHOD__." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testChargeSocialesFetch
	 *
	 * @param	int		$id		Id of social contribution
	 * @return	void
	 *
	 * @depends	testChargeSocialesCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testChargeSocialesFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new ChargeSociales($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testChargeSocialesValid
	 *
	 * @param	ChargeSociales		$localobject	Social contribution
	 * @return	void
	 *
	 * @depends	testChargeSocialesFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testChargeSocialesValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setPaid($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testChargeSocialesOther
	 *
	 * @param	ChargeSociales	$localobject		Social contribution
	 * @return	void
	 *
	 * @depends testChargeSocialesValid
	 * The depends says test is run only if previous is ok
	 */
	public function testChargeSocialesOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->getNomUrl(1);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertNotEquals($result, '');

		$result = $localobject->getSommePaiement();
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThanOrEqual($result, 0);

		return $localobject->id;
	}

	/**
	 * testChargeSocialesDelete
	 *
	 * @param	int		$id			Social contribution
	 * @return 	void
	 *
	 * @depends	testChargeSocialesOther
	 * The depends says test is run only if previous is ok
	 */
	public function testChargeSocialesDelete($id)
	{
		global $conf,$user,$langs,$db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new ChargeSociales($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
