<?php
/* Copyright (C) 2026 Corentin Carayon <corentin.carayon@atm-consulting.fr>
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
 *      \file       test/phpunit/ProductLotBarcodeTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for barcode support on lots/serial numbers
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/productlot.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/barcode/mod_barcode_productlot_standard.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/barcode.lib.php';
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
class ProductLotBarcodeTest extends CommonClassTest
{
	const MASK = '05{0000000000}?';

	/**
	 * @var array<string,mixed> Constant values to restore in tearDown
	 */
	private $savedconstants = array();

	/**
	 * @var bool Whether the barcode module had to be faked as enabled
	 */
	private $savedmodules = false;

	/**
	 * @var int Product used as parent of every lot created by the tests
	 */
	private $productid = 0;

	/**
	 * Set the barcode setup up for a test
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf, $user, $langs, $db;

		parent::setUp();

		// CommonClassTest saves the globals because PHPUnit may replace them between tests
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		foreach (array('BARCODE_USE_ON_PRODUCTLOT', 'BARCODE_PRODUCTLOT_ADDON_NUM', 'BARCODE_STANDARD_PRODUCTLOT_MASK', 'PRODUCTLOT_DEFAULT_BARCODE_TYPE') as $key) {
			$this->savedconstants[$key] = getDolGlobalString($key);
		}
		$this->savedmodules = empty($conf->modules['barcode']);
		$conf->modules['barcode'] = 'barcode';

		$product = new Product($db);
		$product->ref = 'PHPUNITLOTBC'.uniqid();
		$product->label = 'Product for lot barcode unit tests';
		$product->type = Product::TYPE_PRODUCT;
		$product->status_batch = 1;
		$this->productid = $product->create($user);
		$this->assertGreaterThan(0, $this->productid, 'Failed to create the parent product: '.$product->error.' '.implode(',', $product->errors));
	}

	/**
	 * Restore the configuration so tests do not leak into each other
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		foreach ($this->savedconstants as $key => $value) {
			if ($value === '') {
				unset($conf->global->$key);
			} else {
				$conf->global->$key = $value;
			}
		}
		$this->savedconstants = array();

		if ($this->savedmodules) {
			unset($conf->modules['barcode']);
		}

		parent::tearDown();
	}

	/**
	 * Turn the automatic generation on with the standard numbering module
	 *
	 * @param	string	$mask	Mask to configure, empty to leave the mask undefined
	 * @return	int				Rowid of the barcode type used
	 */
	private function enableGeneration($mask = self::MASK)
	{
		global $conf;

		$conf->global->BARCODE_USE_ON_PRODUCTLOT = 1;
		$conf->global->BARCODE_PRODUCTLOT_ADDON_NUM = 'mod_barcode_productlot_standard';
		if ($mask === '') {
			unset($conf->global->BARCODE_STANDARD_PRODUCTLOT_MASK);
		} else {
			$conf->global->BARCODE_STANDARD_PRODUCTLOT_MASK = $mask;
		}

		$typeid = $this->getBarcodeTypeId();
		$conf->global->PRODUCTLOT_DEFAULT_BARCODE_TYPE = $typeid;

		return $typeid;
	}

	/**
	 * Return the rowid of the EAN13 barcode type, or of any type as a fallback
	 *
	 * @param	string	$label	Label to look for into the dictionary
	 * @return	int				Rowid, 0 if the dictionary is empty
	 */
	private function getBarcodeTypeId($label = 'EAN13')
	{
		global $conf, $db;

		$sql = "SELECT rowid FROM ".$db->prefix()."c_barcode_type";
		$sql .= " WHERE libelle = '".$db->escape($label)."'";
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $db->query($sql);
		$rowid = 0;
		if ($resql && $db->num_rows($resql) > 0) {
			$obj = $db->fetch_object($resql);
			$rowid = (int) $obj->rowid;
		}
		if ($resql) {
			$db->free($resql);
		}

		return $rowid;
	}

	/**
	 * Build a valid EAN13 nobody else holds. Hardcoding one is fragile: the suite runs against the
	 * real database, where the unique index makes any collision fail the test for the wrong reason.
	 *
	 * @return	string	13 digit EAN13
	 */
	private function uniqueBarcode()
	{
		$base = '05'.str_pad((string) mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);

		return $base.barcode_gen_ean_sum($base);
	}

	/**
	 * Create a lot on the test product
	 *
	 * @param	string	$batch		Batch value
	 * @param	?string	$barcode	Barcode to force, null to let the generation decide
	 * @return	Productlot			Created object, its id holds the create() return
	 */
	private function createLot($batch, $barcode = null)
	{
		global $db, $user;

		$lot = new Productlot($db);
		$lot->fk_product = $this->productid;
		$lot->batch = $batch;
		if ($barcode !== null) {
			$lot->barcode = $barcode;
		}
		$lot->create($user);

		return $lot;
	}

	/**
	 * A lot created while the feature is off keeps an empty barcode and raises no warning
	 *
	 * @return void
	 */
	public function testGenerationIsSkippedWhenOptionIsOff()
	{
		global $conf;

		// Never rely on the ambient setup: the instance may have the feature turned on
		unset($conf->global->BARCODE_USE_ON_PRODUCTLOT);

		$lot = $this->createLot('PHPUNIT-OFF-1');

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->barcode);
		$this->assertEmpty($lot->warnings, 'No warning must be raised when the user did not ask for a barcode');
	}

	/**
	 * A missing mask degrades to a lot without barcode instead of aborting the creation
	 *
	 * @return void
	 */
	public function testMissingMaskDegradesWithoutBlocking()
	{
		$this->enableGeneration('');

		$lot = $this->createLot('PHPUNIT-NOMASK-1');

		$this->assertGreaterThan(0, $lot->id, 'The lot must be created even without a barcode');
		$this->assertEmpty($lot->barcode);
		$this->assertNotEmpty($lot->warnings, 'A degraded generation must feed warnings');
	}

	/**
	 * A missing barcode type degrades the same way
	 *
	 * @return void
	 */
	public function testMissingBarcodeTypeDegradesWithoutBlocking()
	{
		global $conf;

		$this->enableGeneration();
		unset($conf->global->PRODUCTLOT_DEFAULT_BARCODE_TYPE);

		$lot = $this->createLot('PHPUNIT-NOTYPE-1');

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->barcode);
		$this->assertNotEmpty($lot->warnings);
	}

	/**
	 * An unknown numbering module degrades instead of raising a fatal error
	 *
	 * @return void
	 */
	public function testUnknownNumberingModuleDegradesWithoutBlocking()
	{
		global $conf;

		$this->enableGeneration();
		$conf->global->BARCODE_PRODUCTLOT_ADDON_NUM = 'mod_barcode_productlot_doesnotexist';

		$lot = $this->createLot('PHPUNIT-NOMODULE-1');

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->barcode);
		$this->assertNotEmpty($lot->warnings);
	}

	/**
	 * The generated value follows the configured mask
	 *
	 * @return void
	 */
	public function testGeneratedValueFollowsMask()
	{
		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$lot = $this->createLot('PHPUNIT-MASK-1');

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->warnings, 'Generation must succeed: '.implode(',', $lot->warnings));
		$this->assertSame(13, strlen($lot->barcode), 'Mask 05{0000000000}? yields 13 characters');
		$this->assertSame('05', substr($lot->barcode, 0, 2), 'Prefix must stay disjoint from the product one');
		$this->assertMatchesRegularExpression('/^[0-9]{13}$/', $lot->barcode);
	}

	/**
	 * The trailing joker of the mask is replaced by a valid EAN13 check digit
	 *
	 * @return void
	 */
	public function testGeneratedValueCarriesValidEan13Key()
	{
		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$lot = $this->createLot('PHPUNIT-EAN-1');

		$this->assertSame(13, strlen($lot->barcode));
		$this->assertSame((string) barcode_gen_ean_sum(substr($lot->barcode, 0, 12)), substr($lot->barcode, -1), 'Last digit must be the EAN13 check digit');
	}

	/**
	 * Two lots created in a row get strictly increasing counters
	 *
	 * @return void
	 */
	public function testCountersAreStrictlyIncreasing()
	{
		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$first = $this->createLot('PHPUNIT-SEQ-1');
		$second = $this->createLot('PHPUNIT-SEQ-2');

		$this->assertNotEmpty($first->barcode);
		$this->assertNotEmpty($second->barcode);
		$this->assertGreaterThan((int) substr($first->barcode, 2, 10), (int) substr($second->barcode, 2, 10));
	}

	/**
	 * An exhausted counter must not be written as a barcode.
	 * get_next_value() substitutes ErrorMaxNumberReachForThisMask inside the value instead of
	 * returning it alone, so a check anchored at the start of the string would let it through.
	 *
	 * @return void
	 */
	public function testExhaustedCounterDegradesWithoutBlocking()
	{
		$typeid = $this->enableGeneration('05{000}?');
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$last = $this->createLot('PHPUNIT-FULL-1', '05999?');
		$this->assertSame('05999?', $last->barcode);

		$overflow = $this->createLot('PHPUNIT-FULL-2');

		$this->assertGreaterThan(0, $overflow->id, 'The lot must still be created');
		$this->assertEmpty($overflow->barcode, 'An error sentinel must never be stored as a barcode');
		$this->assertNotEmpty($overflow->warnings);
	}

	/**
	 * A failed generation must not leave a barcode type behind on a lot that has no barcode
	 *
	 * @return void
	 */
	public function testDegradedGenerationDropsTheGuessedType()
	{
		global $db;

		$this->enableGeneration('');

		$lot = $this->createLot('PHPUNIT-NOTYPELEFT-1');

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->barcode);
		$this->assertEmpty($lot->fk_barcode_type, 'A guessed type must be given up when the generation fails');

		$reloaded = new Productlot($db);
		$this->assertEquals(1, $reloaded->fetch($lot->id));
		$this->assertEmpty($reloaded->fk_barcode_type);
	}

	/**
	 * A type set by the caller survives a failed generation
	 *
	 * @return void
	 */
	public function testDegradedGenerationKeepsAnExplicitType()
	{
		global $db, $user;

		$typeid = $this->getBarcodeTypeId();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}
		$this->enableGeneration('');

		$lot = new Productlot($db);
		$lot->fk_product = $this->productid;
		$lot->batch = 'PHPUNIT-EXPLICITTYPE-1';
		$lot->fk_barcode_type = $typeid;
		$lot->create($user);

		$this->assertGreaterThan(0, $lot->id);
		$this->assertEmpty($lot->barcode);
		$this->assertEquals($typeid, (int) $lot->fk_barcode_type, 'An explicit type must never be dropped');
	}

	/**
	 * checkBarcode() hands the normalised value back to the caller
	 *
	 * @return void
	 */
	public function testCheckBarcodeNormalisesTheValue()
	{
		$this->enableGeneration();

		$lot = $this->createLot('PHPUNIT-NORM-1', $this->uniqueBarcode());
		$this->assertGreaterThan(0, $lot->id);

		$free = $this->uniqueBarcode();
		$value = '  '.$free.'  ';
		$this->assertEquals(0, $lot->checkBarcode($value, 'EAN13'));
		$this->assertSame($free, $value, 'The caller must receive the trimmed value it has to store');
	}

	/**
	 * showInputField() must not fatal on the two new fields.
	 * productlot_list.php calls it for every 'integer:' and 'sellist:' field of the search row,
	 * and CommonObject::showInputField() runs abs() on 'visible', which throws a TypeError on PHP 8
	 * when 'visible' holds an expression instead of an int.
	 *
	 * @return void
	 */
	public function testShowInputFieldOnBarcodeFieldsDoesNotFatal()
	{
		global $db;

		$this->enableGeneration();

		$lot = new Productlot($db);

		foreach (array('barcode', 'fk_barcode_type') as $key) {
			$this->assertIsInt($lot->fields[$key]['visible'], $key.": 'visible' must stay an int, showInputField() calls abs() on it without evaluating");
			$out = $lot->showInputField($lot->fields[$key], $key, '', '', '', 'search_', 'maxwidth250', 1);
			$this->assertIsString($out);
		}
	}

	/**
	 * A mask whose length does not match the barcode type leaves the key placeholder unresolved.
	 * Storing '05000000001?' would yield an unscannable code, so it must degrade instead.
	 *
	 * @return void
	 */
	public function testUnresolvedKeyPlaceholderDegradesWithoutBlocking()
	{
		$typeid = $this->enableGeneration('05{00000000}?');
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$lot = $this->createLot('PHPUNIT-JOKER-1');

		$this->assertGreaterThan(0, $lot->id, 'The lot must still be created');
		$this->assertEmpty($lot->barcode, 'An unresolved key placeholder must never be stored');
		$this->assertNotEmpty($lot->warnings);
	}

	/**
	 * A barcode provided by the caller is never overwritten by the generator
	 *
	 * @return void
	 */
	public function testProvidedBarcodeIsPreserved()
	{
		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$expected = $this->uniqueBarcode();
		$lot = $this->createLot('PHPUNIT-MANUAL-1', $expected);

		$this->assertGreaterThan(0, $lot->id);
		$this->assertSame($expected, $lot->barcode);
	}

	/**
	 * verif_dispo() sees a value already taken by another lot
	 *
	 * @return void
	 */
	public function testVerifDispoDetectsValueAlreadyUsed()
	{
		global $db;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$existing = $this->createLot('PHPUNIT-DISPO-1');
		$this->assertNotEmpty($existing->barcode);

		$other = new Productlot($db);
		$mod = new mod_barcode_productlot_standard();

		$this->assertNotEquals(0, $mod->verif_dispo($db, $existing->barcode, $other), 'A used value must not be available');
		$this->assertEquals(0, $mod->verif_dispo($db, '0599999999999', $other), 'An unused value must be available');
		$this->assertEquals(0, $mod->verif_dispo($db, $existing->barcode, $existing), 'A lot must not collide with itself');
	}

	/**
	 * verif() refuses any object that is not a lot
	 *
	 * @return void
	 */
	public function testVerifRejectsWrongClass()
	{
		global $db;

		$this->enableGeneration();

		$mod = new mod_barcode_productlot_standard();
		$product = new Product($db);
		$code = '0512345678905';

		$this->assertEquals(-7, $mod->verif($db, $code, $product, 0, 'EAN13'));
	}

	/**
	 * getNextValue() refuses any object that is not a lot
	 *
	 * @return void
	 */
	public function testGetNextValueRejectsWrongClass()
	{
		global $db;

		$this->enableGeneration();

		$mod = new mod_barcode_productlot_standard();
		$product = new Product($db);

		$this->assertEquals(-1, $mod->getNextValue($product, ''));
	}

	/**
	 * A barcode set on a lot survives an update that only completes a date.
	 * This is the regression test of the create/fetch/update triplet: MouvementStock
	 * completes eatby and sellby on existing lots through a fetch() then update() pair.
	 *
	 * @return void
	 */
	public function testBarcodeSurvivesUpdateAfterFetch()
	{
		global $db, $user;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$created = $this->createLot('PHPUNIT-SURVIVE-1');
		$this->assertNotEmpty($created->barcode);
		$expected = $created->barcode;

		$reloaded = new Productlot($db);
		$this->assertEquals(1, $reloaded->fetch($created->id));
		$this->assertSame($expected, $reloaded->barcode, 'fetch() must hydrate the barcode');

		$reloaded->eatby = dol_now();
		$this->assertGreaterThan(0, $reloaded->update($user));

		$after = new Productlot($db);
		$this->assertEquals(1, $after->fetch($created->id));
		$this->assertSame($expected, $after->barcode, 'update() must not wipe the barcode');
		$this->assertEquals($typeid, (int) $after->fk_barcode_type);
	}

	/**
	 * A barcode already held by another lot is rejected by the unique index, with a readable message
	 *
	 * @return void
	 */
	public function testUpdateRejectsDuplicateBarcode()
	{
		global $db, $user, $langs;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}
		if (!$this->hasUniqueBarcodeIndex()) {
			$this->markTestSkipped('uk_product_lot_barcode is not deployed on the test database');
		}

		$first = $this->createLot('PHPUNIT-DUP-1');
		$second = $this->createLot('PHPUNIT-DUP-2');
		$this->assertNotEmpty($first->barcode);
		$this->assertNotEmpty($second->barcode);

		$second->barcode = $first->barcode;
		$this->assertLessThan(0, $second->update($user), 'The unique index must reject the duplicate');

		$langs->load("errors");
		$this->assertContains($langs->trans("ErrorProductLotBarCodeAlreadyExists", $first->barcode), $second->errors);

		$unchanged = new Productlot($db);
		$this->assertEquals(1, $unchanged->fetch($second->id));
		$this->assertNotSame($first->barcode, $unchanged->barcode, 'The rejected update must have been rolled back');
	}

	/**
	 * Tell whether the unique index shipped with this feature is deployed
	 *
	 * @return bool
	 */
	private function hasUniqueBarcodeIndex()
	{
		global $db;

		$resql = $db->query("SHOW INDEX FROM ".$db->prefix()."product_lot WHERE Key_name = 'uk_product_lot_barcode'");
		if (!$resql) {
			return false;
		}
		$found = ($db->num_rows($resql) > 0);
		$db->free($resql);

		return $found;
	}

	/**
	 * A barcode without a type would defeat uk_product_lot_barcode: MySQL skips rows holding a NULL
	 * in a unique index, so the same value could be stored twice in the same entity.
	 *
	 * @return void
	 */
	public function testBarcodeWithoutTypeCannotBeDuplicated()
	{
		global $db, $user;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}
		if (!$this->hasUniqueBarcodeIndex()) {
			$this->markTestSkipped('uk_product_lot_barcode is not deployed on the test database');
		}

		$first = $this->createLot('PHPUNIT-NOTYPEDUP-1');
		$this->assertNotEmpty($first->barcode);

		// Same value, no type given by the caller
		$second = new Productlot($db);
		$second->fk_product = $this->productid;
		$second->batch = 'PHPUNIT-NOTYPEDUP-2';
		$second->barcode = $first->barcode;
		$this->assertLessThanOrEqual(0, $second->create($user), 'A duplicate must be refused even when no type is provided');
		$this->assertNotEmpty($second->errors);
	}

	/**
	 * The same barcode stays allowed on two different entities
	 *
	 * @return void
	 */
	public function testSameBarcodeIsAllowedOnAnotherEntity()
	{
		global $db, $user;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$first = $this->createLot('PHPUNIT-ENT-1');
		$this->assertNotEmpty($first->barcode);

		$other = new Productlot($db);
		$other->fk_product = $this->productid;
		$other->batch = 'PHPUNIT-ENT-2';
		$other->barcode = $first->barcode;
		$other->entity = 2;
		$this->assertGreaterThan(0, $other->create($user), 'Multi entity must keep its own barcode namespace');
		$this->assertEquals($typeid, (int) $other->fk_barcode_type, 'The type must be filled in so the unique index applies');
	}

	/**
	 * createFromClone() must not carry the barcode over, the unique index forbids it
	 *
	 * @return void
	 */
	public function testCloneDropsBarcode()
	{
		global $db, $user;

		$typeid = $this->enableGeneration();
		if (empty($typeid)) {
			$this->markTestSkipped('No barcode type labelled EAN13 into the dictionary');
		}

		$source = $this->createLot('PHPUNIT-CLONE-1');
		$this->assertNotEmpty($source->barcode);

		$cloner = new Productlot($db);
		$newid = $cloner->createFromClone($user, $source->id);
		if ($newid <= 0) {
			$this->markTestSkipped('createFromClone is blocked by uk_product_lot(fk_product, batch), which is out of scope here');
		}

		$clone = new Productlot($db);
		$this->assertEquals(1, $clone->fetch($newid));
		$this->assertNotSame($source->barcode, $clone->barcode, 'The clone must not reuse the source barcode');
	}
}
