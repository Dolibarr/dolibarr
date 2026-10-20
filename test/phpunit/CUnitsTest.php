<?php
/* Copyright (C) 2026 Nicolas Vidal <nicolas.vidal@atm-consulting.fr>
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
 * \file    test/phpunit/CUnitsTest.php
 * \ingroup core
 * \brief   PHPUnit test for CUnits class.
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
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
class CUnitsTest extends CommonClassTest
{
	/**
	 * testUnitConverterSameTypeInsideTimeUnits
	 *
	 * @return void
	 */
	public function testUnitConverterSameTypeInsideTimeUnits()
	{
		global $db;

		$cunits = new CUnits($db);
		$idhour = $cunits->getUnitFromCode('H', 'code', 'time');
		$idminute = $cunits->getUnitFromCode('MI', 'code', 'time');

		$this->assertEquals(60, $cunits->unitConverterSameType(1, $idhour, $idminute));
		$this->assertEquals(0.25, $cunits->unitConverterSameType(15, $idminute, $idhour));
	}

	/**
	 * A conversion to a larger unit must keep the value, so that a small need is not lost.
	 *
	 * @return void
	 */
	public function testUnitConverterSameTypeKeepsValueOfSmallQuantities()
	{
		global $db;

		$cunits = new CUnits($db);
		$idkg = $cunits->getUnitFromCode('KG', 'code', 'weight');
		$idg = $cunits->getUnitFromCode('G', 'code', 'weight');

		$this->assertEquals(0.001, $cunits->unitConverterSameType(1, $idg, $idkg));
		$this->assertEquals(1000, $cunits->unitConverterSameType(1, $idkg, $idg));
	}

	/**
	 * testUnitConverterSameTypeRefusesTwoDifferentUnitTypes
	 *
	 * @return void
	 */
	public function testUnitConverterSameTypeRefusesTwoDifferentUnitTypes()
	{
		global $db;

		$cunits = new CUnits($db);
		$idunit = $cunits->getUnitFromCode('P', 'code', 'qty');
		$idminute = $cunits->getUnitFromCode('MI', 'code', 'time');

		$this->assertFalse($cunits->unitConverterSameType(1, $idunit, $idminute));
	}

	/**
	 * Units with no metric scale (imperial units, litre, gallon) carry a placeholder scale, not an exponent.
	 *
	 * @return void
	 */
	public function testUnitConverterSameTypeRefusesUnitWithoutMetricScale()
	{
		global $db;

		$cunits = new CUnits($db);
		$idkg = $cunits->getUnitFromCode('KG', 'code', 'weight');
		$idpound = $cunits->getUnitFromCode('LB', 'code', 'weight');

		$this->assertFalse($cunits->unitConverterSameType(1, $idpound, $idkg));
		$this->assertFalse($cunits->unitConverterSameType(1, $idkg, $idpound));
	}

	/**
	 * testUnitConverterSameTypeRefusesUnknownUnit
	 *
	 * @return void
	 */
	public function testUnitConverterSameTypeRefusesUnknownUnit()
	{
		global $db;

		$cunits = new CUnits($db);
		$idhour = $cunits->getUnitFromCode('H', 'code', 'time');

		$this->assertFalse($cunits->unitConverterSameType(1, 0, $idhour));
		$this->assertFalse($cunits->unitConverterSameType(1, $idhour, 0));
	}
}
