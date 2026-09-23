<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France             <frederic.france@free.fr>
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
 *      \file       test/phpunit/CommonClassTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    Class that extends all PHPunit tests. To share similar code between each test.
 */

// Workaround for false security issue with main.inc.php on Windows in tests:
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	$_SERVER['PHP_SELF'] = "phpunit";
}

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';


// Delete the log file to avoid problem of writing permission on it
@unlink(DOL_DATA_ROOT.'/dolibarr.log');


if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

// Capture the pristine global objects once, at file load time (before any test runs).
// Stored in $GLOBALS because @backupGlobals is disabled, so PHPUnit will not serialize
// them. This replaces the old constructor that saved globals into instance properties:
// the constructor override is not allowed anymore since PHPUnit 10 made
// TestCase::__construct() final, and capturing here (instead of in setUpBeforeClass) is
// robust against subclasses that override setUpBeforeClass() without calling the parent.
$GLOBALS['PHPUNIT_SAVCONF'] = $conf;
$GLOBALS['PHPUNIT_SAVUSER'] = $user;
$GLOBALS['PHPUNIT_SAVLANGS'] = $langs;
$GLOBALS['PHPUNIT_SAVDB'] = $db;
$GLOBALS['PHPUNIT_SAVMYSOC'] = $mysoc;

use PHPUnit\Framework\TestCase;

