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
 *
 * Path to WSDL is: http://localhost/dolibarr/webservices/server_productorservice.php?wsdl
 */

/**
 *      \file       test/phpunit/WebservicesProductsTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP


if (empty($user->id)) {
    print "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;

$conf->global->MAIN_UMASK='0666';

if (empty($conf->service->enabled))
{
	print "Error: Module service must be enabled.\n";
	exit(1);
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class WebservicesProductsTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

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
     * testWSProductsCreateProductOrService
     *
     * @return int
     */
    public function testWSProductsCreateProductOrService()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $datestring=dol_print_date(dol_now(),'dayhourlog');

        $WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_productorservice.php';
        $WS_METHOD  = 'createProductOrService';
        $ns='http://www.dolibarr.org/ns/';

        // Set the WebService URL
        print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
        $soapclient = new nusoap_client($WS_DOL_URL);
        if ($soapclient) {
            $soapclient->soap_defencoding='UTF-8';
            $soapclient->decodeUTF8(false);
        }

        // Call the WebService method and store its result in $result.
        $authentication=array(
            'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
            'sourceapplication'=>'DEMO',
            'login'=>'admin',
            'password'=>'admin',
            'entity'=>''
        );

        // Test URL
        $result='';
        $parameters = array(
            'authentication'=>$authentication,'product'=>array(
                'ref'=>'NewProductFromWS'.$datestring,
                'label'=>'New Product From WS '.$datestring,
                'type'=>1,
                'description'=>'This is a new product created from WS PHPUnit test case',
                'barcode'=>'123456789012',
                'barcode_type'=>2
            )
        );
        print __METHOD__." call method ".$WS_METHOD."\n";
        try {
            $result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
        } catch(SoapFault $exception) {
            echo $exception;
            $result=0;
        }
        if (! $result || ! empty($result['faultstring']) || $result['result']['result_code'] != 'OK') {
            //var_dump($soapclient);
            print $soapclient->error_str;
            print "\n<br>\n";
            print $soapclient->request;
            print "\n<br>\n";
            print $soapclient->response;
            print "\n";
        }

        print __METHOD__." result=".$result."\n";
        $this->assertEquals('OK',$result['result']['result_code']);

        return $result['id'];
    }

    /**
     * testWSProductsGetProductOrService
     *
     * @param   int $id     Id of product or service
     * @return  int         Id of product or service
     *
     * @depends	testWSProductsCreateProductOrService
     */
    public function testWSProductsGetProductOrService($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_productorservice.php';
        $WS_METHOD  = 'getProductOrService';
        $ns='http://www.dolibarr.org/ns/';

        // Set the WebService URL
        print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
        $soapclient = new nusoap_client($WS_DOL_URL);
        if ($soapclient) {
            $soapclient->soap_defencoding='UTF-8';
            $soapclient->decodeUTF8(false);
        }

        // Call the WebService method and store its result in $result.
        $authentication=array(
            'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
            'sourceapplication'=>'DEMO',
            'login'=>'admin',
            'password'=>'admin',
            'entity'=>''
        );

        // Test URL
        $result='';
        $parameters = array('authentication'=>$authentication,'id'=>$id,'ref'=>'');
        print __METHOD__." call method ".$WS_METHOD."\n";
        try {
            $result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
        } catch(SoapFault $exception) {
            echo $exception;
            $result=0;
        }
        if (! $result || ! empty($result['faultstring'])) {
            //var_dump($soapclient);
            print $soapclient->error_str;
            print "\n<br>\n";
            print $soapclient->request;
            print "\n<br>\n";
            print $soapclient->response;
            print "\n";
        }

        print __METHOD__." result=".$result."\n";
        $this->assertEquals('OK',$result['result']['result_code']);

        return $id;
    }

    /**
     * testWSProductsDeleteProductOrService
     *
     * @param   int $id     Id of product or service
     * @return  int         0
     *
     * @depends testWSProductsGetProductOrService
     */
    public function testWSProductsDeleteProductOrService($id)
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_productorservice.php';
        $WS_METHOD  = 'deleteProductOrService';
        $ns='http://www.dolibarr.org/ns/';

        // Set the WebService URL
        print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
        $soapclient = new nusoap_client($WS_DOL_URL);
        if ($soapclient) {
            $soapclient->soap_defencoding='UTF-8';
            $soapclient->decodeUTF8(false);
        }

        // Call the WebService method and store its result in $result.
        $authentication=array(
            'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
            'sourceapplication'=>'DEMO',
            'login'=>'admin',
            'password'=>'admin',
            'entity'=>''
        );

        // Test URL
        $result='';
        $parameters = array('authentication'=>$authentication,'listofid'=>$id);
        print __METHOD__." call method ".$WS_METHOD."\n";
        try {
            $result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
        } catch(SoapFault $exception) {
            echo $exception;
            $result=0;
        }
        if (! $result || ! empty($result['faultstring']) || $result['result']['result_code'] != 'OK') {
            //var_dump($soapclient);
            print $soapclient->error_str;
            print "\n<br>\n";
            print $soapclient->request;
            print "\n<br>\n";
            print $soapclient->response;
            print "\n";
        }

        print __METHOD__." result=".$result."\n";
        $this->assertEquals('OK',$result['result']['result_code']);

        return 0;
    }

}
