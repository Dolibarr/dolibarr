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
 *      \file       test/phpunit/WebservicesInvoicesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once(NUSOAP_PATH.'/nusoap.php');        // Include SOAP


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
class WebservicesInvoicesTest extends PHPUnit_Framework_TestCase
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
		
		// create a third_party, needed to create an invoice
		$societe=new Societe($db);
		$societe->ref='';
		$societe->name='name';
		$societe->ref_ext='209';
		$societe->status=1;
		$societe->client=1;
		$societe->fournisseur=0;
		$societe->date_creation=$now;
		$societe->tva_assuj=0;
		$societe->particulier=0;
		
		$societe->create($user);
		
		print __METHOD__." societe created id=".$societe->id."\n";
		

    	print __METHOD__."\n";
    }
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
     * testWSInvoicesCreateInvoice
     *
     * @return int
     */
    public function testWSInvoicesCreateInvoice()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php';
    	$WS_METHOD  = 'createInvoice';
    	$ns='http://www.dolibarr.org/ns/';

    	// load societe first
    	$societe=new Societe($db);
    	$societe->fetch('', '', '209');
    	print __METHOD__." societe loaded id=".$societe->id."\n";

    	// Set the WebService URL
    	print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
    	$soapclient = new nusoap_client($WS_DOL_URL);
    	if ($soapclient)
    	{
    		$soapclient->soap_defencoding='UTF-8';
    		$soapclient->decodeUTF8(false);
    	}
    
    	
    	$body = array (
    			"id" => NULL,
				"ref" => NULL,
				"ref_ext" => "165",
				"thirdparty_id" => $societe->id,
				"fk_user_author" => NULL,
				"fk_user_valid" => NULL,
				"date" => "2015-04-19 20:16:53",
				"date_due" => "",
				"date_creation" => "",
				"date_validation" => "",
				"date_modification" =>  "",
				"type" => "",
				"total_net" => "36.30",
				"total_vat" => "6.00",
				"total" => "42.30",
				"payment_mode_id" => 50,
				"note_private" => "Synchronised from Prestashop",
				"note_public" => "",
				"status" => "1",
				"close_code" => NULL ,
				"close_note" => NULL,
				"project_id" => NULL,
				"lines" => array(
					"id" => NULL,
					"type" => 0,
					"desc" => "Horloge Vinyle Serge",
					"vat_rate" => 20,
					"qty" => "1",
					"unitprice" => "30.000000",
					"total_net" => "30.000000",
					"total_vat" => "6.00",
					"total" => "36.000000",
					"date_start" => "",
					"date_end" => "",
					"payment_mode_id" => "",
					"product_id" => "",
					"product_ref" => "",
					"product_label" => "",
					"product_desc" => "" )
					);

    	// Call the WebService method and store its result in $result.
    	$authentication=array(
    	'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    	'sourceapplication'=>'DEMO',
    	'login'=>'admin',
    	'password'=>'admin',
    	'entity'=>'');

    	// Test URL
    	$result='';
    	$parameters = array('authentication'=>$authentication,'invoice'=>$body);
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
    	}
    	catch(SoapFault $exception)
    	{
    		echo $exception;
    		$result=0;
    	}
    	if (! $result || ! empty($result['faultstring']))
    	{
    		//var_dump($soapclient);
    		print $soapclient->error_str;
    		print "\n<br>\n";
    		print $soapclient->request;
    		print "\n<br>\n";
    		print $soapclient->response;
    		print "\n";
    	}

    	print __METHOD__." result=".$result['result']['result_label']."\n";
    	$this->assertEquals('OK',$result['result']['result_code']);
    	$this->assertEquals('165', $result['ref_ext']);


    	return $result;
    }
    
    /**
     * testWSInvoicesGetInvoice
     *
     * @return int
     */
    public function testWSInvoicesGetInvoice()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php';
    	$WS_METHOD  = 'getInvoice';
    	$ns='http://www.dolibarr.org/ns/';

    	// Set the WebService URL
    	print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
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
    	$result='';
    	$parameters = array('authentication'=>$authentication,'id'=>NULL,'ref'=>NULL,'ref_ext'=>165);
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
    	}
    	catch(SoapFault $exception)
    	{
    		echo $exception;
    		$result=0;
    	}
    	if (! $result || ! empty($result['faultstring']))
    	{
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
    	$this->assertEquals('165', $result['result']['invoice']->ref_ext);


    	return $result;
    }
    
    /**
     * testWSInvoicesUpdateInvoice
     *
     * @return int
     */
    public function testWSInvoicesUpdateInvoice()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php';
    	$WS_METHOD  = 'updateInvoice';
    	$ns='http://www.dolibarr.org/ns/';

    	// Set the WebService URL
    	print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
    	$soapclient = new nusoap_client($WS_DOL_URL);
    	if ($soapclient)
    	{
    		$soapclient->soap_defencoding='UTF-8';
    		$soapclient->decodeUTF8(false);
    	}
    	
    	// update status to 2
    	    	$body = array (
    			    "id" => NULL,
					"ref" => NULL,
				"ref_ext" => "165",
				"thirdparty_id" => "209",
				"fk_user_author" => NULL,
				"fk_user_valid" => NULL,
				"date" => "2015-04-19 20:16:53",
				"date_due" => "",
				"date_creation" => "",
				"date_validation" => "",
				"date_modification" =>  "",
				"type" => "",
				"total_net" => "36.30",
				"total_vat" => "6.00",
				"total" => "42.30",
				"payment_mode_id" => 50,
				"note_private" => "Synchronised from Prestashop",
				"note_public" => "",
				"status" => "2",
				"close_code" => NULL ,
				"close_note" => NULL,
				"project_id" => NULL,
				"lines"  => array(
					"id"  => NULL,
					"type" => 0,
					"desc" => "Horloge Vinyle Serge",
					"vat_rate" => 20,
					"qty" => "1",
					"unitprice" => "30.000000",
					"total_net" => "30.000000",
					"total_vat" => "6.00",
					"total" => "36.000000",
					"date_start" => "",
					"date_end" => "",
					"payment_mode_id" => "",
							"product_id" => "",
							"product_ref" => "",
							"product_label" => "",
							"product_desc" => "" )
								);

    	// Call the WebService method and store its result in $result.
    	$authentication=array(
    	'dolibarrkey'=>$conf->global->WEBSERVICES_KEY,
    	'sourceapplication'=>'DEMO',
    	'login'=>'admin',
    	'password'=>'admin',
    	'entity'=>'');

    	// Test URL
    	$result='';
    	$parameters = array('authentication'=>$authentication,'invoice'=>$body);
    	print __METHOD__." call method ".$WS_METHOD."\n";
    	try {
    		$result = $soapclient->call($WS_METHOD,$parameters,$ns,'');
    	}
    	catch(SoapFault $exception)
    	{
    		echo $exception;
    		$result=0;
    	}
    	if (! $result || ! empty($result['faultstring']))
    	{
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
    	$this->assertEquals('2', $result['result']['invoice']->status);


    	return $result;
    }

}
