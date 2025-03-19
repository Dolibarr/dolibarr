<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';

require_once __DIR__."/AbstractRestAPITest.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIUserTest extends AbstractRestAPITest
{
	/**
	 * testRestGetUser
	 *
	 * @return int
	 */
	public function testRestGetUser()
	{
		global $conf,$user,$langs,$db;

		$test = "Invalid User -";
		$url = $this->api_url.'/users/123456789?api_key='.$this->api_key;
		//$addheaders=array('Content-Type: application/json');

		print __METHOD__." Request GET url=".$url."\n";
		$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		//print __METHOD__." result for get on unexisting user: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test Should not have a curl error");
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertEquals(404, (empty($object['error']['code']) ? 0 : $object['error']['code']), "$test Error code is not 404");

		$test = "Existing User -";
		$url = $this->api_url.'/users/1?api_key='.$this->api_key;

		print __METHOD__." Request GET url=".$url."\n";
		$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." $test result for get: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test should have no error");
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertEquals(1, $object['statut']);

		return $object['id'];
	}

	/**
	 * testRestCreateUser
	 *
	 * @return void
	 *
	 * @depends testRestGetUser
	 * The depends says test is run only if previous is ok
	 */
	public function testRestCreateUser()
	{
		// attempt to create without mandatory fields :
		$test = "Create User Missing Fields -";
		$url = $this->api_url.'/users?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');

		$bodyobj = array(
			"lastname" => "testRestUser",
			"password" => "testRestPassword",
			"email" => "test@restuser.com"
		);
		$body = json_encode($bodyobj);

		print __METHOD__." Request POST url=".$url."\n";
		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
		//print __METHOD__." Result for creating incomplete user".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test Should not have a curl error");
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), "$test Error".(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));

		// create regular user
		$test = "Create Regular User -";
		unset($result);
		$bodyobj = array(
			"login" => "testRestLogin".mt_rand(),
			"lastname" => "testRestUser",
			"password" => "testRestPassword",
			"email" => "test".mt_rand()."@restuser.com"
		);
		$body = json_encode($bodyobj);
		print __METHOD__." Request POST url=".$url."\n";
		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
		print __METHOD__." result code for creating non existing user = ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test Should not have a curl error");
		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array

		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertNotEquals(500, ((is_scalar($object) || empty($object['error']) || empty($object['error']['code'])) ? 0 : $object['error']['code']), "$test Error".(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
		$this->assertGreaterThan(0, $object, 'ID returned is no > 0');

		// attempt to create duplicated user
		$test = "Create Duplicate User -";
		print __METHOD__." Request POST url=".$url."\n";
		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
		//print __METHOD__." Result for creating duplicate user".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test Should not have a curl error");
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), "$test Error".(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
	}
}
