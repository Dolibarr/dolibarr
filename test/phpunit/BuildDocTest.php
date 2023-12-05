<?php
/* Copyright (C) 2010-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2023       Alexandre Janniaux  <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/BuildDocTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/fichinter/class/fichinter.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.product.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/facture/doc/pdf_crabe.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/propale/doc/pdf_azur.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/commande/doc/pdf_einstein.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/project/doc/pdf_baleine.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/fichinter/doc/pdf_soleil.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/expedition/doc/pdf_merou.modules.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/expedition/doc/pdf_rouget.modules.php';
// Mother classes of pdf generators
require_once dirname(__FILE__).'/../../htdocs/core/modules/facture/modules_facture.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/commande/modules_commande.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/supplier_order/modules_commandefournisseur.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/propale/modules_propale.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/project/modules_project.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/fichinter/modules_fichinter.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/expedition/modules_expedition.php';

require_once dirname(__FILE__).'/../../htdocs/core/modules/modExpenseReport.class.php';


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
class BuildDocTest extends PHPUnit\Framework\TestCase
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

		if (!isModEnabled('facture')) {
			print __METHOD__." invoice module not enabled\n";
			die(1);
		}
		if (!isModEnabled('commande')) {
			print __METHOD__." order module not enabled\n";
			die(1);
		}
		if (!isModEnabled('propal')) {
			print __METHOD__." propal module not enabled\n";
			die(1);
		}
		if (!isModEnabled('projet')) {
			print __METHOD__." project module not enabled\n";
			die(1);
		}
		if (!isModEnabled('expedition')) {
			print __METHOD__." shipment module not enabled\n";
			die(1);
		}
		if (!isModEnabled('ficheinter')) {
			print __METHOD__." intervention module not enabled\n";
			die(1);
		}
		if (!isModEnabled('expensereport')) {
			print __METHOD__." expensereport module not enabled\n";
			die(1);
		}

		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

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
	 * testFactureBuild
	 *
	 * @return int
	 */
	public function testFactureBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->facture->dir_output.='/temp';

		$localobjectcom=new Commande($db);
		$localobjectcom->initAsSpecimen();

		$localobject=new Facture($db);
		$localobject->createFromOrder($localobjectcom, $user);
		$localobject->date_lim_reglement = dol_now() + 3600 * 24 *30;

		// Crabe (english)
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $langs);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (japanese)
		$newlangs1=new Translate("", $conf);
		$newlangs1->setDefaultLang('ja_JP');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs1);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (saudiarabia)
		$newlangs2a=new Translate("", $conf);
		$newlangs2a->setDefaultLang('sa_SA');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs2a);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (english_saudiarabia)
		$newlangs2b=new Translate("", $conf);
		$newlangs2b->setDefaultLang('en_SA');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs2b);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (greek)
		$newlangs3=new Translate("", $conf);
		$newlangs3->setDefaultLang('el_GR');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs3);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (chinese)
		$newlangs4=new Translate("", $conf);
		$newlangs4->setDefaultLang('zh_CN');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs4);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Crabe (russian)
		$newlangs5=new Translate("", $conf);
		$newlangs5->setDefaultLang('ru_RU');
		$localobject->model_pdf='crabe';
		$result = $localobject->generateDocument($localobject->model_pdf, $newlangs5);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	* testFactureFournisseurBuild
	*
	* @return int
	*/
	public function testFactureFournisseurBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->fournisseur->facture->dir_output.='/temp';
		$localobject=new FactureFournisseur($db);
		$localobject->initAsSpecimen();

		// Canelle
		$localobject->model_pdf='canelle';
		$result = $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	 * testCommandeBuild
	 *
	 * @return int
	 */
	public function testCommandeBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->commande->dir_output.='/temp';
		$localobject=new Commande($db);
		$localobject->initAsSpecimen();

		// Einstein
		$localobject->model_pdf='einstein';
		$result = $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}


	/**
	 * testCommandeFournisseurBuild
	 *
	 * @return int
	 */
	public function testCommandeFournisseurBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->fournisseur->commande->dir_output.='/temp';
		$localobject=new CommandeFournisseur($db);
		$localobject->initAsSpecimen();

		// Muscadet
		$localobject->model_pdf='muscadet';
		$result= $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	 * testPropalBuild
	 *
	 * @return int
	 */
	public function testPropalBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->propal->dir_output.='/temp';
		$localobject=new Propal($db);
		$localobject->initAsSpecimen();

		// Azur
		$localobject->model_pdf='azur';
		$result = $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	 * testProjectBuild
	 *
	 * @return int
	 */
	public function testProjectBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;
		$conf->project->dir_output.='/temp';
		$localobject=new Project($db);
		$localobject->initAsSpecimen();

		// Baleine
		$localobject->model_pdf='baleine';
		$result = $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	 * testFichinterBuild
	 *
	 * @return int
	 */
	public function testFichinterBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->ficheinter->dir_output.='/temp';
		$localobject=new Fichinter($db);
		$localobject->initAsSpecimen();

		// Soleil
		$localobject->model_pdf='soleil';
		$result=fichinter_create($db, $localobject, $localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}

	/**
	 * testExpeditionBuild
	 *
	 * @return int
	 */
	public function testExpeditionBuild()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->expedition->dir_output.='/temp';
		$localobject=new Expedition($db);
		$localobject->initAsSpecimen();

		// Merou
		$localobject->model_pdf='merou';
		$result= $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		// Rouget
		$localobject->model_pdf='rouget';
		$result= $localobject->generateDocument($localobject->model_pdf, $langs);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";

		return 0;
	}
}
