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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
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
use \nategood\httpful;
use \Httpful\Request;


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
class RestAPIUserTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;
    protected $api_key;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return DateLibTest
     */
    function __construct()
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
        $api_url=DOL_MAIN_URL_ROOT.'/api/index.php';
        $login='admin';
        $password='admin';
        // run whthout json parsing
        $req = Request::init();
        $req->method("GET");
        $req->uri("$api_url/login?login=$login&password=$password");
        $res = $req->send();
        print __METHOD__." body: $res->body \n";
        // Call the API login method to save api_key for this test class
        $req = Request::init();
        $req->mime("application/json");
        $req->method("GET");
        $req->uri("$api_url/login?login=$login&password=$password");
        $res = $req->send();
        $this->assertEquals($res->code,200);
        $this->api_key = $res->body->success->token;
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
      $req = Request::init();
      $req->mime("application/json");
      $req->method("GET");
      $req->uri("$api_url/user/10??api_key=$this->api_key");
      $res = $req->send();
      print __METHOD__."code: $res->code";
      $this->assertEquals($res->code,404);
      $req = Request::init();
      $req->mime("application/json");
      $req->method("GET");
      $req->uri("$api_url/user/1??api_key=$this->api_key");
      $res = $req->send();
      print __METHOD__."code: $res->code";
      $this->assertEquals($res->code,200);
      $this->assertEquals($res->body->login,"admin");
    }

}
