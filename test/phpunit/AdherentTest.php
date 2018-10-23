<?php
/* Copyright (C) 2010      Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Marcos García         <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/AdherentTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/adherents/class/adherent.class.php';
require_once dirname(__FILE__).'/../../htdocs/adherents/class/adherent_type.class.php';

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
class AdherentTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return AdherentTest
     */
    function __construct()
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

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

        if (! empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
            print "\n".__METHOD__." Company must be setup to have name-firstname in order 'Firstname Lastname'\n";
            die();
        }
        if (! empty($conf->global->MAIN_MODULE_LDAP)) { print "\n".__METHOD__." module LDAP must be disabled.\n"; die(); }
        if (! empty($conf->global->MAIN_MODULE_MAILMANSPIP)) { print "\n".__METHOD__." module MailmanSpip must be disabled.\n"; die(); }

        print __METHOD__."\n";
    }

    // tear down after class
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
     * @return	void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testAdherentTypeCreate
     *
     * @return void
     */
    public function testAdherentTypeCreate()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new AdherentType($this->savdb);
        $localobject->statut=1;
        $localobject->label='Adherent type test';
        $localobject->subscription=1;
        $localobject->vote=1;
        $result=$localobject->create($user);
        print __METHOD__." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject->id;
    }

    /**
     * testAdherentCreate
     *
     * @param   int $fk_adherent_type       Id type of member
     * @return  int
     *
     * @depends	testAdherentTypeCreate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentCreate($fk_adherent_type)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Adherent($this->savdb);
        $localobject->initAsSpecimen();
        $localobject->typeid=$fk_adherent_type;
        $result=$localobject->create($user);
        print __METHOD__." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $result;
    }

    /**
     * testAdherentFetch
     *
     * @param   int     $id     Id of object to fetch
     * @return  object          Fetched object
     *
     * @depends	testAdherentCreate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentFetch($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobject=new Adherent($this->savdb);
        $result=$localobject->fetch($id);
        print __METHOD__." id=".$id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        return $localobject;
    }

    /**
     * testAdherentFetchLogin
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentFetch
     * The depends says test is run only if previous is ok
     */
    public function testAdherentFetchLogin(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $newobject = new Adherent($this->savdb);
        $result = $newobject->fetch_login($localobject->login);

        $this->assertEquals($newobject, $localobject);

        return $localobject;
    }

    /**
     * testAdherentUpdate
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentFetchLogin
     * The depends says test is run only if previous is ok
     */
    public function testAdherentUpdate(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $timestamp = dol_now();

        $localobject->civility_id = 0;
        $localobject->login='newlogin';
        $localobject->societe='New company';
        $localobject->note='New note after update';
        //$localobject->note_public='New note public after update';
        $localobject->lastname='New name';
        $localobject->firstname='New firstname';
        $localobject->address='New address';
        $localobject->zip='New zip';
        $localobject->town='New town';
        $localobject->country_id=2;
        $localobject->statut=0;
        $localobject->morphy=0;
        $localobject->phone='New tel pro';
        $localobject->phone_perso='New tel perso';
        $localobject->phone_mobile='New tel mobile';
        $localobject->email='newemail@newemail.com';
        $localobject->birth=$timestamp;
        $result=$localobject->update($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
        $result=$localobject->update_note($localobject->note,'_private');
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);
		$result=$localobject->update_note($localobject->note,'_public');
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $newobject=new Adherent($this->savdb);
        $result=$newobject->fetch($localobject->id);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        $this->assertEquals($localobject->civility_id, $newobject->civility_id);
        $this->assertEquals($localobject->login, $newobject->login);
        $this->assertEquals($localobject->societe, $newobject->societe);
        $this->assertEquals($localobject->note_public, $newobject->note_public);
        $this->assertEquals($localobject->lastname, $newobject->lastname);
        $this->assertEquals($localobject->firstname, $newobject->firstname);
        $this->assertEquals($localobject->address, $newobject->address);
        $this->assertEquals($localobject->zip, $newobject->zip);
        $this->assertEquals($localobject->town, $newobject->town);
        $this->assertEquals($localobject->country_id, $newobject->country_id);
        $this->assertEquals('BE', $newobject->country_code);
        $this->assertEquals('Belgium', $newobject->country);
        $this->assertEquals($localobject->statut, $newobject->statut);
        $this->assertEquals($localobject->phone, $newobject->phone);
        $this->assertEquals($localobject->phone_perso, $newobject->phone_perso);
        $this->assertEquals($localobject->phone_mobile, $newobject->phone_mobile);
        $this->assertEquals($localobject->email, $newobject->email);
        $this->assertEquals($localobject->birth, $timestamp);
        $this->assertEquals($localobject->morphy, $newobject->morphy);

        //We return newobject because of new values
        return $newobject;
    }

    /**
     * testAdherentMakeSubstitution
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentUpdate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentMakeSubstitution(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $conf->global->MAIN_FIRSTNAME_NAME_POSITION = 0;	// Force setup for firstname+lastname

        $template = '__CIVILITY__,__FIRSTNAME__,__LASTNAME__,__FULLNAME__,__COMPANY__,'.
                    '__ADDRESS__,__ZIP__,__TOWN__,__COUNTRY__,__EMAIL__,__BIRTH__,__PHOTO__,__LOGIN__';

        // If option to store clear password has been set, we get 'dolibspec' into PASSWORD field.
        $expected = ',New firstname,New name,New firstname New name,'.
                    'New company,New address,New zip,New town,Belgium,newemail@newemail.com,'.dol_print_date($localobject->birth,'day').',,'.
                    'newlogin';

        $result = $localobject->makeSubstitution($template);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($expected, $result);

        return $localobject;
    }

     /**
     * testAdherentSetUserId
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentMakeSubstitution
     * The depends says test is run only if previous is ok
     */
    public function testAdherentSetUserId(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        //We associate member with user
        $result = $localobject->setUserId($user->id);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals($result, 1);

        //We update user object
        $user->fetch($user->id);
        print __METHOD__." user id=".$user->id." fk_member=".$user->fk_member."\n";

        $this->assertEquals($user->fk_member, $localobject->id);

        //We remove association with user
        $result = $localobject->setUserId(0);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals($result, 1);

        //We update user object
        $user->fetch($user->id);
        print __METHOD__." user id=".$user->id." fk_member=".$user->fk_member."\n";

        $this->assertNull($user->fk_member);

        return $localobject;
    }

    /**
     * testAdherentSetThirdPartyId
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentSetUserId
     * The depends says test is run only if previous is ok
     */
    public function testAdherentSetThirdPartyId(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        //Create a Third Party
        $thirdparty = new Societe($db);
        $thirdparty->initAsSpecimen();
        $result = $thirdparty->create($user);
        print __METHOD__." id=".$localobject->id." third party id=".$thirdparty->id." result=".$result."\n";
        $this->assertTrue($result > 0);

        //Set Third Party ID
        $result = $localobject->setThirdPartyId($thirdparty->id);
        $this->assertEquals($result, 1);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";

        //Adherent is updated with new data
        $localobject->fetch($localobject->id);
        $this->assertEquals($localobject->fk_soc, $thirdparty->id);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";

        //We remove the third party association
        $result = $localobject->setThirdPartyId(0);
        $this->assertEquals($result, 1);

        //And check if it has been updated
        $localobject->fetch($localobject->id);
        $this->assertNull($localobject->fk_soc);

        //Now we remove the third party
        $result = $thirdparty->delete($thirdparty->id,$user);
        $this->assertEquals($result, 1);

        return $localobject;
    }

    /**
     * testAdherentValid
     *
     * @param	Adherent	$localobject	Member instance
     * @return	Adherent
     *
     * @depends	testAdherentSetThirdPartyId
     * The depends says test is run only if previous is ok
     */
    public function testAdherentValidate(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->validate($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject;
    }

    /**
     * testAdherentOther
     *
     * @param   Adherent    $localobject    Member instance
     * @return  int                         Id of object
     *
     * @depends testAdherentValidate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentOther(Adherent $localobject)
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

        return $localobject;
    }

    /**
     * testAdherentResiliate
     *
     * @param   Adherent    $localobject    Member instance
     * @return  Adherent
     *
     * @depends testAdherentOther
     * The depends says test is run only if previous is ok
     */
    public function testAdherentResiliate(Adherent $localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        //Let's resilie un adherent
        $result = $localobject->resiliate($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals($result, 1);

        //Is statut updated?
        $this->assertEquals($localobject->statut, 0);

        //We update the object and let's check if it was updated on DB
        $localobject->fetch($localobject->id);
        $this->assertEquals($localobject->statut, 0);

        //Now that status=0, resiliate should return 0
        $result = $localobject->resiliate($user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertEquals($result, 0);

        return $localobject;
    }

    /**
     * testAdherentDelete
     *
     * @param   Adherent    $localobject    Member instance
     * @return	void
     *
     * @depends	testAdherentResiliate
     * The depends says test is run only if previous is ok
     */
    public function testAdherentDelete($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $result=$localobject->delete($localobject->id, $user);
        print __METHOD__." id=".$localobject->id." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject;
    }


    /**
     * testAdherentTypeDelete
     *
     * @param   Adherent    $localobject    Member instance
     * @return void
     *
     * @depends	testAdherentDelete
     * The depends says test is run only if previous is ok
     */
    public function testAdherentTypeDelete($localobject)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $localobjectat=new AdherentType($this->savdb);
        $result=$localobjectat->fetch($localobject->typeid);
        $result=$localobjectat->delete();
        print __METHOD__." result=".$result."\n";
        $this->assertLessThan($result, 0);

        return $localobject->id;
    }
}
