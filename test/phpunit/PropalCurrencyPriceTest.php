<?php
/* Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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
 *      \file       test/phpunit/PropalCurrencyPriceTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for the per-currency sourced price flag on proposal lines (issue #32379)
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests of sourced per-currency price flag on proposals
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PropalCurrencyPriceTest extends CommonClassTest
{
	/**
	 * Build a proposal with one line carrying a sourced currency unit price.
	 *
	 * @return	array{0:Propal,1:int}	The proposal and the created line id
	 */
	private function buildProposalWithSourcedLine(): array
	{
		global $db, $user;

		$societe = new Societe($db);
		$societe->name = 'PPC test soc '.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$societe->client = 1;
		$societe->code_client = -1;
		$societe->country_id = 1;
		$socid = $societe->create($user);
		$this->assertGreaterThan(0, $socid, 'Societe creation failed: '.$societe->error.' '.implode(',', $societe->errors));

		$product = new Product($db);
		$product->ref = 'PPCDOC'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$product->label = 'Currency line test';
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$product->price = 90;
		$product->price_base_type = 'HT';
		$product->tva_tx = 20;
		$pid = $product->create($user);
		$this->assertGreaterThan(0, $pid, 'Product creation failed: '.$product->error);

		$propal = new Propal($db);
		$propal->socid = $socid;
		$propal->date = dol_now();
		$propal->multicurrency_code = 'USD';
		$propalid = $propal->create($user);
		$this->assertGreaterThan(0, $propalid, 'Propal creation failed: '.$propal->error);

		// Foreign currency context on the object
		$propal->multicurrency_code = 'USD';
		$propal->multicurrency_tx = 1.1;

		// Add a line with a sourced currency unit price (100 USD) and flag = 1
		$res = $propal->addline(
			'Currency sourced line', 0, 2, 20, 0, 0, $pid, 0, 'HT', 0, 0, 0,
			0, 0, 0, 0, 0, '', '', '', array(), null, '', 0, 100.0, 0, 0, 1
		);
		$this->assertGreaterThan(0, $res, 'addline failed: '.$propal->error);

		$propal->fetch($propalid);
		$propal->fetch_lines();
		$this->assertNotEmpty($propal->lines, 'No lines fetched');

		return array($propal, (int) $propal->lines[0]->id);
	}

	/**
	 * The sourced flag is persisted and reloaded on proposal lines.
	 *
	 * @return void
	 */
	public function testSourcedFlagIsPersisted()
	{
		list($propal,) = $this->buildProposalWithSourcedLine();

		$line = $propal->lines[0];
		$this->assertEquals(1, (int) $line->multicurrency_subprice_source, 'Sourced flag must be stored as 1');
		$this->assertEquals(100.0, (float) $line->multicurrency_subprice, 'Currency unit price must be 100');
	}

	/**
	 * Changing the document rate in mode 2 (recompute currency) must NOT alter a sourced currency price;
	 * the company-currency price is recomputed instead.
	 *
	 * @return void
	 */
	public function testRateChangeKeepsSourcedCurrencyPrice()
	{
		global $db;

		list($propal,) = $this->buildProposalWithSourcedLine();

		$subpriceBefore = (float) $propal->lines[0]->subprice;

		// Mode 2 would normally wipe and recompute multicurrency_subprice from the company price
		$res = $propal->setMulticurrencyRate(1.5, 2);
		$this->assertGreaterThan(0, $res, 'setMulticurrencyRate failed');

		$reloaded = new Propal($db);
		$reloaded->fetch($propal->id);
		$reloaded->fetch_lines();

		$this->assertEquals(100.0, (float) $reloaded->lines[0]->multicurrency_subprice, 'Sourced currency price must be preserved on rate change');
		// Company-currency price must have been recomputed (100 / 1.5 != previous 100 / 1.1)
		$this->assertNotEquals($subpriceBefore, (float) $reloaded->lines[0]->subprice, 'Company price must be recomputed from the new rate');
	}
}