// PHPUnit 12+ declares TestCase::onNotSuccessfulTest() with a ": never" return type
// (it was ": void" in PHPUnit <= 11). A ": void" override cannot satisfy a ": never"
// parent (and vice-versa), and the "never" type only exists since PHP 8.2, so the
// override must match the installed PHPUnit version. We detect the parent return type
// at runtime and load the matching trait file. Each file defines the SAME trait name
// (OnNotSuccessfulTestTrait), so only one of them is ever loaded.
$onNotSuccessfulReturnType = 'void';
if (method_exists(TestCase::class, 'onNotSuccessfulTest')) {
	$rt = (new ReflectionMethod(TestCase::class, 'onNotSuccessfulTest'))->getReturnType();
	if ($rt !== null) {
		// @phan-suppress-next-line PhanUndeclaredMethod
		$onNotSuccessfulReturnType = $rt->getName();
	}
}
if (PHP_VERSION_ID >= 80200 && $onNotSuccessfulReturnType === 'never') {
	require_once __DIR__.'/OnNotSuccessfulTestTraitNever.php';
} else {
	require_once __DIR__.'/OnNotSuccessfulTestTrait.php';
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
/** @phpstan-ignore class.notFound */
abstract class CommonClassTest extends TestCase
{
	use OnNotSuccessfulTestTrait;

	/** @var \Conf */
	protected $savconf;
	/** @var \User */
	protected $savuser;
	/** @var \Translate */
	protected $savlangs;
	/** @var \DoliDB */
	protected $savdb;
	/** @var \Societe */
	protected $savmysoc;

	/**
	 * Number of Dolibarr log lines to show in case of error
	 *
	 * @var integer
	 */
	public $nbLinesToShow = 50;

	/**
	 * Log file from which to extract lines in case of failing test
	 *
	 * @var string
	 */
	public $logfile = DOL_DATA_ROOT.'/dolibarr.log';

	/**
	 * Log file size before a test started (=in setUp() call)
	 *
	 * @var int
	 */
	public $logSizeAtSetup = 0;

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
			print get_called_class()."::".__FUNCTION__.PHP_EOL;
		}
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;

		// Populate instance snapshots from the global snapshot captured at file load time
		$this->savconf = $GLOBALS['PHPUNIT_SAVCONF'];
		$this->savuser = $GLOBALS['PHPUNIT_SAVUSER'];
		$this->savlangs = $GLOBALS['PHPUNIT_SAVLANGS'];
		$this->savdb = $GLOBALS['PHPUNIT_SAVDB'];
		$this->savmysoc = $GLOBALS['PHPUNIT_SAVMYSOC'];

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Record the filesize to determine which part of the log to show on error
		if (file_exists($this->logfile)) {
			$this->logSizeAtSetup = (int) filesize($this->logfile);
		} else {
			$this->logSizeAtSetup = 0;
		}

		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
			// @phpstan-ignore method.notFound
			print get_called_class().'::'.$this->getName(false)."::".__FUNCTION__.PHP_EOL;
		}
		//print $db->getVersion()."\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return  void
	 */
	protected function tearDown(): void
	{
		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
			// @phpstan-ignore method.notFound
			print get_called_class().'::'.$this->getName(false)."::".__FUNCTION__.PHP_EOL;
		}
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $db;
		$db->rollback();
		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
			print get_called_class()."::".__FUNCTION__.PHP_EOL;
		}
	}


	/**
	 * Call method, even if protected.
	 *
	 * @param object $obj  Object on which to call method
	 * @param string $name Method to call
	 * @param array<mixed>  $args Arguments to provide in method call
	 * @return mixed Return value
	 */
	public static function callMethod($obj, $name, array $args = [])
	{
		$class = new \ReflectionClass($obj);
		$method = $class->getMethod($name);
		// If PHP is older then 8.1.0
		if (PHP_VERSION_ID < 80100) {
			$method->setAccessible(true);
		}
		return $method->invokeArgs($obj, $args);
	}


	/**
	 * Compare all public properties values of 2 objects
	 *
	 * @param   Object $oA                      Object operand 1
	 * @param   Object $oB                      Object operand 2
	 * @param   boolean $ignoretype             False will not report diff if type of value differs
	 * @param   array<int|string> $fieldstoignorearray      Array of fields to ignore in diff
	 * @return  array<mixed>                    Array with differences
	 */
	public function objCompare($oA, $oB, $ignoretype = true, $fieldstoignorearray = array('id'))
	{
		$retAr = array();

		if (get_class($oA) !== get_class($oB)) {
			$retAr[] = "Supplied objects are not of same class.";
		} else {
			$oVarsA = get_object_vars($oA);
			$oVarsB = get_object_vars($oB);

			$aKeys = array_keys($oVarsA);

			if (method_exists($oA, 'deprecatedProperties')) {
				// Update exclusions
				foreach (self::callMethod($oA, 'deprecatedProperties') as $deprecated => $new) {
					if (in_array($deprecated, $fieldstoignorearray)) {
						$fieldstoignorearray[] = $new;
					}
				}
			}
			foreach ($aKeys as $sKey) {
				if (in_array($sKey, $fieldstoignorearray)) {
					continue;
				}

				if (! $ignoretype && ($oVarsA[$sKey] !== $oVarsB[$sKey])) {
					$retAr[] = get_class($oA).'::'.$sKey.' : '.(is_object($oVarsA[$sKey]) ? get_class($oVarsA[$sKey]) : json_encode($oVarsA[$sKey])).' <> '.(is_object($oVarsB[$sKey]) ? get_class($oVarsB[$sKey]) : json_encode($oVarsB[$sKey]));
				}
				if ($ignoretype && ($oVarsA[$sKey] != $oVarsB[$sKey])) {
					$retAr[] = get_class($oA).'::'.$sKey.' : '.(is_object($oVarsA[$sKey]) ? get_class($oVarsA[$sKey]) : json_encode($oVarsA[$sKey])).' <> '.(is_object($oVarsB[$sKey]) ? get_class($oVarsB[$sKey]) : json_encode($oVarsB[$sKey]));
				}
			}
		}

		return $retAr;
	}

	/**
	 * Assert that the sum of the persisted line totals matches the object header totals.
	 * Catches bugs where update_price() forgets a line, or a total is not recalculated after a line change.
	 *
	 * @param CommonObject $localobject Object with a ->lines array of line objects having total_ht/total_tva/total_ttc
	 * @param string       $message     Extra message to show on failure
	 * @return void
	 */
	protected function assertLineTotalsMatchHeader($localobject, $message = '')
	{
		$sumht = 0.0;
		$sumtva = 0.0;
		$sumttc = 0.0;
		foreach ($localobject->lines as $line) {
			$sumht += (float) $line->total_ht;
			$sumtva += (float) $line->total_tva;
			$sumttc += (float) $line->total_ttc;
		}

		$this->assertEqualsWithDelta($sumht, (float) $localobject->total_ht, 0.01, 'total_ht does not match sum of lines. '.$message);
		$this->assertEqualsWithDelta($sumtva, (float) $localobject->total_tva, 0.01, 'total_tva does not match sum of lines. '.$message);
		$this->assertEqualsWithDelta($sumttc, (float) $localobject->total_ttc, 0.01, 'total_ttc does not match sum of lines. '.$message);
	}

	/**
	 * Compare $localobject against a freshly built specimen of the same class (with the same mutation applied)
	 * to detect fields unexpectedly changed by a lifecycle action such as update() or valid().
	 *
	 * @param object   $localobject         Object to check, already gone through create()/update()/valid()...
	 * @param callable $mutate              Callback(object $specimen): void applying the same mutation that was applied to $localobject
	 * @param array<int|string> $fieldstoignorearray Fields to ignore in the comparison (passed to objCompare)
	 * @param array<mixed> $specimenparam   Param array passed to initAsSpecimen()
	 * @return void
	 */
	protected function assertMatchesFreshSpecimen($localobject, callable $mutate, array $fieldstoignorearray, array $specimenparam = array())
	{
		global $db;

		$class = get_class($localobject);
		$newlocalobject = new $class($db);
		$newlocalobject->initAsSpecimen($specimenparam);
		$mutate($newlocalobject);

		$clonedobject = clone $localobject;
		unset($clonedobject->array_options);

		$arraywithdiff = $this->objCompare($clonedobject, $newlocalobject, true, $fieldstoignorearray);
		$this->assertEquals(array(), $arraywithdiff, 'Found differences '.var_export($arraywithdiff, true));
	}

	/**
	 * Map deprecated module names to new module names
	 */
	const DEPRECATED_MODULE_MAPPING = array(
		'actioncomm' => 'agenda',
		'adherent' => 'member',
		'adherent_type' => 'member_type',
		'banque' => 'bank',
		'categorie' => 'category',
		'commande' => 'order',
		'contrat' => 'contract',
		'entrepot' => 'stock',
		'expedition' => 'shipping',
		'facture' => 'invoice',
		'fichinter' => 'intervention',
		'product_fournisseur_price' => 'productsupplierprice',
		'product_price' => 'productprice',
		'projet'  => 'project',
		'propale' => 'propal',
		'socpeople' => 'contact',
	);

	const EFFECTIVE_DEPRECATED_MODULE_MAPPING = array(
		'adherent' => 'member',
		'adherent_type' => 'member_type',
		'banque' => 'bank',
		'contrat' => 'contract',
		'entrepot' => 'stock',
		'ficheinter' => 'fichinter',
		'projet'  => 'project',
	);

	/**
	 * Map module names to the 'class' name (the class is: mod<CLASSNAME>)
	 * Value is null when the module is not internal to the default
	 * Dolibarr setup.
	 */
	const VALID_MODULE_MAPPING = array(
		'accounting' => 'Accounting',
		'agenda' => 'Agenda',
		'ai' => 'Ai',
		'anothermodule' => null,  // Not used in code, used in translations.lang
		'api' => 'Api',
		'asset' => 'Asset',
		'bank' => 'Banque',
		'barcode' => 'Barcode',
		'blockedlog' => 'BlockedLog',
		'bom' => 'Bom',
		'bookcal' => 'BookCal',
		'bookmark' => 'Bookmark',
		'cashdesk' => null,
		'category' => 'Categorie',
		'clicktodial' => 'ClickToDial',
		'collab' => 'Collab',  // TODO: fill in proper name
		'comptabilite' => 'Comptabilite',
		'contact' => null,  // TODO: fill in proper class
		'contract' => 'Contrat',
		'cron' => 'Cron',
		'datapolicy' => 'DataPolicy',
		'dav' => 'Dav',
		'debugbar' => 'DebugBar',
		'shipping' => 'Expedition',
		'deplacement' => null,
		"documentgeneration" => 'DocumentGeneration',  // TODO: fill in proper name
		'don' => 'Don',
		'dynamicprices' => 'DynamicPrices',
		'ecm' => 'ECM',
		'ecotax' => null,  // TODO: External module ?
		'emailcollector' => 'EmailCollector',
		'eventorganization' => 'EventOrganization',
		'expensereport' => 'ExpenseReport',
		'export' => 'Export',
		'externalrss' => 'ExternalRss',  // TODO: fill in proper name
		'fckeditor' => 'Fckeditor',
		'fournisseur' => 'Fournisseur',
		'ftp' => 'FTP',
		'geoipmaxmind' => 'GeoIPMaxmind',  // TODO: fill in proper name
		'google' => null,  // External ?
		'gravatar' => 'Gravatar',
		'holiday' => 'Holiday',
		'hrm' => 'HRM',
		'import' => 'Import',
		'incoterm' => 'Incoterm',
		'intervention' => 'Ficheinter',
		'intracommreport' => 'Intracommreport',
		'invoice' => 'Facture',
		'knowledgemanagement' => 'KnowledgeManagement',
		'label' => 'Label',
		'ldap' => 'Ldap',
		'loan' => 'Loan',
		'mailing' => 'Mailing',
		'mailman' => null,  // Same module as mailmanspip -> MailmanSpip ??
		'mailmanspip' => 'MailmanSpip',
		'margin' => 'Margin',
		'member' => 'Adherent',
		'memcached' => null, // TODO: External module?
		'modulebuilder' => 'ModuleBuilder',
		'mrp' => 'Mrp',
		'multicompany' => null, // Not provided by default, no module tests
		'multicurrency' => 'MultiCurrency',
		'mymodule' => null, // modMyModule - Name used in module builder (avoid false positives)
		'notification' => 'Notification',
		'numberwords' => null, // Not provided by default, no module tests
		'oauth' => 'Oauth',
		'openstreetmap' => null,  // External module?
		'opensurvey' => 'OpenSurvey',
		'order' => 'Commande',
		'partnership' => 'Partnership',
		'paymentbybanktransfer' => 'PaymentByBankTransfer',
		'paypal' => 'Paypal',
		'paypalplus' => null,
		'prelevement' => 'Prelevement',
		'printing' => 'Printing', // TODO: set proper name
		'product' => 'Product',
		'productbatch' => 'ProductBatch',
		'productprice' => null,
		'productsupplierprice' => null,
		'project' => 'Projet',
		'propal' => 'Propale',
		'quickmemo' => 'QuickMemo',
		'receiptprinter' => 'ReceiptPrinter',
		'reception' => 'Reception',
		'recruitment' => 'Recruitment',
		'resource' => 'Resource',
		'salaries' => 'Salaries',
		'service' => 'Service',
		'socialnetworks' => 'SocialNetworks',
		'societe' => 'Societe',
		'stock' => 'Stock',
		'stocktransfer' => 'StockTransfer',
		'stripe' => 'Stripe',
		'subtotals' => 'Subtotals',
		'supplier_invoice' => null,  // Special case, uses invoice
		'supplier_order' => null,  // Special case, uses invoice
		'supplier_proposal' => 'SupplierProposal',
		'syslog' => 'Syslog',
		'takepos' => 'TakePos',
		'tax' => 'Tax',
		'ticket' => 'Ticket',
		'user' => 'User',
		'variants' => 'Variants',
		'webhook' => 'Webhook',
		'webportal' => 'WebPortal',
		'webservices' => 'WebServices',
		'website' => 'Website',
		'workflow' => 'Workflow',
		'workstation' => 'Workstation',
		'zapier' => 'Zapier',
	);

	/**
	 * Map module names to the 'class' name (the class is: mod<CLASSNAME>)
	 * Value is null when the module is not internal to the default
	 * Dolibarr setup.
	 */
	const OTHER_MODULE_MAPPING = array(
		'captureserver' => 'CaptureServer'
	);

	/**
	 * Run php script (file) using the php binary used for running phpunit.
	 *
	 * The PHP executable may not be in the path, or refer to an uncontrolled
	 * version.
	 * This ensures that the php script is properly run on multiple platforms.
	 *
	 * @param string $phpScriptCommand The command and arguments are run by the php binary.
	 * @param array<string>  $output           The output returned by the command
	 * @param int   $exitCode The exit code returned for the execution.
	 * @return false|string  False on failure, else last line if the output from the command
	 */
	protected function runPhpScript(string $phpScriptCommand, &$output, &$exitCode)
	{
		$phpExecutable = PHP_BINARY;

		// Build the command to execute the PHP script
		$command = "$phpExecutable $phpScriptCommand";

		// Execute the command
		return exec($command, $output, $exitCode);
	}

	/**
	 * Assert that a directory does not exist without triggering deprecation
	 *
	 * @param string $directory The directory to test
	 * @param string $message   The message to show if the directory exists
	 *
	 * @return void
	 */
	protected function assertDirectoryNotExistsCompat($directory, $message = '')
	{
		// @phan-suppress-next-line PhanUndeclaredClassReference, PhanUndeclaredClassMethod
		$phpunitVersion = class_exists('\PHPUnit\Runner\Version') ? \PHPUnit\Runner\Version::id() : '9.0.0';

		// Check if PHPUnit version is less than 9.0.0
		if (version_compare($phpunitVersion, '9.0.0', '<')) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			/** @phpstan-ignore method.notFound */
			$this->assertDirectoryNotExists($directory, $message);
		} else {
			// @phan-suppress-next-line PhanUndeclaredMethod
			/** @phpstan-ignore method.notFound */
			$this->assertDirectoryDoesNotExist($directory, $message);
		}
	}

	/**
	 * Assert that a file does not exist without triggering deprecation
	 *
	 * @param string $file      The file to test
	 * @param string $message   The message to show if the directory exists
	 *
	 * @return void
	 */
	protected function assertFileNotExistsCompat($file, $message = '')
	{
		// @phan-suppress-next-line PhanUndeclaredClassReference, PhanUndeclaredClassMethod
		$phpunitVersion = class_exists('\PHPUnit\Runner\Version') ? \PHPUnit\Runner\Version::id() : '9.0.0';

		// Check if PHPUnit version is less than 9.0.0
		if (version_compare($phpunitVersion, '9.0.0', '<')) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertFileNotExists($file, $message);
		} else {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->assertFileDoesNotExist($file, $message);
		}
	}


	/**
	 * Skip test if test is not running on "Unix"
	 *
	 * @param string $message Message to indicate which test requires "Unix"
	 *
	 * @return bool True if this is not *nix, and fake assert generated
	 */
	protected function fakeAssertIfNotUnix($message)
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// @phan-suppress-next-line PhanUndeclaredMethod
			// @phpstan-ignore method.notFound
			$this->assertTrue(true, "Dummy test to not mark the test as risky");
			// $this->markTestSkipped("PHPUNIT is running on windows.  $message");
			return true;
		}
		return false;
	}
}
