<?php
/* Copyright (C) 2026 ATM Consulting          <contact@atm-consulting.fr>
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
 *      \file       test/phpunit/VariantsTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/variants/class/ProductAttribute.class.php';
require_once dirname(__FILE__).'/../../htdocs/variants/class/ProductAttributeValue.class.php';
require_once dirname(__FILE__).'/../../htdocs/variants/class/ProductCombination.class.php';
require_once dirname(__FILE__).'/../../htdocs/variants/class/ProductCombination2ValuePair.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of the variants module
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class VariantsTest extends CommonClassTest
{
	/**
	 * @var array<string,string>	Enabled modules before the class enabled the variants one
	 */
	private static $savmodules;

	/**
	 * @var int						Value of PRODUIT_MULTIPRICES before the class changed it
	 */
	private static $savmultiprices;

	/**
	 * @var ?int					Value of $conf->variants->enabled before the class changed it
	 */
	private static $savvariantsenabled;

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;

		// $conf is shared by every test suite of the run, so anything changed here must be
		// restored, otherwise the following suites run with the variants module enabled and
		// with multiprices on.
		self::$savmodules = $conf->modules;
		self::$savmultiprices = getDolGlobalInt('PRODUIT_MULTIPRICES');
		self::$savvariantsenabled = isset($conf->variants->enabled) ? $conf->variants->enabled : null;

		if (!isModEnabled('variants')) {
			$conf->modules['variants'] = 'variants';
		}
		// Import::load_arrays() and Export::load_arrays() read $conf-><module>->enabled, not
		// $conf->modules that isModEnabled() reads, so both have to be set.
		if (empty($conf->variants->enabled)) {
			if (!isset($conf->variants)) {
				$conf->variants = new stdClass();
			}
			$conf->variants->enabled = 1;
		}

		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		$conf->global->PRODUIT_MULTIPRICES = self::$savmultiprices;

		parent::tearDown();
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf;

		$conf->modules = self::$savmodules;
		$conf->global->PRODUIT_MULTIPRICES = self::$savmultiprices;
		if (self::$savvariantsenabled === null) {
			unset($conf->variants->enabled);
		} else {
			$conf->variants->enabled = self::$savvariantsenabled;
		}

		parent::tearDownAfterClass();
	}

	/**
	 * Skip the running test when a table of the variants module is absent
	 *
	 * install/step2.php excludes every table file whose name holds a dash, so the tables of the
	 * variants module only exist once the module has been activated.
	 *
	 * @param	string	$table	Table name, without the database prefix
	 * @return	void
	 */
	private function skipIfNoTable($table)
	{
		global $conf;

		if (!count($this->savdb->DDLListTables($conf->db->name, MAIN_DB_PREFIX.$table))) {
			$this->markTestSkipped(MAIN_DB_PREFIX.$table.' does not exist: the variants module was never activated on this database');
		}
	}

	/**
	 * Create a product attribute and return it
	 *
	 * @param	string				$ref	Attribute ref
	 * @param	string				$label	Attribute label
	 * @return	ProductAttribute			Created attribute
	 */
	private function createAttribute($ref, $label)
	{
		global $user;

		$attribute = new ProductAttribute($this->savdb);
		$attribute->ref = $ref;
		$attribute->label = $label;
		$attribute->position = 10;
		$result = $attribute->create($user);
		$this->assertGreaterThan(0, $result, 'createAttribute '.$ref.' '.$attribute->errorsToString());

		return $attribute;
	}

	/**
	 * Create a product attribute value and return it
	 *
	 * @param	int						$attributeid	Parent attribute id
	 * @param	string					$ref			Value ref
	 * @param	string					$value			Value label
	 * @return	ProductAttributeValue					Created value
	 */
	private function createValue($attributeid, $ref, $value)
	{
		global $user;

		$attributevalue = new ProductAttributeValue($this->savdb);
		$attributevalue->fk_product_attribute = $attributeid;
		$attributevalue->ref = $ref;
		$attributevalue->value = $value;
		$attributevalue->position = 10;
		$result = $attributevalue->create($user);
		$this->assertGreaterThan(0, $result, 'createValue '.$ref.' '.$attributevalue->errorsToString());

		return $attributevalue;
	}

	/**
	 * Create a product and return it
	 *
	 * @param	string		$ref		Product ref
	 * @param	string		$label		Product label
	 * @param	float		$price		Selling price
	 * @param	float		$weight			Weight
	 * @param	string		$description	Description
	 * @return	Product						Created product
	 */
	private function createProduct($ref, $label, $price = 100, $weight = 2, $description = null)
	{
		global $user;

		$product = new Product($this->savdb);
		$product->ref = $ref;
		$product->label = $label;
		$product->description = ($description === null ? 'Description of '.$ref : $description);
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$product->status_buy = 1;
		$product->price_base_type = 'HT';
		$product->price = $price;
		$product->tva_tx = 20;
		$product->weight = $weight;
		$result = $product->create($user);
		$this->assertGreaterThan(0, $result, 'createProduct '.$ref.' '.$product->errorsToString());

		return $product;
	}

	/**
	 * Create a parent product and return it
	 *
	 * @param	string		$ref	Product ref
	 * @return	Product				Created product
	 */
	private function createParentProduct($ref)
	{
		return $this->createProduct($ref, 'Parent '.$ref);
	}

	/**
	 * Deleting an attribute value must remove its extrafields, not insert them again
	 *
	 * @return void
	 */
	public function testAttributeValueDeleteRemovesExtrafields()
	{
		global $user;

		$this->skipIfNoTable('product_attribute_value_extrafields');

		$attribute = $this->createAttribute('PHPUNITCOL', 'Color');
		$attributevalue = $this->createValue($attribute->id, 'PHPUNITBLUE', 'Blue');
		$valueid = $attributevalue->id;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_value_extrafields (fk_object) VALUES (".((int) $valueid).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert extrafields row');

		$result = $attributevalue->delete($user);
		$this->assertGreaterThan(0, $result, 'delete value '.$attributevalue->errorsToString());

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."product_attribute_value_extrafields";
		$sql .= " WHERE fk_object = ".((int) $valueid);
		$resql = $this->savdb->query($sql);
		$obj = $this->savdb->fetch_object($resql);
		$this->savdb->free($resql);

		$this->assertEquals(0, (int) $obj->nb, 'no orphan extrafields row after value delete');
	}

	/**
	 * Deleting an attribute must remove its extrafields
	 *
	 * @return void
	 */
	public function testAttributeDeleteRemovesExtrafields()
	{
		global $user;

		$this->skipIfNoTable('product_attribute_extrafields');

		$attribute = $this->createAttribute('PHPUNITCOL2', 'Color');
		$attributeid = $attribute->id;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_extrafields (fk_object) VALUES (".((int) $attributeid).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert extrafields row');

		$result = $attribute->delete($user);
		$this->assertGreaterThan(0, $result, 'delete attribute '.$attribute->errorsToString());

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."product_attribute_extrafields";
		$sql .= " WHERE fk_object = ".((int) $attributeid);
		$resql = $this->savdb->query($sql);
		$obj = $this->savdb->fetch_object($resql);
		$this->savdb->free($resql);

		$this->assertEquals(0, (int) $obj->nb, 'no orphan extrafields row after attribute delete');
	}

	/**
	 * fetch() must accept a ref, as the import engine calls it with fetch('', $ref)
	 *
	 * @return void
	 */
	public function testFetchByRef()
	{
		$attribute = $this->createAttribute('PHPUNITSIZ', 'Size');
		$value = $this->createValue($attribute->id, 'PHPUNITM', 'Medium');

		$tmpattribute = new ProductAttribute($this->savdb);
		$result = $tmpattribute->fetch('', 'PHPUNITSIZ');
		$this->assertGreaterThan(0, $result, 'fetch attribute by ref');
		$this->assertEquals($attribute->id, $tmpattribute->id, 'attribute id from ref');

		$tmpattribute = new ProductAttribute($this->savdb);
		$result = $tmpattribute->fetch('', 'PHPUNITNOTFOUND');
		$this->assertEquals(0, $result, 'fetch attribute by unknown ref returns 0');
		$this->assertNull($tmpattribute->id, 'id stays null when nothing is found');

		$tmpvalue = new ProductAttributeValue($this->savdb);
		$result = $tmpvalue->fetch('', 'PHPUNITM', $attribute->id);
		$this->assertGreaterThan(0, $result, 'fetch value by ref and attribute');
		$this->assertEquals($value->id, $tmpvalue->id, 'value id from ref');
		$this->assertEquals(10, (int) $tmpvalue->position, 'position is loaded');
	}

	/**
	 * The same value ref must be usable on two different attributes
	 *
	 * @return void
	 */
	public function testSameValueRefOnTwoAttributes()
	{
		$size = $this->createAttribute('PHPUNITSIZ2', 'Size');
		$material = $this->createAttribute('PHPUNITMAT', 'Material');
		$sizem = $this->createValue($size->id, 'PHPUNITM2', 'Medium');
		$materialm = $this->createValue($material->id, 'PHPUNITM2', 'Metal');

		$this->assertNotEquals($sizem->id, $materialm->id, 'two distinct values');

		$tmpvalue = new ProductAttributeValue($this->savdb);
		$tmpvalue->fetch('', 'PHPUNITM2', $material->id);
		$this->assertEquals($materialm->id, $tmpvalue->id, 'ref is only unique per attribute');
	}

	/**
	 * Replaying createProductCombination() on an existing variant must converge:
	 * the child description must not accumulate one attribute block per replay.
	 *
	 * @return void
	 */
	public function testCreateProductCombinationIsIdempotent()
	{
		global $user;

		$parent = $this->createParentProduct('PHPUNITSHIRT');
		$color = $this->createAttribute('PHPUNITC3', 'Color');
		$blue = $this->createValue($color->id, 'PHPUNITBLUE3', 'Blue');
		$this->createProduct('PHPUNITSHIRT_BLUE', 'Parent PHPUNITSHIRT', 0, 0);

		$features = array($color->id => $blue->id);

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, $features, array(), array(1 => false), array(1 => 5), 1.0, 'PHPUNITSHIRT_BLUE');
		$this->assertGreaterThan(0, $res, 'first call '.$combination->error.' '.implode(', ', (array) $combination->errors));

		$firstchild = new Product($this->savdb);
		$firstchild->fetch(0, 'PHPUNITSHIRT_BLUE');
		$firstdescription = $firstchild->description;
		$firstweight = $firstchild->weight;

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, $features, array(), array(1 => false), array(1 => 5), 1.0, 'PHPUNITSHIRT_BLUE');
		$this->assertGreaterThan(0, $res, 'second call '.$combination->error.' '.implode(', ', (array) $combination->errors));

		$secondchild = new Product($this->savdb);
		$secondchild->fetch(0, 'PHPUNITSHIRT_BLUE');

		$this->assertEquals($firstdescription, $secondchild->description, 'description must not accumulate on replay');
		$this->assertEquals($firstweight, $secondchild->weight, 'weight must not accumulate on replay');
		$this->assertEquals('PHPUNITSHIRT_BLUE', $secondchild->ref, 'ref must not be suffixed with a counter');

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " WHERE fk_product_parent = ".((int) $parent->id);
		$resql = $this->savdb->query($sql);
		$obj = $this->savdb->fetch_object($resql);
		$this->savdb->free($resql);
		$this->assertEquals(1, (int) $obj->nb, 'no duplicated combination on replay');
	}

	/**
	 * A forced ref equal to the parent ref must be refused, it would silently fall back
	 * to the automatic ref mode and clone the parent product.
	 *
	 * @return void
	 */
	public function testCreateProductCombinationRefusesParentRef()
	{
		global $user;

		$parent = $this->createParentProduct('PHPUNITSAMEREF');
		$color = $this->createAttribute('PHPUNITC6', 'Color');
		$blue = $this->createValue($color->id, 'PHPUNITBLUE6', 'Blue');

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, array($color->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, 'PHPUNITSAMEREF');

		$this->assertLessThan(0, $res, 'a variant ref equal to the parent ref must be refused');
	}

	/**
	 * variation_ref_ext must be stored once unescaped, not double escaped
	 *
	 * @return void
	 */
	public function testVariationRefExtIsNotDoubleEscaped()
	{
		global $user;

		$parent = $this->createParentProduct('PHPUNITREFEXT');
		$color = $this->createAttribute('PHPUNITC4', 'Color');
		$red = $this->createValue($color->id, 'PHPUNITRED4', 'Red');
		$this->createProduct('PHPUNITREFEXT_RED', 'Parent PHPUNITREFEXT', 0, 0);

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, array($color->id => $red->id), array(), array(1 => false), array(1 => 0), 0.0, 'PHPUNITREFEXT_RED', "O'Brien");
		$this->assertGreaterThan(0, $res, 'create '.$combination->error.' '.implode(', ', (array) $combination->errors));

		$tmpcombination = new ProductCombination($this->savdb);
		$tmpcombination->fetch('', 'PHPUNITREFEXT_RED');
		$this->assertEquals("O'Brien", $tmpcombination->variation_ref_ext, 'ref_ext stored once unescaped');
	}

	/**
	 * resolveAttributeValueId() must resolve a value ref within its own attribute only
	 *
	 * @return void
	 */
	public function testResolveAttributeValueId()
	{
		$size = $this->createAttribute('PHPUNITSIZ7', 'Size');
		$material = $this->createAttribute('PHPUNITMAT7', 'Material');
		$sizem = $this->createValue($size->id, 'PHPUNITM7', 'Medium');
		$materialm = $this->createValue($material->id, 'PHPUNITM7', 'Metal');

		// The line of the file is: child product ref, attribute ref, value ref
		$arrayrecord = array(
			0 => array('val' => 'PHPUNITSHIRT_X', 'type' => 1),
			1 => array('val' => 'PHPUNITMAT7', 'type' => 1),
			2 => array('val' => 'PHPUNITM7', 'type' => 1),
		);
		$arrayfield = array(
			'pac2v.fk_prod_combination' => 0,
			'pac2v.fk_prod_attr' => 1,
			'pac2v.fk_prod_attr_val' => 2,
		);

		$combination = new ProductCombination($this->savdb);
		$result = $combination->resolveAttributeValueId($arrayrecord, $arrayfield, 2);
		$this->assertEquals($materialm->id, $result, 'the value of the attribute of the same line is resolved');
		$this->assertNotEquals($sizem->id, $result, 'the homonym value of another attribute is not resolved');
		$this->assertEmpty($combination->error, 'no error on success');

		// The id: prefix of the engine forces the value to be read as an id
		$arrayrecord[2]['val'] = 'id:'.$materialm->id;
		$combination = new ProductCombination($this->savdb);
		$this->assertEquals($materialm->id, $combination->resolveAttributeValueId($arrayrecord, $arrayfield, 2), 'an id: prefixed value is read as an id');

		// An id belonging to another attribute than the one of the line must be refused
		$arrayrecord[2]['val'] = 'id:'.$sizem->id;
		$combination = new ProductCombination($this->savdb);
		$this->assertEquals(0, $combination->resolveAttributeValueId($arrayrecord, $arrayfield, 2), 'a value of another attribute is refused');
		$this->assertNotEmpty($combination->error, 'a value of another attribute fills the error');
		$arrayrecord[2]['val'] = 'PHPUNITM7';

		// An unknown attribute ref must fill the error, which the engine turns into a CLASSERROR
		$arrayrecord[1]['val'] = 'PHPUNITUNKNOWN';
		$combination = new ProductCombination($this->savdb);
		$result = $combination->resolveAttributeValueId($arrayrecord, $arrayfield, 2);
		$this->assertEquals(0, $result, 'unknown attribute ref resolves to 0');
		$this->assertNotEmpty($combination->error, 'unknown attribute ref fills the error');
	}

	/**
	 * The import trigger must ignore what is not its business, and refuse the fast_bulk
	 * trigger mode on the tables of the module.
	 *
	 * @return void
	 */
	public function testImportTriggerGuards()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$trigger = new InterfaceVariantsImport($this->savdb);

		// An object without the import context must be ignored
		$object = new stdClass();
		$object->id = 0;
		$object->context = array();
		$this->assertEquals(0, $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf), 'no import context');

		// fast_bulk on a variants table must be refused
		$object = new stdClass();
		$object->id = 0;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'bulk', 'importtriggermode' => 'fast_bulk');
		$object->bulk_stats = array('insert' => 1, 'update' => 0, 'tables' => array('product_attribute_combination' => array('insert' => 1, 'update' => 0)));
		$this->assertLessThan(0, $trigger->runTrigger('IMPORT_BULK_DONE', $object, $user, $langs, $conf), 'fast_bulk must be refused');
		$this->assertNotEmpty($trigger->errors, 'fast_bulk must fill errors');

		// fast_bulk on another table must be ignored
		$trigger = new InterfaceVariantsImport($this->savdb);
		$object->bulk_stats = array('insert' => 1, 'update' => 0, 'tables' => array('societe' => array('insert' => 1, 'update' => 0)));
		$this->assertEquals(0, $trigger->runTrigger('IMPORT_BULK_DONE', $object, $user, $langs, $conf), 'other tables are not our business');
	}

	/**
	 * The import trigger must reconcile an imported combination the way the screen does,
	 * and must refuse a value set already carried by another variant of the same parent.
	 *
	 * @return void
	 */
	public function testImportTriggerReconciliation()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$parent = $this->createParentProduct('PHPUNITTRIG');
		$color = $this->createAttribute('PHPUNITC8', 'Color');
		$blue = $this->createValue($color->id, 'PHPUNITBLUE8', 'Blue');
		// No description on the child: this is what an import writes, and the description of
		// the variant has to be composed from the parent and the features.
		$child = $this->createProduct('PHPUNITTRIG_BLUE', 'Child PHPUNITTRIG', 0, 0, '');

		// Simulate what the import engine writes with raw SQL for the combinations dataset
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 5, 0, 1, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination');
		$combinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $combinationid;
		$object->rowid = $combinationid;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf);
		$this->assertGreaterThan(0, $result, 'combination reconciliation '.$trigger->error.' '.implode(', ', (array) $trigger->errors));

		// The child product must now carry the price of the parent plus the price impact
		$tmpchild = new Product($this->savdb);
		$tmpchild->fetch($child->id);
		$this->assertEquals(105, (float) $tmpchild->price, 'price of the child is the price of the parent plus the impact');
		$this->assertEquals(3, (float) $tmpchild->weight, 'weight of the child is the weight of the parent plus the impact');

		// Simulate the features dataset
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
		$sql .= " VALUES (".((int) $combinationid).", ".((int) $color->id).", ".((int) $blue->id).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination2val');
		$pairid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination2val');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $pairid;
		$object->rowid = $pairid;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION2VAL_CREATE', $object, $user, $langs, $conf);
		$this->assertGreaterThan(0, $result, 'features reconciliation '.$trigger->error.' '.implode(', ', (array) $trigger->errors));

		$tmpchild = new Product($this->savdb);
		$tmpchild->fetch($child->id);
		$this->assertStringContainsString('Blue', $tmpchild->description, 'the description carries the feature');
		$this->assertStringContainsString('Color', $tmpchild->description, 'the description carries the attribute');
		$this->assertStringContainsString((string) $parent->description, $tmpchild->description, 'the description carries the description of the parent');

		// Two combinations of the same parent must not point to the same child product
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert duplicated combination');
		$othercombinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $othercombinationid;
		$object->rowid = $othercombinationid;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf);
		$this->assertLessThan(0, $result, 'a child product carried by two combinations must be refused');
		$this->assertNotEmpty($trigger->errors, 'a child product carried by two combinations must fill errors');
	}

	/**
	 * ProductCombination2ValuePair::fetch() is required by fetchObjectByElement(), which the
	 * import engine calls on the element of the attribute/value links.
	 *
	 * @return void
	 */
	public function testFetchObjectByElementOnValuePair()
	{
		global $conf;

		$parent = $this->createParentProduct('PHPUNITPAIR');
		$child = $this->createProduct('PHPUNITPAIR_RED', 'Child PHPUNITPAIR', 0, 0);
		$color = $this->createAttribute('PHPUNITC10', 'Color');
		$red = $this->createValue($color->id, 'PHPUNITRED10', 'Red');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination');
		$combinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
		$sql .= " VALUES (".((int) $combinationid).", ".((int) $color->id).", ".((int) $red->id).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination2val');
		$pairid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination2val');

		$object = fetchObjectByElement($pairid, 'product_attribute_combination2val');
		$this->assertInstanceOf('ProductCombination2ValuePair', $object, 'the value pair is resolved and fetched');
		$this->assertEquals($combinationid, $object->fk_prod_combination, 'the fetched pair carries its combination');
		$this->assertEquals($red->id, $object->fk_prod_attr_val, 'the fetched pair carries its value');
	}

	/**
	 * A child product whose description was customised by the user must not be overwritten
	 *
	 * @return void
	 */
	public function testCustomisedChildDescriptionIsPreserved()
	{
		global $user;

		$parent = $this->createParentProduct('PHPUNITKEEP');
		$color = $this->createAttribute('PHPUNITC9', 'Color');
		$blue = $this->createValue($color->id, 'PHPUNITBLUE9', 'Blue');
		$this->createProduct('PHPUNITKEEP_BLUE', 'Child PHPUNITKEEP', 0, 0, 'A description written by the user');

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, array($color->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, 'PHPUNITKEEP_BLUE');
		$this->assertGreaterThan(0, $res, 'create '.$combination->error.' '.implode(', ', (array) $combination->errors));

		$tmpchild = new Product($this->savdb);
		$tmpchild->fetch(0, 'PHPUNITKEEP_BLUE');
		$this->assertEquals('A description written by the user', $tmpchild->description, 'a customised description is preserved');
	}

	/**
	 * An imposed combination must belong to the parent product passed to the method, and must
	 * receive the value pairs it does not carry yet.
	 *
	 * @return void
	 */
	public function testForcedCombinationIsCheckedAgainstItsParent()
	{
		global $user, $conf;

		$parenta = $this->createParentProduct('PHPUNITFORCEDA');
		$parentb = $this->createParentProduct('PHPUNITFORCEDB');
		$childa = $this->createProduct('PHPUNITFORCEDA_C', 'Child A', 0, 0);
		$childb = $this->createProduct('PHPUNITFORCEDB_C', 'Child B', 0, 0);
		$colour = $this->createAttribute('PHPUNITC13', 'Colour');
		$blue = $this->createValue($colour->id, 'PHPUNITBLUE13', 'Blue');

		// A combination of parent B, carrying no value pair yet
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parentb->id).", ".((int) $childb->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination of parent B');
		$combinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$combinationofb = new ProductCombination($this->savdb);
		$this->assertGreaterThan(0, $combinationofb->fetch($combinationid), 'fetch combination of parent B');

		// Imposing it while asking for parent A must be refused, not silently reparented
		$tmp = new ProductCombination($this->savdb);
		$res = $tmp->createProductCombination($user, $parenta, array($colour->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, $childa->ref, '', false, $combinationofb);
		$this->assertLessThan(0, $res, 'a combination of another parent must be refused');
		$this->assertNotEmpty($tmp->error, 'the refusal fills the error');

		$tmpcombination = new ProductCombination($this->savdb);
		$tmpcombination->fetch($combinationid);
		$this->assertEquals($parentb->id, $tmpcombination->fk_product_parent, 'the combination keeps its parent');
		$this->assertEquals($childb->id, $tmpcombination->fk_product_child, 'the combination keeps its child product');

		// Imposing it for its own parent must work and create the missing value pair
		$tmp = new ProductCombination($this->savdb);
		$res = $tmp->createProductCombination($user, $parentb, array($colour->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, $childb->ref, '', false, $combinationofb);
		$this->assertGreaterThan(0, $res, 'imposing a combination of the right parent '.$tmp->error.' '.implode(', ', (array) $tmp->errors));

		$pairs = (new ProductCombination2ValuePair($this->savdb))->fetchByFkCombination($combinationid);
		$this->assertCount(1, $pairs, 'the missing value pair has been created');
		$this->assertEquals($blue->id, $pairs[0]->fk_prod_attr_val, 'the created pair carries the right value');

		// Replaying it must not duplicate the pair
		$tmp = new ProductCombination($this->savdb);
		$res = $tmp->createProductCombination($user, $parentb, array($colour->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, $childb->ref, '', false, $combinationofb);
		$this->assertGreaterThan(0, $res, 'replay '.$tmp->error);
		$pairs = (new ProductCombination2ValuePair($this->savdb))->fetchByFkCombination($combinationid);
		$this->assertCount(1, $pairs, 'the replay has not duplicated the value pair');
	}

	/**
	 * A description written by the user must survive the reconciliation, even when it uses the
	 * same formatting as a generated one.
	 *
	 * @return void
	 */
	public function testUserWrittenDescriptionInBoldIsPreserved()
	{
		global $user;

		$parent = $this->createParentProduct('PHPUNITBOLD');
		$colour = $this->createAttribute('PHPUNITC14', 'Colour');
		$blue = $this->createValue($colour->id, 'PHPUNITBLUE14', 'Blue');
		$userdescription = (string) $parent->description.'<br><strong>Care:</strong> machine wash at 30 degrees';
		$this->createProduct('PHPUNITBOLD_BLUE', 'Child PHPUNITBOLD', 0, 0, $userdescription);

		$combination = new ProductCombination($this->savdb);
		$res = $combination->createProductCombination($user, $parent, array($colour->id => $blue->id), array(), array(1 => false), array(1 => 0), 0.0, 'PHPUNITBOLD_BLUE');
		$this->assertGreaterThan(0, $res, 'create '.$combination->error.' '.implode(', ', (array) $combination->errors));

		$tmpchild = new Product($this->savdb);
		$tmpchild->fetch(0, 'PHPUNITBOLD_BLUE');
		$this->assertEquals($userdescription, $tmpchild->description, 'a description written by the user is preserved even in bold');
	}

	/**
	 * An import reads one attribute per file line, so the description of a multi attribute
	 * variant is composed over several passes and must not stop at the first attribute.
	 *
	 * @return void
	 */
	public function testDescriptionOfAMultiAttributeVariantIsComplete()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$parent = $this->createParentProduct('PHPUNITMULTI');
		$colour = $this->createAttribute('PHPUNITC12', 'Colour');
		$blue = $this->createValue($colour->id, 'PHPUNITBLUE12', 'Blue');
		$size = $this->createAttribute('PHPUNITS12', 'Size');
		$large = $this->createValue($size->id, 'PHPUNITL12', 'Large');
		$child = $this->createProduct('PHPUNITMULTI_BLUE_L', 'Child PHPUNITMULTI', 0, 0, '');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination');
		$combinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		// One trigger pass per attribute, as the engine does line by line
		foreach (array($colour->id => $blue->id, $size->id => $large->id) as $attributeid => $valueid) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
			$sql .= " VALUES (".((int) $combinationid).", ".((int) $attributeid).", ".((int) $valueid).")";
			$this->assertNotFalse($this->savdb->query($sql), 'insert feature '.$attributeid);
			$pairid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination2val');

			$trigger = new InterfaceVariantsImport($this->savdb);
			$object = new stdClass();
			$object->id = $pairid;
			$object->rowid = $pairid;
			$object->import_key = '20260903';
			$object->context = array('import' => 1, 'operation' => 'insert');
			$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION2VAL_CREATE', $object, $user, $langs, $conf);
			$this->assertGreaterThan(0, $result, 'reconciliation of feature '.$attributeid.' '.$trigger->error.' '.implode(', ', (array) $trigger->errors));
		}

		$tmpchild = new Product($this->savdb);
		$tmpchild->fetch($child->id);
		$this->assertStringContainsString('Blue', $tmpchild->description, 'the description carries the first attribute');
		$this->assertStringContainsString('Large', $tmpchild->description, 'the description carries the last attribute');
	}

	/**
	 * The reconciliation must never repoint the child product of another variant of the same
	 * parent, which the lookup by value set does as long as the imported set is partial.
	 *
	 * @return void
	 */
	public function testReconciliationNeverStealsAnotherVariantChild()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$parent = $this->createParentProduct('PHPUNITSTEAL');
		$colour = $this->createAttribute('PHPUNITC11', 'Colour');
		$blue = $this->createValue($colour->id, 'PHPUNITBLUE11', 'Blue');
		$size = $this->createAttribute('PHPUNITS11', 'Size');
		$medium = $this->createValue($size->id, 'PHPUNITM11', 'Medium');

		$childsimple = $this->createProduct('PHPUNITSTEAL_BLUE', 'Blue variant', 0, 0);
		$childcomposed = $this->createProduct('PHPUNITSTEAL_BLUE_M', 'Blue medium variant', 0, 0);

		// The parent is declined by colour alone, and by colour plus size. Both are legitimate.
		$combinationids = array();
		foreach (array('simple' => $childsimple, 'composed' => $childcomposed) as $key => $child) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
			$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
			$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
			$this->assertNotFalse($this->savdb->query($sql), 'insert combination '.$key);
			$combinationids[$key] = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
		$sql .= " VALUES (".((int) $combinationids['simple']).", ".((int) $colour->id).", ".((int) $blue->id).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert value set of the simple variant');

		// First line of the composed variant: its set is still partial and equal to the complete
		// set of the simple variant.
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
		$sql .= " VALUES (".((int) $combinationids['composed']).", ".((int) $colour->id).", ".((int) $blue->id).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert first line of the composed variant');
		$pairid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination2val');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $pairid;
		$object->rowid = $pairid;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION2VAL_CREATE', $object, $user, $langs, $conf);
		$this->assertGreaterThan(0, $result, 'partial set reconciliation '.$trigger->error.' '.implode(', ', (array) $trigger->errors));

		$tmpcombination = new ProductCombination($this->savdb);
		$tmpcombination->fetch($combinationids['simple']);
		$this->assertEquals($childsimple->id, $tmpcombination->fk_product_child, 'the simple variant keeps its own child product');

		$tmpcombination = new ProductCombination($this->savdb);
		$tmpcombination->fetch($combinationids['composed']);
		$this->assertEquals($childcomposed->id, $tmpcombination->fk_product_child, 'the composed variant keeps its own child product');
	}

	/**
	 * An exported file must be reimportable without manual mapping: the import screen
	 * preselects a target field when the translated label of the file column equals the
	 * translated label of the field, so every mandatory field of an import dataset must
	 * appear among the labels of the export profile that feeds it.
	 *
	 * @return void
	 */
	public function testExportedFieldsFeedTheImportDatasets()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';
		require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

		$conf->global->PRODUIT_MULTIPRICES = 1;
		$langs->load('products');

		// Which export profile is expected to feed which import dataset
		$feeds = array(
			'variants_attribute' => array('variants_attribute', 'variants_value'),
			'variants_combination' => array('variants_combination', 'variants_combination2val'),
			'variants_pricelevel' => array('variants_pricelevel'),
		);

		$objexport = new Export($this->savdb);
		$objexport->load_arrays($user, '');

		foreach ($feeds as $exportcode => $importcodes) {
			$exportlabels = array();
			foreach ($objexport->array_export_code as $key => $code) {
				if ($code !== $exportcode) {
					continue;
				}
				foreach ($objexport->array_export_fields[$key] as $labelkey) {
					$exportlabels[strtolower($langs->transnoentities($labelkey))] = 1;
				}
			}
			$this->assertNotEmpty($exportlabels, 'export profile '.$exportcode.' is declared');

			foreach ($importcodes as $importcode) {
				$objimport = new Import($this->savdb);
				$objimport->load_arrays($user, $importcode);
				$this->assertEquals($importcode, $objimport->array_import_code[0], 'import dataset '.$importcode.' is declared');

				foreach ($objimport->array_import_fields[0] as $alias => $labelkey) {
					if (strpos($labelkey, '*') === false) {
						continue;	// only mandatory fields make a file unusable
					}
					$label = strtolower($langs->transnoentities(str_replace('*', '', $labelkey)));
					$this->assertArrayHasKey($label, $exportlabels, $alias.' of '.$importcode.' is not exported by '.$exportcode);
				}
			}
		}
	}

	/**
	 * Every preselected update key of the import datasets must be a declared update key,
	 * otherwise commonImportInsert() silently drops it.
	 *
	 * @return void
	 */
	public function testImportDatasetsPreselectedUpdateKeys()
	{
		global $user, $conf;

		require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';

		$conf->global->PRODUIT_MULTIPRICES = 1;
		$datasets = array('variants_attribute', 'variants_value', 'variants_combination', 'variants_combination2val', 'variants_pricelevel');

		foreach ($datasets as $dataset) {
			$objimport = new Import($this->savdb);
			$objimport->load_arrays($user, $dataset);
			$this->assertEquals($dataset, $objimport->array_import_code[0], 'dataset '.$dataset.' is declared');

			// imports/import.php cannot tell "no update key chosen" from "no choice made", so a
			// preselection can never be cancelled by the user: only the three link tables, where
			// inserting a duplicate row is refused anyway, are allowed to preselect.
			$preselected = (array) $objimport->array_import_preselected_updatekeys[0];
			if (in_array($dataset, array('variants_attribute', 'variants_value'), true)) {
				$this->assertEmpty($preselected, 'dataset '.$dataset.' leaves the update keys to the user');
			} else {
				$this->assertNotEmpty($preselected, 'dataset '.$dataset.' preselects its update keys');
			}
			foreach ($preselected as $alias) {
				$this->assertArrayHasKey($alias, $objimport->array_import_updatekeys[0], $alias.' is a declared update key of '.$dataset);
				$this->assertArrayHasKey($alias, $objimport->array_import_fields[0], $alias.' is a field of '.$dataset);
			}
		}
	}

	/**
	 * A parent product that is a variant itself must be refused
	 *
	 * @return void
	 */
	public function testImportTriggerRefusesVariantAsParent()
	{
		global $user, $langs, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$parent = $this->createParentProduct('PHPUNITCHAIN');
		$child = $this->createProduct('PHPUNITCHAIN_A', 'Child PHPUNITCHAIN', 0, 0);
		$grandchild = $this->createProduct('PHPUNITCHAIN_B', 'Child PHPUNITCHAIN', 0, 0);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert first combination');

		// $child is already a variant, so it must not be usable as a parent product
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $child->id).", ".((int) $grandchild->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert chained combination');
		$combinationid = $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $combinationid;
		$object->rowid = $combinationid;
		$object->import_key = '20260903';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf);

		$this->assertLessThan(0, $result, 'a variant must not be usable as a parent product');
	}

	/**
	 * Delete the combination rows of a product without ever deleting a product
	 *
	 * @return	void
	 */
	public function testDeleteLinksByProductLeavesTheProductsAlone()
	{
		global $conf, $user;

		$parent = $this->createParentProduct('PHPUNITLINKS');
		$child = $this->createProduct('PHPUNITLINKS_A', 'Child PHPUNITLINKS', 0, 0);
		$other = $this->createProduct('PHPUNITLINKS_OTHER', 'Unrelated PHPUNITLINKS', 50, 1);
		$attribute = $this->createAttribute('PHPUNITLINKSCOL', 'Colour');
		$value = $this->createValue($attribute->id, 'PHPUNITLINKSBLUE', 'Blue');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 5, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination');
		$combinationid = (int) $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination2val (fk_prod_combination, fk_prod_attr, fk_prod_attr_val)";
		$sql .= " VALUES (".$combinationid.", ".((int) $attribute->id).", ".((int) $value->id).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert value pair');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination_price_level";
		$sql .= " (fk_product_attribute_combination, fk_price_level, variation_price, variation_price_percentage)";
		$sql .= " VALUES (".$combinationid.", 2, 9, 0)";
		$this->assertNotFalse($this->savdb->query($sql), 'insert price level');

		// An unrelated combination, to prove the deletion is scoped to the given product
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $other->id).", 0, 0, 0, 0, 'PHPUNITKEEP', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert unrelated combination');

		$prodcomb = new ProductCombination($this->savdb);
		$this->assertGreaterThan(0, $prodcomb->deleteLinksByProduct($child->id), 'deleteLinksByProduct on the child');

		$this->assertSame(0, $this->countRows('product_attribute_combination', 'fk_product_parent = '.((int) $parent->id)), 'the combination is gone');
		$this->assertSame(0, $this->countRows('product_attribute_combination2val', 'fk_prod_combination = '.$combinationid), 'the value pair is gone');
		$this->assertSame(0, $this->countRows('product_attribute_combination_price_level', 'fk_product_attribute_combination = '.$combinationid), 'the price level is gone');
		$this->assertSame(1, $this->countRows('product_attribute_combination', "variation_ref_ext = 'PHPUNITKEEP'"), 'the unrelated combination is untouched');

		// Not a single product may have been deleted
		$check = new Product($this->savdb);
		$this->assertGreaterThan(0, $check->fetch($parent->id), 'the parent product still exists');
		$check = new Product($this->savdb);
		$this->assertGreaterThan(0, $check->fetch($child->id), 'the child product still exists');

		// Calling it again on a product carrying nothing must succeed and change nothing
		$this->assertGreaterThan(0, $prodcomb->deleteLinksByProduct($child->id), 'deleteLinksByProduct is replayable');
		$this->assertSame(1, $this->countRows('product_attribute_combination', "variation_ref_ext = 'PHPUNITKEEP'"), 'the unrelated combination is still untouched');
	}

	/**
	 * Count rows of a variants table matching a caller-built filter
	 *
	 * @param	string	$table	Table name without prefix
	 * @param	string	$filter	SQL filter, values cast by the caller
	 * @return	int				Number of rows
	 */
	private function countRows($table, $filter)
	{
		$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX.$this->savdb->sanitize($table);
		$sql .= " WHERE ".$filter;
		$resql = $this->savdb->query($sql);
		$this->assertNotFalse($resql, 'count on '.$table);
		$obj = $this->savdb->fetch_object($resql);
		$this->savdb->free($resql);

		return (int) $obj->nb;
	}

	/**
	 * A product carrying variants of its own must not become a variant, whatever the row order
	 *
	 * @return	void
	 */
	public function testChildAlreadyParentIsRefused()
	{
		global $conf, $user, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$top = $this->createParentProduct('PHPUNITDEPTH');
		$middle = $this->createProduct('PHPUNITDEPTH_M', 'Middle PHPUNITDEPTH', 100, 2);
		$leaf = $this->createProduct('PHPUNITDEPTH_L', 'Leaf PHPUNITDEPTH', 0, 0);

		// The deepest link first: middle is the parent of leaf
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $middle->id).", ".((int) $leaf->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert deepest combination');

		// Then the shallowest: middle would become a variant of top
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $top->id).", ".((int) $middle->id).", 0, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert shallowest combination');
		$combinationid = (int) $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $combinationid;
		$object->rowid = $combinationid;
		$object->import_key = '20260904';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf);

		$this->assertLessThan(0, $result, 'a product that already has variants must not become a variant');
		$this->assertStringContainsString('PHPUNITDEPTH_M', implode(' ', $trigger->errors), 'the message names the offending product');
	}

	/**
	 * A child the ref resolution cannot reach must be refused, never created
	 *
	 * @return	void
	 */
	public function testChildNotResolvableByRefIsRefused()
	{
		global $conf, $user, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modVariants_VariantsImport.class.php';

		$parent = $this->createParentProduct('PHPUNITREACH');
		$child = $this->createProduct('PHPUNITREACH_A', 'Child PHPUNITREACH', 0, 0);

		// Product::fetch($id) crosses every entity, fetch(0, $ref) does not: move the child out of
		// reach of the ref resolution that createProductCombination() performs downstream.
		$sql = "UPDATE ".MAIN_DB_PREFIX."product SET entity = 999999 WHERE rowid = ".((int) $child->id);
		$this->assertNotFalse($this->savdb->query($sql), 'move the child out of the current entity');

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $parent->id).", ".((int) $child->id).", 5, 0, 0, '', ".((int) $conf->entity).")";
		$this->assertNotFalse($this->savdb->query($sql), 'insert combination');
		$combinationid = (int) $this->savdb->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		$countbefore = $this->countRows('product', "ref = 'PHPUNITREACH_A'");

		$trigger = new InterfaceVariantsImport($this->savdb);
		$object = new stdClass();
		$object->id = $combinationid;
		$object->rowid = $combinationid;
		$object->import_key = '20260904';
		$object->context = array('import' => 1, 'operation' => 'insert');
		$result = $trigger->runTrigger('PRODUCT_ATTRIBUTE_COMBINATION_CREATE', $object, $user, $langs, $conf);

		$this->assertLessThan(0, $result, 'an unreachable child must be refused');
		$this->assertSame($countbefore, $this->countRows('product', "ref = 'PHPUNITREACH_A'"), 'no product may be created');
		$this->assertSame((int) $child->id, (int) $this->fetchOne('product_attribute_combination', 'fk_product_child', 'rowid = '.$combinationid), 'the combination must not be repointed on a clone');
	}

	/**
	 * Read one column of one row of a table
	 *
	 * @param	string	$table	Table name without prefix
	 * @param	string	$column	Column to read
	 * @param	string	$filter	SQL filter, values cast by the caller
	 * @return	string			Value read, empty string if no row
	 */
	private function fetchOne($table, $column, $filter)
	{
		$sql = "SELECT ".$this->savdb->sanitize($column)." as val FROM ".MAIN_DB_PREFIX.$this->savdb->sanitize($table);
		$sql .= " WHERE ".$filter;
		$resql = $this->savdb->query($sql);
		$this->assertNotFalse($resql, 'select on '.$table);
		$obj = $this->savdb->fetch_object($resql);
		$this->savdb->free($resql);

		return empty($obj) ? '' : (string) $obj->val;
	}
}
