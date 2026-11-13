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
	 * @param 'POST'|'GET'|'PUT'|'DELETE'	$method		Call Method
	 * @param mixed				$data		Data that will be JSON encoded (POST/PUT) or sent as query params (GET/DELETE)
	 * @param int				$expected_error	Expected error code
	 *
	 * @return mixed	Decodes JSON value returned by the service
	 */
	private function getUrl($endpoint, $test_title = "", $method = "GET", $data = null, $expected_error = 0)
	{
		$params = '';
		$body = null;
		if ($method === 'POST' || $method === 'PUT') {
			$body = json_encode($data);
		} elseif (!empty($data)) {
			$params = '?'.http_build_query($data);
		}

		$addheaders = array(
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: {$this->api_key}"
		);

		$url = $this->api_url.'/'.$endpoint.$params;

		$httpmethod = ($method === 'PUT') ? 'PUTALREADYFORMATED' : $method;

		$result = getURLContent($url, $httpmethod, $body, 1, $addheaders, array('http', 'https'), 2);

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
	 * Create a draft contract
	 *
	 * @param string	$test	Text to be added to output
	 *
	 * @return int		Id of the contract
	 */
	private function createContract($test)
	{
		$data = array(
			"socid" => 1,
			"date_contrat" => dol_now(),
		);
		$result = $this->getUrl('contracts', "$test (create contract)", 'POST', $data);
		$contract_id = (int) $result['content'];
		$this->assertGreaterThan(0, $contract_id, "$test Id of contract is not > 0");

		return $contract_id;
	}

	/**
	 * Add a free text line to a contract
	 *
	 * @param int		$contract_id	Id of the contract
	 * @param string	$test			Text to be added to output
	 * @param ?int		$date_end		Planned end date of the line
	 *
	 * @return int		Id of the line
	 */
	private function createContractLine($contract_id, $test, $date_end = null)
	{
		$data = array(
			"desc" => "API test service",
			"subprice" => 10,
			"qty" => 1,
			"tva_tx" => 0,
			"localtax1_tx" => 0,
			"localtax2_tx" => 0,
			"fk_product" => 0,
			"remise_percent" => 0,
			"date_start" => null,
			"date_end" => $date_end,
			"price_base_type" => "HT",
			"subprice_excl_tax" => 0,
			"info_bits" => 0,
			"fk_fournprice" => null,
			"pa_ht" => 0,
			"array_options" => array(),
			"fk_unit" => null,
			"rang" => 0,
		);
		$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (create line)", 'POST', $data);
		$line_id = (int) $result['content'];
		$this->assertGreaterThan(0, $line_id, "$test Id of line is not > 0");

		return $line_id;
	}

	/**
	 * testRestContractDeleteLineUnknownLine
	 *
	 * @return void
	 */
	public function testRestContractDeleteLineUnknownLine()
	{
		$test = "Delete a line that does not exist";

		$contract_id = $this->createContract($test);

		try {
			$this->getUrl('contracts/'.$contract_id.'/lines/999999999', $test, 'DELETE', null, 404);
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractDeleteLine
	 *
	 * @return void
	 */
	public function testRestContractDeleteLine()
	{
		$test = "Delete a line";

		$contract_id = $this->createContract($test);

		try {
			$line_id = $this->createContractLine($contract_id, $test);
			$kept_line_id = $this->createContractLine($contract_id, $test);

			$this->getUrl('contracts/'.$contract_id.'/lines/'.$line_id, $test, 'DELETE');

			$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read after delete)", 'GET');
			$line_ids = array_map('intval', array_column($result['content'], 'id'));
			$this->assertNotContains($line_id, $line_ids, "$test Deleted line must be gone");
			$this->assertContains($kept_line_id, $line_ids, "$test Other line must be kept");
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractActivateLineUnknownLine
	 *
	 * @return void
	 */
	public function testRestContractActivateLineUnknownLine()
	{
		$test = "Activate a line that does not exist";

		$contract_id = $this->createContract($test);

		try {
			$this->getUrl('contracts/'.$contract_id.'/lines/999999999/activate', $test, 'PUT', array("datestart" => dol_now()), 404);
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractActivateLineOfAnotherContract
	 *
	 * @return void
	 */
	public function testRestContractActivateLineOfAnotherContract()
	{
		$test = "Activate a line of another contract";

		$contract_id = $this->createContract($test);

		try {
			$other_contract_id = $this->createContract($test);

			try {
				$other_line_id = $this->createContractLine($other_contract_id, $test);

				$this->getUrl('contracts/'.$contract_id.'/lines/'.$other_line_id.'/activate', $test, 'PUT', array("datestart" => dol_now()), 403);
			} finally {
				$this->getUrl('contracts/'.$other_contract_id, "$test (cleanup other contract)", 'DELETE');
			}
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractActivateLineKeepsPlannedEndDate
	 *
	 * @return void
	 */
	public function testRestContractActivateLineKeepsPlannedEndDate()
	{
		$test = "Activate a line without dateend";

		$contract_id = $this->createContract($test);

		try {
			$line_id = $this->createContractLine($contract_id, $test, dol_now() + 30 * 24 * 3600);

			$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read before activate)", 'GET');
			$date_end = $result['content'][0]['date_end'] ?? '';
			$this->assertNotEmpty($date_end, "$test Line must have a planned end date before activation");

			$this->getUrl('contracts/'.$contract_id.'/lines/'.$line_id.'/activate', $test, 'PUT', array("datestart" => dol_now()));

			$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read after activate)", 'GET');
			$this->assertNotEmpty($result['content'][0]['date_start_real'] ?? '', "$test Line must be activated");
			$this->assertEquals($date_end, $result['content'][0]['date_end'] ?? '', "$test Planned end date must be kept");
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractUnactivateLineUnknownLine
	 *
	 * @return void
	 */
	public function testRestContractUnactivateLineUnknownLine()
	{
		$test = "Unactivate a line that does not exist";

		$contract_id = $this->createContract($test);

		try {
			$this->getUrl('contracts/'.$contract_id.'/lines/999999999/unactivate', $test, 'PUT', array("datestart" => dol_now()), 404);
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractUnactivateLineOfAnotherContract
	 *
	 * @return void
	 */
	public function testRestContractUnactivateLineOfAnotherContract()
	{
		$test = "Unactivate a line of another contract";

		$contract_id = $this->createContract($test);

		try {
			$other_contract_id = $this->createContract($test);

			try {
				$other_line_id = $this->createContractLine($other_contract_id, $test);

				$this->getUrl('contracts/'.$contract_id.'/lines/'.$other_line_id.'/unactivate', $test, 'PUT', array("datestart" => dol_now()), 403);
			} finally {
				$this->getUrl('contracts/'.$other_contract_id, "$test (cleanup other contract)", 'DELETE');
			}
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractUnactivateLine
	 *
	 * @return void
	 */
	public function testRestContractUnactivateLine()
	{
		$test = "Unactivate an active line";

		$contract_id = $this->createContract($test);

		try {
			$line_id = $this->createContractLine($contract_id, $test);

			$this->getUrl('contracts/'.$contract_id.'/lines/'.$line_id.'/activate', "$test (activate)", 'PUT', array("datestart" => dol_now()));

			$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read before unactivate)", 'GET');
			$this->assertEquals(4, $result['content'][0]['statut'] ?? '', "$test Line must be active before unactivation");
			$this->assertEmpty($result['content'][0]['date_end_real'] ?? '', "$test Line must not have a real end date before unactivation");

			$this->getUrl('contracts/'.$contract_id.'/lines/'.$line_id.'/unactivate', $test, 'PUT', array("datestart" => dol_now()));

			$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read after unactivate)", 'GET');
			$this->assertEquals(5, $result['content'][0]['statut'] ?? '', "$test Line must be closed");
			$this->assertNotEmpty($result['content'][0]['date_end_real'] ?? '', "$test Line must have a real end date");
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * Data of a contract line, with every field read by POST and PUT contracts/{id}/lines
	 *
	 * @param string	$desc	Description of the line
	 *
	 * @return array<string,mixed>
	 */
	private function getLineData($desc)
	{
		return array(
			"desc" => $desc,
			"subprice" => 20,
			"qty" => 2,
			"tva_tx" => 0,
			"localtax1_tx" => 0,
			"localtax2_tx" => 0,
			"remise_percent" => 0,
			"fk_product" => 0,
			"date_start" => null,
			"date_end" => null,
			"date_start_real" => null,
			"date_end_real" => null,
			"price_base_type" => "HT",
			"subprice_excl_tax" => 0,
			"info_bits" => 0,
			"fk_fournprice" => null,	// read by POST
			"fk_fourn_price" => null,	// read by PUT
			"pa_ht" => 5,
			"array_options" => array(),
			"fk_unit" => null,
		);
	}

	/**
	 * Read one line of a contract
	 *
	 * @param int		$contract_id	Id of the contract
	 * @param int		$line_id		Id of the line
	 * @param string	$test			Text to be added to output
	 *
	 * @return array<string,mixed>		The line
	 */
	private function getContractLine($contract_id, $line_id, $test)
	{
		$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (read lines)", 'GET');
		$found = array();
		foreach ($result['content'] as $line) {
			if ((int) $line['id'] === $line_id) {
				$found = $line;
				break;
			}
		}
		$this->assertNotEmpty($found, "$test Line $line_id not found in contract $contract_id");

		return $found;
	}

	/**
	 * testRestContractPutLineUnknownLine
	 *
	 * @return void
	 */
	public function testRestContractPutLineUnknownLine()
	{
		$test = "Update a line that does not exist";

		$contract_id = $this->createContract($test);

		try {
			$this->getUrl('contracts/'.$contract_id.'/lines/999999999', $test, 'PUT', $this->getLineData("Updated"), 404);
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractPutLineKeepsPosition
	 *
	 * @return void
	 */
	public function testRestContractPutLineKeepsPosition()
	{
		$test = "Update a line without rang";

		$contract_id = $this->createContract($test);

		try {
			$line_ids = array();
			foreach (array(1, 2) as $rang) {
				$data = $this->getLineData("API test service ".$rang);
				$data["rang"] = $rang;
				$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (create line $rang)", 'POST', $data);
				$line_ids[$rang] = (int) $result['content'];
				$this->assertGreaterThan(0, $line_ids[$rang], "$test Id of line $rang is not > 0");
			}

			$line = $this->getContractLine($contract_id, $line_ids[2], "$test (before update)");
			$this->assertEquals(2, $line['rang'] ?? '', "$test Second line must be at position 2 before update");

			$this->getUrl('contracts/'.$contract_id.'/lines/'.$line_ids[2], $test, 'PUT', $this->getLineData("Updated"));

			$line = $this->getContractLine($contract_id, $line_ids[2], "$test (after update)");
			$this->assertEquals("Updated", $line['description'] ?? '', "$test Line must be updated");
			$this->assertEquals(2, $line['rang'] ?? '', "$test Position of the line must be kept");
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
	}

	/**
	 * testRestContractPaginationLimitZero
	 *
	 * @return void
	 */
	public function testRestContractPaginationLimitZero()
	{
		$test = "Pagination data with limit=0";

		$contract_id = $this->createContract($test);

		try {
			$other_contract_id = $this->createContract($test);

			try {
				$this->createContractLine($contract_id, $test);
				$this->createContractLine($contract_id, $test);

				// limit=0 means no limit: must not fail with a division by zero, everything is on 1 page
				$data = array("sqlfilters" => "(t.rowid:in:".$contract_id.",".$other_contract_id.")", "limit" => 0, "pagination_data" => "true");
				$result = $this->getUrl('contracts', "$test (list)", 'GET', $data);
				$this->assertEquals(200, $result['http_code'], "$test (list) Return code must be 200");
				$this->assertCount(2, $result['content']['data'], "$test (list) All contracts must be returned");
				$this->assertEquals(2, $result['content']['pagination']['total'], "$test (list) Total");
				$this->assertEquals(1, $result['content']['pagination']['page_count'], "$test (list) Everything is on 1 page");

				// limit=1: 2 contracts on 2 pages
				$data["limit"] = 1;
				$result = $this->getUrl('contracts', "$test (list, limit=1)", 'GET', $data);
				$this->assertCount(1, $result['content']['data'], "$test (list, limit=1) Only 1 contract must be returned");
				$this->assertEquals(2, $result['content']['pagination']['page_count'], "$test (list, limit=1) 2 pages");

				$data = array("limit" => 0, "pagination_data" => "true");
				$result = $this->getUrl('contracts/'.$contract_id.'/lines', "$test (lines)", 'GET', $data);
				$this->assertEquals(200, $result['http_code'], "$test (lines) Return code must be 200");
				$this->assertCount(2, $result['content']['data'], "$test (lines) All lines must be returned");
				$this->assertEquals(2, $result['content']['pagination']['total'], "$test (lines) Total");
				$this->assertEquals(1, $result['content']['pagination']['page_count'], "$test (lines) Everything is on 1 page");
			} finally {
				$this->getUrl('contracts/'.$other_contract_id, "$test (cleanup other contract)", 'DELETE');
			}
		} finally {
			$this->getUrl('contracts/'.$contract_id, "$test (cleanup contract)", 'DELETE');
		}
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
