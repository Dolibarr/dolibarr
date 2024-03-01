<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \remarks    Class that extends all PHPunit tests. To share similare code between each test.
 */

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
		$logfile = DOL_DATA_ROOT.'/dolibarr.log';

		$lines = file($logfile);

		$nbLinesToShow = $this->nbLinesToShow;
		if ($t instanceof PHPUnit\Framework\Error\Notice) {
			$nbLinesToShow = 3;
		}


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

		print PHP_EOL;
		// Use GitHub Action compatible group output (:warning: arguments not encoded)
		print "##[group]$className::$failedTestMethod failed - $argsText.".PHP_EOL;
		print "## Exception: {$t->getMessage()}".PHP_EOL;

		if ($nbLinesToShow) {
			// Show partial log file contents when requested.
			print "## Show last ".$nbLinesToShow." lines of dolibarr.log file -----".PHP_EOL;
			foreach ($last_lines as $line) {
				print $line.PHP_EOL;
			}
			print "## end of dolibarr.log for $className::$failedTestMethod".PHP_EOL;
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
		'expedition' => 'delivery_note',
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
		'delivery_note' => 'Expedition',
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
		'webservicesclient' => 'WebServicesClient',  // TODO: set proper name
		'website' => 'Website',
		'workflow' => 'Workflow',
		'workstation' => 'Workstation',
		'zapier' => 'Zapier',
	);

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
}
