<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/ModulesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
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
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ModulesTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 * @return BuildDocTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
	}
	/**
	 * End phpunit tests
	 *
	 * @return	void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * testModulesInit
	 *
	 * @return int
	 */
	public function testModulesInit()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$modulelist=array('Accounting','Adherent','Agenda','Api','Asset','Banque','Barcode','BlockedLog','Bom','Bookmark',
		'Categorie','ClickToDial','Collab','Commande','Comptabilite','Contrat','Cron','DataPolicy','Dav','DebugBar','Deplacement','DocumentGeneration','Don','DynamicPrices',
		'ECM','EmailCollector','EventOrganization','Expedition','ExpenseReport','Export','ExternalRss','ExternalSite',
		'Facture','Fckeditor','Ficheinter','Fournisseur','FTP','GeoIPMaxmind','Gravatar','Holiday','HRM','Import','Incoterm','Intracommreport',
		'KnowledgeManagement','Label','Ldap','Loan',
		'Mailing','MailmanSpip','Margin','ModuleBuilder','Mrp','MultiCurrency',
		'Notification','Oauth','OpenSurvey','Paybox','PaymentByBankTransfer','Paypal','Prelevement','Printing','Product','ProductBatch','Projet','Propale',
		'ReceiptPrinter','Reception','Recruitment','Resource',
		'Salaries','Service','SocialNetworks','Societe','Stock','Stripe','SupplierProposal','Syslog',
		'TakePos','Tax','Ticket','User','Variants','WebServices','WebServicesClient','Website','Workflow','Workstation','Zapier');
		foreach ($modulelist as $modlabel) {
			require_once DOL_DOCUMENT_ROOT.'/core/modules/mod'.$modlabel.'.class.php';
			$class='mod'.$modlabel;
			$mod=new $class($db);

			$result=$mod->remove();
			$result=$mod->init();

			$this->assertLessThan($result, 0, $modlabel." ".$mod->error);
			print __METHOD__." test remove/init for module ".$modlabel.", result=".$result."\n";

			if (in_array($modlabel, array('Ldap', 'MailmanSpip'))) {
				$result=$mod->remove();
			}
		}

		return 0;
	}
}
