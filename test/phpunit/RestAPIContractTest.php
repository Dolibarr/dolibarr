<?php
/* Copyright (C) 2026 William Mead <william@m34d.com>
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
 *      \file       test/phpunit/RestAPIContractTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

require_once __DIR__."/AbstractRestAPITest.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIContractTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('contract'), 'module contract must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string			$endpoint 	Endpoint - "contracts/id"
	 * @param string			$test_title	Text to be added to output
	 * @param 'POST'|'GET'|'DELETE'	$method		Call Method
	 * @param mixed				$data		Data that will be JSON encoded (POST) or sent as query params (GET/DELETE)
	 * @param int				$expected_error	Expected error code
	 *
	 * @return mixed	Decodes JSON value returned by the service
	 */
	private function getUrl($endpoint, $test_title = "", $method = "GET", $data = null, $expected_error = 0)
	{
		$params = '';
		$body = null;
		if ($method === 'POST') {
			$body = json_encode($data);
		} elseif (!empty($data)) {
			$params = '?'.http_build_query($data);
		}

		$addheaders = array(
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: {$this->api_key}"
		);

		$url = $this->api_url.'/'.$endpoint.$params;

		$result = getURLContent($url, $method, $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no'], $test_title." Should not have a curl error");

		$object = json_decode($result['content'], true);

		$dbg_info = PHP_EOL.json_encode($result, JSON_PRETTY_PRINT);

		$this->assertNotNull($object, $test_title." - Parsing of JSON result must not be null ".$dbg_info);

		$result['content'] = $object;
		$dbg_info = PHP_EOL.json_encode($result, JSON_PRETTY_PRINT);

		$resultcode = (empty($object['error']['code']) ? 0 : $object['error']['code']);
		$this->assertEquals($expected_error, $resultcode, $test_title." Error code is ".$resultcode." so not ".$expected_error.$dbg_info);

		return $result;
	}

	/**
	 * testRestContractPostExtrafieldHtml
	 *
	 * @return void
	 */
	public function testRestContractPostExtrafieldHtml()
	{
		$test = "Create a contract with an html extrafield";

		$attrname = 'apitest'.mt_rand(0, 99999999);
		$extrafield = array(
			'label' => $attrname, 'type' => 'html', 'pos' => 100, 'size' => '', 'list' => '1', 'default' => '', 'param' => '', 'perms' => '', 'help' => '',
			'computed' => '', 'langfile' => '', 'unique' => 0, 'required' => 0, 'alwayseditable' => 1, 'totalizable' => 0, 'printable' => 0, 'showintooltip' => 0,
		);
		$this->getUrl('setup/extrafields/contrat/'.$attrname, "$test (create extrafield)", 'POST', $extrafield);

		try {
			$data = array(
				"socid" => 1,
				"date_contrat" => dol_now(),
				"array_options" => array($attrname => '<strong>Bold</strong> text'),
			);
			$result = $this->getUrl('contracts', $test, 'POST', $data);
			$contract_id = (int) $result['content'];
			$this->assertGreaterThan(0, $contract_id, "$test Id of contract is not > 0");

			$result = $this->getUrl('contracts/'.$contract_id, "$test (read after create)", 'GET');
			$this->assertStringContainsString('<strong>Bold</strong>', $result['content']['array_options']['options_'.$attrname] ?? '', "$test Extrafield must keep its allowed markup");

			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		} finally {
			$this->getUrl('setup/extrafields/contrat/'.$attrname, "$test (cleanup extrafield)", 'DELETE');
		}
	}
}
