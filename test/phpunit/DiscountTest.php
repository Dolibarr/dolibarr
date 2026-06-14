<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Vincent de Grandpré		<vincent@de-grandpre.quebec>
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
 *      \file       test/phpunit/DiscountTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/discount.class.php';
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
class DiscountTest extends CommonClassTest
{
	/**
	 * testDiscountCreate
	 *
	 * @return	int
	 */
	public function testDiscountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "CommandeTest Unittest";
		$socid = $soc->create($user);
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new DiscountAbsolute($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDiscountFetch
	 *
	 * @param	int	$id		Id of discount
	 * @return	int
	 *
	 * @depends	testDiscountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDiscountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DiscountAbsolute($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $id;
	}

	/**
	 * testDiscountDelete
	 *
	 * @param	int		$id		Id of discount
	 * @return	int
	 *
	 * @depends	testDiscountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDiscountDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DiscountAbsolute($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testDiscountScenarioConfirmSplit
	 *
	 * test the scenario of action 'confirm_split' in remx.php
	 * also support 'confirm_split_more'
	 * @param	float 	$total_amount initial amount of discount to split
	 * @param	float 	$splitamount_1 first half of split discount
	 * @param	float	$tva_tx vat rate
	 * @param	float	$localtax1_tx localtax1 rate
	 * @param	int		$localtax1_type localtax1 type
	 * @param	float	$localtax2_tx localtax2 rate
	 * @param	int		$localtax2_type localtax1 type
	 * @param	float	$ex_ht_amount1 expected amount 1 HT
	 * @param	float	$ex_total_amount1 expected amount 1 TTC
	 * @param	float	$ex_ht_amount2 expected amount 2 HT
	 * @param	float	$ex_total_amount2 expected amount 2 TTC
	 * @return	int
	 * @dataProvider providerSplitRemiseData
	 */
	public function testDiscountSplitScenarioConfirmSplit($total_amount, $splitamount_1, $tva_tx, $localtax1_tx, $localtax1_type, $localtax2_tx, $localtax2_type, $ex_ht_amount1, $ex_total_amount1, $ex_ht_amount2, $ex_total_amount2)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/**
		 * Create a DiscountAbsolute object from spec, split it and
		 * test results with expected.
		 */
		$localobject = new DiscountAbsolute($db);
		$localobject->tva_tx = $tva_tx;
		$localobject->localtax1_tx = $localtax1_tx;
		$localobject->localtax1_type = $localtax1_type;
		$localobject->localtax2_tx = $localtax2_tx;
		$localobject->localtax2_type = $localtax2_type;
		$newDiscounts = $localobject->splitAmount($splitamount_1, $total_amount - $splitamount_1);
		$newdiscount1 = $newDiscounts[0];
		$newdiscount2 = $newDiscounts[1];
		$this->assertEquals($ex_ht_amount1, $newdiscount1->amount_ht);
		$this->assertEquals($ex_total_amount1, $newdiscount1->amount_ttc);
		$this->assertEquals($ex_ht_amount2, $newdiscount2->amount_ht);
		$this->assertEquals($ex_total_amount2, $newdiscount2->amount_ttc);
		$result = 1;

		print __METHOD__." total_amount=".$total_amount." splitamount_1=".$splitamount_1." tva_tx=".$tva_tx." localtax1_tx=".$localtax1_tx." localtax1_type=".$localtax1_type." localtax2_tx=".$localtax2_tx." localtax2_type=".$localtax2_type." result=".$result."\n";

		return $result;
	}

