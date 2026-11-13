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
 * \file    test/unit/BillOfMaterialsTest.php
 * \ingroup billofmaterials
 * \brief   PHPUnit test for BillOfMaterials class.
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/bom/class/bom.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/cunits.class.php';
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
class BOMTest extends CommonClassTest
{
	/**
	 * testBOMCreate
	 *
	 * @return int
	 */
	public function testBOMCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BOM($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testBOMDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	void
	 *
	 * @depends	testBOMCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testBOMDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BOM($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * A product used at several levels of the tree with different units of the same unit type
	 * must be cumulated once converted, not added raw.
	 *
	 * @return void
	 */
	public function testBOMGetNetNeedsCumulatesMixedUnits()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$cunits = new CUnits($db);
		$idhour = $cunits->getUnitFromCode('H', 'code', 'time');
		$idminute = $cunits->getUnitFromCode('MI', 'code', 'time');

		$childline = new BOMLine($db);
		$childline->fk_product = 101;
		$childline->qty = 15;
		$childline->fk_unit = $idminute;

		$childbom = new BOM($db);
		$childbom->id = 2;
		$childbom->qty = 1;
		$childbom->lines = array($childline);

		$serviceline = new BOMLine($db);
		$serviceline->fk_product = 101;
		$serviceline->qty = 1;
		$serviceline->fk_unit = $idhour;

		$subassemblyline = new BOMLine($db);
		$subassemblyline->fk_product = 200;
		$subassemblyline->qty = 1;
		$subassemblyline->childBom = array($childbom);

		$localobject = new BOM($db);
		$localobject->id = 1;
		$localobject->qty = 1;
		$localobject->lines = array($serviceline, $subassemblyline);

		$TNetNeeds = array();
		$localobject->getNetNeeds($TNetNeeds, 1);

		print __METHOD__." qty=".$TNetNeeds[101]['qty']."\n";
		$this->assertEquals($idhour, $TNetNeeds[101]['fk_unit']);
		$this->assertEquals(1.25, $TNetNeeds[101]['qty']);
	}
}
