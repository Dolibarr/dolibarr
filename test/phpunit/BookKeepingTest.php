<?php
/*
 * Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *
 * \file       test/phpunit/BookKeepingTest.php
 * \ingroup    test
 * \brief      PHPUnit test
 *	\remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/bookkeeping.class.php';
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
class BookKeepingTest extends CommonClassTest
{
	/**
	 * testBookKeepingCreate
	 *
	 * @return	int
	 */
	public function testBookKeepingCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "BookKeepingTest Unittest";
		$socid = $soc->create($user);
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new BookKeeping($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		print __METHOD__." result=".$result." id=".$localobject->id."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject->id;
	}

	/**
	 * testBookKeepingFetch
	 *
	 * @param	int		$id    Id of bookkeeping entry
	 * @return	BookKeeping    BookKeeping record object
	 *
	 * @depends	testBookKeepingCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BookKeeping($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testBookKeepingUpdate
	 *
	 * @param	BookKeeping	$localobject	BookKeeping record object
	 * @return	int
	 *
	 * @depends	testBookKeepingFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// TODO
		$localobject->owner = 'New owner';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testBookKeepingDelete
	 *
	 * @param	BookKeeping	$localobject	BookKeeping record object
	 * @return	int
	 *
	 * @depends	testBookKeepingUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// TODO
		$result = $localobject->delete($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
