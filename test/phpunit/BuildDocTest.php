<?php
/* Copyright (C) 2010-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin       <regis.houssin@inodbox.com>
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
     * @return BuildDocTest
     */
    public function __construct()
    {
        parent::__construct();

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
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;

        if (! $conf->facture->enabled) { print __METHOD__." invoice module not enabled\n"; die(); }
        if (! $conf->commande->enabled) { print __METHOD__." order module not enabled\n"; die(); }
        if (! $conf->propal->enabled) { print __METHOD__." propal module not enabled\n"; die(); }
        if (! $conf->projet->enabled) { print __METHOD__." project module not enabled\n"; die(); }
        if (! $conf->expedition->enabled) { print __METHOD__." shipment module not enabled\n"; die(); }
        if (! $conf->ficheinter->enabled) { print __METHOD__." intervention module not enabled\n"; die(); }
        if (! $conf->expensereport->enabled) { print __METHOD__." expensereport module not enabled\n"; die(); }

        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

        print __METHOD__."\n";
    }

    /**
     * tearDownAfterClass
     *
     * @return	void
     */
    public static function tearDownAfterClass()
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
    protected function setUp()
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
    protected function tearDown()
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

        $localobjectcom=new Commande($this->savdb);
        $localobjectcom->initAsSpecimen();

        $localobject=new Facture($this->savdb);
        $localobject->createFromOrder($localobjectcom, $user);
        $localobject->date_lim_reglement = dol_now() + 3600 * 24 *30;

        // Crabe (english)
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $langs);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (japanese)
        $newlangs1=new Translate("", $conf);
        $newlangs1->setDefaultLang('ja_JP');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs1);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (saudiarabia)
        $newlangs2a=new Translate("", $conf);
        $newlangs2a->setDefaultLang('sa_SA');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs2a);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (english_saudiarabia)
        $newlangs2b=new Translate("", $conf);
        $newlangs2b->setDefaultLang('en_SA');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs2b);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (greek)
        $newlangs3=new Translate("", $conf);
        $newlangs3->setDefaultLang('el_GR');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs3);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (chinese)
        $newlangs4=new Translate("", $conf);
        $newlangs4->setDefaultLang('zh_CN');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs4);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Crabe (russian)
        $newlangs5=new Translate("", $conf);
        $newlangs5->setDefaultLang('ru_RU');
        $localobject->modelpdf='crabe';
        $result = $localobject->generateDocument($localobject->modelpdf, $newlangs5);
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
        $localobject=new FactureFournisseur($this->savdb);
        $localobject->initAsSpecimen();

        // Canelle
        $localobject->modelpdf='canelle';
        $result = $localobject->generateDocument($localobject->modelpdf, $langs);

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
        $localobject=new Commande($this->savdb);
        $localobject->initAsSpecimen();

        // Einstein
        $localobject->modelpdf='einstein';
        $result = $localobject->generateDocument($localobject->modelpdf, $langs);

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
        $localobject=new CommandeFournisseur($this->savdb);
        $localobject->initAsSpecimen();

        // Muscadet
        $localobject->modelpdf='muscadet';
        $result= $localobject->generateDocument($localobject->modelpdf, $langs);

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
        $localobject=new Propal($this->savdb);
        $localobject->initAsSpecimen();

        // Azur
        $localobject->modelpdf='azur';
        $result = $localobject->generateDocument($localobject->modelpdf, $langs);

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
        $conf->projet->dir_output.='/temp';
        $localobject=new Project($this->savdb);
        $localobject->initAsSpecimen();

        // Baleine
        $localobject->modelpdf='baleine';
        $result = $localobject->generateDocument($localobject->modelpdf, $langs);

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
        $localobject=new Fichinter($this->savdb);
        $localobject->initAsSpecimen();

        // Soleil
        $localobject->modelpdf='soleil';
        $result=fichinter_create($db, $localobject, $localobject->modelpdf, $langs);

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
        $localobject=new Expedition($this->savdb);
        $localobject->initAsSpecimen();

        // Merou
        $localobject->modelpdf='merou';
        $result= $localobject->generateDocument($localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Rouget
        $localobject->modelpdf='rouget';
        $result= $localobject->generateDocument($localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }
}
