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
 *      \file       test/phpunit/DocumentCurrencyPriceFlagTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test: sourced per-currency price flag persists through order and invoice lines (issue #32379)
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
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
 * Class for PHPUnit tests of the sourced per-currency price flag on order and invoice lines
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DocumentCurrencyPriceFlagTest extends CommonClassTest
{
	/**
	 * Create a throwaway customer.
	 *
	 * @return int Societe id
	 */
	private function createSoc(): int
	{
		global $db, $user;
		$societe = new Societe($db);
		$societe->name = 'DCF soc '.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$societe->client = 1;
		$societe->code_client = -1;
		$societe->country_id = 1;
		$socid = $societe->create($user);
		$this->assertGreaterThan(0, $socid, 'Societe creation failed: '.$societe->error.' '.implode(',', $societe->errors));
		return $socid;
	}

	/**
	 * Create a throwaway product.
	 *
	 * @return int Product id
	 */
	private function createProduct(): int
	{
		global $db, $user;
		$product = new Product($db);
		$product->ref = 'DCF'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').mt_rand(1, 9999);
		$product->label = 'Flag test';
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$product->price = 90;
		$product->price_base_type = 'HT';
		$product->tva_tx = 20;
		$pid = $product->create($user);
		$this->assertGreaterThan(0, $pid, 'Product creation failed: '.$product->error);
		return $pid;
	}

	/**
	 * Call addline by parameter name, filling defaults for the rest (robust to signature differences).
	 *
	 * @param	object					$object		Document object
	 * @param	array<string,mixed>		$named		Values keyed by parameter name
	 * @return	int									addline return
	 */
	private function addlineByName($object, array $named): int
	{
		$ref = new ReflectionMethod(get_class($object), 'addline');
		$args = array();
		foreach ($ref->getParameters() as $p) {
			$name = $p->getName();
			if (array_key_exists($name, $named)) {
				$args[] = $named[$name];
			} elseif ($p->isDefaultValueAvailable()) {
				$args[] = $p->getDefaultValue();
			} else {
				$args[] = null;
			}
		}
		return (int) $ref->invokeArgs($object, $args);
	}

	/**
	 * addline of Commande and Facture must expose the multicurrency_subprice_source parameter.
	 *
	 * @return void
	 */
	public function testAddlineSignaturesExposeFlag()
	{
		foreach (array('Commande', 'Facture') as $class) {
			foreach (array('addline', 'updateline') as $method) {
				$ref = new ReflectionMethod($class, $method);
				$names = array_map(static function ($p) {
					return $p->getName();
				}, $ref->getParameters());
				$this->assertContains('multicurrency_subprice_source', $names, $class.'::'.$method.' must expose the flag parameter');
			}
		}
	}

	/**
	 * Call updateline by parameter name, filling defaults for the rest (robust to signature differences).
	 *
	 * @param	object					$object		Document object
	 * @param	array<string,mixed>		$named		Values keyed by parameter name
	 * @return	int									updateline return
	 */
	private function updatelineByName($object, array $named): int
	{
		$ref = new ReflectionMethod(get_class($object), 'updateline');
		$args = array();
		foreach ($ref->getParameters() as $p) {
			$name = $p->getName();
			if (array_key_exists($name, $named)) {
				$args[] = $named[$name];
			} elseif ($p->isDefaultValueAvailable()) {
				$args[] = $p->getDefaultValue();
			} else {
				$args[] = null;
			}
		}
		return (int) $ref->invokeArgs($object, $args);
	}

	/**
	 * The freeze flag on an invoice line must survive a document rate change AND a plain line edit, otherwise
	 * the fixed currency price is silently recomputed afterwards. This guards the FactureLigne::update() path,
	 * which used to normalize a null flag to 0 and clear the freeze. (issue #32379)
	 *
	 * @return void
	 */
	public function testInvoiceFreezeSurvivesRateChangeAndEdit()
	{
		global $db, $user;

		$socid = $this->createSoc();
		$pid = $this->createProduct();

		$invoice = new Facture($db);
		$invoice->socid = $socid;
		$invoice->date = dol_now();
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->multicurrency_code = 'USD';
		$fid = $invoice->create($user);
		$this->assertGreaterThan(0, $fid, 'Invoice creation failed: '.$invoice->error);

		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = 1.1;

		$res = $this->addlineByName($invoice, array(
			'desc' => 'sourced line', 'pu_ht' => 0, 'qty' => 2, 'txtva' => 20,
			'fk_product' => $pid, 'price_base_type' => 'HT', 'pu_ht_devise' => 100.0,
			'multicurrency_subprice_source' => 1,
		));
		$this->assertGreaterThan(0, $res, 'Invoice addline failed: '.$invoice->error);

		// A rate change in mode 2 (recompute currency) must keep the fixed currency price and the flag.
		$reloaded = new Facture($db);
		$reloaded->fetch($fid);
		$reloaded->fetch_lines();
		$reloaded->multicurrency_tx = 1.1;
		$reloaded->setMulticurrencyRate(1.5, 2);

		$afterrate = new Facture($db);
		$afterrate->fetch($fid);
		$afterrate->fetch_lines();
		$this->assertEquals(100.0, (float) $afterrate->lines[0]->multicurrency_subprice, 'Fixed currency price must survive the rate change');
		$this->assertEquals(1, (int) $afterrate->lines[0]->multicurrency_subprice_source, 'Freeze flag must survive the rate change');

		// A plain line edit (quantity change, no flag passed) must not clear the freeze flag.
		$line = $afterrate->lines[0];
		$this->updatelineByName($afterrate, array(
			'rowid' => $line->id, 'desc' => 'sourced line', 'pu' => $line->subprice, 'qty' => 5,
			'remise_percent' => $line->remise_percent, 'txtva' => $line->tva_tx,
			'price_base_type' => 'HT', 'type' => $line->product_type,
			'pu_ht_devise' => $line->multicurrency_subprice,
		));

		$afteredit = new Facture($db);
		$afteredit->fetch($fid);
		$afteredit->fetch_lines();
		$this->assertEquals(1, (int) $afteredit->lines[0]->multicurrency_subprice_source, 'Freeze flag must survive a plain line edit');
		$this->assertEquals(100.0, (float) $afteredit->lines[0]->multicurrency_subprice, 'Fixed currency price must survive a plain line edit');
	}

	/**
	 * A sourced currency price flag set at addline time is persisted on order lines.
	 *
	 * @return void
	 */
	public function testOrderLineFlagPersisted()
	{
		global $db, $user;

		$socid = $this->createSoc();
		$pid = $this->createProduct();

		$order = new Commande($db);
		$order->socid = $socid;
		$order->date = dol_now();
		$order->multicurrency_code = 'USD';
		$oid = $order->create($user);
		$this->assertGreaterThan(0, $oid, 'Order creation failed: '.$order->error);

		$order->multicurrency_code = 'USD';
		$order->multicurrency_tx = 1.1;

		$res = $this->addlineByName($order, array(
			'desc' => 'sourced line', 'pu_ht' => 0, 'qty' => 2, 'txtva' => 20,
			'fk_product' => $pid, 'price_base_type' => 'HT', 'pu_ht_devise' => 100.0,
			'multicurrency_subprice_source' => 1,
		));
		$this->assertGreaterThan(0, $res, 'Order addline failed: '.$order->error);

		$reloaded = new Commande($db);
		$reloaded->fetch($oid);
		$reloaded->fetch_lines();
		$this->assertNotEmpty($reloaded->lines, 'No order lines');
		$this->assertEquals(1, (int) $reloaded->lines[0]->multicurrency_subprice_source, 'Order line flag must persist');
		$this->assertEquals(100.0, (float) $reloaded->lines[0]->multicurrency_subprice, 'Order line currency price must be 100');
	}

	/**
	 * A sourced currency price flag set at addline time is persisted on invoice lines.
	 *
	 * @return void
	 */
	public function testInvoiceLineFlagPersisted()
	{
		global $db, $user;

		$socid = $this->createSoc();
		$pid = $this->createProduct();

		$invoice = new Facture($db);
		$invoice->socid = $socid;
		$invoice->date = dol_now();
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->multicurrency_code = 'USD';
		$fid = $invoice->create($user);
		$this->assertGreaterThan(0, $fid, 'Invoice creation failed: '.$invoice->error);

		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = 1.1;

		$res = $this->addlineByName($invoice, array(
			'desc' => 'sourced line', 'pu_ht' => 0, 'qty' => 2, 'txtva' => 20,
			'fk_product' => $pid, 'price_base_type' => 'HT', 'pu_ht_devise' => 100.0,
			'multicurrency_subprice_source' => 1,
		));
		$this->assertGreaterThan(0, $res, 'Invoice addline failed: '.$invoice->error);

		$reloaded = new Facture($db);
		$reloaded->fetch($fid);
		$reloaded->fetch_lines();
		$this->assertNotEmpty($reloaded->lines, 'No invoice lines');
		$this->assertEquals(1, (int) $reloaded->lines[0]->multicurrency_subprice_source, 'Invoice line flag must persist');
		$this->assertEquals(100.0, (float) $reloaded->lines[0]->multicurrency_subprice, 'Invoice line currency price must be 100');
	}
}
