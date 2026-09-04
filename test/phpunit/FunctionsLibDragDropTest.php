<?php
/* Copyright (C) 2026 ATM Consulting
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
 *      \file       test/phpunit/FunctionsLibDragDropTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test of the directory resolution used by the drag and drop of a file on a card.
 *		\remarks	To run this script as CLI:  phpunit FunctionsLibDragDropTest.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}

/**
 * Class for PHPUnit tests of the directory resolution used by the drag and drop of a file on a card
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FunctionsLibDragDropTest extends CommonClassTest
{
	/**
	 * The string returned by getMultidirOutput() when it does not know the module of the object.
	 * It is a relative path, so writing into it creates files under the web root.
	 */
	const SENTINEL = 'error-diroutput-not-defined-for-this-object=';

	/**
	 * The elements equipped with the drag and drop of a file on their card by this work.
	 * Their 'dir_output' must always be usable to forge an absolute path.
	 *
	 * @return array<int,array<int,string>>
	 */
	public static function providerEquippedElements()
	{
		$elements = array(
			'contact', 'product', 'societe', 'action', 'expedition', 'reception', 'don', 'expensereport',
			'holiday', 'mo', 'partnership', 'stocktransfer', 'productlot', 'resource', 'workstation',
			'job', 'position', 'skill', 'evaluation', 'knowledgerecord', 'conferenceorbooth', 'asset',
			'payment', 'payment_supplier', 'payment_various', 'salary', 'chargesociales', 'project_task',
		);
		$out = array();
		foreach ($elements as $element) {
			$out[$element] = array($element);
		}
		return $out;
	}


	//
	// getMultidirOutput()
	//

	/**
	 * getMultidirOutput() answers a sentinel string, not an empty string, when it does not know the module.
	 * Every caller must reject it, so the contract is asserted here once.
	 *
	 * @return void
	 */
	public function testGetMultidirOutputReturnsARelativeSentinelOnFailure()
	{
		$object = new stdClass();
		$object->element = 'anelementthatdoesnotexist';
		$object->id = 1;
		$object->entity = 1;

		$dir = getMultidirOutput($object, 'anelementthatdoesnotexist');

		$this->assertSame(self::SENTINEL.'anelementthatdoesnotexist', $dir, 'The failure of getMultidirOutput must be reported by the sentinel string');
		$this->assertStringStartsNotWith('/', $dir, 'The sentinel is a relative path, so it must never be used to forge a path');

		// The 'temp' mode has its own sentinel
		$this->assertSame('error-dirtemp-not-defined-for-this-object=anelementthatdoesnotexist', getMultidirTemp($object, 'anelementthatdoesnotexist'));

		// And a bad mode has a third one
		$this->assertSame('error-bad-value-for-mode', getMultidirOutput($object, 'anelementthatdoesnotexist', 0, 'notamode'));
	}

	/**
	 * The sentinel is the answer for a majority of the elements equipped with the drag and drop, so any
	 * caller that forges a path from getMultidirOutput() alone is broken. This test documents the list.
	 *
	 * @return void
	 */
	public function testGetMultidirOutputIsUnableToResolveMostEquippedElements()
	{
		$nbresolved = 0;
		$nbsentinel = 0;
		foreach (self::providerEquippedElements() as $row) {
			$element = $row[0];
			if ($element == 'project_task') {
				continue;	// Needs a real object, it calls fetchProject()
			}
			$object = new stdClass();
			$object->element = $element;
			$object->id = 1;
			$object->entity = 1;
			$dir = getMultidirOutput($object, $element);
			if (strpos((string) $dir, self::SENTINEL) === 0) {
				$nbsentinel++;
			} else {
				$nbresolved++;
				$this->assertStringStartsWith(DOL_DATA_ROOT, (string) $dir, 'A resolved directory must be inside DOL_DATA_ROOT for the element '.$element);
			}
		}
		$this->assertGreaterThan(0, $nbsentinel, 'getMultidirOutput is expected to fail on some equipped elements, the fallback on getElementProperties is required');
		$this->assertGreaterThan(0, $nbresolved, 'getMultidirOutput is expected to resolve some equipped elements');
	}

	/**
	 * A module with a directory for the current entity only must not answer an undefined index (so a path
	 * relative to the web root) when the object belongs to another entity.
	 *
	 * @return void
	 */
	public function testGetMultidirOutputFallsBackOnTheCurrentEntity()
	{
		global $conf;

		$object = new Product($conf->db);
		$object->id = 1;
		$object->ref = 'AREF';
		$object->entity = 99;	// No directory is declared for this entity

		$this->assertArrayNotHasKey(99, $conf->product->multidir_output, 'The fixture requires no directory declared for the entity 99');

		$dir = getMultidirOutput($object, 'product');

		$this->assertSame($conf->product->multidir_output[$conf->entity], $dir, 'The directory of the current entity must be used as a fallback');
		$this->assertStringStartsWith(DOL_DATA_ROOT, $dir, 'The fallback must not answer a path relative to the web root');

		// Same fallback for the temporary directory
		$dirtemp = getMultidirTemp($object, 'product');
		$this->assertStringStartsWith(DOL_DATA_ROOT, $dirtemp, 'The temporary directory must not be relative either');
	}

	/**
	 * The subdirectory of a partnership and of a stock transfer must be appended, because it is the one
	 * read by their "Attached files" tab (see partnership_document.php and stocktransfer_document.php).
	 *
	 * @return void
	 */
	public function testGetMultidirOutputAppendsTheSubDirectoryOfTheTab()
	{
		global $conf;

		$expected = array(
			'partnership' => '/partnership/partnership',
			'stocktransfer' => '/stocktransfer/stocktransfer',
			'knowledgerecord' => '/knowledgemanagement/knowledgerecord',
			'expedition' => '/expedition/sending',
		);
		$asserted = 0;
		foreach ($expected as $element => $suffix) {
			if (!isset($conf->{explode('/', ltrim($suffix, '/'))[0]})) {
				continue;
			}
			$object = new stdClass();
			$object->element = $element;
			$object->id = 1;
			$object->entity = $conf->entity;
			$dir = getMultidirOutput($object, $element);
			if (strpos((string) $dir, self::SENTINEL) === 0) {
				continue;	// Module not enabled on this instance
			}
			$this->assertSame(DOL_DATA_ROOT.$suffix, $dir, 'Wrong directory for the element '.$element);
			$asserted++;
		}

		if (!$asserted) {
			// None of the modules of the elements above is enabled, so the test asserted nothing. Say it
			// instead of reporting a green test that checked nothing.
			$this->markTestSkipped('None of the modules partnership, stocktransfer, knowledgemanagement and expedition is enabled');
		}
	}


	//
	// get_exdir()
	//

	/**
	 * get_exdir() is the reference implementation used both by the "Attached files" tabs and by FileUpload
	 * to forge the directory of an object. Its fallbacks must be asserted, they are load bearing.
	 *
	 * @return void
	 */
	public function testGetExdirFallbacks()
	{
		global $db;

		$object = new Product($db);
		$object->id = 42;
		$object->ref = 'MYREF';

		// Nominal case: the ref is used
		$this->assertSame('MYREF', get_exdir(0, 0, 0, 1, $object, 'product'));

		// The trailing slash is added when $withoutslash is 0. FileUpload appends its own '/', so it must
		// call get_exdir() with $withoutslash = 1 to avoid a double slash into the path.
		$this->assertSame('MYREF/', get_exdir(0, 0, 0, 0, $object, 'product'));

		// The id is used as a fallback when the ref is empty (a draft object with no numbering yet)
		$object->ref = '';
		$this->assertSame('42', get_exdir(0, 0, 0, 1, $object, 'product'), 'The id must be used when the ref is empty');
		$object->ref = null;
		$this->assertSame('42', get_exdir(0, 0, 0, 1, $object, 'product'), 'The id must be used when the ref is null');
		$object->ref = '0';
		$this->assertSame('42', get_exdir(0, 0, 0, 1, $object, 'product'), 'A ref "0" is empty for php, the id is used');

		// An object with neither a ref nor an id gives the directory '0', because the id is cast to an int
		// and then to a string. This is a shared directory for every unsaved object, so a caller must never
		// forge a path from an object it did not load: FileUpload throws 'objectnotfound' before this point.
		$empty = new Product($db);
		$empty->id = 0;
		$empty->ref = '';
		$this->assertSame('0', get_exdir(0, 0, 0, 1, $empty, 'product'), 'An object with no id and no ref falls back on the directory "0"');

		// The ref is a user input, it must be sanitized: no directory traversal, no separator
		$object->ref = '../../etc';
		$this->assertStringNotContainsString('..', get_exdir(0, 0, 0, 1, $object, 'product'), 'A ref must never allow a directory traversal');
		$object->ref = 'A/B';
		$this->assertStringNotContainsString('/', get_exdir(0, 0, 0, 1, $object, 'product'), 'A ref must never introduce a sub directory');

		// The modulepart is read from the object when it is not given
		$object->ref = 'MYREF';
		$this->assertSame('MYREF', get_exdir(0, 0, 0, 1, $object), 'The modulepart must be deduced from the object');
	}

	/**
	 * A thirdparty stores its documents into a directory named after its id, because its ref is a company
	 * name and two thirdparties may share the same name.
	 *
	 * @return void
	 */
	public function testGetExdirUsesTheIdForAThirdparty()
	{
		global $db;

		$thirdparty = new Societe($db);
		$thirdparty->id = 7;
		$thirdparty->ref = 'My company';

		$this->assertSame('7', get_exdir(0, 0, 0, 1, $thirdparty, 'societe'), 'The id must be used for a thirdparty');
		$this->assertSame('7', get_exdir(0, 0, 0, 1, $thirdparty, 'thirdparty'), 'The id must be used for a thirdparty');

		// The rule is on the class, not only on the modulepart: a contact of the module 'societe' keeps its ref
		$contact = new Contact($db);
		$contact->id = 8;
		$contact->ref = 'DOE';
		$this->assertSame('DOE', get_exdir(0, 0, 0, 1, $contact, 'contact'), 'A contact is not a thirdparty, its ref is used');
	}

	/**
	 * A module storing its documents on 2 levels answers the level directories only, so the caller must
	 * append the directory of the object itself.
	 *
	 * @return void
	 */
	public function testGetExdirForATwoLevelsModule()
	{
		global $db;

		$object = new Product($db);	// Any object, only the id and the modulepart matter here
		$object->id = 42;
		$object->ref = 'MYREF';

		$this->assertSame('2/4', get_exdir(0, 0, 0, 1, $object, 'invoice_supplier'), 'Two levels of directories are expected');
		$this->assertSame('2/4', get_exdir(0, 0, 0, 1, $object, 'supplier_invoice'), 'The two aliases must answer the same directory');
		$this->assertSame('2/4/', get_exdir(0, 0, 0, 0, $object, 'invoice_supplier'));

		// The levels are built from the id, not from the ref
		$object->id = 1234;
		$this->assertSame('4/3', get_exdir(0, 0, 0, 1, $object, 'invoice_supplier'));
	}


	//
	// getElementProperties()
	//

	/**
	 * The elements added or fixed by this work must answer the class that is really able to load them.
	 *
	 * @return void
	 */
	public function testGetElementPropertiesOfTheNewElements()
	{
		$expected = array(
			// element => array(module, classname, classpath, classfile, table_element)
			'payment' => array('facture', 'Paiement', 'compta/paiement/class', 'paiement', 'paiement'),
			'payment_supplier' => array('fournisseur', 'PaiementFourn', 'fourn/class', 'paiementfourn', 'paiementfourn'),
			'payment_various' => array('bank', 'PaymentVarious', 'compta/bank/class', 'paymentvarious', 'payment_various'),
			'stocktransfer' => array('stocktransfer', 'StockTransfer', 'product/stock/stocktransfer/class', 'stocktransfer', 'stocktransfer_stocktransfer'),
			'job' => array('hrm', 'Job', 'hrm/class', 'job', 'hrm_job'),
			'position' => array('hrm', 'Position', 'hrm/class', 'position', 'hrm_job_user'),
			'skill' => array('hrm', 'Skill', 'hrm/class', 'skill', 'hrm_skill'),
			'evaluation' => array('hrm', 'Evaluation', 'hrm/class', 'evaluation', 'hrm_evaluation'),
		);

		foreach ($expected as $element => $values) {
			$prop = getElementProperties($element);

			$this->assertSame($element, $prop['element'], 'The element '.$element.' must not be rewritten by the myobject_mysubobject rule');
			$this->assertSame($values[0], $prop['module'], 'Wrong module for the element '.$element);
			$this->assertSame($values[1], $prop['classname'], 'Wrong classname for the element '.$element);
			$this->assertSame($values[2], $prop['classpath'], 'Wrong classpath for the element '.$element);
			$this->assertSame($values[3], $prop['classfile'], 'Wrong classfile for the element '.$element);
			$this->assertSame($values[4], $prop['table_element'], 'Wrong table for the element '.$element);

			// The class must really exist, otherwise fetchObjectByElement() ends on a fatal error
			$file = DOL_DOCUMENT_ROOT.'/'.$prop['classpath'].'/'.$prop['classfile'].'.class.php';
			$this->assertFileExists($file, 'The class file of the element '.$element.' does not exist');
			require_once $file;
			$this->assertTrue(class_exists($prop['classname']), 'The class '.$prop['classname'].' of the element '.$element.' does not exist');

			// And the table must exist too, restrictedArea() builds its sql on it.
			// The table of an optional module is only created when the module is enabled, so the check is
			// skipped otherwise: the mapping asserted above does not depend on the module being enabled.
			if (isModEnabled($values[0])) {
				$this->assertGreaterThan(0, $this->countTable($prop['table_element']), 'The table '.$prop['table_element'].' of the element '.$element.' is not readable');
			}
		}
	}

	/**
	 * Count the rows of a table, only to assert the table exists.
	 *
	 * @param	string	$table	Table name without the prefix
	 * @return	int				-1 if the table does not exist, 1 otherwise
	 */
	protected function countTable($table)
	{
		global $db;

		$sql = "SELECT COUNT(*) as nb FROM ".$db->prefix().$db->escape($table);
		$resql = $db->query($sql);
		if (!$resql) {
			return -1;
		}
		$db->free($resql);
		return 1;
	}

	/**
	 * The 'dir_output' of an element must be the directory read by its "Attached files" tab, otherwise a
	 * file uploaded by drag and drop is stored but never shown to the user.
	 *
	 * @return void
	 */
	public function testGetElementPropertiesDirOutputSubDirectory()
	{
		global $conf;

		$expected = array(
			'contact' => array('societe', '/societe/contact', '/societe/temp/contact'),
			'job' => array('hrm', '/hrm/job', '/hrm/temp/job'),
			'position' => array('hrm', '/hrm/position', '/hrm/temp/position'),
			'skill' => array('hrm', '/hrm/skill', '/hrm/temp/skill'),
			'evaluation' => array('hrm', '/hrm/evaluation', '/hrm/temp/evaluation'),
			'conferenceorbooth' => array('eventorganization', '/eventorganization/conferenceorbooth', '/eventorganization/temp/conferenceorbooth'),
		);
		foreach ($expected as $element => $values) {
			if (!isset($conf->{$values[0]})) {
				$this->markTestSkipped('The module '.$values[0].' is not enabled, the fixture is not usable');
			}
			$prop = getElementProperties($element);
			$this->assertSame(DOL_DATA_ROOT.$values[1], $prop['dir_output'], 'Wrong dir_output for the element '.$element);
			$this->assertSame(DOL_DATA_ROOT.$values[2], $prop['dir_temp'], 'Wrong dir_temp for the element '.$element.', the sub directory applies to it too');
		}
	}

	/**
	 * When the module of an element is disabled, its directory must be an empty string and NOT the sub
	 * directory alone: a caller would then read or write into '/contact', at the root of the file system.
	 * This is the regression the guard on $dir_output prevents, and it can only be seen with a module off.
	 *
	 * @return void
	 */
	public function testGetElementPropertiesDirOutputWhenTheModuleIsDisabled()
	{
		global $conf;

		$elements = array('contact' => 'societe', 'job' => 'hrm', 'position' => 'hrm', 'skill' => 'hrm',
			'evaluation' => 'hrm', 'conferenceorbooth' => 'eventorganization');

		foreach ($elements as $element => $module) {
			$savconfmodule = isset($conf->$module) ? $conf->$module : null;
			$savmodules = $conf->modules;

			// Simulate the module being disabled: no entry into $conf and no entry into $conf->modules
			unset($conf->$module);
			unset($conf->modules[$module]);

			try {
				$prop = getElementProperties($element);

				$this->assertSame('', $prop['dir_output'], 'The dir_output of the element '.$element.' must be empty when the module '.$module.' is disabled, not the sub directory alone');
				$this->assertSame('', $prop['dir_temp'], 'The dir_temp of the element '.$element.' must be empty when the module '.$module.' is disabled');
			} finally {
				if ($savconfmodule !== null) {
					$conf->$module = $savconfmodule;
				}
				$conf->modules = $savmodules;
			}
		}
	}

	/**
	 * The directory of any equipped element is either empty or inside DOL_DATA_ROOT. It is never a path
	 * at the root of the file system, and never the sentinel of getMultidirOutput().
	 *
	 * @dataProvider providerEquippedElements
	 *
	 * @param	string	$element	Element to check
	 * @return	void
	 */
	public function testGetElementPropertiesDirOutputIsAlwaysSafe($element)
	{
		$prop = getElementProperties($element);

		foreach (array('dir_output', 'dir_temp') as $key) {
			$dir = (string) $prop[$key];
			$this->assertStringNotContainsString(self::SENTINEL, $dir, 'The '.$key.' of the element '.$element.' must never be the sentinel');
			$this->assertTrue(
				$dir === '' || strpos($dir, DOL_DATA_ROOT) === 0,
				'The '.$key.' of the element '.$element.' must be empty or inside DOL_DATA_ROOT, got "'.$dir.'"'
			);
		}
	}
}
