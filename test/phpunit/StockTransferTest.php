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
 *      \file       test/phpunit/StockTransferTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/stocktransfer/class/stocktransfer.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
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
class StockTransferTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('stocktransfer')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modStockTransfer');
			self::assertEmpty($result['errors'], 'Failed to activate module stocktransfer: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('stocktransfer'), 'module stocktransfer must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testStockTransferCreate
	 *
	 * @return int
	 */
	public function testStockTransferCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$source = new Entrepot($db);
		$source->initAsSpecimen();
		$source->label = 'PHPUnit source warehouse';
		$sourceid = $source->create($user);
		$this->assertGreaterThan(0, $sourceid, $source->errorsToString());

		$destination = new Entrepot($db);
		$destination->initAsSpecimen();
		$destination->label = 'PHPUnit destination warehouse';
		$destinationid = $destination->create($user);
		$this->assertGreaterThan(0, $destinationid, $destination->errorsToString());

		$localobject = new StockTransfer($db);
		$localobject->initAsSpecimen();
		// initAsSpecimen() sets a fixed ref ('ABCD1234'), but a real StockTransfer is created with the
		// '(PROV)' placeholder so createCommon() assigns it a real provisional ref ("(PROVid)") - use
		// that here so the ref-renumbering on validate() tested below reflects real usage.
		$localobject->ref = '(PROV)';
		$localobject->label = 'PHPUnit stock transfer';
		$localobject->fk_warehouse_source = $sourceid;
		$localobject->fk_warehouse_destination = $destinationid;
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." source=".$sourceid." destination=".$destinationid."\n";
		return $result;
	}

	/**
	 * testStockTransferFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	StockTransfer
	 *
	 * @depends	testStockTransferCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new StockTransfer($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame('PHPUnit stock transfer', $localobject->label);
		$this->assertEquals(StockTransfer::STATUS_DRAFT, $localobject->status);
		$this->assertMatchesRegularExpression('/^\(?PROV/i', (string) $localobject->ref, 'A not yet validated stock transfer must have a provisional ref');

		return $localobject;
	}

	/**
	 * testStockTransferUpdate
	 *
	 * @param	StockTransfer	$localobject	Stock transfer
	 * @return	StockTransfer
	 *
	 * @depends	testStockTransferFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Updated label after update';
		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated label after update', $localobject->label);
		$this->assertSame('New note private after update', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testStockTransferValidate
	 *
	 * @param	StockTransfer	$localobject	Stock transfer
	 * @return	StockTransfer
	 *
	 * @depends	testStockTransferUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferValidate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$oldref = $localobject->ref;
		$result = $localobject->validate($user);

		$this->assertEquals(1, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result." ref=".$localobject->ref."\n";

		$this->assertEquals(StockTransfer::STATUS_VALIDATED, $localobject->status);
		$this->assertNotEquals($oldref, $localobject->ref, 'validate() must replace the provisional ref with a definitive one');
		$this->assertNotRegExp('/^\(?PROV/i', $localobject->ref);

		return $localobject;
	}

	/**
	 * testStockTransferCancel
	 *
	 * cancel() actually closes the transfer (there is no separate "canceled" status): validated -> closed.
	 *
	 * @param	StockTransfer	$localobject	Stock transfer
	 * @return	StockTransfer
	 *
	 * @depends	testStockTransferValidate
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->cancel($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(StockTransfer::STATUS_CLOSED, $localobject->status);

		return $localobject;
	}

	/**
	 * testStockTransferReopen
	 *
	 * @param	StockTransfer	$localobject	Stock transfer
	 * @return	int
	 *
	 * @depends	testStockTransferCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferReopen($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->reopen($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(StockTransfer::STATUS_VALIDATED, $localobject->status);

		return $localobject->id;
	}

	/**
	 * testStockTransferDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testStockTransferReopen
	 * The depends says test is run only if previous is ok
	 */
	public function testStockTransferDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new StockTransfer($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
