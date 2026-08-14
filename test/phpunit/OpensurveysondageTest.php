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
 *      \file       test/phpunit/OpensurveysondageTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/opensurvey/class/opensurveysondage.class.php';
require_once dirname(__FILE__).'/../../htdocs/opensurvey/lib/opensurvey.lib.php';
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
class OpensurveysondageTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('opensurvey'), 'module opensurvey must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * testOpensurveysondageCreate
	 *
	 * The primary key (id_sondage) is a random string chosen by the caller before create(), like
	 * the real usage in opensurvey/lib/opensurvey.lib.php::ajouter_sondage(). It is used instead of
	 * the create() return value to identify the record: Opensurveysondage::create() returns
	 * $this->id, but $this->id is never set by this class (the real key is $this->id_sondage), so
	 * the return value is always null on success.
	 *
	 * @return Opensurveysondage
	 */
	public function testOpensurveysondageCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Opensurveysondage($db);
		$localobject->initAsSpecimen();
		$localobject->id_sondage = dol_survey_random(16);
		// initAsSpecimen() sets format='classic', but the column is varchar(2) and the only documented
		// valid values are 'A' (text choice), 'D' (date choice) or 'F' (form) - use a valid one here.
		$localobject->format = 'A';
		$result = $localobject->create($user);

		print __METHOD__." id_sondage=".$localobject->id_sondage." result=".var_export($result, true)."\n";
		$this->assertEmpty($localobject->errors, $localobject->errorsToString());

		// Confirm the record was really created (create()'s return value is not reliable, see above)
		$checkobject = new Opensurveysondage($db);
		$this->assertGreaterThan(0, $checkobject->fetch(0, $localobject->id_sondage));

		return $localobject;
	}

	/**
	 * testOpensurveysondageFetch
	 *
	 * @param	Opensurveysondage	$localobject	Poll created by the previous test
	 * @return	Opensurveysondage
	 *
	 * @depends	testOpensurveysondageCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testOpensurveysondageFetch($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$fetched = new Opensurveysondage($db);
		$result = $fetched->fetch(0, $localobject->id_sondage);

		$this->assertGreaterThan(0, $result, $fetched->errorsToString());
		print __METHOD__." id_sondage=".$localobject->id_sondage." result=".$result."\n";

		$this->assertSame($localobject->id_sondage, $fetched->id_sondage);
		$this->assertSame('This is a specimen survey', $fetched->title);
		$this->assertSame('Description of the specimen survey', $fetched->description);
		$this->assertSame('A', $fetched->format);
		$this->assertEquals(Opensurveysondage::STATUS_VALIDATED, $fetched->status);

		return $fetched;
	}

	/**
	 * testOpensurveysondageUpdate
	 *
	 * @param	Opensurveysondage	$localobject	Poll
	 * @return	Opensurveysondage
	 *
	 * @depends	testOpensurveysondageFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testOpensurveysondageUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->title = 'Updated title after update';
		$localobject->description = 'Updated description after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id_sondage=".$localobject->id_sondage." result=".$result."\n";

		$localobject->fetch(0, $localobject->id_sondage);
		$this->assertSame('Updated title after update', $localobject->title);
		$this->assertSame('Updated description after update', $localobject->description);

		return $localobject;
	}

	/**
	 * testOpensurveysondageDelete
	 *
	 * @param	Opensurveysondage	$localobject	Poll
	 * @return	int
	 *
	 * @depends	testOpensurveysondageUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testOpensurveysondageDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id_sondage=".$localobject->id_sondage." result=".$result."\n";

		$checkobject = new Opensurveysondage($db);
		$this->assertSame(0, $checkobject->fetch(0, $localobject->id_sondage), 'Poll must no longer be found after delete');

		return $result;
	}
}
