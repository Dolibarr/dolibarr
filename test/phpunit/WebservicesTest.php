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
 *      \file       test/phpunit/DateLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/lib/date.lib.php';
require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP


if (empty($user->id))
{
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * When no cover is provided. We use everything.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class WebservicesTest extends PHPUnit_Framework_TestCase
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
	function WebservicesTest()
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
    public function testWSVersion()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

        $WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_other.php';
        //$WS_DOL_URL = 'http://localhost:8080/';   // If not a page, should end with /
        $WS_METHOD  = 'getVersions';
        $ns='http://www.dolibarr.org/ns/';


        // Set the WebService URL
        print __METHOD__."Create nusoap_client for URL=".$WS_DOL_URL."\n";
        $soapclient = new nusoap_client($WS_DOL_URL);
        if ($soapclient)
        {
            $soapclient->soap_defencoding='UTF-8';
            $soapclient->decodeUTF8(false);
        }

        // Call the WebService method and store its result in $result.
        $authentication=array(
            'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
            'sourceapplication'=>'DEMO',
            'login'=>'admin',
            'password'=>'admin',
            'entity'=>'');

        // Test URL
        if ($WS_METHOD)
        {
            $parameters = array('authentication'=>$authentication);
            print __METHOD__."Call method ".$WS_METHOD."\n";
            $result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
            if (! $result)
            {
                //var_dump($soapclient);
                //print_r($soapclient);
                print $soapclient->error_str;
                print "<br>\n\n";
                print $soapclient->request;
                print "<br>\n\n";
                print $soapclient->response;
                exit;
            }
        }

        print __METHOD__." result=".$result."\n";
        $this->assertEquals('OK',$result['result']['result_code']);

		return $result;
    }


}
?>