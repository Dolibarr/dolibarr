<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/BarcodeTest.php
 *		\ingroup    test
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
	$user->getrights();
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
class BarcodeTest extends CommonClassTest
{
	/**
	 * testBarcodeZATCAEncode
	 *
	 * @return  int
	 */
	public function testBarcodeZATCAEncode()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$company = new Societe($db);
		$company->name = 'Specimen company';
		$company->tva_intra = '123456789';

		$tmpinvoice = new Facture($db);

		$tmpinvoice->thirdparty = $company;
		$tmpinvoice->total_ht = 100;
		$tmpinvoice->total_tva = 20;
		$tmpinvoice->total_ttc = $tmpinvoice->total_ht + $tmpinvoice->total_tva;
		$tmpinvoice->date = dol_mktime(12, 34, 56, 1, 1, 2020, 'gmt');

		$string_zatca = $tmpinvoice->buildZATCAQRString();

		$this->assertEquals($string_zatca, "ARBTcGVjaW1lbiBjb21wYW55AgkxMjM0NTY3ODkDFDIwMjAtMDEtMDFUMDk6MzQ6NTZaBAMxMjAFAjIw");

		return 1;
	}



	/**
	 * testBarcodeZATCADecode
	 *
	 * @return  int
	 */
	public function testBarcodeZATCADecode()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		//$string_zatca_base64 = "AQZSYWZlZXECDTEyMzQ1Njc4OVQxMjUDFDIwMjEtMDctMTJUMTQ6MjU6MDlaBAM3ODYFAjI1";
		$string_zatca_base64 = "ARBTcGVjaW1lbiBjb21wYW55AgkxMjM0NTY3ODkDFDIwMjAtMDEtMDFUMDk6MzQ6NTZaBAMxMjAFAjIw";

		$decoded = base64_decode($string_zatca_base64);

		//print_r($decoded)
		//raw data
		//\u0001\u0006Rafeeq\u0002\t123456789\u0003\u00142021-07-12T14:25:09Z\u0004\u0003786\u0005\u000225

		$result_data = preg_replace('/[\x00-\x1F\x80-\xFF]/', ',', $decoded);

		$arrayOfData = explode(',,', $result_data);


		print __METHOD__." result=".var_export($arrayOfData, true)."\n";
		$this->assertEquals("", $arrayOfData[0]);
		$this->assertEquals("Specimen company", $arrayOfData[1]);
		$this->assertEquals("123456789", $arrayOfData[2]);
		$this->assertEquals("2020-01-01T09:34:56Z", $arrayOfData[3]);
		$this->assertEquals("120", $arrayOfData[4]);
		$this->assertEquals("20", $arrayOfData[5]);

		return 1;
	}
}
