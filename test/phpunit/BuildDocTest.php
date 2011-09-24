<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/phpunit/BuildDocTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/fichinter/class/fichinter.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
require_once dirname(__FILE__).'/../../htdocs/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/facture/doc/pdf_crabe.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/facture/doc/pdf_oursin.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/commande/pdf_edison.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/commande/pdf_einstein.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/propale/pdf_propale_azur.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/propale/pdf_propale_jaune.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/project/pdf/pdf_baleine.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/fichinter/pdf_soleil.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/expedition/pdf/pdf_expedition_merou.modules.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/expedition/pdf/pdf_expedition_rouget.modules.php';
// Mother classes of pdf generators
require_once dirname(__FILE__).'/../../htdocs/includes/modules/facture/modules_facture.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/commande/modules_commande.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/propale/modules_propale.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/project/modules_project.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/fichinter/modules_fichinter.php';
require_once dirname(__FILE__).'/../../htdocs/includes/modules/expedition/pdf/ModelePdfExpedition.class.php';

if (empty($user->id))
{
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
class BuildDocTest extends PHPUnit_Framework_TestCase
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
	function BuildDocTest()
	{
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

	// Static methods
  	public static function setUpBeforeClass()
    {
    	global $conf,$user,$langs,$db;

        if (! $conf->facture->enabled) { print __METHOD__." invoice module not enabled\n"; die(); }
        if (! $conf->commande->enabled) { print __METHOD__." order module not enabled\n"; die(); }
        if (! $conf->propale->enabled) { print __METHOD__." propal module not enabled\n"; die(); }
        if (! $conf->projet->enabled) { print __METHOD__." project module not enabled\n"; die(); }
        if (! $conf->expedition->enabled) { print __METHOD__." shipment module not enabled\n"; die(); }

		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
    }

	/**
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
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

    /**
     */
    public function testFactureBuild()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$conf->facture->dir_output.='/temp';
		$localobject=new Facture($this->savdb);
    	$localobject->initAsSpecimen();
    	$localobject->socid=1;

    	// Crabe
    	$localobject->modelpdf='crabe';
    	$result=facture_pdf_create($db, $localobject, '', $localobject->modelpdf, $langs);

    	$this->assertLessThan($result, 0);
    	print __METHOD__." result=".$result."\n";

    	// Oursin
    	$localobject->modelpdf='oursin';
    	$result=facture_pdf_create($db, $localobject, '', $localobject->modelpdf, $langs);

    	$this->assertLessThan($result, 0);
    	print __METHOD__." result=".$result."\n";

    	return 0;
    }

    /**
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
        $localobject->socid=1;

        // Einstein
        $localobject->modelpdf='einstein';
        $result=commande_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Edison
        $localobject->modelpdf='edison';
        $result=commande_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }

    /**
     */
    public function testPropalBuild()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $conf->propale->dir_output.='/temp';
        $localobject=new Propal($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->socid=1;

        // Einstein
        $localobject->modelpdf='azur';
        $result=propale_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Edison
        $localobject->modelpdf='jaune';
        $result=propale_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }

    /**
     */
    public function testProjectBuild()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $conf->project->dir_output.='/temp';
        $localobject=new Project($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->socid=1;

        // Soleil
        $localobject->modelpdf='baleine';
        $result=project_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }

    /**
     */
    public function testFichinterBuild()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $conf->fichinter->dir_output.='/temp';
        $localobject=new Fichinter($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->socid=1;

        // Soleil
        $localobject->modelpdf='soleil';
        $result=fichinter_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }

    /**
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
        $localobject->socid=1;

        // Soleil
        $localobject->modelpdf='merou';
        $result=expedition_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        // Soleil
        $localobject->modelpdf='rouget';
        $result=expedition_pdf_create($db, $localobject, $localobject->modelpdf, $langs);

        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";

        return 0;
    }
}
?>