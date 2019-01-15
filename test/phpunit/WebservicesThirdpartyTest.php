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
 *      \file       test/phpunit/WebservicesThirdpartyTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP


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
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class WebservicesThirdpartyTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;
    protected $soapclient;

    private $_WS_DOL_URL;
    private $_ns='http://www.dolibarr.org/ns/';




    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return DateLibTest
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

        $this->_WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_thirdparty.php';

        // Set the WebService URL
        print __METHOD__." create nusoap_client for URL=".$this->_WS_DOL_URL."\n";
        $this->soapclient = new nusoap_client($this->_WS_DOL_URL);
        if ($this->soapclient) {
        	$this->soapclient->soap_defencoding='UTF-8';
        	$this->soapclient->decodeUTF8(false);
        }

        print __METHOD__." db->type=".$db->type." user->id=".$user->id;
        //print " - db ".$db->db;
        print "\n";
    }

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        $db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }

    /**
     * testWSThirdpartycreateThirdParty
     *
     * @return array thirdparty created
     */
    public function testWSThirdpartycreateThirdParty()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$WS_METHOD  = 'createThirdParty';


    	// Call the WebService method and store its result in $result.
    	$authentication=array(
    			'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    			'sourceapplication'=>'DEMO',
    			'login'=>'admin',
    			'password'=>'admin',
    			'entity'=>'');

    	$body = array (
    			"id" => null,
    			"ref" => "name",
    			"ref_ext" => "12",
    			"fk_user_author" => null,
    			"status" => null,
    			"client" => 1,
    			"supplier" => 0,
    			"customer_code" => "CU0901-5678",
    			"supplier_code" => "SU0901-5678",
    			"customer_code_accountancy" => "",
    			"supplier_code_accountancy" => "",
    			"date_creation" => "", // dateTime
    			"date_modification" => "", // dateTime
    			"note_private" => "",
    			"note_public" => "",
    			"address" => "",
    			"zip" => "",
    			"town" => "",
    			"province_id" => "",
    			"country_id" => "",
    			"country_code" => "",
    			"country" => "",
    			"phone" => "",
    			"fax" => "",
    			"email" => "",
    			"url" => "",
    			"profid1" => "",
    			"profid2" => "",
    			"profid3" => "",
    			"profid4" => "",
    			"profid5" => "",
    			"profid6" => "",
    			"capital" => "",
    			"vat_used" => "",
    			"vat_number" => ""
    	);

    	// Test URL
    	$result='';
    	$parameters = array('authentication'=>$authentication, 'thirdparty'=>$body);
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $this->soapclient->call($WS_METHOD,$parameters,$this->ns,'');
    	} catch(SoapFault $exception) {
    		echo $exception;
    		$result=0;
    	}
    	if (! $result || ! empty($result['faultstring'])) {
    		//var_dump($soapclient);
    		print $this->soapclient->error_str;
    		print "\n<br>\n";
    		print $this->soapclient->request;
    		print "\n<br>\n";
    		print $this->soapclient->response;
    		print "\n";
    	}

    	print __METHOD__." result=".$result['result']['result_code']."\n";
    	$this->assertEquals('OK',$result['result']['result_code']);
    	$this->assertEquals('name',$result['ref']);

    	return $result;
    }

    /**
     * testWSThirdpartygetThirdPartyById
     *
     * Use id to retrieve thirdparty
     * @depends testWSThirdpartycreateThirdParty
     *
     * @param	array	$result		thirdparty created by create method
     * @return	array				thirpdarty updated
     */
    public function testWSThirdpartygetThirdPartyById($result)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;
        $id = $result['id'];

        $WS_METHOD  = 'getThirdParty';

        // Call the WebService method and store its result in $result.
        $authentication=array(
        'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
        'sourceapplication'=>'DEMO',
        'login'=>'admin',
        'password'=>'admin',
        'entity'=>'');

        $result='';
        $parameters = array('authentication'=>$authentication, 'id'=>$id);
        print __METHOD__." call method ".$WS_METHOD."\n";
        try {
            $result = $this->soapclient->call($WS_METHOD,$parameters,$this->_ns,'');
        } catch(SoapFault $exception) {
            echo $exception;
            $result=0;
        }
        if (! $result || ! empty($result['faultstring'])) {
            //var_dump($soapclient);
            print $this->soapclient->error_str;
            print "\n<br>\n";
            print $this->soapclient->request;
            print "\n<br>\n";
            print $this->soapclient->response;
            print "\n";
        }

        print __METHOD__." result=".$result['result']['result_code']."\n";
        $this->assertEquals('OK',$result['result']['result_code']);
        $this->assertEquals($id, $result['thirdparty']['id']);
        $this->assertEquals('name', $result['thirdparty']['ref']);
        $this->assertEquals('12', $result['thirdparty']['ref_ext']);
        $this->assertEquals('0', $result['thirdparty']['status']);
        $this->assertEquals('1', $result['thirdparty']['client']);
        $this->assertEquals('0', $result['thirdparty']['supplier']);


        return $result;
    }

    /**
     * testWSThirdpartygetThirdPartyByRefExt
     *
     * Use ref_ext to retrieve thirdparty
     *
	 * @depends testWSThirdpartycreateThirdParty
	 *
     * @param	array	$result		thirdparty created by create method
     * @return	array				thirdparty
     */
    public function testWSThirdpartygetThirdPartyByRefExt($result)
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;
    	$id = $result['id'];

    	$WS_METHOD  = 'getThirdParty';

    	// Call the WebService method and store its result in $result.
    	$authentication=array(
    			'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    			'sourceapplication'=>'DEMO',
    			'login'=>'admin',
    			'password'=>'admin',
    			'entity'=>'');

    	// Test URL
    	$result='';
    	$parameters = array('authentication'=>$authentication, 'id'=>'', 'ref'=>'', 'ref_ext'=>'12');
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $this->soapclient->call($WS_METHOD,$parameters,$this->_ns,'');
    	} catch(SoapFault $exception) {
    		echo $exception;
    		$result=0;
    	}
    	print $this->soapclient->response;
    	if (! $result || ! empty($result['faultstring'])) {
    		//var_dump($soapclient);
    		print $this->soapclient->error_str;
    		print "\n<br>\n";
    		print $this->soapclient->request;
    		print "\n<br>\n";
    		print $this->soapclient->response;
    		print "\n";
    	}

    	print __METHOD__." result=".$result['result']['result_code']."\n";
    	$this->assertEquals('OK',$result['result']['result_code']);
    	$this->assertEquals($id, $result['thirdparty']['id']);
    	$this->assertEquals('name', $result['thirdparty']['ref']);
    	$this->assertEquals('12', $result['thirdparty']['ref_ext']);
    	$this->assertEquals('0', $result['thirdparty']['status']);
    	$this->assertEquals('1', $result['thirdparty']['client']);
    	$this->assertEquals('0', $result['thirdparty']['supplier']);


    	return $result;
    }

    /**
     * testWSThirdpartydeleteThirdParty
     *
     * @depends testWSThirdpartycreateThirdParty
     *
     * @param	array	$result		thirdparty created by create method
     * @return	array				thirdparty
     */
    public function testWSThirdpartydeleteThirdPartyById($result)
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;
    	$id = $result['id'];

    	$WS_METHOD  = 'deleteThirdParty';

    	// Call the WebService method and store its result in $result.
    	$authentication=array(
    			'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    			'sourceapplication'=>'DEMO',
    			'login'=>'admin',
    			'password'=>'admin',
    			'entity'=>'');

    	$result='';
    	$parameters = array('authentication'=>$authentication, 'id'=>$id, 'ref'=>'', 'ref_ext'=>'');
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $this->soapclient->call($WS_METHOD,$parameters,$this->_ns,'');
    	} catch(SoapFault $exception) {
    		echo $exception;
    		$result=0;
    	}
    	if (! $result || ! empty($result['faultstring'])) {
    		print $this->soapclient->error_str;
    		print "\n<br>\n";
    		print $this->soapclient->request;
    		print "\n<br>\n";
    		print $this->soapclient->response;
    		print "\n";
    	}

    	print __METHOD__." result=".$result['result']['result_code']."\n";
    	$this->assertEquals('OK',$result['result']['result_code']);

    	return $result;
    }
}
