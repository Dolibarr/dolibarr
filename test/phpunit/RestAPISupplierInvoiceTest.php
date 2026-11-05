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
 *      \file       test/phpunit/RestAPISupplierInvoiceTest.php
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
class RestAPISupplierInvoiceTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('supplier_invoice'), 'module supplier invoice must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string		$endpoint 	Endpoint - "supplierinvoices/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'GET'|'POST'|'PUT'|'DELETE'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded (POST/PUT) or sent as query params (GET/DELETE)
	 * @param int			$expected_error	Expected error code
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
	 * Create a draft supplier invoice
	 *
	 * @param string	$test	Text to be added to output
	 *
	 * @return int		Id of the invoice
	 */
	private function createInvoice($test)
	{
		$data = array(
			"socid" => 1,
			"ref_supplier" => "TEST".mt_rand(0, 99999999),
		);
		$result = $this->getUrl('supplierinvoices', $test, 'POST', $data);
		$invoice_id = (int) $result['content'];
		$this->assertGreaterThan(0, $invoice_id, "$test Id of invoice is not > 0");

		return $invoice_id;
	}

	/**
	 * Get the data of a line, with a unit price of 10 and no VAT, to POST or PUT
	 *
	 * @param float		$qty	Quantity
	 *
	 * @return array<string,mixed>	Data of the line
	 */
	private function getLineData($qty)
	{
		return array(
			"description" => "RestAPISupplierInvoiceTest line",
			"pu_ht" => 10,
			"tva_tx" => 0,
			"localtax1_tx" => 0,
			"localtax2_tx" => 0,
			"qty" => $qty,
			"fk_product" => 0,
			"remise_percent" => 0,
			"date_start" => "",
			"date_end" => "",
			"fk_code_ventilation" => 0,
			"info_bits" => 0,
			"price_base_type" => "HT",
			"product_type" => 0,
			"rang" => 1,
			"array_options" => array(),
			"fk_unit" => null,
			"origin_id" => 0,
			"multicurrency_subprice" => 0,
			"ref_supplier" => "",
			"special_code" => 0,
		);
	}

	/**
	 * Add a line to a supplier invoice
	 *
	 * @param string	$test		Text to be added to output
	 * @param int		$invoice_id	Id of the invoice
	 * @param float		$qty		Quantity
	 *
	 * @return int		Id of the line
	 */
	private function addLine($test, $invoice_id, $qty)
	{
		$result = $this->getUrl('supplierinvoices/'.$invoice_id.'/lines', $test, 'POST', $this->getLineData($qty));
		$line_id = (int) $result['content'];
		$this->assertGreaterThan(0, $line_id, "$test Id of line is not > 0");

		return $line_id;
	}

	/**
	 * testRestSupplierInvoiceGetLines
	 *
	 * @return void
	 */
	public function testRestSupplierInvoiceGetLines()
	{
		$test = "Get lines of supplier invoice";

		$data = array(
			"socid" => 1,
			"ref_supplier" => "TEST".mt_rand(0, 99999999),
		);
		$result = $this->getUrl('supplierinvoices', $test, 'POST', $data);
		$invoice_id = (int) $result['content'];
		$this->assertGreaterThan(0, $invoice_id, "$test Id of invoice is not > 0");

		$data = array(
			"description" => "testRestSupplierInvoiceGetLines line",
			"pu_ht" => 10,
			"tva_tx" => 0,
			"localtax1_tx" => 0,
			"localtax2_tx" => 0,
			"qty" => 2,
			"fk_product" => 0,
			"remise_percent" => 0,
			"date_start" => 0,
			"date_end" => 0,
			"fk_code_ventilation" => 0,
			"info_bits" => 0,
			"price_base_type" => "HT",
			"product_type" => 0,
			"rang" => -1,
			"array_options" => array(),
			"fk_unit" => null,
			"origin_id" => 0,
			"multicurrency_subprice" => 0,
			"ref_supplier" => "",
			"special_code" => 0,
		);
		$result = $this->getUrl('supplierinvoices/'.$invoice_id.'/lines', $test, 'POST', $data);
		$line_id = (int) $result['content'];
		$this->assertGreaterThan(0, $line_id, "$test Id of line is not > 0");

		$result = $this->getUrl('supplierinvoices/'.$invoice_id.'/lines', $test, 'GET');
		$this->assertIsArray($result['content'], "$test Lines must be an array");
		$this->assertCount(1, $result['content'], "$test Invoice must have one line");
		$this->assertEquals($line_id, $result['content'][0]['id'] ?? null, "$test Line returned must be the line added");
		$this->assertEquals(2, $result['content'][0]['qty'] ?? null, "$test Quantity of line");
	}

	/**
	 * testRestSupplierInvoiceGetLinesNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierInvoiceGetLinesNotFound()
	{
		$test = "Get lines of a supplier invoice that does not exist";

		$this->getUrl('supplierinvoices/999999999/lines', $test, 'GET', null, 404);
	}

	/**
	 * testRestSupplierInvoiceDraftLinesCanBeChanged
	 *
	 * @return void
	 */
	public function testRestSupplierInvoiceDraftLinesCanBeChanged()
	{
		$test = "Change the lines of a draft supplier invoice";

		$invoice_id = $this->createInvoice($test);
		$line_id = $this->addLine($test, $invoice_id, 2);
		$other_line_id = $this->addLine($test, $invoice_id, 1);

		$this->getUrl('supplierinvoices/'.$invoice_id.'/lines/'.$line_id, $test, 'PUT', $this->getLineData(3));

		$result = $this->getUrl('supplierinvoices/'.$invoice_id, $test, 'GET');
		$this->assertEquals(40, $result['content']['total_ht'] ?? null, "$test Total after updating a line");

		$this->getUrl('supplierinvoices/'.$invoice_id.'/lines/'.$other_line_id, $test, 'DELETE');

		$result = $this->getUrl('supplierinvoices/'.$invoice_id.'/lines', $test, 'GET');
		$this->assertCount(1, $result['content'], "$test Invoice must have one line left");
		$this->assertEquals($line_id, $result['content'][0]['id'] ?? null, "$test Line left must be the line updated");
		$this->assertEquals(3, $result['content'][0]['qty'] ?? null, "$test Quantity of line");

		$result = $this->getUrl('supplierinvoices/'.$invoice_id, $test, 'GET');
		$this->assertEquals(30, $result['content']['total_ht'] ?? null, "$test Total after deleting a line");
	}

	/**
	 * testRestSupplierInvoiceValidatedLinesCannotBeChanged
	 *
	 * @return void
	 */
	public function testRestSupplierInvoiceValidatedLinesCannotBeChanged()
	{
		$test = "Change the lines of a validated supplier invoice";

		$invoice_id = $this->createInvoice($test);
		$line_id = $this->addLine($test, $invoice_id, 2);

		$this->getUrl('supplierinvoices/'.$invoice_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$this->getUrl('supplierinvoices/'.$invoice_id.'/lines/'.$line_id, "$test (update)", 'PUT', $this->getLineData(3), 405);
		$this->getUrl('supplierinvoices/'.$invoice_id.'/lines/'.$line_id, "$test (delete)", 'DELETE', null, 405);

		$result = $this->getUrl('supplierinvoices/'.$invoice_id.'/lines', $test, 'GET');
		$this->assertCount(1, $result['content'], "$test Invoice must still have its line");
		$this->assertEquals($line_id, $result['content'][0]['id'] ?? null, "$test Line must be unchanged");
		$this->assertEquals(2, $result['content'][0]['qty'] ?? null, "$test Quantity of line must be unchanged");

		$result = $this->getUrl('supplierinvoices/'.$invoice_id, $test, 'GET');
		$this->assertEquals(20, $result['content']['total_ht'] ?? null, "$test Total must be unchanged");
		$this->assertEquals(20, $result['content']['total_ttc'] ?? null, "$test Total with tax must be unchanged");
	}
}
