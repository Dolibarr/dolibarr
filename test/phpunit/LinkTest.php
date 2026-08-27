<?php
/* Copyright (C) 2026 Frédéric France  <frederic.france@free.fr>
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
 *      \file       test/phpunit/LinkTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/link.class.php';
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
class LinkTest extends CommonClassTest
{
	/**
	 * testLinkCreate
	 *
	 * @return int
	 */
	public function testLinkCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Link($db);
		$localobject->url = 'https://www.dolibarr.org';
		$localobject->label = 'Specimen link';
		$localobject->objecttype = 'societe';
		$localobject->objectid = 1;

		$result = $localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		return $result;
	}

	/**
	 * testLinkCreateWithoutUrl
	 *
	 * A link with no url must be rejected.
	 *
	 * @return void
	 *
	 * @depends testLinkCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkCreateWithoutUrl()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Link($db);
		$localobject->objecttype = 'societe';
		$localobject->objectid = 1;

		$result = $localobject->create($user);
		print __METHOD__." result=".$result."\n";
		$this->assertLessThan(0, $result);
	}

	/**
	 * testLinkFetch
	 *
	 * @param   int $id     Id of link
	 * @return  Link
	 *
	 * @depends testLinkCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Link($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		$this->assertEquals('https://www.dolibarr.org', $localobject->url);
		$this->assertEquals('Specimen link', $localobject->label);

		return $localobject;
	}

	/**
	 * testLinkFetchWithoutParameters
	 *
	 * A fetch() with neither rowid nor hashforshare must fail instead of
	 * returning an arbitrary record.
	 *
	 * @return void
	 *
	 * @depends testLinkCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkFetchWithoutParameters()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Link($db);
		$result = $localobject->fetch(0);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan(0, $result);
	}

	/**
	 * testLinkUpdate
	 *
	 * @param   Link    $localobject    Link
	 * @return  Link
	 *
	 * @depends testLinkFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Specimen link updated';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		$localobject->fetch($localobject->id);
		$this->assertEquals('Specimen link updated', $localobject->label);

		return $localobject;
	}

	/**
	 * testLinkFetchAll
	 *
	 * @param   Link    $localobject    Link
	 * @return  Link
	 *
	 * @depends testLinkUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkFetchAll($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$links = array();
		$tmpobject = new Link($db);
		$result = $tmpobject->fetchAll($links, $localobject->objecttype, $localobject->objectid);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		$this->assertGreaterThan(0, count($links));

		return $localobject;
	}

	/**
	 * testLinkCount
	 *
	 * @param   Link    $localobject    Link
	 * @return  Link
	 *
	 * @depends testLinkFetchAll
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkCount($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$nb = Link::count($db, $localobject->objecttype, $localobject->objectid);

		print __METHOD__." nb=".$nb."\n";
		$this->assertGreaterThan(0, $nb);

		return $localobject;
	}

	/**
	 * testLinkDelete
	 *
	 * @param   Link    $localobject    Link
	 * @return  int
	 *
	 * @depends testLinkCount
	 * The depends says test is run only if previous is ok
	 */
	public function testLinkDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->delete($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		$resultFetch = $localobject->fetch($localobject->id);
		print __METHOD__." resultFetch=".$resultFetch."\n";
		$this->assertEquals(0, $resultFetch);

		return $result;
	}
}
