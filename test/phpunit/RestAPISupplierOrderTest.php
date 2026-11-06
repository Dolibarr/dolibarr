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
	 * Create a draft supplier order
	 *
	 * @param string				$test	Text to be added to output
	 * @param array<string,mixed>	$data	Other fields of the order
	 * @return int							Id of the supplier order
	 */
	private function createOrder($test, $data = array())
	{
		$data = array_merge(array(
			"socid" => 1,
			"ref_supplier" => "TR".mt_rand(0, 99999999),
		), $data);
		$result = $this->getUrl('supplierorders', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of order is not > 0");

		return (int) $result['content'];
	}

	/**
	 * testRestSupplierOrderRefuse
	 *
	 * @return int	Id of the refused supplier order
	 */
	public function testRestSupplierOrderRefuse()
	{
		$test = "Refuse supplier order";

		$order_id = $this->createOrder($test);

		// A draft order is not waiting for approval
		$this->getUrl('supplierorders/'.$order_id.'/refuse', "$test (draft)", 'POST', array("comment" => ""), 405);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/refuse', $test, 'POST', array("comment" => "Prices too high"));
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Refuse must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_REFUSED, $result['content']['status'] ?? null, "$test Order must be refused");

		return $order_id;
	}

	/**
	 * testRestSupplierOrderRefuseAgain
	 *
	 * @depends testRestSupplierOrderRefuse
	 *
	 * @param int $order_id	Id of the refused supplier order
	 * @return void
	 */
	public function testRestSupplierOrderRefuseAgain($order_id)
	{
		$test = "Refuse an already refused supplier order";

		$this->getUrl('supplierorders/'.$order_id.'/refuse', $test, 'POST', array("comment" => ""), 405);
	}

	/**
	 * testRestSupplierOrderRefuseNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderRefuseNotFound()
	{
		$test = "Refuse a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/refuse', $test, 'POST', array("comment" => ""), 404);
	}

	/**
	 * testRestSupplierOrderMakeOrder
	 *
	 * @return int	Id of the ordered supplier order
	 */
	public function testRestSupplierOrderMakeOrder()
	{
		$test = "Make a supplier order";

		$order_id = $this->createOrder($test);
		$data = array("date" => dol_now(), "method" => 1, "comment" => "");

		// Only approved orders can be sent to the vendor
		$this->getUrl('supplierorders/'.$order_id.'/makeorder', "$test (draft)", 'POST', $data, 405);

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_DRAFT, $result['content']['status'] ?? null, "$test Order status must not change");

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$this->getUrl('supplierorders/'.$order_id.'/makeorder', "$test (validated)", 'POST', $data, 405);

		$this->getUrl('supplierorders/'.$order_id.'/approve', $test, 'POST', array("idwarehouse" => 0, "secondlevel" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/makeorder', $test, 'POST', $data);
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Make order must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_ORDERSENT, $result['content']['status'] ?? null, "$test Order must be sent");

		return $order_id;
	}

	/**
	 * testRestSupplierOrderMakeOrderAgain
	 *
	 * @depends testRestSupplierOrderMakeOrder
	 *
	 * @param int $order_id	Id of the ordered supplier order
	 * @return void
	 */
	public function testRestSupplierOrderMakeOrderAgain($order_id)
	{
		$test = "Make an already ordered supplier order";

		$this->getUrl('supplierorders/'.$order_id.'/makeorder', $test, 'POST', array("date" => dol_now(), "method" => 1, "comment" => ""), 405);
	}

	/**
	 * testRestSupplierOrderMakeOrderNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderMakeOrderNotFound()
	{
		$test = "Make a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/makeorder', $test, 'POST', array("date" => dol_now(), "method" => 1, "comment" => ""), 404);
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

	/**
	 * testRestSupplierOrderSetToDraftValidated
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetToDraftValidated()
	{
		$test = "Set a validated supplier order back to draft";

		$order_id = $this->createOrder($test);

		// A draft order can not be set to draft
		$this->getUrl('supplierorders/'.$order_id.'/settodraft', "$test (draft)", 'POST', array(), 405);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/settodraft', $test, 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Set to draft must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_DRAFT, $result['content']['status'] ?? null, "$test Order must be draft");
	}

	/**
	 * testRestSupplierOrderSetToDraftApproved
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetToDraftApproved()
	{
		$test = "Set an approved supplier order back to draft";

		$order_id = $this->createOrder($test);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));
		$this->getUrl('supplierorders/'.$order_id.'/approve', $test, 'POST', array("idwarehouse" => 0, "secondlevel" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_ACCEPTED, $result['content']['status'] ?? null, "$test Order must be approved");
		$this->assertNotEmpty($result['content']['user_approve_id'] ?? null, "$test Order must have an approver");

		$result = $this->getUrl('supplierorders/'.$order_id.'/settodraft', $test, 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Set to draft must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(CommandeFournisseur::STATUS_DRAFT, $result['content']['status'] ?? null, "$test Order must be draft");
		$this->assertEmpty($result['content']['user_approve_id'] ?? null, "$test Approver must be cleared");
		$this->assertEmpty($result['content']['date_approve'] ?? null, "$test Approval date must be cleared");
	}

	/**
	 * testRestSupplierOrderSetToDraftNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetToDraftNotFound()
	{
		$test = "Set to draft a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/settodraft', $test, 'POST', array(), 404);
	}

	/**
	 * testRestSupplierOrderSetInvoiced
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetInvoiced()
	{
		$test = "Classify a supplier order as billed";

		$order_id = $this->createOrder($test);

		// Only approved orders, or later statuses, can be classified as billed
		$this->getUrl('supplierorders/'.$order_id.'/setinvoiced', "$test (draft)", 'POST', array(), 405);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		$this->getUrl('supplierorders/'.$order_id.'/setinvoiced', "$test (validated)", 'POST', array(), 405);

		$this->getUrl('supplierorders/'.$order_id.'/approve', $test, 'POST', array("idwarehouse" => 0, "secondlevel" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/setinvoiced', $test, 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Classify billed must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(1, $result['content']['billed'] ?? null, "$test Order must be billed");
		$this->assertEquals(CommandeFournisseur::STATUS_ACCEPTED, $result['content']['status'] ?? null, "$test Order status must not change");

		// Classifying an already billed order again does nothing and succeeds
		$result = $this->getUrl('supplierorders/'.$order_id.'/setinvoiced', "$test (already billed)", 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Classify billed again must succeed");
	}

	/**
	 * testRestSupplierOrderSetInvoicedNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetInvoicedNotFound()
	{
		$test = "Classify as billed a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/setinvoiced', $test, 'POST', array(), 404);
	}

	/**
	 * testRestSupplierOrderCreateKeepsDate
	 *
	 * @return void
	 */
	public function testRestSupplierOrderCreateKeepsDate()
	{
		$test = "Create supplier order with a date";

		$date = 1579089600;	// 2020-01-15 12:00:00 UTC, a date in the past so it can't be mistaken for dol_now()

		$order_id = $this->createOrder($test, array("date" => $date));

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals($date, $result['content']['date'] ?? null, "$test The given date must be kept");
	}

	/**
	 * testRestSupplierOrderUpdate
	 *
	 * @return int	Id of the updated supplier order
	 */
	public function testRestSupplierOrderUpdate()
	{
		$test = "Update supplier order";

		$order_id = $this->createOrder($test);

		$ref_supplier = "TR".mt_rand(0, 99999999);
		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'PUT', array("ref_supplier" => $ref_supplier, "note_private" => "Updated via the REST API"));
		$this->assertEquals($order_id, $result['content']['id'] ?? null, "$test The updated order must be returned");
		$this->assertEquals($ref_supplier, $result['content']['ref_supplier'] ?? null, "$test ref_supplier must be updated");
		$this->assertEquals("Updated via the REST API", $result['content']['note_private'] ?? null, "$test note_private must be updated");

		return $order_id;
	}

	/**
	 * testRestSupplierOrderUpdateError
	 *
	 * @depends testRestSupplierOrderUpdate
	 *
	 * @param int $order_id	Id of the supplier order
	 * @return void
	 */
	public function testRestSupplierOrderUpdateError($order_id)
	{
		$test = "Update supplier order with an unknown supplier";

		// The foreign key on fk_soc makes update() fail
		$this->getUrl('supplierorders/'.$order_id, $test, 'PUT', array("socid" => 999999999), 500);

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(1, $result['content']['socid'] ?? null, "$test The supplier must not change");
	}

	/**
	 * testRestSupplierOrderDeleteContact
	 *
	 * @return void
	 */
	public function testRestSupplierOrderDeleteContact()
	{
		$test = "Unlink a contact from a supplier order";

		$order_id = $this->createOrder($test);

		$this->getUrl('supplierorders/'.$order_id.'/contact/1/SALESREPFOLL/internal', $test, 'POST', array());

		$result = $this->getUrl('supplierorders/'.$order_id.'/contact/1/SALESREPFOLL/internal', $test, 'DELETE');
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Unlink must succeed");

		// The contact is no longer linked
		$this->getUrl('supplierorders/'.$order_id.'/contact/1/SALESREPFOLL/internal', "$test (again)", 'DELETE', null, 404);
	}

	/**
	 * testRestSupplierOrderSetUninvoiced
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetUninvoiced()
	{
		$test = "Classify a supplier order as unbilled";

		$order_id = $this->createOrder($test);

		// A draft order can not be classified as unbilled
		$this->getUrl('supplierorders/'.$order_id.'/setuninvoiced', "$test (draft)", 'POST', array(), 405);

		$this->getUrl('supplierorders/'.$order_id.'/validate', $test, 'POST', array("idwarehouse" => 0, "notrigger" => 0));

		// Classifying an order that is not billed does nothing and succeeds
		$result = $this->getUrl('supplierorders/'.$order_id.'/setuninvoiced', "$test (validated)", 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Classify unbilled must succeed");

		$this->getUrl('supplierorders/'.$order_id.'/approve', $test, 'POST', array("idwarehouse" => 0, "secondlevel" => 0));

		$result = $this->getUrl('supplierorders/'.$order_id.'/setuninvoiced', "$test (approved)", 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Classify unbilled must succeed");

		$result = $this->getUrl('supplierorders/'.$order_id, $test, 'GET');
		$this->assertEquals(0, $result['content']['billed'] ?? null, "$test Order must not be billed");
		$this->assertEquals(CommandeFournisseur::STATUS_ACCEPTED, $result['content']['status'] ?? null, "$test Order status must not change");
	}

	/**
	 * testRestSupplierOrderSetUninvoicedNotFound
	 *
	 * @return void
	 */
	public function testRestSupplierOrderSetUninvoicedNotFound()
	{
		$test = "Classify as unbilled a supplier order that does not exist";

		$this->getUrl('supplierorders/999999999/setuninvoiced', $test, 'POST', array(), 404);
	}
}
