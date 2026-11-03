<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
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

require_once __DIR__."/AbstractRestAPITest.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIContactTest extends AbstractRestAPITest
{
	/**
	 * testRestContactCategories
	 *
	 * Link a contact category to a contact, link it again, unlink it, then check the errors
	 * for an unknown category, an unknown contact and a category that is not of type contact.
	 *
	 * @return void
	 */
	public function testRestContactCategories()
	{
		$addheaders = array('Content-Type: application/json', 'Accept: application/json', 'DOLAPIKEY: '.$this->api_key);
		$api_url = $this->api_url;
		$call = function ($method, $endpoint, $body = '') use ($api_url, $addheaders) {
			$result = getURLContent($api_url.$endpoint, $method, $body, 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no'], $method.' '.$endpoint.' must not have a curl error');
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, $method.' '.$endpoint.' - Parsing of json result must not be null: '.$result['content']);
			return $object;
		};

		$contactid = 0;
		$categoryid = 0;
		$customercategoryid = 0;
		try {
			$contactid = (int) $call('POST', '/contacts', json_encode(array('lastname' => 'testRestContactCategories'.mt_rand())));
			$this->assertGreaterThan(0, $contactid, 'Contact must be created');

			$categoryid = (int) $call('POST', '/categories', json_encode(array('label' => 'testRestContactCategories '.mt_rand(), 'type' => 4)));	// 4 = contact
			$this->assertGreaterThan(0, $categoryid, 'Contact category must be created');
			$customercategoryid = (int) $call('POST', '/categories', json_encode(array('label' => 'testRestContactCategories customer '.mt_rand(), 'type' => 2)));	// 2 = customer
			$this->assertGreaterThan(0, $customercategoryid, 'Customer category must be created');

			// Link the category, then find it in the contact's categories
			$object = $call('PUTALREADYFORMATED', '/contacts/'.$contactid.'/categories/'.$categoryid);
			$this->assertArrayNotHasKey('error', $object, 'Linking the category must succeed');
			$this->assertEquals($contactid, $object['id']);
			$object = $call('GET', '/contacts/'.$contactid.'/categories');
			$this->assertEquals(array($categoryid), array_map('intval', array_column($object, 'id')), 'The linked category must be returned');

			// Linking it again is not an error and keeps a single link
			$object = $call('PUTALREADYFORMATED', '/contacts/'.$contactid.'/categories/'.$categoryid);
			$this->assertArrayNotHasKey('error', $object, 'Linking an already linked category must succeed');
			$object = $call('GET', '/contacts/'.$contactid.'/categories');
			$this->assertCount(1, $object, 'The category must be linked only once');

			// A category of another type can't be linked
			$object = $call('PUTALREADYFORMATED', '/contacts/'.$contactid.'/categories/'.$customercategoryid);
			$this->assertEquals(400, $object['error']['code'] ?? 0, 'Linking a customer category must return 400');
			$object = $call('DELETE', '/contacts/'.$contactid.'/categories/'.$customercategoryid);
			$this->assertEquals(400, $object['error']['code'] ?? 0, 'Unlinking a customer category must return 400');

			// Unlink the category, then check it is gone
			$object = $call('DELETE', '/contacts/'.$contactid.'/categories/'.$categoryid);
			$this->assertArrayNotHasKey('error', $object, 'Unlinking the category must succeed');
			$object = $call('GET', '/contacts/'.$contactid.'/categories');
			$this->assertSame(array(), $object, 'The contact must have no category left');

			// Unknown category
			$object = $call('PUTALREADYFORMATED', '/contacts/'.$contactid.'/categories/999999999');
			$this->assertEquals(404, $object['error']['code'] ?? 0, 'Linking an unknown category must return 404');
			$object = $call('DELETE', '/contacts/'.$contactid.'/categories/999999999');
			$this->assertEquals(404, $object['error']['code'] ?? 0, 'Unlinking an unknown category must return 404');

			// Unknown contact
			$object = $call('GET', '/contacts/999999999/categories');
			$this->assertEquals(404, $object['error']['code'] ?? 0, 'Categories of an unknown contact must return 404');
			$object = $call('PUTALREADYFORMATED', '/contacts/999999999/categories/'.$categoryid);
			$this->assertEquals(404, $object['error']['code'] ?? 0, 'Linking to an unknown contact must return 404');
			$object = $call('DELETE', '/contacts/999999999/categories/'.$categoryid);
			$this->assertEquals(404, $object['error']['code'] ?? 0, 'Unlinking from an unknown contact must return 404');
		} finally {
			// Clean up
			if ($contactid > 0) {
				getURLContent($api_url.'/contacts/'.$contactid, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);
			}
			if ($categoryid > 0) {
				getURLContent($api_url.'/categories/'.$categoryid, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);
			}
			if ($customercategoryid > 0) {
				getURLContent($api_url.'/categories/'.$customercategoryid, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);
			}
		}
	}

	protected $api_url;
	protected $api_key;

	/**
	 * testRestGetContact
	 *
	 * @return int
	 */
	public function testRestGetContact()
	{
		//fetch Non-Existent contact
		$url = $this->api_url.'/contacts/123456789?api_key='.$this->api_key;
		//$addheaders=array('Content-Type: application/json');

		print __METHOD__." Request GET url=".$url."\n";
		$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result for get on unexisting contact: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals(0, $result['curl_error_no']);
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertEquals(404, $object['error']['code'], 'Error code is not 404');

		//fetch an existent contact
		$url = $this->api_url.'/contacts/1?api_key='.$this->api_key;

		print __METHOD__." Request GET url=".$url."\n";
		$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result for get on an existing contact: ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals(0, $result['curl_error_no']);
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertEquals(1, $object['status']);
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
		// attempt to create without mandatory fields
		$url = $this->api_url.'/contacts?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');

		$bodyobj = array(
			"firstname" => "firstname"
		);

		$body = json_encode($bodyobj);

		//print __METHOD__." Request POST url=".$url."\n";
		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
		//print __METHOD__." Result for creating incomplete contact".var_export($result, true)."\n";
		//print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals(0, $result['curl_error_no']);
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

		var_export($body);

		$result = getURLContent($url, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no']);

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
		// attempt to create without mandatory fields
		$url = $this->api_url.'/contacts?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');

		//update the contact

		// Update the firstname of the contact
		$updateBody = array(
			"firstname" => "UpdatedFirstName",
		);

		$updateRequestBody = json_encode($updateBody);
		$updateUrl = $this->api_url . '/contacts/' . $objid. '?api_key=' . $this->api_key;
		$updateResult = getURLContent($updateUrl, 'PUTALREADYFORMATED', $updateRequestBody, 1, $addheaders, array('http', 'https'), 2);
		$this->assertEquals(0, $updateResult['curl_error_no']);

		$updateResponse = json_decode($updateResult['content'], true);

		$this->assertNotNull($updateResponse, "Parsing of JSON result must not be null");
		print_r($updateResponse);

		// Check if the updated fields match the changes you made
		$this->assertEquals($updateBody['firstname'], $updateResponse['firstname'], 'Update failed for request body: '.$updateRequestBody);

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
