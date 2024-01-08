<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
class RestAPIContactTest extends PHPUnit\Framework\TestCase
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
	 * @param 	string	$name		Name
	 * @return RestAPIContactTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		if (!isModEnabled('api')) {
			print __METHOD__." module api must be enabled.\n";
			die(1);
		}

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
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
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$this->api_url = DOL_MAIN_URL_ROOT.'/api/index.php';

		$login='admin';
		$password='admin';
		$url=$this->api_url.'/login?login='.$login.'&password='.$password;
		// Call the API login method to save api_key for this test class.
		// At first call, if token is not defined a random value is generated and returned.
		$result=getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result = ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals($result['curl_error_no'], '');
		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array

		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertNotEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Error'.(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
		$this->assertEquals('200', $object['success']['code']);

		$this->api_key = $object['success']['token'];

		print __METHOD__." api_key: $this->api_key \n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}


	/**
	 * testRestGetContact
	 *
	 * @return int
	 */
	public function testRestGetContact()
	{
		global $conf,$user,$langs,$db;
		//fetch Non-Existent contact
		$url = $this->api_url.'/contacts/123456789?api_key='.$this->api_key;
		//$addheaders=array('Content-Type: application/json');

		print __METHOD__." Request GET url=".$url."\n";
		$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result for get on unexisting contact: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals($result['curl_error_no'], '');
		$object=json_decode($result['content'], true);
		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertEquals(404, $object['error']['code'], 'Error code is not 404');

		//fetch an existent contact
		$url = $this->api_url.'/contacts/1?api_key='.$this->api_key;

		print __METHOD__." Request GET url=".$url."\n";
		$result=getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result for get on an existing contact: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals($result['curl_error_no'], '');
		$object=json_decode($result['content'], true);
		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertEquals(1, $object['statut']);
	}

	/**
	 * testRestCreateContact
	 *
	 * @return int
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestCreateContact()
	{
		global $conf,$user,$langs,$db;
		// attempt to create without mandatory fields
		$url = $this->api_url.'/contacts?api_key='.$this->api_key;
		$addheaders=array('Content-Type: application/json');

		$bodyobj= array(
			"firstname" => "firstname"
		);

		$body = json_encode($bodyobj);

		//print __METHOD__." Request POST url=".$url."\n";
		$result=getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
		//print __METHOD__." Result for creating incomplete contact".var_export($result, true)."\n";
		//print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals($result['curl_error_no'], '');
		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array
		$this->assertNotNull($object, "Parsing of json result must no be null");
		$this->assertEquals(400, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Error'.(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));

		$idofcontactcreated = (int) $object;

		// create regular contact
		unset($result);
		// Creating a Contact
		$bodyobj = array(
			"firstname" => "testRestContact" . mt_rand(),
			"lastname" => "testRestContact",
		);

		$body = json_encode($bodyobj);

		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals($result['curl_error_no'], '');

		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array
		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertNotEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Error'.(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
		$this->assertGreaterThan(0, $object, 'ID return is no > 0');

		return $idofcontactcreated;
	}

	/**
	 * testRestUpdateContact
	 *
	 * @param	int		$objid		Id of object created at previous test
	 * @return 	int
	 *
	 * @depends testRestCreateContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestUpdateContact($objid)
	{
		global $conf,$user,$langs,$db;
		// attempt to create without mandatory fields
		$url = $this->api_url.'/contacts?api_key='.$this->api_key;
		$addheaders=array('Content-Type: application/json');

		//update the contact

		// Update the firstname of the contact
		$updateBody = array(
			"firstname" => "UpdatedFirstName",
		);

		$updateRequestBody = json_encode($updateBody);
		$updateUrl = $this->api_url . '/contacts/' . $objid. '?api_key=' . $this->api_key;
		$updateResult = getURLContent($updateUrl, 'PUTALREADYFORMATED', $updateRequestBody, 1, $addheaders, array('http', 'https'), 2);
		$this->assertEquals($updateResult['curl_error_no'], '');

		$updateResponse = json_decode($updateResult['content'], true);

		$this->assertNotNull($updateResponse, "Parsing of JSON result must not be null");
		print_r($updateResponse);

		// Check if the updated fields match the changes you made
		$this->assertTrue($updateResponse['firstname'] === $updateBody['firstname'], 'Update might have failed');

		// Deleting the Contact
		/*
		$deleteUrl = $this->api_url . '/contacts/' . $objid . '?api_key=' . $this->api_key;

		$deleteResult = getURLContent($deleteUrl, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals($deleteResult['curl_error_no'], '');

		$deleteResponse = json_decode($deleteResult['content'], true);
		$this->assertNotNull($deleteResponse, "Parsing of json result must not be null");
		//$this->assertEquals(1, $deleteResponse, "Deletion should return a 200 status");



		// Update Non-Existent Contact



		// Delete Non-Existent Contact


		*/

		return 0;
	}
}
