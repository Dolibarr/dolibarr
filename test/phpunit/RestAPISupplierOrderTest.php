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
 *      \file       test/phpunit/RestAPISupplierOrderTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

require_once __DIR__."/AbstractRestAPITest.php";
require_once __DIR__."/../../htdocs/fourn/class/fournisseur.commande.class.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPISupplierOrderTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('supplier_order'), 'module supplier order must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string		$endpoint 	Endpoint - "supplierorders/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'POST'|'GET'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded
	 * @param int			$expected_error	Expected error code
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
	 * Create a draft supplier order
	 *
	 * @param string	$test	Text to be added to output
	 * @return int				Id of the supplier order
	 */
	private function createOrder($test)
	{
		$data = array(
			"socid" => 1,
			"ref_supplier" => "TR".mt_rand(0, 99999999),
		);
		$result = $this->getUrl('supplierorders', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of order is not > 0");

		return (int) $result['content'];
	}

	/**
	 * testRestSupplierOrderCancel
	 *
	 * @return int	Id of the canceled supplier order
	 */
	public function testRestSupplierOrderCancel()
	{
		$test = "Cancel supplier order";

		$order_id = $this->createOrder($test);

		// Only approved orders can be canceled
		$this->getUrl('supplierorders/'.$order_id.'/cancel', "$test (draft)", 'POST', array("comment" => ""), 405);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$this->getUrl('supplierorders/'.$order_id.'/cancel', "$test (validated)", 'POST', array("comment" => ""), 405);

		$this->getUrl('supplierorders/'.$order_id.'/approve', $test, 'POST', array("idwarehouse" => 0, "secondlevel" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/cancel', $test, 'POST', array("comment" => "Not needed anymore"));
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Cancel must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_CANCELED, $result['content']['status'] ?? null, "$test Order must be canceled");

		return $order_id;
	}

	/**
	 * testRestSupplierOrderCancelAgain
	 *
	 * @depends testRestSupplierOrderCancel
	 *
	 * @param int $order_id	Id of the canceled supplier order
	 * @return void
	 */
	public function testRestSupplierOrderCancelAgain($order_id)
	{
		$test = "Cancel an already canceled supplier order";

		$this->getUrl('supplierorders/'.$order_id.'/cancel', $test, 'POST', array("comment" => ""), 405);
	}

	/**
	 * testRestSupplierOrderCancelNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderCancelNotFound()
	{
		$test = "Cancel a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/cancel', $test, 'POST', array("comment" => ""), 404);
	}
}
