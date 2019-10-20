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
 *      \file       test/phpunit/RestAPIUserTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/geturl.lib.php';


if (empty($user->id)) {
    print "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;
$conf->global->MAIN_UMASK='0666';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIUserTest extends PHPUnit\Framework\TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;
    protected $api_url;
    protected $api_key;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return DateLibTest
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

        if (empty($conf->api->enabled)) { print __METHOD__." module api must be enabled.\n"; die(); }

        print __METHOD__." db->type=".$db->type." user->id=".$user->id;
        //print " - db ".$db->db;
        print "\n";
    }

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

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

        $this->api_url=DOL_MAIN_URL_ROOT.'/api/index.php';

        $login='admin';
        $password='admin';
        $url=$this->api_url.'/login?login='.$login.'&password='.$password;
        // Call the API login method to save api_key for this test class
        $result=getURLContent($url, 'GET', '', 1, array());
        print __METHOD__." result = ".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object=json_decode($result['content'], true);
        $this->assertNotNull($object, "Parsing of json result must no be null");
        $this->assertEquals('200', $object['success']['code']);

        $this->api_key = $object['success']['token'];
        print __METHOD__." api_key: $this->api_key \n";

        print __METHOD__."\n";
    }

    /**
     * End phpunit tests
     *
     * @return void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }


    /**
     * testRestGetUser
     *
     * @return int
     */
    public function testRestGetUser()
    {
        global $conf,$user,$langs,$db;

        $url = $this->api_url.'/users/123456789?api_key='.$this->api_key;
        //$addheaders=array('Content-Type: application/json');

        print __METHOD__." Request GET url=".$url."\n";
        $result=getURLContent($url, 'GET', '', 1, array());
        //print __METHOD__." Result for unexisting user: ".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object=json_decode($result['content'], true);
        $this->assertNotNull($object, "Parsing of json result must no be null");
        $this->assertEquals(404, $object['error']['code']);

        $url = $this->api_url.'/users/1?api_key='.$this->api_key;

        print __METHOD__." Request GET url=".$url."\n";
        $result=getURLContent($url, 'GET', '', 1, array());
        //print __METHOD__." Result for existing user user: ".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object=json_decode($result['content'], true);
        $this->assertNotNull($object, "Parsing of json result must no be null");
        $this->assertEquals(1, $object['statut']);
    }

    /**
     * testRestCreateUser
     *
     * @return void
     */
    public function testRestCreateUser()
    {

        // attemp to create without mandatory fields :
        $url = $this->api_url.'/users?api_key='.$this->api_key;
        $addheaders=array('Content-Type: application/json');

        $bodyobj = array(
            "lastname"=>"testRestUser",
            "password"=>"testRestPassword",
            "email"=>"test@restuser.com"
        );
        $body = json_encode($bodyobj);

        print __METHOD__." Request POST url=".$url."\n";
        $result=getURLContent($url, 'POST', $body, 1, $addheaders);
        //print __METHOD__." Result for creating incomplete user".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object=json_decode($result['content'], true);
        $this->assertNotNull($object, "Parsing of json result must no be null");
        $this->assertEquals(500, $object['error']['code'], $object['error']['code'].' '.$object['error']['message']);

        // create regular user
        unset($result);
        $bodyobj = array(
            "login"=>"testRestLogin".mt_rand(),
            "lastname"=>"testRestUser",
            "password"=>"testRestPassword",
            "email"=>"test@restuser.com"
        );
        $body = json_encode($bodyobj);
        print __METHOD__." Request POST url=".$url."\n";
        $result=getURLContent($url, 'POST', $body, 1, $addheaders);
        print __METHOD__." Result code for creating user ".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $resid=json_decode($result['content'], true);
        $this->assertNotNull($resid, "Parsing of json result must no be null");
        $this->assertGreaterThan(0, $resid, $object['error']['code'].' '.$object['error']['message']);

        // attempt to create duplicated user
        print __METHOD__." Request POST url=".$url."\n";
        $result=getURLContent($url, 'POST', $body, 1, $addheaders);
        //print __METHOD__." Result for creating duplicate user".var_export($result, true)."\n";
        print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object=json_decode($result['content'], true);
        $this->assertNotNull($object, "Parsing of json result must no be null");
        $this->assertEquals(500, $object['error']['code'], $object['error']['code'].' '.$object['error']['message']);
    }
}
