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
class RestAPIMosTest extends AbstractRestAPITest
{
	/**
	 *
	 * @param string		$endpoint 	Endpoint - "user/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'POST'|'GET'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded
	 * @param int			$expected_error	Expected error code
	 *
	 * @return mixed	Decodes JSON value returned by the service
	 *
	 */
	private function getUrl($endpoint, $test_title = "", $method = "GET", $data = null, $expected_error = 0)
	{

		// Encode parameters in JSON body when POST, as URL query key/values otherwise.
		$params = '';
		$body = null;
		if ($method === 'POST') {
			$body = json_encode($data);
		} elseif (!empty($data)) {
			$params = '?'.http_build_query($data);
		}


		$addheaders = array(
		'Content-Type: application/json','Accept: application/json', "DOLAPIKEY: {$this->api_key}");

		// $url = $this->api_url.'/'.$endpoint.'?'.http_build_query(['api_key'=>$this->api_key]);
		//
		$url = $this->api_url.'/'.$endpoint.$params;

		// getURLContent($url, $postorget, $param, $followlocation, $addheaders, $allowedschemes, $localurl, $ssl_verifypeer)

		$result = getURLContent($url, $method, $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals('', $result['curl_error_no'], "{$test_title}Should not have a curl error");

		$object = json_decode($result['content'], true);

		$dbg_info = PHP_EOL.json_encode($result, JSON_PRETTY_PRINT);

		$this->assertNotNull($object, "{$test_title}Parsing of JSON result must not be null$dbg_info");

		$result['content'] = $object;
		$dbg_info = PHP_EOL.json_encode($result, JSON_PRETTY_PRINT);

		$this->assertEquals($expected_error, (empty($object['error']['code']) ? 0 : $object['error']['code']), "{$test_title}Error code is not $expected_error$dbg_info");

		return $result;
	}

	/**
	 * testRestMoCreate
	 *
	 * @return int
	 */
	public function testRestMoCreate()
	{

		$test = "Create MO ";
		$data = [
			'ref' => 'Try1',
			'mrptype' => 0,
			'fk_product' => 1,
			'qty' => 1,
			'status' => 1, // 0=Draft,1=Validated,2=InProgress,3=Produced,9=Canceled
		];
		$result = $this->getUrl('mos', $test, 'POST', $data);

		print json_encode($result, JSON_PRETTY_PRINT);
		$this->assertTrue(is_int($result['content']), "$test Result data is expected to be integer");

		/// return $object['id'];
		return $result['content'];
	}


	/**
	 * testRestMoList
	 *
	 * @depends testRestMoCreate
	 *
	 * @param int $mos_id Id of MO that was created
	 * @return void
	 */
	public function testRestMoList($mos_id)
	{

		$test = "Produce MO ";

		//$data = ['ref' => 'Try1', 'mrptype' => 0, 'fk_product' => 1, 'qty' => 1, 'status' => 0,  ];
		$data = null;
		$result = $this->getUrl("mos", $test, 'GET', ['sortfield' => 't.rowid', 'sortorder' => 'DESC', 'limit' => 100]);

		// print json_encode($result, JSON_PRETTY_PRINT);

		$this->assertEquals($mos_id, $result['content'][0]['id'] ?? null, "{$test}First item in reversed list should be new item");
	}

	/**
	 * testRestMoProduceAndConsume
	 *
	 * @depends testRestMoCreate
	 *
	 * @param int $mos_id Id of MO that was created
	 * @return int
	 */
	public function testRestMoProduceAndConsume($mos_id)
	{

		$test = "Produce and Consume MO ";

		$mos_state_id = 1; // $depends;



		$data
			= [
		 "inventorylabel" => "Produce and consume using API",
		 "inventorycode" => "PRODUCEAPI-YY-MM-DD",
		 "autoclose" => 1,
		 "arraytoconsume" => [],
		 "arraytoproduce" => [$mod_id] ];

		$result = $this->getUrl("mos/{$mos_state_id}/produceandconsumeall", $test, 'POST', $data);

		print json_encode($result, JSON_PRETTY_PRINT);
		$this->assertTrue(is_int($result['content']), "{$test}Result data is expected to be integer");

		/// return $object['id'];
		return $result['content'];
	}
}
