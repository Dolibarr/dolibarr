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
 *      \file       test/phpunit/PropalTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
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
class PropalTest extends CommonClassTest
{
	/**
	 * testPropalCreate
	 *
	 * @return	void
	 */
	public function testPropalCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$param = array('tosell' => 1);
		$localobject->initAsSpecimen($param);
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testPropalFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Propal
	 *
	 * @depends	testPropalCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalUpdate
	 *
	 * @param	Propal		$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalAddLine
	 *
	 * @param	Propal		$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$result = $localobject->addline('Added line', 10, 2, 19.6);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalValid
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->valid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testPropalOther
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	int
	 *
	 * @depends testPropalValid
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testPropalDelete
	 *
	 * @param	int		$id		Id of proposal
	 * @return	void
	 *
	 * @depends	testPropalOther
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * Option line is persisted with a real quantity (is_option=1, qty kept).
	 *
	 * @return void
	 */
	public function testPropalLineIsOptionPersistence()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$propal = new Propal($db);
		$propal->initAsSpecimen(array('tosell' => 1));
		$propal->lines = array();
		$resultcreate = $propal->create($user);
		$this->assertGreaterThan(0, $resultcreate, 'Propal creation must succeed');

		// Option line with a real quantity (is_option = last argument)
		$lineid = $propal->addline('Option line', 100, 3, 20, 0, 0, 0, 0, 'HT', 0, 0, 0, -1, 0, 0, 0, 0, '', '', '', array(), null, '', 0, 0, 0, 0, 1);
		$this->assertGreaterThan(0, $lineid, 'addline with is_option=1 must succeed');

		$line = new PropaleLigne($db);
		$line->fetch($lineid);
		$this->assertEquals(1, (int) $line->is_option, 'is_option must be persisted and refetched');
		$this->assertEquals(3, (float) $line->qty, 'qty must stay real (not forced to 0)');
		$this->assertTrue($line->isOptionLine(), 'isOptionLine() must return true');

		print __METHOD__." lineid=".$lineid."\n";
	}

	/**
	 * Backward ABI compatibility: a legacy positional addline() call without is_option must keep working (default 0).
	 *
	 * @return void
	 */
	public function testPropalAddlineAbiCompat()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$propal = new Propal($db);
		$propal->initAsSpecimen(array('tosell' => 1));
		$propal->lines = array();
		$propal->create($user);

		$lineid = $propal->addline('Legacy call', 50, 1, 20);
		$this->assertGreaterThan(0, $lineid, 'Legacy addline call must succeed');

		$line = new PropaleLigne($db);
		$line->fetch($lineid);
		$this->assertEquals(0, (int) $line->is_option, 'is_option must default to 0 for legacy calls');

		print __METHOD__." lineid=".$lineid."\n";
	}

	/**
	 * Option lines are excluded from the proposal totals computed by update_price().
	 *
	 * @return void
	 */
	public function testPropalTotalsExcludeOptions()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$propal = new Propal($db);
		$propal->initAsSpecimen(array('tosell' => 1));
		$propal->lines = array();
		$propal->create($user);

		// Normal line: 100 HT x 2 @ 20% => 200 HT / 40 VAT / 240 TTC
		$propal->addline('Normal', 100, 2, 20, 0, 0, 0, 0, 'HT', 0, 0, 0, -1, 0, 0, 0, 0, '', '', '', array(), null, '', 0, 0, 0, 0, 0);
		// Option line: 500 HT x 1, excluded from totals
		$propal->addline('Option', 500, 1, 20, 0, 0, 0, 0, 'HT', 0, 0, 0, -1, 0, 0, 0, 0, '', '', '', array(), null, '', 0, 0, 0, 0, 1);

		$propal->fetch($propal->id);

		$this->assertEquals(200, (float) $propal->total_ht, 'Option line must be excluded from total_ht');
		$this->assertEquals(40, (float) $propal->total_tva, 'Option VAT must be excluded from total_tva');
		$this->assertEquals(240, (float) $propal->total_ttc, 'Option line must be excluded from total_ttc');

		print __METHOD__." total_ht=".$propal->total_ht." total_ttc=".$propal->total_ttc."\n";
	}
}
