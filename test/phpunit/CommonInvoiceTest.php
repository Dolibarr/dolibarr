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
 *      \file       test/phpunit/CommonInvoiceTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
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
class CommonInvoiceTest extends CommonClassTest
{
	/**
	 *  testCalculateDateLimReglement
	 *
	 *  @return void
	 */
	public function testCalculateDateLimReglement()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Facture($db);
		$localobject->fetch(1);

		$result = 0;

		print "\n";

		// Add 45 days, take end of month, add 15 days
		$localobject->date = dol_mktime(12, 0, 0, 1, 1, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST1', 'TEST1', 45, 1, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/03/2010 12:00', dol_print_date($result, 'dayhour', 'gmt'));

		// Add 45 days, take end of month, add 15 days
		$localobject->date = dol_mktime(12, 0, 0, 3, 31, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST1b', 'TEST1b', 45, 1, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/06/2010 12:00', dol_print_date($result, 'dayhour', 'gmt'));

		// Test on the mode type_cdr = 2

		// 2010-01-01  Add 45 days, go to the next 15th
		$localobject->date = dol_mktime(12, 0, 0, 1, 1, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST2', 'TEST2', 45, 2, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		if ($id <= 0) {
			die(1);
		}
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/02/2010 00:00', dol_print_date($result, 'dayhour', 'gmt'));

		// 2010-03-30  Add 45 days, go to the next 15th
		$localobject->date = dol_mktime(12, 0, 0, 3, 30, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST3', 'TEST3', 45, 2, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		if ($id <= 0) {
			die(1);
		}
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/05/2010 00:00', dol_print_date($result, 'dayhour', 'gmt'));

		// 2010-03-31  Add 45 days, go to the next 15th
		$localobject->date = dol_mktime(12, 0, 0, 3, 31, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST3b', 'TEST3b', 45, 2, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		if ($id <= 0) {
			die(1);
		}
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/05/2010 00:00', dol_print_date($result, 'dayhour', 'gmt'));

		// 2010-04-01  Add 45 days, go to the next 15th
		$localobject->date = dol_mktime(12, 0, 0, 4, 1, 2010);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(code, libelle, nbjour, type_cdr, decalage) VALUES ('TEST3c', 'TEST3c', 45, 2, 15)";
		$resql = $db->query($sql);
		$id = $db->last_insert_id("c_payment_term");
		if ($id <= 0) {
			die(1);
		}
		$result = $localobject->calculate_date_lim_reglement($id);
		print __METHOD__." date=".dol_print_date($localobject->date, 'dayhour', 'gmt')."\n";
		print __METHOD__." result=".dol_print_date($result, 'dayhour', 'gmt')."\n";
		$this->assertEquals('15/06/2010 00:00', dol_print_date($result, 'dayhour', 'gmt'));

		return $result;
	}
}
