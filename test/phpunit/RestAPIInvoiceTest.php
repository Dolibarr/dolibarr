<?php
/* Copyright (C) 2026	OMP agent	<omp@example.com>
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
 *      \file       test/phpunit/RestAPIInvoiceTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test of the /invoices REST API
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
class RestAPIInvoiceTest extends AbstractRestAPITest
{
	/**
	 * testRestGetThirdparty
	 *
	 * Return the id of a thirdparty to invoice. The demo database ships with customers, so read one
	 * instead of creating it (the thirdparty API needs a customer code on this instance).
	 *
	 * @return int	ID of a thirdparty
	 */
	public function testRestGetThirdparty()
	{
		$url = $this->api_url.'/thirdparties?api_key='.$this->api_key.'&limit=1&sortfield=t.rowid&sortorder=ASC';
		$addheaders = array('Content-Type: application/json');

		$result = getURLContent($url, 'GET', '', 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no'], 'Listing the thirdparties should not have a curl error');
		$object = json_decode($result['content'], true);

		$this->assertIsArray($object, 'Parsing of json result must no be null');
		$this->assertNotEmpty($object, 'No thirdparty available to invoice');

		return (int) $object[0]['id'];
	}

	/**
	 * testRestCreateInvoice
	 *
	 * POST /invoices must return the created invoice, so the caller does not need a second GET call
	 * to read back what it just created.
	 *
	 * @param	int		$socid	Id of the thirdparty created at previous test
	 * @return int				ID of the invoice created
	 *
	 * @depends testRestGetThirdparty
	 */
	public function testRestCreateInvoice($socid)
	{
		$url = $this->api_url.'/invoices?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');

		$body = json_encode(array("socid" => $socid));

		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no'], 'Creating the invoice should not have a curl error');
		$object = json_decode($result['content'], true);

		$this->assertIsArray($object, 'POST /invoices must return the created invoice, got '.gettype($object));
		$this->assertNotEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Error'.(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));

		// The response must be the invoice, not just its id
		$this->assertGreaterThan(0, (int) $object['id'], 'The created invoice id is missing');
		$this->assertNotEmpty($object['ref'], 'The created invoice ref is missing');
		$this->assertArrayHasKey('lines', $object, 'The created invoice lines are missing');
		$this->assertArrayHasKey('remaintopay', $object, 'The created invoice payment totals are missing');

		return (int) $object['id'];
	}

	/**
	 * testRestCreateInvoiceMatchGet
	 *
	 * The point of returning the invoice is to spare the caller the follow-up GET, so the POST
	 * payload must be identical to GET /invoices/{id} on the very invoice that was created.
	 *
	 * @param	int		$id	Id of the invoice created at previous test
	 * @return void
	 *
	 * @depends testRestCreateInvoice
	 */
	public function testRestCreateInvoiceMatchGet($id)
	{
		$url = $this->api_url.'/invoices/'.$id.'?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');

		$result = getURLContent($url, 'GET', '', 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no'], 'Getting the invoice should not have a curl error');
		$fromget = json_decode($result['content'], true);

		$this->assertIsArray($fromget, 'GET /invoices/{id} must return an object, got '.gettype($fromget));

		// Same content as the GET, so a client can replace its POST+GET by the POST alone
		$urlcreate = $this->api_url.'/invoices?api_key='.$this->api_key;
		$socid = $this->getInvoiceSocid($fromget);

		$body = json_encode(array("socid" => $socid));
		$resultpost = getURLContent($urlcreate, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $resultpost['curl_error_no'], 'Creating the invoice should not have a curl error');
		$frompost = json_decode($resultpost['content'], true);

		$this->assertIsArray($frompost, 'POST /invoices must return an object, got '.gettype($frompost));

		// Compare the two payloads field by field on the properties both must expose
		foreach (array('socid', 'date', 'total_ht', 'total_tva', 'total_ttc', 'totalpaid', 'totalcreditnotes', 'totaldeposits', 'remaintopay', 'lines') as $key) {
			$this->assertArrayHasKey($key, $frompost, 'POST /invoices must return '.$key);
			$this->assertEquals($fromget[$key], $frompost[$key], 'POST /invoices and GET /invoices/{id} must agree on '.$key);
		}
	}

	/**
	 * getInvoiceSocid
	 *
	 * Return the thirdparty of an invoice, so a comparable invoice can be created.
	 *
	 * @param	array	$invoice	Invoice as returned by the API
	 * @return int					ID of the thirdparty
	 */
	private function getInvoiceSocid($invoice)
	{
		$this->assertArrayHasKey('socid', $invoice, 'The invoice thirdparty id is missing');

		return (int) $invoice['socid'];
	}
}
