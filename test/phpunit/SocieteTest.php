<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/SocieteTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
$langs->load("dict");

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
class SocieteTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return SocieteTest
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

        if ($conf->global->SOCIETE_CODECLIENT_ADDON != 'mod_codeclient_monkey') { print "\n".__METHOD__." third party ref checker must be setup to 'mod_codeclient_monkey' not to '".$conf->global->SOCIETE_CODECLIENT_ADDON."'.\n"; die(); }

        if (! empty($conf->global->MAIN_DISABLEPROFIDRULES)) { print "\n".__METHOD__." constant MAIN_DISABLEPROFIDRULES must be empty (if a module set it, disable module).\n"; die(); }

        $db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testSocieteCreate
     *
     * @return int
     */
    public function testSocieteCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Societe($this->savdb);
        $localobject->initAsSpecimen();
        $result=$localobject->create($user);

        print __METHOD__." result=".$result."\n";
        $this->assertLessThanOrEqual($result, 0);

        return $result;
    }

    /**
     * testSocieteFetch
     *
     * @param   int     $id             Company id
     * @return  Societe $localobject    Company
     *
     * @depends	testSocieteCreate
     * The depends says test is run only if previous is ok
     */
    public function testSocieteFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Societe($this->savdb);
        $result=$localobject->fetch($id);
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $result=$localobject->verify();
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertEquals($result, 0);

        return $localobject;
    }

    /**
     * testSocieteUpdate
     *
     * @param   Societe $localobject    Company
     * @return  Societe $localobject    Company
    *
     * @depends testSocieteFetch
     * The depends says test is run only if previous is ok
     */
    public function testSocieteUpdate($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject->note_private='New private note after update';
        $localobject->note_public='New public note after update';
        $localobject->name='New name';
        $localobject->address='New address';
        $localobject->zip='New zip';
        $localobject->town='New town';
        $localobject->country_id=2;
        $localobject->status=0;
        $localobject->phone='New tel';
        $localobject->fax='New fax';
        $localobject->email='newemail@newemail.com';
        $localobject->url='New url';
        $localobject->idprof1='new idprof1';
        $localobject->idprof2='new idprof2';
        $localobject->idprof3='new idprof3';
        $localobject->idprof4='new idprof4';

        $result=$localobject->update($localobject->id, $user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

		$result=$localobject->update_note($localobject->note_private, '_private');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Holiday::update_note (private) error');

		$result=$localobject->update_note($localobject->note_public, '_public');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Holiday::update_note (public) error');

        $newobject=new Societe($this->savdb);
        $result=$newobject->fetch($localobject->id);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $this->assertEquals($localobject->note_private, $newobject->note_private);
        //$this->assertEquals($localobject->note_public, $newobject->note_public);
        $this->assertEquals($localobject->name, $newobject->name);
        $this->assertEquals($localobject->address, $newobject->address);
        $this->assertEquals($localobject->zip, $newobject->zip);
        $this->assertEquals($localobject->town, $newobject->town);
        $this->assertEquals($localobject->country_id, $newobject->country_id);
        $this->assertEquals('BE', $newobject->country_code);
        $this->assertEquals($localobject->status, $newobject->status);
        $this->assertEquals($localobject->phone, $newobject->phone);
        $this->assertEquals($localobject->fax, $newobject->fax);
        $this->assertEquals($localobject->email, $newobject->email);
        $this->assertEquals($localobject->url, $newobject->url);
        $this->assertEquals($localobject->idprof1, $newobject->idprof1);
        $this->assertEquals($localobject->idprof2, $newobject->idprof2);
        $this->assertEquals($localobject->idprof3, $newobject->idprof3);
        $this->assertEquals($localobject->idprof4, $newobject->idprof4);

        return $localobject;
    }

    /**
     * testIdProfCheck
     *
     * @param   Societe $localobject    Company
     * @return  Societe $localobject    Company
     *
     * @depends testSocieteUpdate
     * The depends says test is run only if previous is ok
     */
    public function testIdProfCheck($localobject)
    {
        // OK FR
        $localobject->country_code='FR';
        $localobject->idprof1=493861496;
        $localobject->idprof2=49386149600021;
        $result=$localobject->id_prof_check(1, $localobject);    // Must be > 0
        print __METHOD__." OK FR idprof1 result=".$result."\n";
        $this->assertGreaterThanOrEqual(1, $result);
        $result=$localobject->id_prof_check(2, $localobject);    // Must be > 0
        print __METHOD__." OK FR idprof2 result=".$result."\n";
        $this->assertGreaterThanOrEqual(1, $result);

        // KO FR
        $localobject->country_code='FR';
        $localobject->idprof1='id1ko';
        $localobject->idprof2='id2ko';
        $result=$localobject->id_prof_check(1, $localobject);    // Must be <= 0
        print __METHOD__." KO FR idprof1 result=".$result."\n";
        $this->assertLessThan(1, $result);
        $result=$localobject->id_prof_check(2, $localobject);    // Must be <= 0
        print __METHOD__." KO FR idprof2 result=".$result."\n";
        $this->assertLessThan(1, $result);

        // KO ES
        $localobject->country_code='ES';
        $localobject->idprof1='id1ko';
        $result=$localobject->id_prof_check(1, $localobject);    // Must be <= 0
        print __METHOD__." KO ES idprof1 result=".$result."\n";
        $this->assertLessThan(1, $result);

        // OK AR
        $localobject->country_code='AR';
        $localobject->idprof1='id1ko';
        $localobject->idprof2='id2ko';
        $result=$localobject->id_prof_check(1, $localobject);    // Must be > 0
        print __METHOD__." OK AR idprof1 result=".$result."\n";
        $this->assertGreaterThanOrEqual(0, $result);
        $result=$localobject->id_prof_check(2, $localobject);    // Must be > 0
        print __METHOD__." OK AR idprof2 result=".$result."\n";
        $this->assertGreaterThanOrEqual(1, $result);

        return $localobject;
    }


    /**
     * testSocieteOther
     *
     * @param   Societe $localobject    Company
     * @return  int     $id             Id of company
     *
     * @depends testIdProfCheck
     * The depends says test is run only if previous is ok
     */
    public function testSocieteOther($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->set_as_client();
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $result=$localobject->set_price_level(1, $user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $result=$localobject->set_remise_client(10, 'Gift', $user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $result=$localobject->getNomUrl(1);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertNotEquals($result, '');

        $result=$localobject->isInEEC();
        print __METHOD__." id=".$localobject->id." country_code=".$localobject->country_code." result=".$result."\n";
        $this->assertTrue(true, $result);

        $localobject->info($localobject->id);
        print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
        $this->assertNotEquals($localobject->date_creation, '');

        return $localobject->id;
    }


    /**
     * testSocieteDelete
     *
     * @param   int     $id     Id of company
     * @return  int
     *
     * @depends testSocieteOther
     * The depends says test is run only if previous is ok
     */
    public function testSocieteDelete($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Societe($this->savdb);
        $result=$localobject->fetch($id);

        $result=$localobject->delete($id, $user);
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $result;
    }

    /**
     * testSocieteStatic
     *
     * @return  void
     */
    public function testSocieteStatic()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Societe($db);


        return;
    }


    /**
     * testSocieteGetFullAddress
     *
     * @return  int     $id             Id of company
     */
    public function testSocieteGetFullAddress()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobjectadd=new Societe($db);
        $localobjectadd->initAsSpecimen();

        // France
        unset($localobjectadd->country_code);
        $localobjectadd->country_id=1;
        $localobjectadd->name='New name';
        $localobjectadd->address='New address';
        $localobjectadd->zip='New zip';
        $localobjectadd->town='New town';
        $result=$localobjectadd->getFullAddress(1);
        print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
        $this->assertContains("New address\nNew zip New town\nFrance", $result);

        // Belgium
        unset($localobjectadd->country_code);
        $localobjectadd->country_id=2;
        $localobjectadd->name='New name';
        $localobjectadd->address='New address';
        $localobjectadd->zip='New zip';
        $localobjectadd->town='New town';
        $result=$localobjectadd->getFullAddress(1);
        print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
        $this->assertContains("New address\nNew zip New town\nBelgium", $result);

        // Switzerland
        unset($localobjectadd->country_code);
        $localobjectadd->country_id=6;
        $localobjectadd->name='New name';
        $localobjectadd->address='New address';
        $localobjectadd->zip='New zip';
        $localobjectadd->town='New town';
        $result=$localobjectadd->getFullAddress(1);
        print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
        $this->assertContains("New address\nNew zip New town\nSwitzerland", $result);

        // USA
        unset($localobjectadd->country_code);
        $localobjectadd->country_id=11;
        $localobjectadd->name='New name';
        $localobjectadd->address='New address';
        $localobjectadd->zip='New zip';
        $localobjectadd->town='New town';
        $localobjectadd->state='New state';
        $result=$localobjectadd->getFullAddress(1);
        print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
        $this->assertContains("New address\nNew town, New state, New zip\nUnited States", $result);

        return $localobjectadd->id;
    }
}
