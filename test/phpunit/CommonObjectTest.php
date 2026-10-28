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

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/defaultvalues.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * DefaultValues that records the trigger codes it emits instead of running the triggers
 */
class DefaultValuesTriggerSpy extends DefaultValues
{
	/**
	 * @var string[]	Trigger codes emitted, in call order
	 */
	public $calledTriggers = array();

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Record the trigger code
	 *
	 * @param	string	$triggerName	Trigger code
	 * @param	?User	$user			User
	 * @return	int						Always 0
	 */
	public function call_trigger($triggerName, $user)
	{
		// phpcs:enable
		$this->calledTriggers[] = $triggerName;
		return 0;
	}
}


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
	 *  testGetRightsForUserWhenModuleIsSet
	 *  The module of the object is set by fetchObjectByElement(). A core permission named like the element
	 *  (commande->commande->export) must not be returned as the permissions of the object, else hasUserPermission() is false.
	 *
	 *  @return void
	 */
	public function testGetRightsForUserWhenModuleIsSet()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertTrue(isModEnabled('order'), 'Module order must be enabled for this test');

		$savrights = isset($user->rights->commande) ? $user->rights->commande : null;

		$localobject = new Commande($db);
		$localobject->module = 'commande'; // As set by fetchObjectByElement()

		$user->rights->commande = new stdClass();
		$user->rights->commande->lire = 1;
		$user->rights->commande->creer = 1;
		$user->rights->commande->commande = new stdClass();
		$user->rights->commande->commande->export = 1;
		$this->assertSame($user->rights->commande, $localobject->getRightsForUser($user), 'Permissions of the core module');
		$this->assertTrue($localobject->hasUserPermission($user, 'write'), 'Write permission of the core module');

		// Object of a module built with ModuleBuilder, named like its module
		$user->rights->commande->commande->read = 1;
		$user->rights->commande->commande->write = 1;
		$this->assertSame($user->rights->commande->commande, $localobject->getRightsForUser($user), 'Permissions of the object of a ModuleBuilder module');

		$user->rights->commande = $savrights;

		print __METHOD__." OK\n";
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
	 *  testShowOutputFieldAndValidateFieldWithDoublePrecision
	 *
	 *  Type double(xx,yy), used by amounts of documents, must be managed like double.
	 *
	 *  @return void
	 */
	public function testShowOutputFieldAndValidateFieldWithDoublePrecision()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);

		$fields = array(
			'total_ht' => array('type' => 'double(24,8)', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => 1, 'position' => 10, 'isameasure' => 1),
		);

		$this->assertSame(price('58.40000000'), $localobject->showOutputField($fields['total_ht'], 'total_ht', '58.40000000'), 'double(24,8) is output like double');
		$this->assertSame($localobject->showOutputField(array('type' => 'double') + $fields['total_ht'], 'total_ht', '58.40000000'), $localobject->showOutputField($fields['total_ht'], 'total_ht', '58.40000000'));

		$this->assertTrue($localobject->validateField($fields, 'total_ht', '58.4'), 'Numeric value is valid');
		$this->assertFalse($localobject->validateField($fields, 'total_ht', 'abc'), 'double(24,8) is validated as numeric');

		print __METHOD__." OK\n";
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

		$result = $localobject->setFieldValue($user, 'ref', 'AA2501-0001', /* byPassUserPermission */ true);

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

		$result = $localobject->setFieldValue($user, 'status', 2, /* byPassUserPermission */ true);

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

		$result = $localobject->setFieldValue($user, 'total_ht', 100.0, /* byPassUserPermission */ true);

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
					$this->setFieldValue($user, 'total_ttc', ((float) $value) * 1.2, /* byPassUserPermission */ true);
				} elseif ($fieldKey === 'total_ttc') {
					$this->setFieldValue($user, 'total_ht', ((float) $value) / 1.2, /* byPassUserPermission */ true);
				}
			}
		};

		$result = $localobject->setFieldValue($user, 'total_ht', 100.0, /* byPassUserPermission */ true);

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

			/**
			 * Constructor
			 *
			 * @param DoliDB $db      Database handler
			 * @param string $oldProp Old property name
			 * @param string $newProp New property name
			 */
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
		$result = $localobject->setFieldValue($user, $oldProp, $value, /* byPassUserPermission */ true);

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
		$result1 = $localobject->setFieldValue($user, 'old_field', 'deprecated_value', /* byPassUserPermission */ true);
		$this->assertTrue($result1, 'setFieldValue should succeed for child deprecated property old_field');
		$this->assertSame('deprecated_value', $localobject->old_field, 'old_field should have the set value via magic method');
		$this->assertSame('deprecated_value', $localobject->new_field, 'new_field should be synchronized with old_field');

		// Test another child-specific deprecated property: legacy_name (not declared) -> current_name (declared)
		$result2 = $localobject->setFieldValue($user, 'legacy_name', 'legacy_value', /* byPassUserPermission */ true);
		$this->assertTrue($result2, 'setFieldValue should succeed for child deprecated property legacy_name');
		$this->assertSame('legacy_value', $localobject->legacy_name, 'legacy_name should have the set value via magic method');
		$this->assertSame('legacy_value', $localobject->current_name, 'current_name should be synchronized with legacy_name');

		// Test that parent deprecated properties still work (statut -> status, inherited from CommonObject)
		$result3 = $localobject->setFieldValue($user, 'statut', 1, /* byPassUserPermission */ true);
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

			/**
			 * Constructor
			 *
			 * @param DoliDB $db          Database handler
			 * @param string $fieldName   Field name
			 * @param array  $fieldConfig Field configuration
			 */
			public function __construct($db, $fieldName, $fieldConfig)
			{
				$this->db = $db;
				if (!empty($fieldConfig)) {
					$this->fields[$fieldName] = $fieldConfig;
				}
			}
		};

		$result = $localobject->isFieldEditAllowed($user, $fieldName, /* byPassUserPermission */ true);

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
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 0;
			public $statut = 0;

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

		// Use reflection to test protected method
		$reflection = new ReflectionClass($obj1);
		$method = $reflection->getMethod('isFieldBlockedByObjectState');
		$method->setAccessible(true);

		$result1 = $method->invoke($obj1, 'ref');
		$this->assertFalse($result1, 'Field should NOT be blocked when both status and statut are 0 (draft)');

		// Test 2: status is non-zero, statut is 0 - field SHOULD be blocked
		$obj2 = new class ($db) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;
			public $statut = 0;

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

		$method->setAccessible(true);
		$result2 = $method->invoke($obj2, 'ref');
		$this->assertTrue($result2, 'Field SHOULD be blocked when status is non-zero');

		// Test 3: status is 0, statut is non-zero - field SHOULD be blocked
		$obj3 = new class ($db) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 0;
			public $statut = 1;

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

		$method->setAccessible(true);
		$result3 = $method->invoke($obj3, 'ref');
		$this->assertTrue($result3, 'Field SHOULD be blocked when statut is non-zero');

		// Test 4: Both are non-zero - field SHOULD be blocked
		$obj4 = new class ($db) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;
			public $statut = 1;

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

		$method->setAccessible(true);
		$result4 = $method->invoke($obj4, 'ref');
		$this->assertTrue($result4, 'Field SHOULD be blocked when both are non-zero');

		// Test 5: Field with alwayseditable flag should NEVER be blocked
		$obj5 = new class ($db) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'alwayseditable' => 1)
			);
			public $status = 1;
			public $statut = 1;

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

		$method->setAccessible(true);
		$result5 = $method->invoke($obj5, 'ref');
		$this->assertFalse($result5, 'Field with alwayseditable=1 should NEVER be blocked');

		// Test 6: Object without STATUS_DRAFT constant (no draft concept) - field should NOT be blocked
		$obj6 = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;
			public $statut = 1;

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

		$result6 = $method->invoke($obj6, 'ref');
		$this->assertFalse($result6, 'Field should NOT be blocked when object has no STATUS_DRAFT constant');

		// Test 7: Only status is set (ModuleBuilder objects never fill statut) - field SHOULD be blocked
		$obj7 = new class ($db) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array('ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1));
			public $status = 1;

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

		$result7 = $method->invoke($obj7, 'ref');
		$this->assertTrue($result7, 'Field SHOULD be blocked when status is non-draft and statut is null');

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
				 * Callback triggered when a field value changes
				 *
				 * @param  string $fieldKey Field key
				 * @param  mixed  $value    New value
				 * @return void
				 */
				protected function onFieldValueChanged($fieldKey, $value)
				{
					$this->hookCalled = true;
					$this->lastFieldKey = $fieldKey;
					$this->lastValue = $value;
				}
			};

			$result = $localobject->setFieldValue($user, 'test_field', $value, /* byPassUserPermission */ true);
			$this->assertTrue($result, "setFieldValue should succeed for $type");
			$this->assertTrue($localobject->hookCalled, "onFieldValueChanged should be called for $type");
			$this->assertSame('test_field', $localobject->lastFieldKey, "Field key should be 'test_field' for $type");
			$this->assertSame($value, $localobject->lastValue, "Value should match for $type");
		}

		print __METHOD__." OK\n";
	}

	/**
	 * isFieldBlockedByObjectState() and hasUserWritePermissionOnField() on core child classes.
	 * Documents (Commande, Facture, Propal) are locked outside draft, Product and Societe have no draft status.
	 * Their write permission is "creer", not the generic "write" of ModuleBuilder objects.
	 *
	 * @return void
	 */
	public function testFieldEditRulesOnCoreChildClasses()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$documents = array(
			'Commande' => array(new Commande($db), 'commande'),
			'Facture' => array(new Facture($db), 'facture'),
			'Propal' => array(new Propal($db), 'propal'),
		);
		foreach ($documents as $classname => $def) {
			list($object, $module) = $def;
			$method = new ReflectionMethod($object, 'isFieldBlockedByObjectState');
			$method->setAccessible(true);

			$object->status = $object->statut = $object::STATUS_DRAFT;
			$this->assertFalse($method->invoke($object, 'ref_ext'), $classname.' draft must not be blocked');

			$object->status = $object->statut = $object::STATUS_VALIDATED;
			$this->assertTrue($method->invoke($object, 'ref_ext'), $classname.' validated must be blocked');
			$this->assertFalse($method->invoke($object, 'ref_client'), $classname.' ref_client is alwayseditable, validated must not block it');

			$this->assertSame((bool) $user->hasRight($module, 'creer'), $object->hasUserWritePermissionOnField($user, 'ref_client'), $classname.' must use the creer permission');
			$this->assertFalse($object->hasUserWritePermissionOnField($user, ''), $classname.' empty field must be refused');
		}

		// The private note is never editable by an external user, even with the write permission
		$externaluser = clone $user;
		$externaluser->socid = 1;
		$propal = new Propal($db);
		$propal->status = $propal->statut = Propal::STATUS_DRAFT;
		$this->assertFalse($propal->isFieldEditableFromUi($externaluser, 'note_private'), 'Private note must not be editable from UI by an external user');
		$this->assertSame((bool) $user->hasRight('propal', 'creer'), $propal->isFieldEditableFromUi($externaluser, 'note_public'), 'Public note follows the write permission for an external user');

		$product = new Product($db);
		$product->type = Product::TYPE_PRODUCT;
		$product->status = 1;
		$method = new ReflectionMethod($product, 'isFieldBlockedByObjectState');
		$method->setAccessible(true);
		$this->assertFalse($method->invoke($product, 'label'), 'Product on sale must not be blocked by state');
		$this->assertSame((bool) $user->hasRight('produit', 'creer'), $product->hasUserWritePermissionOnField($user, 'label'));
		$product->type = Product::TYPE_SERVICE;
		$this->assertSame((bool) $user->hasRight('service', 'creer'), $product->hasUserWritePermissionOnField($user, 'label'));

		$societe = new Societe($db);
		$societe->status = 1;
		$method = new ReflectionMethod($societe, 'isFieldBlockedByObjectState');
		$method->setAccessible(true);
		$this->assertFalse($method->invoke($societe, 'name_alias'), 'Active thirdparty must not be blocked by state');
		$this->assertSame((bool) $user->hasRight('societe', 'creer'), $societe->hasUserWritePermissionOnField($user, 'name_alias'));

		print __METHOD__." OK\n";
	}

	/**
	 * Data provider for testIsFieldEditableFromUi
	 *
	 * @return array<string,array{0:array<string,mixed>,1:int,2:bool,3:bool}>	field config, object status, user has write permission, expected result
	 */
	public function fieldEditableFromUiProvider()
	{
		return array(
			'no_uieditable_flag' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1), 0, true, false),
			'uieditable_flag_zero' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 0), 0, true, false),
			'uieditable_draft_with_right' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 1), 0, true, true),
			'uieditable_without_right' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 1), 0, false, false),
			'uieditable_noteditable' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 1, 'noteditable' => 1), 0, true, false),
			'uieditable_not_enabled' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 0, 'uieditable' => 1), 0, true, false),
			'uieditable_validated' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 1), 1, true, false),
			'uieditable_alwayseditable_validated' => array(array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'uieditable' => 1, 'alwayseditable' => 1), 1, true, true),
		);
	}

	/**
	 * isFieldEditableFromUi() requires the "uieditable" opt-in in addition to all rules of isFieldEditAllowed()
	 *
	 * @dataProvider fieldEditableFromUiProvider
	 * @param array<string,mixed> $fieldConfig  Field configuration
	 * @param int                 $status       Object status
	 * @param bool                $hasRight     User has write permission
	 * @param bool                $expected     Expected result
	 * @return void
	 */
	public function testIsFieldEditableFromUi($fieldConfig, $status, $hasRight, $expected)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db, $fieldConfig, $status, $hasRight) extends CommonObject {
			const STATUS_DRAFT = 0;
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array();
			/** @var bool */
			public $hasRight;

			/**
			 * Constructor
			 *
			 * @param DoliDB              $db          Database handler
			 * @param array<string,mixed> $fieldConfig Field configuration
			 * @param int                 $status      Object status
			 * @param bool                $hasRight    User has write permission
			 */
			public function __construct($db, $fieldConfig, $status, $hasRight)
			{
				$this->db = $db;
				$this->fields['test_field'] = $fieldConfig;
				$this->status = $status;
				$this->hasRight = $hasRight;
			}

			/**
			 * @param User   $user  User
			 * @param string $field Field name
			 * @param string $mode  Permission to check
			 * @return bool
			 */
			public function hasUserPermissionOnField(User $user, $field, $mode = 'write')
			{
				return $this->hasRight;
			}
		};

		$this->assertSame($expected, $localobject->isFieldEditableFromUi($user, 'test_field'), 'Config: '.json_encode($fieldConfig).', status '.$status.', right '.json_encode($hasRight));
		$this->assertFalse($localobject->isFieldEditableFromUi($user, 'undefined_field'), 'Undefined field must not be editable from UI');

		print __METHOD__." OK\n";
	}

	/**
	 * Fields opened to the user interface on core classes: only declared safe fields, never computed or technical ones
	 *
	 * @return void
	 */
	public function testIsFieldEditableFromUiOnCoreClasses()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$documents = array(
			'Commande' => array(new Commande($db), 'commande'),
			'Facture' => array(new Facture($db), 'facture'),
			'Propal' => array(new Propal($db), 'propal'),
		);
		foreach ($documents as $classname => $def) {
			list($object, $module) = $def;
			$canWrite = (bool) $user->hasRight($module, 'creer');

			foreach (array($object::STATUS_DRAFT, $object::STATUS_VALIDATED) as $status) {
				$object->status = $object->statut = $status;
				foreach (array('note_public', 'note_private', 'ref_client') as $field) {
					$this->assertSame($canWrite, $object->isFieldEditableFromUi($user, $field), $classname.' '.$field.' status '.$status);
				}
				foreach (array('ref', 'ref_ext', 'total_ht', 'total_tva', 'total_ttc', 'fk_statut', 'fk_soc', 'entity') as $field) {
					$this->assertFalse($object->isFieldEditableFromUi($user, $field), $classname.' '.$field.' must not be editable from UI, status '.$status);
				}
			}
		}

		$product = new Product($db);
		$product->type = Product::TYPE_PRODUCT;
		$this->assertSame((bool) $user->hasRight('produit', 'creer'), $product->isFieldEditableFromUi($user, 'note_public'));
		foreach (array('ref', 'label', 'entity') as $field) {
			$this->assertFalse($product->isFieldEditableFromUi($user, $field), 'Product '.$field.' must not be editable from UI');
		}

		$societe = new Societe($db);
		$societe->status = 1;
		foreach (array('note_public', 'note_private') as $field) {
			$this->assertSame((bool) $user->hasRight('societe', 'creer'), $societe->isFieldEditableFromUi($user, $field));
		}
		foreach (array('nom', 'code_client', 'entity') as $field) {
			$this->assertFalse($societe->isFieldEditableFromUi($user, $field), 'Societe '.$field.' must not be editable from UI');
		}

		print __METHOD__." OK\n";
	}

	/**
	 * setFieldValue() and getFieldUiValue() use the property holding a field when its name differs from the field
	 * (the field fk_opp_status of a project is in the property opp_status)
	 *
	 * @return void
	 */
	public function testFieldPropertyName()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		$project = new Project($db);
		$project->opp_status = 3;
		$this->assertSame(3, $project->getFieldUiValue('fk_opp_status'), 'Value read in the property opp_status');
		$this->assertSame($project->title, $project->getFieldUiValue('title'), 'Other fields are read in the property of the same name');
		$this->assertNull($project->getFieldUiValue('undefined_field'));

		$project->fields['fk_opp_status']['enabled'] = 1;
		$project->status = Project::STATUS_DRAFT;
		$this->assertTrue($project->setFieldValue($user, 'fk_opp_status', 2, /* byPassUserPermission */ true), $project->getFieldError('fk_opp_status'));
		$this->assertSame(2, $project->opp_status, 'Value set in the property opp_status');
		$this->assertSame(2, $project->getFieldUiValue('fk_opp_status'));

		print __METHOD__." OK\n";
	}

	/**
	 * Data provider for testIsFieldReadableFromUi
	 *
	 * @return array<string,array{0:string,1:array<string,mixed>,2:bool}>
	 */
	public function fieldReadableFromUiProvider()
	{
		return array(
			'visible_card' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => 1), true),
			'visible_card_not_list' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => -1), true),
			'visible_view_only' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => 5), true),
			'visible_expression_true' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => '1 + 0'), true),
			'visible_expression_false' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => '0 + 0'), false),
			'visible_list_only' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => 2), false),
			'hidden' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1, 'visible' => 0), false),
			'no_visible_attribute' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 1), false),
			'hidden_but_uieditable' => array('test_field', array('type' => 'html', 'label' => 'Test', 'enabled' => 1, 'visible' => 0, 'uieditable' => 1), true),
			'not_enabled' => array('test_field', array('type' => 'varchar(30)', 'label' => 'Test', 'enabled' => 0, 'visible' => 1), false),
			'password' => array('test_field', array('type' => 'password', 'label' => 'Test', 'enabled' => 1, 'visible' => 1), false),
		);
	}

	/**
	 * isFieldReadableFromUi() allows only enabled fields visible on the card, or editable from the user interface
	 *
	 * @dataProvider fieldReadableFromUiProvider
	 * @param string              $fieldName   Field name
	 * @param array<string,mixed> $fieldConfig Field configuration
	 * @param bool                $expected    Expected result
	 * @return void
	 */
	public function testIsFieldReadableFromUi($fieldName, $fieldConfig, $expected)
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

			/**
			 * Constructor
			 *
			 * @param DoliDB              $db          Database handler
			 * @param string              $fieldName   Field name
			 * @param array<string,mixed> $fieldConfig Field configuration
			 */
			public function __construct($db, $fieldName, $fieldConfig)
			{
				$this->db = $db;
				$this->fields[$fieldName] = $fieldConfig;
			}
		};

		$this->assertSame($expected, $localobject->isFieldReadableFromUi($user, $fieldName), 'Config: '.json_encode($fieldConfig));
		$this->assertFalse($localobject->isFieldReadableFromUi($user, 'undefined_field'), 'Undefined field must not be readable from UI');

		print __METHOD__." OK\n";
	}

	/**
	 * Fields readable from the user interface on a core class: totals and notes, but not the private note for external users
	 *
	 * @return void
	 */
	public function testIsFieldReadableFromUiOnCoreClasses()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

		$propal = new Propal($db);
		foreach (array('ref', 'ref_client', 'total_ht', 'total_tva', 'total_ttc', 'note_public', 'note_private') as $field) {
			$this->assertTrue($propal->isFieldReadableFromUi($user, $field), 'Propal '.$field.' must be readable from UI');
		}
		$this->assertFalse($propal->isFieldReadableFromUi($user, 'import_key'), 'Propal import_key is hidden');

		$externaluser = clone $user;
		$externaluser->socid = 1;
		$this->assertTrue($propal->isFieldReadableFromUi($externaluser, 'note_public'));
		$this->assertFalse($propal->isFieldReadableFromUi($externaluser, 'note_private'), 'The private note is hidden to external users');

		print __METHOD__." OK\n";
	}

	/**
	 * getFieldValueFromAjaxPost() reads the inputs named ajaxfield_<field> with a filter matching the type of field
	 *
	 * @return void
	 */
	public function testGetFieldValueFromAjaxPost()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new class ($db) extends CommonObject {
			public $element = 'testobject';
			public $table_element = 'testobject';
			public $fields = array(
				'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'position' => 10),
				'note' => array('type' => 'html', 'label' => 'Note', 'enabled' => 1, 'visible' => 1, 'position' => 20),
				'qty' => array('type' => 'integer', 'label' => 'Qty', 'enabled' => 1, 'visible' => 1, 'position' => 30),
				'date_start' => array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'visible' => 1, 'position' => 40),
				'date_end' => array('type' => 'datetime', 'label' => 'DateEnd', 'enabled' => 1, 'visible' => 1, 'position' => 50),
				'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 1, 'position' => 60),
				'amount' => array('type' => 'price', 'label' => 'Amount', 'enabled' => 1, 'visible' => 1, 'position' => 70),
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

		$savpost = $_POST;
		$_POST = array(
			'ajaxfield_label' => 'My <b>label</b>',
			'ajaxfield_note' => '<b>Bold</b><script>alert(1)</script>',
			'ajaxfield_qty' => '12',
			'ajaxfield_date_startday' => '15', 'ajaxfield_date_startmonth' => '3', 'ajaxfield_date_startyear' => '2026',
			'ajaxfield_date_endday' => '15', 'ajaxfield_date_endmonth' => '3', 'ajaxfield_date_endyear' => '2026', 'ajaxfield_date_endhour' => '14', 'ajaxfield_date_endmin' => '30',
			'ajaxfield_fk_soc' => '1',
			'ajaxfield_amount' => '10',
			'value' => 'Not read, inputs are prefixed',
		);

		$this->assertSame('My label', $localobject->getFieldValueFromAjaxPost('label'), 'varchar is filtered with alphanohtml');
		$this->assertStringContainsString('<b>Bold</b>', $localobject->getFieldValueFromAjaxPost('note'));
		$this->assertStringNotContainsString('<script', $localobject->getFieldValueFromAjaxPost('note'), 'html is filtered with restricthtml');
		$this->assertSame(12, $localobject->getFieldValueFromAjaxPost('qty'));
		$this->assertEquals(dol_mktime(12, 0, 0, 3, 15, 2026), $localobject->getFieldValueFromAjaxPost('date_start'), 'Dates at noon like the cards');
		$this->assertEquals(dol_mktime(14, 30, 0, 3, 15, 2026), $localobject->getFieldValueFromAjaxPost('date_end'));
		$this->assertSame(1, $localobject->getFieldValueFromAjaxPost('fk_soc'), 'Links are read as an id');
		$this->assertNull($localobject->getFieldValueFromAjaxPost('amount'), 'Amounts are not supported yet');

		$_POST = array('ajaxfield_label' => '');
		$this->assertSame('', $localobject->getFieldValueFromAjaxPost('label'), 'An empty input empties the field');

		$_POST = array('value' => 'Not read');
		$this->assertNull($localobject->getFieldValueFromAjaxPost('label'), 'A missing input must never empty a field');
		$this->assertNull($localobject->getFieldValueFromAjaxPost('date_start'));

		$_POST = $savpost;

		print __METHOD__." OK\n";
	}

	/**
	 * ajaxOutputField() returns the output of showOutputField(), also for fields hidden from generic forms like notes
	 *
	 * @return void
	 */
	public function testAjaxOutputField()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

		$propal = new Propal($db);
		$propal->ref_client = 'REF<CLIENT>';
		$propal->note_public = '<b>Public note</b>';

		$this->assertSame($propal->showOutputField($propal->fields['ref_client'], 'ref_client', $propal->ref_client), $propal->ajaxOutputField('ref_client'));
		$this->assertStringNotContainsString('<CLIENT>', $propal->ajaxOutputField('ref_client'), 'Output is escaped');
		$this->assertStringContainsString('<b>Public note</b>', $propal->ajaxOutputField('note_public'), 'Note (visible = 0) is output');

		print __METHOD__." OK\n";
	}

	/**
	 * ajaxInputField() returns inputs named ajaxfield_<field>, read back by getFieldValueFromAjaxPost(), also for notes
	 *
	 * @return void
	 */
	public function testAjaxInputField()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

		$propal = new Propal($db);
		$propal->ref_client = 'REF"CLIENT';
		$propal->note_public = 'Public note';

		$input = $propal->ajaxInputField('ref_client');
		$this->assertStringContainsString('name="ajaxfield_ref_client"', $input);
		$this->assertStringContainsString('value="REF&quot;CLIENT"', $input, 'Value is escaped');

		// Constant used by DolEditor, defined by main.inc.php only
		if (!defined('ROWS_3')) {
			define('ROWS_3', 3);
		}
		$input = $propal->ajaxInputField('note_public');
		$this->assertSame(1, preg_match('/<textarea[^>]*name="ajaxfield_note_public"/', $input), 'Note (visible = 0) has an input');	// Not assertMatchesRegularExpression(), only available since PHPUnit 9.1
		$this->assertStringContainsString('Public note', $input);

		print __METHOD__." OK\n";
	}

	/**
	 * updateFieldFromUi() saves only fields editable from the user interface, with the dedicated business method when there is one
	 *
	 * @return void
	 */
	public function testUpdateFieldFromUi()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$localobject->initAsSpecimen();
		$localobject->ref = '';
		$localobject->ref_client = '';
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, 'create: '.$localobject->error);
		$localobject = new Commande($db);
		$localobject->fetch($id);

		// Field without the opt-in "uieditable"
		$this->assertLessThan(0, $localobject->updateFieldFromUi($user, 'ref', 'HACKED'));
		$this->assertNotEmpty($localobject->error);

		// User without the write permission
		$userwithoutright = new User($db);
		$this->assertLessThan(0, $localobject->updateFieldFromUi($userwithoutright, 'note_public', 'Not allowed'));

		// Note saved with update_note()
		$this->assertGreaterThan(0, $localobject->updateFieldFromUi($user, 'note_public', '<b>Public note</b>'), $localobject->error);
		$checkobject = new Commande($db);
		$checkobject->fetch($id);
		$this->assertSame('<b>Public note</b>', $checkobject->note_public);

		// Field saved with the default setValueFrom(), blocked when object is not a draft
		$localobject->fields['ref_ext']['uieditable'] = 1;
		$localobject->status = $localobject->statut = Commande::STATUS_VALIDATED;
		$this->assertLessThan(0, $localobject->updateFieldFromUi($user, 'ref_ext', 'REFEXT-LOCKED'));
		$localobject->status = $localobject->statut = Commande::STATUS_DRAFT;
		$this->assertGreaterThan(0, $localobject->updateFieldFromUi($user, 'ref_ext', 'REFEXT-'.$id), $localobject->error);
		$checkobject->fetch($id);
		$this->assertSame('REFEXT-'.$id, $checkobject->ref_ext);

		// Customer ref saved with set_ref_client(), that refuses a ref already used by another order of the same thirdparty
		$refclient = 'REFCLIENT-UI-'.$id;
		$this->assertGreaterThan(0, $localobject->updateFieldFromUi($user, 'ref_client', $refclient), $localobject->error);
		$checkobject->fetch($id);
		$this->assertSame($refclient, $checkobject->ref_client);

		$otherobject = new Commande($db);
		$otherobject->initAsSpecimen();
		$otherobject->socid = $localobject->socid;
		$otherobject->ref = '';
		$otherobject->ref_client = '';
		$otherid = $otherobject->create($user);
		$this->assertGreaterThan(0, $otherid, 'create: '.$otherobject->error);
		$otherobject = new Commande($db);
		$otherobject->fetch($otherid);
		if (!getDolGlobalString('ORDER_ALLOW_DUPLICATE_REF_CLIENT')) {
			$this->assertLessThan(0, $otherobject->updateFieldFromUi($user, 'ref_client', $refclient));
			$this->assertNotEmpty($otherobject->error);
			$this->assertSame('', (string) $otherobject->ref_client, 'Value in memory must be restored when save fails');
		}

		$otherobject->delete($user);
		$localobject->delete($user);

		print __METHOD__." OK\n";
	}

	/**
	 * The opportunity status of a project is changed from the kanban view (core/ajax/objectfield.php), also when the project is open
	 *
	 * @return void
	 */
	public function testUpdateOpportunityStatusOfProjectFromUi()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$savopportunities = getDolGlobalString('PROJECT_USE_OPPORTUNITIES');
		$conf->global->PROJECT_USE_OPPORTUNITIES = 1;
		// The module and the write permission may be missing in the database of the CI
		$savmodules = $conf->modules;
		$savrights = isset($user->rights->projet) ? clone $user->rights->projet : null;
		$conf->modules['projet'] = 'projet';
		if (!isset($user->rights->projet)) {
			$user->rights->projet = new stdClass();
		}
		$user->rights->projet->creer = 1;

		$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE active = 1 ORDER BY rowid DESC LIMIT 1");
		$obj = $resql ? $db->fetch_object($resql) : null;
		$this->assertNotEmpty($obj, 'A status of opportunity is required');
		$oppstatus = (int) $obj->rowid;

		$localobject = new Project($db);
		$localobject->initAsSpecimen();
		$localobject->ref = 'PJUI-'.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand(100, 999);
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, 'create: '.$localobject->error);
		$localobject = new Project($db);
		$localobject->fetch($id);
		$localobject->setValid($user);

		$this->assertTrue($localobject->isFieldEditableFromUi($user, 'fk_opp_status'), 'Opportunity status editable from UI on an open project');
		$this->assertFalse($localobject->isFieldEditableFromUi($user, 'ref'), 'Ref of a project must not be editable from UI');
		$this->assertFalse($localobject->isFieldEditableFromUi(new User($db), 'fk_opp_status'), 'User without permission');

		$this->assertGreaterThan(0, $localobject->updateFieldFromUi($user, 'fk_opp_status', $oppstatus), $localobject->errorsToString());
		$checkobject = new Project($db);
		$checkobject->fetch($id);
		$this->assertSame($oppstatus, (int) $checkobject->opp_status);
		$code = dol_getIdFromCode($db, $oppstatus, 'c_lead_status', 'rowid', 'code');
		$this->assertSame(dol_escape_htmltag($langs->trans('OppStatus'.$code)), $checkobject->ajaxOutputField('fk_opp_status'), 'Status shown with its label');

		$localobject->delete($user);
		$conf->global->PROJECT_USE_OPPORTUNITIES = $savopportunities;
		$conf->modules = $savmodules;
		if ($savrights === null) {
			unset($user->rights->projet);
		} else {
			$user->rights->projet = $savrights;
		}

		print __METHOD__." OK\n";
	}

	/**
	 * The level of a line of evaluation is editable from the user interface only while its evaluation is a draft,
	 * and only with a value in the range of levels
	 *
	 * @return void
	 */
	public function testEvaluationLineFromUi()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluationdet.class.php';
		require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';

		$line = new EvaluationLine($db);
		$this->assertSame('Evaluation', getElementProperties($line->parent_element)['classname'], 'Parent of a line is the evaluation');

		$line->fk_evaluation = 0;
		$this->assertFalse($line->isFieldEditableFromUi($user, 'rankorder'), 'Line without its evaluation is not editable');
		$this->assertFalse($line->isFieldEditableFromUi($user, 'required_rank'), 'Only the level is editable from UI');

		$maxrank = getDolGlobalInt('HRM_MAXRANK', Skill::DEFAULT_MAX_RANK_PER_SKILL);
		$savpost = $_POST;
		foreach (array(-1 => -1, 0 => 0, $maxrank => $maxrank, $maxrank + 1 => null, -2 => null) as $posted => $expected) {
			$_POST = array('ajaxfield_rankorder' => (string) $posted);
			$this->assertSame($expected, $line->getFieldValueFromAjaxPost('rankorder'), 'Level '.$posted);
		}
		$_POST = $savpost;

		print __METHOD__." OK\n";
	}

	/**
	 *  testCrudCommonTriggerCodesUseTriggerPrefix
	 *
	 *  @return void
	 */
	public function testCrudCommonTriggerCodesUseTriggerPrefix()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DefaultValuesTriggerSpy($db);
		$localobject->TRIGGER_PREFIX = 'MYMODULE_MYOBJECT';

		$this->assertSame(
			array('MYMODULE_MYOBJECT_CREATE', 'MYMODULE_MYOBJECT_MODIFY', 'MYMODULE_MYOBJECT_DELETE'),
			$this->runCrudCommon($localobject, 'triggerprefixset')
		);

		print __METHOD__." OK\n";
	}

	/**
	 *  testCrudCommonTriggerCodesFallBackToClassName
	 *
	 *  @return void
	 */
	public function testCrudCommonTriggerCodesFallBackToClassName()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new DefaultValuesTriggerSpy($db);

		$this->assertSame(
			array('DEFAULTVALUESTRIGGERSPY_CREATE', 'DEFAULTVALUESTRIGGERSPY_MODIFY', 'DEFAULTVALUESTRIGGERSPY_DELETE'),
			$this->runCrudCommon($localobject, 'triggerprefixempty')
		);

		print __METHOD__." OK\n";
	}

	/**
	 * Create, update then delete the object with createCommon(), updateCommon() and deleteCommon()
	 *
	 * @param	DefaultValuesTriggerSpy	$localobject	Object to process
	 * @param	string					$param			Value of the param field, unique per test
	 * @return	string[]								Business trigger codes emitted, OBJECT_LINK_* excluded
	 */
	private function runCrudCommon(DefaultValuesTriggerSpy $localobject, $param)
	{
		global $user;

		$localobject->type = 'createform';
		$localobject->user_id = 0;
		$localobject->page = 'test/phpunit/CommonObjectTest.php';
		$localobject->param = $param;
		$localobject->value = 'A';
		$this->assertGreaterThan(0, $localobject->create($user), 'create: '.$localobject->error.implode(',', $localobject->errors));

		$localobject->value = 'B';
		$this->assertGreaterThan(0, $localobject->update($user), 'update: '.$localobject->error.implode(',', $localobject->errors));

		$this->assertGreaterThan(0, $localobject->delete($user), 'delete: '.$localobject->error.implode(',', $localobject->errors));

		return array_values(array_filter($localobject->calledTriggers, function ($code) {
			return strpos($code, 'OBJECT_LINK_') !== 0;
		}));
	}
}
