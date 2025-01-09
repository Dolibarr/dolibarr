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
 *      \file       test/phpunit/EmailCollectorTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/emailcollector/class/emailcollector.class.php';
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
class EmailCollectorTest extends CommonClassTest
{
	/**
	 * testEmailCollectorCreate
	 *
	 * @return	void
	 */
	public function testEmailCollectorCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$langs->load("main");

		// Create supplier order with a too low quantity
		$localobject = new EmailCollector($db);
		$localobject->initAsSpecimen();         // Init a specimen with lines

		$this->filters = array();

		$this->actions = array();

		$result = $localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, "Error on test EmailCollector create: ".$localobject->error.' '.join(',', $localobject->errors));

		return $result;
	}


	/**
	 * testEmailCollectorFetch
	 *
	 * @param   int $id     Id of supplier order
	 * @return  void
	 *
	 * @depends testEmailCollectorCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testEmailCollectorFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new EmailCollector($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testEmailCollectorOther
	 *
	 * @param   EmailCollector $localobject     EmailCollector
	 * @return  void
	 *
	 * @depends testEmailCollectorFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testEmailCollectorCollect($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->getConnectStringIMAP();
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertEquals('{localhost:993/service=imap/ssl/novalidate-cert}', $result, 'Error in getConnectStringIMAP '.$localobject->error);

		return $localobject->id;
	}

	/**
	 * testEmailCollectorDelete
	 *
	 * @param   int $id     Id of order
	 * @return  void
	 *
	 * @depends testEmailCollectorCollect
	 * The depends says test is run only if previous is ok
	 */
	public function testEmailCollectorDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new EmailCollector($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		return $result;
	}
}
