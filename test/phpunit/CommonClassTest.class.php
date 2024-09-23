<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/CommonClassTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    Class that extends all PHPunit tests. To share similar code between each test.
 */

// Workaround for false security issue with main.inc.php on Windows in tests:
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	$_SERVER['PHP_SELF'] = "phpunit";
}

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';



if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

use PHPUnit\Framework\TestCase;

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
abstract class CommonClassTest extends TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Number of Dolibarr log lines to show in case of error
	 *
	 * @var integer
	 */
	public $nbLinesToShow = 100;

	/**
	 * Log file from which to extract lines in case of failing test
	 */
	public $logfile = DOL_DATA_ROOT.'/dolibarr.log';

	/**
	 * Log file size before a test started (=in setUp() call)
	 */
	public $logSizeAtSetup = 0;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param string       $name       Name
	 * @param array        $data       Test data
	 * @param string       $dataName   Test data name.
	 */
	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
			print get_called_class()." db->type=".$db->type." user->id=".$user->id.PHP_EOL;
		}
		//print " - db ".$db->db;
	}

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
	 *	This method is called when a test fails
	 *
	 *  @param	Throwable	$t		Throwable object
	 *  @return void
	 */
	protected function onNotSuccessfulTest(Throwable $t): void
	{

		// Get the lines that were added since the start of the test

		$filecontent = (string) @file_get_contents($this->logfile);
		$currentSize = strlen($filecontent);
		if ($currentSize >= $this->logSizeAtSetup) {
			$filecontent = substr($filecontent, $this->logSizeAtSetup);
		}
		$lines = preg_split("/\r?\n/", $filecontent, -1, PREG_SPLIT_NO_EMPTY);


		// Determine the number of lines to show

		$nbLinesToShow = $this->nbLinesToShow;
		if ($t instanceof PHPUnit\Framework\Error\Notice) {
			$nbLinesToShow = 3;
		}

		// Determine test information to show

		$failedTestMethod = $this->getName(false);
		$className = get_called_class();

		// Get the test method's reflection
		$reflectionMethod = new ReflectionMethod($className, $failedTestMethod);

		// Get the test method's data set
		$argsText = $this->getDataSetAsString(true);

		$totalLines = count($lines);
		$first_line = max(0, $totalLines - $nbLinesToShow);
		// Get the last line of the log
		$last_lines = array_slice($lines, $first_line, $nbLinesToShow);


		// Show log information

		print PHP_EOL;
		// Use GitHub Action compatible group output (:warning: arguments not encoded)
		print "##[group]$className::$failedTestMethod failed - $argsText.".PHP_EOL;
		print "## ".get_class($t).": {$t->getMessage()}".PHP_EOL;

		if ($nbLinesToShow) {
			$newLines = count($last_lines);
			if ($newLines > 0) {
				// Show partial log file contents when requested.
				print "## Show last ".count($last_lines)." lines of dolibarr.log file -----".PHP_EOL;
				foreach ($last_lines as $line) {
					print $line.PHP_EOL;
				}
				print "## end of dolibarr.log for $className::$failedTestMethod".PHP_EOL;
			} else {
				print "## No new lines in 'dolibarr.log' since start of this test.".PHP_EOL;
			}
		}
		print "##[endgroup]".PHP_EOL;

		parent::onNotSuccessfulTest($t);
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Record the filesize to determine which part of the log to show on error
		$this->logSizeAtSetup = (int) filesize($this->logfile);

		if ((int) getenv('PHPUNIT_DEBUG') > 0) {
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
	 * @param array  $args Arguments to provide in method call
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
	 * @param   array $fieldstoignorearray      Array of fields to ignore in diff
	 * @return  array                           Array with differences
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
		'deplacement' => 'Deplacement',
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
		'externalsite' => 'ExternalSite',
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
		'paybox' => 'Paybox',
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
	 * Run php script (file) using the php binary used for running phpunit.
	 *
	 * The PHP executable may not be in the path, or refer to an uncontrolled
	 * version.
	 * This ensures that the php script is properly run on multiple platforms.
	 *
	 * @param string $phpScriptCommand The command and arguments are run by the php binary.
	 * @param array  $output           The output returned by the command
	 * @param int   $exitCode The exit code returned for the execution.
	 * @return false|string  False on failure, else last line if the output from the command
	 */
	protected function runPhpScript($phpScriptCommand, &$output, &$exitCode)
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
		$phpunitVersion = \PHPUnit\Runner\Version::id();

		// Check if PHPUnit version is less than 9.0.0
		if (version_compare($phpunitVersion, '9.0.0', '<')) {
			$this->assertDirectoryNotExists($directory, $message);
		} else {
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
		$phpunitVersion = \PHPUnit\Runner\Version::id();

		// Check if PHPUnit version is less than 9.0.0
		if (version_compare($phpunitVersion, '9.0.0', '<')) {
			$this->assertFileNotExists($file, $message);
		} else {
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
			$this->assertTrue(true, "Dummy test to not mark the test as risky");
			// $this->markTestSkipped("PHPUNIT is running on windows.  $message");
			return true;
		}
		return false;
	}
}
