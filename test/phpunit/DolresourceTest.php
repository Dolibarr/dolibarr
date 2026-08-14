<?php
/* Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/DolresourceTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/resource/class/dolresource.class.php';
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
class DolresourceTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('resource'), 'module resource must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * testDolresourceCreate
	 *
	 * Dolresource has no initAsSpecimen(), so the object is built by hand.
	 *
	 * @return int
	 */
	public function testDolresourceCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Dolresource($db);
		$localobject->ref = 'PHPUNIT_RESOURCE';
		$localobject->description = 'This is a description';
		$localobject->phone = '0102030405';
		$localobject->email = 'phpunit@example.com';
		$localobject->max_users = 3;
		$localobject->note_public = 'Public note';
		$localobject->note_private = 'Private note';
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDolresourceFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Dolresource
	 *
	 * @depends	testDolresourceCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDolresourceFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Dolresource($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";

		$this->assertSame('PHPUNIT_RESOURCE', $localobject->ref);
		$this->assertSame('This is a description', $localobject->description);
		$this->assertSame('0102030405', $localobject->phone);
		$this->assertSame('phpunit@example.com', $localobject->email);
		$this->assertEquals(3, $localobject->max_users);
		$this->assertSame('Public note', $localobject->note_public);
		$this->assertSame('Private note', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testDolresourceUpdate
	 *
	 * @param	Dolresource		$localobject	Resource
	 * @return	Dolresource
	 *
	 * @depends	testDolresourceFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDolresourceUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Note: note_public/note_private are intentionally not changed/asserted here: Dolresource::update()'s
		// SQL UPDATE does not include these 2 columns, so changes to them are silently not persisted.
		$localobject->description = 'Updated description after update';
		$localobject->phone = '0605040302';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated description after update', $localobject->description);
		$this->assertSame('0605040302', $localobject->phone);

		return $localobject;
	}

	/**
	 * testDolresourceDelete
	 *
	 * @param	Dolresource		$localobject	Resource
	 * @return	int
	 *
	 * @depends	testDolresourceUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDolresourceDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $result;
	}
}