	/**
	 * testDiscountScenarioSetRemise
	 *
	 * test the scenario of function set_remise_exept of class Societe
	 *
	 * @param	float 	$amount amount of discount
	 * @param	float	$vat_tx vat rate
	 * @param	float	$localtax1_tx localtax1 rate
	 * @param	int		$localtax1_type localtax1 type
	 * @param	float	$localtax2_tx localtax2 rate
	 * @param	int		$localtax2_type localtax1 type
	 * @param	string	$price_base ('HT' or something else)
	 * @param	float	$ex_total_tva expected discount vat amount
	 * @param	float	$ex_total_localtax1 expected discount localtax1 amount
	 * @param	float	$ex_total_localtax2 expected discount localtax2 amount
	 * @param	float	$ex_total_ttc expected discount total TTC amount
	 * @param	float	$ex_total_ht expected discount HT amount
	 * @return	int
	 * @dataProvider providerRemiseData
	 */
	public function testDiscountScenarioSetRemise($amount, $vat_tx, $localtax1_tx, $localtax1_type, $localtax2_tx, $localtax2_type, $price_base, $ex_total_tva, $ex_total_localtax1, $ex_total_localtax2, $ex_total_ttc, $ex_total_ht)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/**
		 * Create a DiscountAbsolute object with spec and test with expected result
		 */
		$localobject = new DiscountAbsolute($db);

		$localtax1_type2 = 0;
		if ($localtax1_type > 0 && $localtax1_type % 2 == 0) {
			$localtax1_type2 = 1;
		}
		$localtax2_type2 = 0;
		if ($localtax2_type > 0 && $localtax2_type % 2 == 0) {
			$localtax2_type2 = 1;
		}

		$localobject->generateFromAmount($amount, ($price_base == 'HT' ? 0 : 1), $vat_tx, $localtax1_tx, $localtax2_tx, $localtax1_type2, $localtax2_type2);

		$this->assertEquals($ex_total_ht, $localobject->amount_ht);
		$this->assertEquals($ex_total_ttc, $localobject->amount_ttc);
		$this->assertEquals($ex_total_tva, $localobject->amount_tva);
		$this->assertEquals($ex_total_localtax1, price2num($localobject->total_localtax1,'MT'));
		$this->assertEquals($ex_total_localtax2, price2num($localobject->total_localtax2,'MT'));
		$result = 1;

		print __METHOD__." amount=".$amount." vat_tx=".$vat_tx." localtax1_tx=".$localtax1_tx." localtax1_type=".$localtax1_type." localtax2_tx=".$localtax2_tx." localtax2_type=".$localtax2_type." price_base=".$price_base." result=".$result."\n";

		return $result;
	}

	/**
	 * Provide test data for AbsoluteDiscount
	 *
	 * @return array values and expectations data
	 */
	public function providerRemiseData()
	{
		// array(amount, vat_tx, localtax1_tx, localtax1_type, localtax2_tx, localtax2_type, price_base, ex_total_tva, ex_total_localtax1, ex_total_localtax2, ex_total_ttc, ex_total_ht),
		return array(
			array(1234,5,9.975,1,0,0,'HT',61.7,123.09,0,1418.79,1234),
			array(1418.79,5,9.975,1,0,0,'',61.7,123.09,0,1418.79,1234),
			array(1234,5,9.975,1,4,1,'HT',61.7,123.09,49.36,1468.15,1234),
			array(1468.15,5,9.975,1,4,1,'',61.7,123.09,49.36,1468.15,1234),
			array(1234,5,9.975,2,0,0,'HT',61.7,129.25,0,1424.95,1234),
			array(1424.95,5,9.975,2,0,0,'',61.7,129.25,0,1424.95,1234),
			array(1234,5,9.975,2,4,2,'HT',61.7,129.25,57,1481.94,1234),
			array(1481.94,5,9.975,2,4,2,'',61.7,129.25,57,1481.94,1234)
		);
	}

	/**
	 * Provide test data for AbsoluteDiscount splitting
	 *
	 * @return array values and expectations data
	 */
	public function providerSplitRemiseData()
	{
		// A fixed discount with a taxed total
		// array(total_amount, splitamount_1, tva_tx, localtax1_tx, localtax1_type, localtax2_tx, localtax2_type, ex_ht_amount1, ex_total_amount1, ex_ht_amount2, ex_total_amount2),
		return array(
			array(1468.15,1000,5,9.975,1,0,0,869.75,1000,407.18,468.15)
		);
	}
}
