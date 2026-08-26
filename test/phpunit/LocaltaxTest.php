<?php
/* Copyright (C) 2011-2014	Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2024-2026	MDW				<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France <frederic.france@free.fr>
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
 */

/**
 *      \file       test/phpunit/LocaltaxTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for Localtax class
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/localtax/class/localtax.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");
$langs->load("bills");

/**
 * Class for PHPUnit tests on Localtax
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LocaltaxTest extends CommonClassTest
{
	/**
	 * testLocaltaxCreate
	 *
	 * @return  int
	 */
	public function testLocaltaxCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Localtax($db);
		$localobject->initAsSpecimen();

		// Set required fields for Localtax
		$localobject->ltt = 0; // Local tax type
		$localobject->datep = dol_now();
		$localobject->datev = dol_now();
		$localobject->amount = 100.00;
		$localobject->label = 'Test Local Tax Payment';
		$localobject->entity = $conf->entity;

		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		print __METHOD__." localobject->id=".$localobject->id."\n";
		$this->assertGreaterThan(0, $result, 'Failed to create Localtax object');

		return $localobject->id;
	}

	/**
	 * testLocaltaxFetch
	 *
	 * @param   int $id     Id of localtax
	 * @return  Localtax
	 *
	 * @depends testLocaltaxCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testLocaltaxFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Localtax($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		print __METHOD__." localobject->id=".$localobject->id."\n";
		print __METHOD__." localobject->label=".$localobject->label."\n";
		print __METHOD__." localobject->amount=".$localobject->amount."\n";
		$this->assertGreaterThan(0, $result, 'Failed to fetch Localtax object');
		$this->assertEquals($id, $localobject->id, 'Fetched ID does not match');
		$this->assertEquals('Test Local Tax Payment', $localobject->label, 'Fetched label does not match');

		return $localobject;
	}

	/**
	 * testLocaltaxUpdate
	 *
	 * @param   Localtax  $localobject    Localtax object
	 * @return  int
	 *
	 * @depends testLocaltaxFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testLocaltaxUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$oldLabel = $localobject->label;
		$oldAmount = $localobject->amount;

		// Modify some fields
		$localobject->label = 'Updated Local Tax Payment';
		$localobject->amount = 150.00;

		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		print __METHOD__." old label=".$oldLabel." new label=".$localobject->label."\n";
		print __METHOD__." old amount=".$oldAmount." new amount=".$localobject->amount."\n";
		$this->assertGreaterThan(0, $result, 'Failed to update Localtax object');

		// Re-fetch to verify update
		$localobject2 = new Localtax($db);
		$localobject2->fetch($localobject->id);
		$this->assertEquals('Updated Local Tax Payment', $localobject2->label, 'Updated label not persisted');
		$this->assertEquals(150.00, $localobject2->amount, 'Updated amount not persisted');

		return $localobject->id;
	}

	/**
	 * testLocaltaxDelete
	 *
	 * @param   int $id     Id of localtax
	 * @return  int
	 *
	 * @depends testLocaltaxUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testLocaltaxDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Localtax($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Failed to delete Localtax object');

		return $result;
	}
}
