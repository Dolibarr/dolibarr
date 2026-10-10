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
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/defaultvalues.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

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
	 *  testGetObjectLineHtml
	 *  The HTML of a line must be the same as the one printed by printObjectLines() in the card
	 *
	 *  @return void
	 */
	public function testGetObjectLineHtml()
	{
		global $conf,$user,$langs,$db,$mysoc;
		global $object,$form,$inputalsopricewithtax;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Commande($db);
		$localobject->initAsSpecimen();
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, 'create: '.$localobject->error);

		$localobject = new Commande($db);
		$localobject->fetch($id);
		$localobject->fetch_thirdparty();
		$localobject->getLinesArray();
		$this->assertNotEmpty($localobject->lines);
		$lineid = $localobject->lines[count($localobject->lines) - 1]->id;

		// Lines printed as in the card
		$object = $localobject;
		$form = new Form($db);
		$linesContext = $localobject->initObjectLinesDisplay();
		$this->assertSame(1, $inputalsopricewithtax, 'initObjectLinesDisplay must set the global variables of the line templates');
		ob_start();
		$localobject->printObjectLines('', $linesContext['seller'], $linesContext['buyer'], 0, $linesContext['dateSelector']);
		$cardhtml = ob_get_clean();

		// Line rendered alone, with other global variables of the caller
		$callerobject = new stdClass();
		$object = $callerobject;
		$form = null;
		$inputalsopricewithtax = 0;
		$linehtml = $localobject->getObjectLineHtml($lineid);

		$this->assertStringContainsString('id="row-'.$lineid.'"', $linehtml);
		$this->assertStringContainsString($linehtml, $cardhtml, 'The line must be rendered as in printObjectLines()');
		$this->assertSame($callerobject, $object, 'Global $object of the caller must be restored');
		$this->assertNull($form, 'Global $form of the caller must be restored');
		$this->assertSame(0, $inputalsopricewithtax, 'Global variables of the line templates must be restored');

		// Unknown line
		$this->assertSame('', $localobject->getObjectLineHtml(-1));
		$this->assertSame('ErrorRecordNotFound', $localobject->error);

		$this->assertGreaterThan(0, $localobject->delete($user));

		print __METHOD__." OK\n";
	}

	/**
	 *  testGetObjectLinesDisplayContextSupplier
	 *  On supplier documents, the supplier is the seller
	 *
	 *  @return void
	 */
	public function testGetObjectLinesDisplayContextSupplier()
	{
		global $conf,$user,$langs,$db,$mysoc;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureFournisseur($db);
		$localobject->thirdparty = new Societe($db);

		$context = $localobject->getObjectLinesDisplayContext();
		$this->assertSame($localobject->thirdparty, $context['seller']);
		$this->assertSame($mysoc, $context['buyer']);
		$this->assertSame(1, $context['forceall']);
		$this->assertSame(1, $context['inputalsopricewithtax']);
		$this->assertSame(getDolGlobalInt('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY') ? getDolGlobalInt('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY') : 2, $context['senderissupplier']);

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
