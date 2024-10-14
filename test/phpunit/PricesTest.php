<?php
/* Copyright (C) 2010 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2015 Juanjo Menent			<jmenent@2byte.es>
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
 *      \file       test/phpunit/PricesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/price.lib.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

if (getDolGlobalString('MAIN_ROUNDING_RULE_TOT')) {
	print "Parameter MAIN_ROUNDING_RULE_TOT must be set to 0 or not set.\n";
	exit(1);
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PricesTest extends CommonClassTest
{
	/**
	 * Test function calcul_price_total
	 *
	 * @return 	boolean
	 * @see		http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
	 */
	public function testCalculPriceTotal()
	{
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		global $mysoc;
		$mysoc = new Societe($db);

		/*
		 *  Country France
		 */

		// qty=1, unit_price=1.24, discount_line=0, vat_rate=10, price_base_type='HT' (method we provide value)
		$mysoc->country_code = 'FR';
		$mysoc->country_id = 1;
		$result1 = calcul_price_total(1, 1.24, 0, 10, 0, 0, 0, 'HT', 0, 0);
		print __METHOD__." result1=".join(', ', $result1)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(1.24, 0.12, 1.36, 1.24, 0.124, 1.364, 1.24, 0.12, 1.36, 0, 0, 0, 0, 0, 0, 0, 1.24, 0.12, 1.36, 1.24, 0.124, 1.364, 1.24, 0.12, 1.36, 0, 0), $result1, 'Test1 FR');

		// qty=1, unit_price=1.24, discount_line=0, vat_rate=10, price_base_type='HT', multicurrency_tx=1.09205 (method we provide value)
		$mysoc->country_code = 'FR';
		$mysoc->country_id = 1;
		$result1 = calcul_price_total(2, 8.56, 0, 10, 0, 0, 0, 'HT', 0, 0, '', '', 100, 1.09205);
		print __METHOD__." result1=".join(', ', $result1)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(17.12, 1.71, 18.83, 8.56, 0.856, 9.416, 17.12, 1.71, 18.83, 0, 0, 0, 0, 0, 0, 0, 18.7, 1.87, 20.57, 9.34795, 0.93479, 10.28274, 18.7, 1.87, 20.57, 0, 0), $result1, 'Test1b FR');

		// qty=2, unit_price=0, discount_line=0, vat_rate=10, price_base_type='HT', multicurrency_tx=1.09205 (method we provide value), pu_ht_devise=100
		$mysoc->country_code = 'FR';
		$mysoc->country_id = 1;
		$result1 = calcul_price_total(2, 0, 0, 10, 0, 0, 0, 'HT', 0, 0, '', '', 100, 1.09205, 20);
		print __METHOD__." result1=".join(', ', $result1)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(36.63, 3.66, 40.29, 18.31418, 1.83142, 20.1456, 36.63, 3.66, 40.29, 0, 0, 0, 0, 0, 0, 0, 40, 4, 44, 20, 2, 22, 40, 4, 44, 0, 0), $result1, 'Test1c FR');

		/*
		 *  Country Spain
		 */

		// 10 * 10 HT - 0% discount with 10% vat, seller not using localtax1, not localtax2 (method we provide value)
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 0;
		$result2 = calcul_price_total(10, 10, 0, 10, 0, 0, 0, 'HT', 0, 0);	// 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1, 0% localtax2
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0, 0, 0, 0, 0, 0, 100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0), $result2, 'Test1 ES');

		// 10 * 10 HT - 0% discount with 10% vat, seller not using localtax1, not localtax2 (other method autodetect)
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 0;
		$result2 = calcul_price_total(10, 10, 0, 10, -1, -1, 0, 'HT', 0, 0);	// 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1, 0% localtax2
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0, 0, 0, 0, 0, 0, 100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0), $result2, 'Test2 ES');

		// --------------------------------------------------------

		// 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1 type 3, 0% localtax2 type 5 (method we provide value)
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 1;
		$mysoc->localtax2_assuj = 0;
		$result2 = calcul_price_total(10, 10, 0, 10, 1.4, 0, 0, 'HT', 0, 0);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0, 0.14, 0, 0, 1.4, 0, 100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0), $result2, 'Test3 ES');

		// 10 * 10 HT - 0% discount with 10% vat and 1.4% localtax1 type 3, 0% localtax2 type 5 (other method autodetect)
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 1;
		$mysoc->localtax2_assuj = 0;
		$result2 = calcul_price_total(10, 10, 0, 10, -1, -1, 0, 'HT', 0, 0);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0, 0.14, 0, 0, 1.4, 0, 100, 10, 111.4, 10, 1, 11.14, 100, 10, 111.4, 1.4, 0), $result2, 'Test4 ES');

		// --------------------------------------------------------

		// 10 * 10 HT - 0% discount with 10% vat and 0% localtax1 type 3, 19% localtax2 type 5 (method we provide value), we provide a service and not a product
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 1;
		$result2 = calcul_price_total(10, 10, 0, 10, 0, -19, 0, 'HT', 0, 1);
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 91, 10, 1, 9.1, 100, 10, 91, 0, -19, 0, -1.90, 0, 0, -19, 100, 10, 91, 10, 1, 9.1, 100, 10, 91, 0, -19), $result2, 'Test5 ES for service');

		// 10 * 10 HT - 0% discount with 10% vat and 0% localtax1 type 3, 21% localtax2 type 5 (other method autodetect), we provide a service and not a product
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 1;
		$result2 = calcul_price_total(10, 10, 0, 10, -1, -1, 0, 'HT', 0, 0);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0, 0, 0, 0, 0, 0, 100, 10, 110, 10, 1, 11, 100, 10, 110, 0, 0), $result2, 'Test6 ES for product');

		// 10 * 10 HT - 0% discount with 10% vat and 0% localtax1 type 3, 21% localtax2 type 5 (other method autodetect), we provide a product and not a service
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 1;
		$result2 = calcul_price_total(10, 10, 0, 10, -1, -1, 0, 'HT', 0, 1);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 10, 91, 10, 1, 9.1, 100, 10, 91, 0, -19, 0, -1.90, 0, 0, -19, 100, 10, 91, 10, 1, 9.1, 100, 10, 91, 0, -19), $result2, 'Test6 ES for service');

		// --------------------------------------------------------

		// Credit Note: 10 * -10 HT - 0% discount with 10% vat and 0% localtax1 type 3, 19% localtax2 type 5 (method we provide value), we provide a product and not a service
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 1;
		$result2 = calcul_price_total(10, -10, 0, 10, 0, 19, 0, 'HT', 0, 0);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(-100, -10, -110, -10, -1, -11, -100, -10, -110, 0, 0, 0, 0, 0, 0, 0, -100, -10, -110, -10, -1, -11, -100, -10, -110, 0, 0), $result2, 'Test7 ES for product');

		// Credit Note: 10 * -10 HT - 0% discount with 10% vat and 1.4% localtax1 type 3, 0% localtax2 type 5 (other method autodetect), we provide a service and not a product
		$mysoc->country_code = 'ES';
		$mysoc->country_id = 4;
		$mysoc->localtax1_assuj = 0;
		$mysoc->localtax2_assuj = 1;
		$result2 = calcul_price_total(10, -10, 0, 10, -1, -1, 0, 'HT', 0, 1);
		print __METHOD__." result2=".join(', ', $result2)."\n";
		$this->assertEquals(array(-100, -10, -91, -10, -1, -9.1, -100, -10, -91, 0, 19, 0, 1.90, 0, 0, 19, -100, -10, -91, -10, -1, -9.1, -100, -10, -91, 0, 19), $result2, 'Test8 ES for service');


		/*
		 * Country Côte d'Ivoire
		 */

		// 10 * 10 HT - 0% discount with 18% vat, seller using localtax1 type 2, not localtax2 (method we provide value)
		$mysoc->country_code = 'CI';
		$mysoc->country_id = 21;
		$mysoc->localtax1_assuj = 1;
		$mysoc->localtax2_assuj = 0;
		//$localtaxes=getLocalTaxesFromRate(18, 0, null, $mysoc);
		//var_dump($locataxes);
		$result3 = calcul_price_total(10, 10, 0, 18, 7.5, 0, 0, 'HT', 0, 0);	// 10 * 10 HT - 0% discount with 18% vat and 7.5% localtax1, 0% localtax2
		print __METHOD__." result3=".join(', ', $result3)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 18, 126.85, 10, 1.8, 12.685, 100, 18, 126.85, 8.85, 0, 0.885, 0, 0, 8.85, 0, 100, 18, 126.85, 10, 1.8, 12.685, 100, 18, 126.85, 8.85, 0), $result3, 'Test9 CI');

		// 10 * 10 HT - 0% discount with 18% vat, seller using localtax1 type 2, not localtax2 (other method autodetect)
		$mysoc->country_code = 'CI';
		$mysoc->country_id = 21;
		$mysoc->localtax1_assuj = 1;
		$mysoc->localtax2_assuj = 0;
		$result3 = calcul_price_total(10, 10, 0, 18, -1, -1, 0, 'HT', 0, 0);	// 10 * 10 HT - 0% discount with 18% vat and 7.5% localtax1, 0% localtax2
		print __METHOD__." result3=".join(', ', $result3)."\n";
		// result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
		$this->assertEquals(array(100, 18, 126.85, 10, 1.8, 12.685, 100, 18, 126.85, 8.85, 0, 0.885, 0, 0, 8.85, 0, 100, 18, 126.85, 10, 1.8, 12.685, 100, 18, 126.85, 8.85, 0), $result3, 'Test10 CI');

		return true;
	}


	/**
	 * Test function addline and update_price
	 *
	 * @return 	boolean
	 * @see		http://wiki.dolibarr.org/index.php/Draft:VAT_calculation_and_rounding#Standard_usage
	 */
	public function testUpdatePrice()
	{
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		$conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND = 0;

		// Two lines of 1.24 give 2.48 HT and 2.72 TTC with standard vat rounding mode
		$localobject = new Facture($db);
		$localobject->initAsSpecimen('nolines');
		$invoiceid = $localobject->create($user);

		$localobject->addline('Desc', 1.24, 1, 10, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT');
		$localobject->addline('Desc', 1.24, 1, 10, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT');

		$newlocalobject = new Facture($db);
		$newlocalobject->fetch($invoiceid);

		$this->assertEquals(2.48, $newlocalobject->total_ht, "testUpdatePrice test1");
		$this->assertEquals(0.24, $newlocalobject->total_tva, "testUpdatePrice test2");
		$this->assertEquals(2.72, $newlocalobject->total_ttc, "testUpdatePrice test3");


		// Two lines of 1.24 give 2.48 HT and 2.73 TTC with global vat rounding mode
		$localobject = new Facture($db);
		$localobject->initAsSpecimen('nolines');
		$invoiceid = $localobject->create($user);

		$localobject->addline('Desc', 1.24, 1, 10, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT');
		$localobject->addline('Desc', 1.24, 1, 10, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT');

		$newlocalobject = new Facture($db);
		$newlocalobject->fetch($invoiceid);

		$this->assertEquals(2.48, $newlocalobject->total_ht, "testUpdatePrice test4");
		//$this->assertEquals(0.25,$newlocalobject->total_tva);
		//$this->assertEquals(2.73,$newlocalobject->total_ttc);
	}
}
