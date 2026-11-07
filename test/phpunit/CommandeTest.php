<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/CommandeTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';

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
class CommandeTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		if (!isModEnabled('order')) {
			print __METHOD__." module customer order must be enabled.\n";
			die(1);
		}

		print __METHOD__."\n";
	}


	/**
	 * testCommandeCreate
	 *
	 * @return  void
	 */
	public function testCommandeCreate()
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

		$localobject = new Commande($db);
		$param = array('tosell' => 1);
		$localobject->initAsSpecimen($param);
		// Persist a regular provisional order instead of keeping the specimen reference.
		$localobject->ref = '';
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testCommandeFetch
	 *
	 * @param   int $id     Id order
	 * @return  Commande
	 *
	 * @depends	testCommandeCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";

		// Specimen lines are built from real products picked at random (see Commande::initAsSpecimen), so the
		// exact line count is not stable (a kit/BOM product can expand into extra lines) - only check totals coherence.
		$this->assertNotEmpty($localobject->lines);
		$this->assertLineTotalsMatchHeader($localobject, 'after fetch');

		return $localobject;
	}

	/**
	 * testCommandeUpdate
	 *
	 * @param	Commande		$localobject	Commande
	 * @return	Commande
	 *
	 * @depends	testCommandeFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testCommandeAddLine
	 *
	 * @param	Commande	$localobject	Commande
	 * @return	array{0:Commande,1:int}		Commande and id of the line added
	 *
	 * @depends	testCommandeUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$lineid = $localobject->addline('PHPUnit addline test', 100, 2, 20);	// 2 x 100 HT at 20% VAT = 200 HT / 40 VAT / 240 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid."\n";
		$this->assertGreaterThan(0, $lineid, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount + 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 200, (float) $localobject->total_ht, 0.01, 'total_ht not updated after addline');
		$this->assertLineTotalsMatchHeader($localobject, 'after addline');

		return array($localobject, $lineid);
	}

	/**
	 * testCommandeUpdateLine
	 *
	 * @param	array{0:Commande,1:int}	$params	Commande and id of the line to update
	 * @return	array{0:Commande,1:int}			Commande and id of the line updated
	 *
	 * @depends	testCommandeAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeUpdateLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->updateline($lineid, 'PHPUnit addline test', 100, 3, 0, 20);	// qty 2 -> 3, so +100 HT / +20 VAT / +120 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 100, (float) $localobject->total_ht, 0.01, 'total_ht not updated after updateline');
		$this->assertLineTotalsMatchHeader($localobject, 'after updateline');

		return array($localobject, $lineid);
	}

	/**
	 * testCommandeDeleteLine
	 *
	 * @param	array{0:Commande,1:int}	$params	Commande and id of the line to delete
	 * @return	Commande
	 *
	 * @depends	testCommandeUpdateLine
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeDeleteLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->deleteLine($user, $lineid);

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		// Back to the original specimen lines, with the same totals
		$this->assertCount($beforelinecount - 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht - 300, (float) $localobject->total_ht, 0.01, 'total_ht not updated after deleteLine');
		$this->assertLineTotalsMatchHeader($localobject, 'after deleteLine');

		return $localobject;
	}

	/**
	 * testCommandeValid
	 *
	 * @param   Commande  $localobject    Order
	 * @return  Commande
	 *
	 * @depends	testCommandeDeleteLine
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$oldref = dol_sanitizeFileName($localobject->ref);
		$outputdir = getMultidirOutput($localobject);
		$dirsource = $outputdir.'/'.$oldref;
		$oldfile = $dirsource.'/'.$oldref.'.pdf';
		$dirnew = '';
		$newfile = '';

		try {
			$this->assertGreaterThanOrEqual(0, dol_mkdir($dirsource));
			$this->assertNotFalse(file_put_contents($oldfile, 'Provisional order document'));

			$result = $localobject->valid($user);

			print __METHOD__." id=".$localobject->id." result=".$result."\n";
			$this->assertLessThan($result, 0);

			$newref = dol_sanitizeFileName($localobject->ref);
			$dirnew = $outputdir.'/'.$newref;
			$newfile = $dirnew.'/'.$newref.'.pdf';
			$this->assertFileExists($newfile);
			$this->assertFileNotExistsCompat($dirnew.'/'.$oldref.'.pdf');
		} finally {
			if ($newfile) {
				@unlink($newfile);
			}
			@unlink($oldfile);
			if ($dirnew) {
				@rmdir($dirnew);
			}
			@rmdir($dirsource);
		}

		// Test everything is still the same as a freshly built specimen with the same mutation applied
		// (catches unwanted field changes introduced by update()/valid())
		$this->assertMatchesFreshSpecimen(
			$localobject,
			function ($specimen) {
				$specimen->note_private = 'New note private after update';
			},
			array(
				'newref', 'oldcopy', 'oldref', 'id', 'lines', 'line', 'client', 'thirdparty', 'brouillon', 'specimen',
				'fk_user_author', 'user_author_id', 'user_creation_id', 'user_modification_id',
				'date', 'date_commande', 'date_creation', 'date_validation', 'date_lim_reglement', 'datem', 'date_modification',
				'ref', 'statut', 'status', 'socid', 'billed', 'fk_incoterms', 'actiontypecode', 'actionmsg2', 'actionmsg',
				'mode_reglement', 'cond_reglement', 'mode_reglement_code', 'cond_reglement_code', 'availability_code', 'demand_reason_code',
				'cond_reglement_doc', 'modelpdf',
				// Totals are ignored here: specimen lines reference random real products, and a kit/BOM product can
				// expand into extra lines with a different amount - total correctness is checked by assertLineTotalsMatchHeader() instead.
				'total_ht', 'total_tva', 'total_ttc', 'total_localtax1', 'total_localtax2',
				'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc', 'fk_multicurrency', 'multicurrency_code', 'multicurrency_tx',
				'trackid', 'user_creat', 'user_valid', 'note',
			),
			array('tosell' => 1)
		);

		return $localobject;
	}

	/**
	 * testCommandeCancel
	 *
	 * @param   Commande  $localobject    Order
	 * @return  Commande
	 *
	 * @depends testCommandeValid
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->cancel($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCommandeOther
	 *
	 * @param   Commande  $localobject    Order
	 * @return  int						Order id
	 *
	 * @depends testCommandeCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testCommandeDelete
	 *
	 * @param   int $id         Id of order
	 * @return  void
	 *
	 * @depends testCommandeOther
	 * The depends says test is run only if previous is ok
	 */
	public function testCommandeDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * testCommandeUpdatelinePackaging
	 *
	 * With PRODUCT_USE_CUSTOMER_PACKAGING, when a line is updated, the amounts must be computed
	 * on the quantity rounded to the packaging, not on the quantity typed.
	 *
	 * @return  void
	 */
	public function testCommandeUpdatelinePackaging()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$savconst = getDolGlobalString('PRODUCT_USE_CUSTOMER_PACKAGING');
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = 1;

		$product = new Product($db);
		$product->ref = 'PHPUNIT-PACKAGING-'.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand(100, 999);
		$product->label = 'Product sold by packaging of 6';
		$product->type = Product::TYPE_PRODUCT;
		$product->price = 10;
		$product->tva_tx = 20;
		$product->packaging = 6;
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, (string) $product->error);

		$localobject = new Commande($db);
		$localobject->socid = 1;
		$localobject->date = dol_now();
		$this->assertGreaterThan(0, $localobject->create($user), (string) $localobject->error);
		$localobject->fetch_thirdparty();

		$lineid = $localobject->addline('Line', 10, 6, 20, 0, 0, $productid);
		$this->assertGreaterThan(0, $lineid, (string) $localobject->error);
		$result = $localobject->updateline($lineid, 'Line', 10, 7, 0, 20);
		$this->assertGreaterThan(0, $result, (string) $localobject->error);

		$line = new OrderLine($db);
		$line->fetch($lineid);
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = $savconst;

		print __METHOD__." qty=".$line->qty." total_ht=".$line->total_ht."\n";
		$this->assertEquals(12, $line->qty, 'Quantity 7 rounded to the packaging of 6');
		$this->assertEquals(120, $line->total_ht, 'Total of the rounded quantity 12, not of 7');
	}

	/**
	 * Create a product with a packaging of quantities and a draft order, with PRODUCT_USE_CUSTOMER_PACKAGING enabled.
	 * The caller must restore PRODUCT_USE_CUSTOMER_PACKAGING with the returned value.
	 *
	 * @param	float	$packaging	Packaging of quantities of the product
	 * @return	array{0:Commande,1:int,2:string}	Draft order, id of product, previous value of PRODUCT_USE_CUSTOMER_PACKAGING
	 */
	private function initPackagingOrder($packaging)
	{
		global $conf, $user, $db;

		$savconst = getDolGlobalString('PRODUCT_USE_CUSTOMER_PACKAGING');
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = 1;

		$product = new Product($db);
		$product->ref = 'PHPUNIT-PACKAGING-'.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand(100, 999);
		$product->label = 'Product sold by packaging of '.$packaging;
		$product->type = Product::TYPE_PRODUCT;
		$product->price = 10;
		$product->tva_tx = 20;
		$product->packaging = $packaging;
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, (string) $product->error);

		$order = new Commande($db);
		$order->socid = 1;
		$order->date = dol_now();
		$this->assertGreaterThan(0, $order->create($user), (string) $order->error);
		$order->fetch_thirdparty();

		return array($order, $productid, $savconst);
	}

	/**
	 * testCommandeNegativeQtyPackaging
	 *
	 * With PRODUCT_USE_CUSTOMER_PACKAGING, the rounding of a negative quantity must keep the sign.
	 *
	 * @return  void
	 */
	public function testCommandeNegativeQtyPackaging()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $productid, $savconst) = $this->initPackagingOrder(6);

		$result = array();
		foreach (array(-4, -7, -12) as $qty) {
			$lineid = $localobject->addline('Line', 10, $qty, 20, 0, 0, $productid);
			$this->assertGreaterThan(0, $lineid, (string) $localobject->error);
			$line = new OrderLine($db);
			$line->fetch($lineid);
			$result['add '.$qty] = (float) $line->qty;
		}
		$localobject->updateline($lineid, 'Line', 10, -7, 0, 20);
		$line = new OrderLine($db);
		$line->fetch($lineid);
		$result['update -7'] = (float) $line->qty;
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = $savconst;

		print __METHOD__." result=".json_encode($result)."\n";
		$this->assertEquals(array('add -4' => -6, 'add -7' => -12, 'add -12' => -12, 'update -7' => -12), $result, 'Negative quantities rounded to the packaging of 6');
	}

	/**
	 * testCommandeDecimalPackaging
	 *
	 * With PRODUCT_USE_CUSTOMER_PACKAGING, a quantity multiple of a decimal packaging must not be rounded
	 * to the next multiple (fmod(1, 0.1) is 0.0999..., not 0).
	 *
	 * @return  void
	 */
	public function testCommandeDecimalPackaging()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $productid, $savconst) = $this->initPackagingOrder(0.1);

		$lineid = $localobject->addline('Line', 10, 0.1, 20, 0, 0, $productid);
		$this->assertGreaterThan(0, $lineid, (string) $localobject->error);
		$result = array();
		foreach (array(0.5, 1, 2.2, 0.25) as $qty) {
			$localobject->updateline($lineid, 'Line', 10, $qty, 0, 20);
			$line = new OrderLine($db);
			$line->fetch($lineid);
			$result['update '.$qty] = (float) $line->qty;
		}
		$conf->global->PRODUCT_USE_CUSTOMER_PACKAGING = $savconst;

		print __METHOD__." result=".json_encode($result)."\n";
		$this->assertEquals(array('update 0.5' => 0.5, 'update 1' => 1, 'update 2.2' => 2.2, 'update 0.25' => 0.3), $result, 'Quantities with a packaging of 0.1');
	}
}
