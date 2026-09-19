<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       test/phpunit/CommonObjectTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
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
class CommonObjectTest extends CommonClassTest
{
	/**
	 *  testFetchUser
	 *
	 *  @return void
	 */
	public function testFetchUser()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$localobject->fetch(1);

		$result = $localobject->fetch_user(1);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($localobject->user->id, 0);
		return $result;
	}

	/**
	 *  testFetchProject
	 *
	 *  @return void
	 */
	public function testFetchProject()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$localobject->fetch(1);
		$result = $localobject->fetchProject();

		print __METHOD__." result=".$result."\n";
		$this->assertLessThanOrEqual($result, 0);
		return $result;
	}

	/**
	 *  testFetchThirdParty
	 *
	 *  @return void
	 */
	public function testFetchThirdParty()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$localobject->fetch(1);

		$result = $localobject->fetch_thirdparty();

		print __METHOD__." result=".$result."\n";
		$this->assertLessThanOrEqual($result, 0);
		return $result;
	}

	/**
	 *  testIsInt
	 *
	 *  @return void
	 */
	public function testIsInt()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);

		// Integer types, with or without a size/unsigned suffix, must be detected.
		$this->assertTrue($localobject->isInt(array('type' => 'int')), 'int');
		$this->assertTrue($localobject->isInt(array('type' => 'int(11)')), 'int(11)');
		$this->assertTrue($localobject->isInt(array('type' => 'integer')), 'integer');
		$this->assertTrue($localobject->isInt(array('type' => 'tinyint(4)')), 'tinyint(4)');
		$this->assertTrue($localobject->isInt(array('type' => 'smallint(6)')), 'smallint(6)');
		$this->assertTrue($localobject->isInt(array('type' => 'bigint(20)')), 'bigint(20)');
		// Dolibarr foreign-key column syntax "integer:Class:path" must keep being detected.
		$this->assertTrue($localobject->isInt(array('type' => 'integer:User:user/class/user.class.php')), 'integer:User:...');

		// Non-integer types must not be detected, including strings that merely contain "int".
		$this->assertFalse($localobject->isInt(array('type' => 'varchar(255)')), 'varchar(255)');
		$this->assertFalse($localobject->isInt(array('type' => 'double(24,8)')), 'double(24,8)');
		$this->assertFalse($localobject->isInt(array('type' => 'date')), 'date');
		$this->assertFalse($localobject->isInt(array('type' => 'sellist:llx_c_typent:libelle:id')), 'sellist:...');

		print __METHOD__." OK\n";
	}

	/**
	 * setFieldValue() must only alter the targeted field/property.
	 * Regression test: a previous implementation iterated over the values of
	 * deprecatedProperties() (status, totalpaid, fk_project, project, origin_object, ...)
	 * regardless of the field being set, corrupting all of these unrelated properties
	 * on every single call.
	 *
	 * @return void
	 */
	public function testSetFieldValueDoesNotCorruptUnrelatedProperties()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db) extends CommonObject {
			/**
			 * @var string Element name
			 */
			public $element = 'testobject';
			/**
			 * @var string Table name
			 */
			public $table_element = 'testobject';
			/**
			 * @var array<string,array{type:string,label:string,enabled:int}> Fields definition
			 */
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1),
			);

			/**
			 * Constructor
			 *
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
			}
		};

		$localobject->status = 0;
		$localobject->statut = 0;
		$localobject->project = null;
		$localobject->fk_project = 42;
		$localobject->origin_object = null;
		$localobject->totalpaid = 100;

		$result = $localobject->setFieldValue($user, 'ref', 'AA2501-0001', true);

		$this->assertTrue($result);
		$this->assertSame('AA2501-0001', $localobject->ref);
		$this->assertSame(0, $localobject->status, 'status must not be altered when setting an unrelated field');
		$this->assertSame(0, $localobject->statut, 'statut must not be altered when setting an unrelated field');
		$this->assertSame(42, $localobject->fk_project, 'fk_project must not be altered when setting an unrelated field');
		$this->assertSame(100, $localobject->totalpaid, 'totalpaid must not be altered when setting an unrelated field');
		$this->assertNull($localobject->origin_object, 'origin_object must not be altered when setting an unrelated field');

		print __METHOD__." OK\n";
	}

	/**
	 * setFieldValue() must keep the deprecated 'statut' property in sync with 'status'
	 * since both are still declared as real properties on CommonObject (DolDeprecationHandler
	 * magic methods are never triggered for them).
	 *
	 * @return void
	 */
	public function testSetFieldValuePropagatesDeprecatedStatutAlias()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db) extends CommonObject {
			/**
			 * @var string Element name
			 */
			public $element = 'testobject';
			/**
			 * @var string Table name
			 */
			public $table_element = 'testobject';
			/**
			 * @var array<string,array{type:string,label:string,enabled:int}> Fields definition
			 */
			public $fields = array(
				'status' => array('type' => 'smallint', 'label' => 'Status', 'enabled' => 1),
			);

			/**
			 * Constructor
			 *
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
			}
		};

		$result = $localobject->setFieldValue($user, 'status', 2, true);

		$this->assertTrue($result);
		$this->assertSame(2, $localobject->status);
		$this->assertSame(2, $localobject->statut, 'deprecated statut property must stay in sync with status');

		print __METHOD__." OK\n";
	}

	/**
	 * onFieldValueChanged() must be called after a field is set, so that dependent fields
	 * (ex: a TTC amount recomputed from an HT amount on a line) can be recalculated.
	 * The recommended pattern is a direct property assignment in the hook, not a recursive
	 * call to setFieldValue().
	 *
	 * @return void
	 */
	public function testSetFieldValueTriggersOnFieldValueChangedHook()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db) extends CommonObject {
			/**
			 * @var string Element name
			 */
			public $element = 'testline';
			/**
			 * @var string Table name
			 */
			public $table_element = 'testline';
			/**
			 * @var array<string,array{type:string,label:string,enabled:int}> Fields definition
			 */
			public $fields = array(
				'total_ht' => array('type' => 'double', 'label' => 'Total HT', 'enabled' => 1),
			);
			/**
			 * @var float Simulated VAT-included amount, recomputed from total_ht
			 */
			public $total_ttc = 0.0;

			/**
			 * Constructor
			 *
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
			}

			/**
			 * Recompute total_ttc directly (no recursive call to setFieldValue()) when total_ht changes.
			 *
			 * @param string $fieldKey Name of the field that was just modified
			 * @param mixed  $value    New value assigned to the field
			 * @return void
			 */
			protected function onFieldValueChanged($fieldKey, $value)
			{
				if ($fieldKey === 'total_ht') {
					$this->total_ttc = ((float) $value) * 1.2;
				}
			}
		};

		$result = $localobject->setFieldValue($user, 'total_ht', 100.0, true);

		$this->assertTrue($result);
		$this->assertSame(100.0, $localobject->total_ht);
		$this->assertSame(120.0, $localobject->total_ttc, 'total_ttc must be recomputed from total_ht by onFieldValueChanged');

		print __METHOD__." OK\n";
	}

	/**
	 * setFieldValue() must protect itself against an infinite loop if onFieldValueChanged()
	 * is (incorrectly, or after a future evolution) implemented with a recursive call back
	 * into setFieldValue() for a field that is still being processed.
	 *
	 * @return void
	 */
	public function testSetFieldValueBlocksRecursiveSideEffectLoop()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db) extends CommonObject {
			/**
			 * @var string Element name
			 */
			public $element = 'testline';
			/**
			 * @var string Table name
			 */
			public $table_element = 'testline';
			/**
			 * @var array<string,array{type:string,label:string,enabled:int}> Fields definition
			 */
			public $fields = array(
				'total_ht' => array('type' => 'double', 'label' => 'Total HT', 'enabled' => 1),
				'total_ttc' => array('type' => 'double', 'label' => 'Total TTC', 'enabled' => 1),
			);
			/**
			 * @var int Number of times onFieldValueChanged() ran, to prove there is no infinite loop
			 */
			public $hookCallCount = 0;

			/**
			 * Constructor
			 *
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
			}

			/**
			 * Deliberately buggy: ping-pongs between total_ht and total_ttc through setFieldValue()
			 * itself, to check that the re-entrancy guard in setFieldValue() breaks the cycle.
			 *
			 * @param string $fieldKey Name of the field that was just modified
			 * @param mixed  $value    New value assigned to the field
			 * @return void
			 */
			protected function onFieldValueChanged($fieldKey, $value)
			{
				global $user;

				$this->hookCallCount++;

				if ($fieldKey === 'total_ht') {
					$this->setFieldValue($user, 'total_ttc', ((float) $value) * 1.2, true);
				} elseif ($fieldKey === 'total_ttc') {
					$this->setFieldValue($user, 'total_ht', ((float) $value) / 1.2, true);
				}
			}
		};

		$result = $localobject->setFieldValue($user, 'total_ht', 100.0, true);

		$this->assertTrue($result, 'the initial call must still succeed');
		$this->assertSame(2, $localobject->hookCallCount, 'the loop must be broken after the second (recursive) call');

		print __METHOD__." OK\n";
	}

	/**
	 * Data provider for deprecated property mappings
	 * Tests all mappings from deprecatedProperties() method
	 *
	 * @return array<array{oldProp:string, newProp:string, oldValue:mixed, newValue:mixed}>
	 */
	public function deprecatedPropertyMappingProvider()
	{
		return array(
			// Test statut -> status mapping with multiple values to ensure they evolve
			'statut_to_status_draft' => array('statut', 'status', 0, null),
			'statut_to_status_validated' => array('statut', 'status', 1, null),
			'statut_to_status_canceled' => array('statut', 'status', 2, null),
			'status_to_statut_draft' => array('status', 'statut', 0, null),
			'status_to_statut_validated' => array('status', 'statut', 1, null),
			// Test alreadypaid -> totalpaid mapping
			'alreadypaid_to_totalpaid' => array('alreadypaid', 'totalpaid', 500, null),
			'totalpaid_to_alreadypaid' => array('totalpaid', 'alreadypaid', 750, null),
			// Test fk_project -> fk_project (self-mapping but projet -> project exists)
			'projet_to_project' => array('projet', 'project', 'PROJ001', null),
			'project_to_projet' => array('project', 'projet', 'Project Object', null),
			// Test cond_reglement -> depr_cond_reglement mapping
		);
	}

	/**
	 * Test that setFieldValue correctly propagates ALL deprecated property mappings
	 * This tests each deprecated->new property pair from deprecatedProperties()
	 *
	 * @dataProvider deprecatedPropertyMappingProvider
	 * @param string $oldProp Old/deprecated property name
	 * @param string $newProp New/replacement property name
	 * @param mixed $value Value to set
	 * @param mixed $expectedNewValue Expected value for new property (or null if same as value)
	 * @return void
	 */
	public function testSetFieldValuePropagatesAllDeprecatedPropertyMappings($oldProp, $newProp, $value, $expectedNewValue)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Create a test object with the field defined
		// Note: We need to set status and statut to 0 (draft) so fields are not blocked
		$localobject = new class ($db, $oldProp, $newProp) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array();
			public $status = 0;
			public $statut = 0;

			public function __construct($db, $oldProp, $newProp)
			{
				$this->db = $db;
				// Add both properties to fields if they're different
				if ($oldProp !== $newProp) {
					$this->fields[$oldProp] = array('type' => 'varchar(255)', 'label' => 'Old Prop', 'enabled' => 1);
					$this->fields[$newProp] = array('type' => 'varchar(255)', 'label' => 'New Prop', 'enabled' => 1);
				} else {
					$this->fields[$oldProp] = array('type' => 'varchar(255)', 'label' => 'Property', 'enabled' => 1);
				}
			}
		};

		// Set the old property value
		$result = $localobject->setFieldValue($user, $oldProp, $value, true);

		$this->assertTrue($result, "setFieldValue should succeed for deprecated property $oldProp");
		$this->assertSame($value, $localobject->{$oldProp}, "Old property $oldProp should have the set value");

		// Check that the new property was also updated
		$expectedValue = $expectedNewValue !== null ? $expectedNewValue : $value;
		$this->assertSame($expectedValue, $localobject->{$newProp}, "New property $newProp should be synchronized with $oldProp");

		print __METHOD__." [$oldProp->$newProp] OK\n";
	}

	/**
	 * Test setFieldValue with child class that overrides deprecatedProperties
	 * This tests DolDeprecationHandler compatibility with custom deprecated properties in child classes
	 * Old properties are NOT declared - they are handled by magic methods in DolDeprecationHandler
	 * New properties ARE declared in fields array and as class properties
	 *
	 * @return void
	 */
	public function testSetFieldValueWithChildClassDeprecatedProperties()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Create a child class that adds custom deprecated property mappings
		// Only NEW properties (new_field, current_name) are declared in fields and as properties
		// OLD properties (old_field, legacy_name) are NOT declared - handled by DolDeprecationHandler magic
		$localobject = new class ($db) extends CommonObject {
			/**
			 * @var string Element name
			 */
			public $element = 'testchild';
			/**
			 * @var string Table name
			 */
			public $table_element = 'testchild';
			/**
			 * @var array Fields definition - includes both old and new field names
			 * Old properties are in fields so setFieldValue accepts them, but not declared as class properties
			 * statut is inherited from parent but needs to be in fields array for setFieldValue to accept it
			 */
			public $fields = array(
				'old_field' => array('type' => 'varchar(255)', 'label' => 'Old Field', 'enabled' => 1),
				'new_field' => array('type' => 'varchar(255)', 'label' => 'New Field', 'enabled' => 1),
				'legacy_name' => array('type' => 'varchar(255)', 'label' => 'Legacy Name', 'enabled' => 1),
				'current_name' => array('type' => 'varchar(255)', 'label' => 'Current Name', 'enabled' => 1),
				'statut' => array('type' => 'smallint', 'label' => 'Statut', 'enabled' => 1),
			);
			/**
			 * @var string New field property (declared)
			 */
			public $new_field;
			/**
			 * @var string Current name property (declared)
			 */
			public $current_name;
			/**
			 * @var int Status (inherited from parent CommonObject, not redeclared)
			 */

			/**
			 * Constructor
			 *
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
				// Inherited from CommonObject - don't redeclare
				$this->status = 0;
				$this->statut = 0;
			}

			/**
			 * Override deprecatedProperties to add child-specific mappings
			 * This simulates a class that extends CommonObject and adds its own deprecated properties
			 * Old properties (old_field, legacy_name) are NOT declared in fields or as properties
			 *
			 * @return array<string,string> Mapping of old property names to new property names
			 */
			protected function deprecatedProperties()
			{
				// Start with parent mappings (statut->status, etc.)
				$mappings = parent::deprecatedProperties();
				// Add child-specific mappings: old (undeclared) -> new (declared)
				$mappings['old_field'] = 'new_field';
				$mappings['legacy_name'] = 'current_name';
				return $mappings;
			}
		};

		// Test child-specific deprecated property: old_field (not declared) -> new_field (declared)
		$result1 = $localobject->setFieldValue($user, 'old_field', 'deprecated_value', true);
		$this->assertTrue($result1, 'setFieldValue should succeed for child deprecated property old_field');
		$this->assertSame('deprecated_value', $localobject->old_field, 'old_field should have the set value via magic method');
		$this->assertSame('deprecated_value', $localobject->new_field, 'new_field should be synchronized with old_field');

		// Test another child-specific deprecated property: legacy_name (not declared) -> current_name (declared)
		$result2 = $localobject->setFieldValue($user, 'legacy_name', 'legacy_value', true);
		$this->assertTrue($result2, 'setFieldValue should succeed for child deprecated property legacy_name');
		$this->assertSame('legacy_value', $localobject->legacy_name, 'legacy_name should have the set value via magic method');
		$this->assertSame('legacy_value', $localobject->current_name, 'current_name should be synchronized with legacy_name');

		// Test that parent deprecated properties still work (statut -> status, inherited from CommonObject)
		$result3 = $localobject->setFieldValue($user, 'statut', 1, true);
		$this->assertTrue($result3, 'setFieldValue should succeed for parent deprecated property statut');
		$this->assertSame(1, $localobject->statut, 'statut should have the set value');
		$this->assertSame(1, $localobject->status, 'status should be synchronized with statut');

		print __METHOD__." OK\n";
	}

	/**
	 * Data provider for field configuration tests
	 * Tests various field configurations: enabled, disabled, noteditable, validate, etc.
	 *
	 * @return array<array{fieldName:string, fieldConfig:array, expectedResult:bool}>
	 */
	public function fieldConfigurationProvider()
	{
		return array(
			// Field is enabled (default)
			'field_enabled_default' => array(
				'ref',
				array('type' => 'varchar(30)', 'label' => 'Ref'),
				true
			),
			// Field is explicitly enabled
			'field_enabled_explicit' => array(
				'ref',
				array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => '1'),
				true
			),
			// Field is disabled
			'field_disabled' => array(
				'ref',
				array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 0, 'disabled' => 1),
				false
			),
			// Field is not editable
			'field_noteditable' => array(
				'ref',
				array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'noteditable' => 1),
				false
			),
			// Field is not defined
			'field_not_defined' => array(
				'undefined_field',
				array(),
				false
			),
		);
	}

	/**
	 * Test isFieldEditAllowed with various field configurations
	 *
	 * @dataProvider fieldConfigurationProvider
	 * @param string $fieldName Field name to test
	 * @param array $fieldConfig Field configuration
	 * @param bool $expectedResult Expected result from isFieldEditAllowed
	 * @return void
	 */
	public function testIsFieldEditAllowedWithVariousConfigurations($fieldName, $fieldConfig, $expectedResult)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db, $fieldName, $fieldConfig) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array();
			public $status = 0;
			public $statut = 0;

			public function __construct($db, $fieldName, $fieldConfig)
			{
				$this->db = $db;
				if (!empty($fieldConfig)) {
					$this->fields[$fieldName] = $fieldConfig;
				}
			}
		};

		$result = $localobject->isFieldEditAllowed($user, $fieldName, true);

		$this->assertSame($expectedResult, $result, "isFieldEditAllowed should return $expectedResult for field $fieldName with config: " . json_encode($fieldConfig));

		print __METHOD__." [$fieldName] OK\n";
	}



	/**
	 * Test isFieldBlockedByObjectState with various status/statut combinations
	 * This tests the logic that fields are blocked when not in draft state
	 *
	 * @return void
	 */
	public function testIsFieldBlockedByObjectStateWithVariousStates()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Test 1: Both status and statut are 0 (draft) - field should NOT be blocked
		$obj1 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 0;
			public $statut = 0;

			public function __construct($db) { $this->db = $db; }
		};

		// Use reflection to test private method
		$reflection = new ReflectionClass($obj1);
		$method = $reflection->getMethod('isFieldBlockedByObjectState');
		$method->setAccessible(true);

		$result1 = $method->invoke($obj1, 'ref');
		$this->assertFalse($result1, 'Field should NOT be blocked when both status and statut are 0 (draft)');

		// Test 2: status is non-zero, statut is 0 - field SHOULD be blocked
		$obj2 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;
			public $statut = 0;

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result2 = $method->invoke($obj2, 'ref');
		$this->assertTrue($result2, 'Field SHOULD be blocked when status is non-zero');

		// Test 3: status is 0, statut is non-zero - field SHOULD be blocked
		$obj3 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 0;
			public $statut = 1;

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result3 = $method->invoke($obj3, 'ref');
		$this->assertTrue($result3, 'Field SHOULD be blocked when statut is non-zero');

		// Test 4: Both are non-zero - field SHOULD be blocked
		$obj4 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;
			public $statut = 1;

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result4 = $method->invoke($obj4, 'ref');
		$this->assertTrue($result4, 'Field SHOULD be blocked when both are non-zero');

		// Test 5: Field with alwayseditable flag should NEVER be blocked
		$obj5 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'alwayseditable' => 1)
			);
			public $status = 1;
			public $statut = 1;

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result5 = $method->invoke($obj5, 'ref');
		$this->assertFalse($result5, 'Field with alwayseditable=1 should NEVER be blocked');

		print __METHOD__." OK\n";
	}

	/**
	 * Test isFieldEnabled with expression-based enabled flag
	 * This tests that dol_eval is correctly used to evaluate enabled expressions
	 *
	 * @return void
	 */
	public function testIsFieldEnabledWithExpression()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Test with a simple expression that evaluates to 1
		$obj1 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => '1')
			);

			public function __construct($db) { $this->db = $db; }
		};

		// Use reflection to test private method
		$reflection = new ReflectionClass($obj1);
		$method = $reflection->getMethod('isFieldEnabled');
		$method->setAccessible(true);

		$result1 = $method->invoke($obj1, 'ref');
		$this->assertTrue($result1, 'Field with enabled=\"1\" should be enabled');

		// Test with expression that evaluates to 0
		$obj2 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => '0')
			);

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result2 = $method->invoke($obj2, 'ref');
		$this->assertFalse($result2, 'Field with enabled=\"0\" should be disabled');

		// Test with isModEnabled expression
		$obj3 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 'isModEnabled("multicurrency")')
			);

			public function __construct($db) { $this->db = $db; }
		};

		$method->setAccessible(true);
		$result3 = $method->invoke($obj3, 'ref');
		// This will depend on whether multicurrency module is enabled
		// We just check it returns a boolean value
		$this->assertIsBool($result3, 'isFieldEnabled should return a boolean');

		print __METHOD__." OK\n";
	}

	/**
	 * Test that onFieldValueChanged is called for all field types
	 *
	 * @return void
	 */
	public function testOnFieldValueChangedCalledForVariousFieldTypes()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$testValues = array(
			'string' => 'test_string',
			'integer' => 123,
			'float' => 45.67,
			'array' => array('a', 'b', 'c'),
			'null' => null,
			'empty_string' => '',
			'zero' => 0,
			'bool_true' => true,
			'bool_false' => false,
		);

		foreach ($testValues as $type => $value) {
			$localobject = new class ($db) extends CommonObject {
				public $element = 'testobject';
				public $table_element = 'testobject';
				public $fields = array(
					'test_field' => array('type' => 'text', 'label' => 'Test Field', 'enabled' => 1)
				);
				public $hookCalled = false;
				public $lastFieldKey = null;
				public $lastValue = null;

				public function __construct($db)
				{
					$this->db = $db;
				}

				protected function onFieldValueChanged($fieldKey, $value)
				{
					$this->hookCalled = true;
					$this->lastFieldKey = $fieldKey;
					$this->lastValue = $value;
				}
			};

			$result = $localobject->setFieldValue($user, 'test_field', $value, true);
			$this->assertTrue($result, "setFieldValue should succeed for $type");
			$this->assertTrue($localobject->hookCalled, "onFieldValueChanged should be called for $type");
			$this->assertSame('test_field', $localobject->lastFieldKey, "Field key should be 'test_field' for $type");
			$this->assertSame($value, $localobject->lastValue, "Value should match for $type");
		}

		print __METHOD__." OK\n";
	}
}
