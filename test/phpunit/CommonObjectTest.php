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

		$localobject->status = 1;
		$localobject->project = null;
		$localobject->fk_project = 42;
		$localobject->origin_object = null;
		$localobject->totalpaid = 100;

		$result = $localobject->setFieldValue($user, 'ref', 'AA2501-0001', true);

		$this->assertTrue($result);
		$this->assertSame('AA2501-0001', $localobject->ref);
		$this->assertSame(1, $localobject->status, 'status must not be altered when setting an unrelated field');
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
}
