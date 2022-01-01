<?php
/* Copyright (C) 2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Frédéric France       <frederic.france@netlogic.fr>
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
 *      \file       test/phpunit/FactureTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';

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
class FactureTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return FactureTest
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

        if (empty($conf->facture->enabled)) { print __METHOD__." module customer invoice must be enabled.\n"; die(); }
        if (! empty($conf->ecotaxdeee->enabled)) { print __METHOD__." ecotaxdeee module must not be enabled.\n"; die(); }

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
     * @return  void
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
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testFactureCreate
     *
     * @return int
     */
    public function testFactureCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Facture($this->savdb);
        $localobject->initAsSpecimen();
        $result=$localobject->create($user);
        $this->assertLessThan($result, 0);
        print __METHOD__." result=".$result."\n";
        return $result;
    }

    /**
     * testFactureFetch
     *
     * @param   int $id     Id invoice
     * @return  int
     *
     * @depends testFactureCreate
     * The depends says test is run only if previous is ok
     */
    public function testFactureFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Facture($this->savdb);
        $result=$localobject->fetch($id);

        $this->assertLessThan($result, 0);
        print __METHOD__." id=".$id." result=".$result."\n";
        return $localobject;
    }

    /**
     * testFactureFetch
     *
     * @param   Object $localobject Invoice
     * @return  int
     *
     * @depends testFactureFetch
     * The depends says test is run only if previous is ok
     */
    public function testFactureUpdate($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $this->changeProperties($localobject);
        $result=$localobject->update($user);

        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testFactureValid
     *
     * @param   Object $localobject Invoice
     * @return  void
     *
     * @depends testFactureUpdate
     * The depends says test is run only if previous is ok
     */
    public function testFactureValid($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->validate($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";

        $this->assertLessThan($result, 0);

        // Test everything are still same than specimen
        $newlocalobject=new Facture($this->savdb);
        $newlocalobject->initAsSpecimen();
        $this->changeProperties($newlocalobject);

        // Hack to avoid test to be wrong when module sellyoursaas is on
        unset($localobject->array_options['options_commission']);
        unset($localobject->array_options['options_reseller']);

        $arraywithdiff = $this->objCompare(
			$localobject,
			$newlocalobject,
			true,
			array(
				'newref','oldref','id','lines','client','thirdparty','brouillon','user_author','date_creation','date_validation','datem','date_modification',
				'ref','statut','paye','specimen','ref','actiontypecode','actionmsg2','actionmsg','mode_reglement','cond_reglement',
				'cond_reglement_doc','situation_cycle_ref','situation_counter','situation_final','multicurrency_total_ht','multicurrency_total_tva',
				'multicurrency_total_ttc','fk_multicurrency','multicurrency_code','multicurrency_tx',
                'retained_warranty' ,'retained_warranty_date_limit', 'retained_warranty_fk_cond_reglement'
			)
		);
        $this->assertEquals($arraywithdiff, array());    // Actual, Expected

        return $localobject;
    }

    /**
     * testFactureOther
     *
     * @param   Object $localobject Invoice
     * @return  int
     *
     * @depends testFactureValid
     * The depends says test is run only if previous is ok
     */
    public function testFactureOther($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        /*$result=$localobject->setstatus(0);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        */

        $localobject->info($localobject->id);
        print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
        $this->assertNotEquals($localobject->date_creation, '');

        $result=$localobject->demande_prelevement($user);
        print __METHOD__." result=".$result."\n";
       	$this->assertLessThan($result, 0);

        return $localobject->id;
    }

    /**
     * testFactureDelete
     *
     * @param   int $id     Id of invoice
     * @return  int
     *
     * @depends testFactureOther
     * The depends says test is run only if previous is ok
     */
    public function testFactureDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        // Force default setup
        unset($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED);
        unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

        $localobject=new Facture($this->savdb);
        $result=$localobject->fetch($id);

        // Create another invoice and validate it after $localobject
        $localobject2=new Facture($this->savdb);
        $result=$localobject2->initAsSpecimen();
        $result=$localobject2->create($user);
        $result=$localobject2->validate($user);
		print 'Invoice $localobject ref = '.$localobject->ref."\n";
        print 'Invoice $localobject2 created with ref = '.$localobject2->ref."\n";

        $conf->global->INVOICE_CAN_NEVER_BE_REMOVED = 1;

        $result=$localobject2->delete($user);					// Deletion is KO, option INVOICE_CAN_NEVER_BE_REMOVED is on
        print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
        $this->assertEquals(0, $result, 'Deletion should fail, option INVOICE_CAN_NEVER_BE_REMOVED is on');

        unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

        $result=$localobject->delete($user);					// Deletion is KO, it is not last invoice
        print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
        $this->assertEquals(0, $result, 'Deletion should fail, it is not last invoice');

        $result=$localobject2->delete($user);					// Deletion is OK, it is last invoice
        print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
        $this->assertGreaterThan(0, $result, 'Deletion should work, it is last invoice');

        $result=$localobject->delete($user);					// Deletion is KO, it is not last invoice
        print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
        $this->assertGreaterThan(0, $result, 'Deletion should work, it is again last invoice');

        return $result;
    }

    /**
     * Edit an object to test updates
     *
     * @param   mixed $localobject        Object Facture
     * @return  void
     */
    public function changeProperties(&$localobject)
    {
        $localobject->note_private='New note';
        //$localobject->note='New note after update';
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
        $retAr=array();

        if (get_class($oA) !== get_class($oB))
        {
            $retAr[]="Supplied objects are not of same class.";
        }
        else
        {
            $oVarsA=get_object_vars($oA);
            $oVarsB=get_object_vars($oB);
            $aKeys=array_keys($oVarsA);
            foreach($aKeys as $sKey)
            {
                if (in_array($sKey, $fieldstoignorearray)) continue;
                if (! $ignoretype && $oVarsA[$sKey] !== $oVarsB[$sKey])
                {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
                if ($ignoretype && $oVarsA[$sKey] != $oVarsB[$sKey])
                {
                    $retAr[]=$sKey.' : '.(is_object($oVarsA[$sKey])?get_class($oVarsA[$sKey]):$oVarsA[$sKey]).' <> '.(is_object($oVarsB[$sKey])?get_class($oVarsB[$sKey]):$oVarsB[$sKey]);
                }
            }
        }
        return $retAr;
    }
}
