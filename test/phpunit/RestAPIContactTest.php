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
	 * testRestCreateContactWithCountryCode
	 *
	 * Create contacts with a 2 or 3 letters country code, then with an unknown country code.
	 *
	 * @return void
	 */
	public function testRestCreateContactWithCountryCode()
	{
		$addheaders = array('Content-Type: application/json');
		$lastname = 'testRestContactCountry'.mt_rand();
		$contactids = array();

		try {
			foreach (array('FR', 'FRA') as $country_code) {
				$body = json_encode(array('lastname' => $lastname, 'country_code' => $country_code));
				$result = getURLContent($this->api_url.'/contacts?api_key='.$this->api_key, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
				$this->assertEquals(0, $result['curl_error_no']);
				$object = json_decode($result['content'], true);	// If success content is just an id, if not an array
				$this->assertIsNumeric($object, 'Creating contact with country_code '.$country_code.' failed: '.$result['content']);
				$contactids[] = (int) $object;

				$result = getURLContent($this->api_url.'/contacts/'.((int) $object).'?api_key='.$this->api_key, 'GET', '', 1, array(), array('http', 'https'), 2);
				$this->assertEquals(0, $result['curl_error_no']);
				$object = json_decode($result['content'], true);
				$this->assertNotNull($object, "Parsing of json result must not be null");
				$this->assertEquals('FR', $object['country_code'], 'Bad country for country_code '.$country_code);
				$this->assertGreaterThan(0, (int) $object['country_id'], 'Bad country_id for country_code '.$country_code);
			}

			// Unknown country code must be rejected and the contact not created
			$body = json_encode(array('lastname' => $lastname.'ZZ', 'country_code' => 'ZZ'));
			$result = getURLContent($this->api_url.'/contacts?api_key='.$this->api_key, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			if (is_numeric($object)) {
				$contactids[] = (int) $object;
			}
			$this->assertEquals(404, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Unknown country_code must return 404: '.$result['content']);

			$sqlfilters = "(t.lastname:=:'".$lastname."ZZ')";
			$result = getURLContent($this->api_url.'/contacts?sqlfilters='.urlencode($sqlfilters).'&api_key='.$this->api_key, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertSame(array(), $object, 'No contact must be created with an unknown country_code: '.$result['content']);
		} finally {
			foreach ($contactids as $contactid) {
				getURLContent($this->api_url.'/contacts/'.$contactid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
		}
	}

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
	 * testRestGetContactByEmail
	 *
	 * Get a contact by a unique email, an unknown email, then an email shared by two contacts.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestGetContactByEmail()
	{
		$addheaders = array('Content-Type: application/json');
		$email = 'testrestcontactbyemail'.mt_rand().'@example.com';
		$emailurl = $this->api_url.'/contacts/email/'.rawurlencode($email).'?api_key='.$this->api_key;
		$contactids = array();

		try {
			// Create a contact with a unique email
			$body = json_encode(array("firstname" => "testRestContactByEmail1", "lastname" => "testRestContact", "email" => $email));
			$result = getURLContent($this->api_url.'/contacts?api_key='.$this->api_key, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$contactid = json_decode($result['content'], true);
			$this->assertIsInt($contactid, 'Contact creation failed: '.$result['content']);
			$contactids[] = $contactid;

			// Get the contact by its unique email
			print __METHOD__." Request GET url=".$emailurl."\n";
			$result = getURLContent($emailurl, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertEquals($contactid, $object['id'] ?? null, 'Get by unique email failed: '.$result['content']);
			$this->assertEquals($email, $object['email'] ?? null);

			// Get a contact by an unknown email
			$url = $this->api_url.'/contacts/email/'.rawurlencode('unknown'.$email).'?api_key='.$this->api_key;
			print __METHOD__." Request GET url=".$url."\n";
			$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertEquals(404, $object['error']['code'] ?? null, 'Get by unknown email must return 404: '.$result['content']);

			// Create a second contact with the same email
			$body = json_encode(array("firstname" => "testRestContactByEmail2", "lastname" => "testRestContact", "email" => $email));
			$result = getURLContent($this->api_url.'/contacts?api_key='.$this->api_key, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$contactid = json_decode($result['content'], true);
			$this->assertIsInt($contactid, 'Contact creation failed: '.$result['content']);
			$contactids[] = $contactid;

			// Get by an email shared by two contacts must not return an empty contact
			print __METHOD__." Request GET url=".$emailurl."\n";
			$result = getURLContent($emailurl, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertEquals(409, $object['error']['code'] ?? null, 'Get by duplicate email must return 409: '.$result['content']);
		} finally {
			// Clean up
			foreach ($contactids as $contactid) {
				getURLContent($this->api_url.'/contacts/'.$contactid.'?api_key='.$this->api_key, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);
			}
		}
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
		$idofcontactcreated = (int) $object;

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

	/**
	 * Call the API and return the decoded json response
	 *
	 * @param	string				$method		HTTP method ('GET', 'POST', 'PUTALREADYFORMATED', ...)
	 * @param	string				$path		Path of the API route, without api_key (for example '/contacts/1')
	 * @param	?array<string,mixed>	$bodyobj	Body to send as json, or null
	 * @return	mixed							Decoded json response
	 */
	private function callApi($method, $path, $bodyobj = null)
	{
		$url = $this->api_url.$path.'?api_key='.$this->api_key;
		$addheaders = array('Content-Type: application/json');
		$body = ($bodyobj === null ? '' : json_encode($bodyobj));

		print __METHOD__." Request ".$method." url=".$url." body=".$body."\n";
		$result = getURLContent($url, $method, $body, 1, $addheaders, array('http', 'https'), 2);
		$this->assertEquals(0, $result['curl_error_no']);

		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, "Parsing of json result must not be null for ".$method." ".$path);

		return $object;
	}

	/**
	 * testRestUpdateContactThirdparty
	 *
	 * Update a contact, link it to a thirdparty, check an empty socid keeps the link, then detach it with socid=-1.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestUpdateContactThirdparty()
	{
		// Create a thirdparty to link the contact to
		$socid = $this->callApi('POST', '/thirdparties', array("name" => "testRestContactThirdparty".mt_rand()));
		$this->assertIsInt($socid, 'Thirdparty creation failed: '.var_export($socid, true));
		$this->assertGreaterThan(0, $socid);

		// Create a contact
		$contactid = $this->callApi('POST', '/contacts', array("firstname" => "testRestContact".mt_rand(), "lastname" => "testRestContact"));
		$this->assertIsInt($contactid, 'Contact creation failed: '.var_export($contactid, true));
		$this->assertGreaterThan(0, $contactid);

		// Update the lastname
		$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("lastname" => "UpdatedLastName"));
		$this->assertEquals('UpdatedLastName', $object['lastname'] ?? null, 'Update of lastname failed: '.var_export($object, true));

		// Link the contact to the thirdparty
		$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("socid" => $socid));
		$this->assertEquals($socid, $object['socid'] ?? null, 'Link to thirdparty failed: '.var_export($object, true));

		// An empty or 0 socid must be ignored and keep the link
		foreach (array(0, null, '') as $emptysocid) {
			$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("socid" => $emptysocid, "firstname" => "KeepLink"));
			$this->assertEquals($socid, $object['socid'] ?? null, 'socid='.var_export($emptysocid, true).' must keep the link: '.var_export($object, true));
			$this->assertEquals('KeepLink', $object['firstname'] ?? null, 'Other fields must be updated when socid is empty');
		}

		// Detach the contact with socid=-1
		$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("socid" => -1));
		$this->assertArrayHasKey('lastname', $object, 'Detach with socid=-1 failed: '.var_export($object, true));
		$this->assertEmpty($object['socid'], 'Contact must not be linked to a thirdparty after socid=-1');
		$this->assertEquals('UpdatedLastName', $object['lastname'], 'Other fields must be kept when detaching');

		// Link to a nonexistent thirdparty
		$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("socid" => 123456789));
		$this->assertEquals(404, $object['error']['code'] ?? null, 'Link to a nonexistent thirdparty must return 404: '.var_export($object, true));

		// Update a nonexistent contact
		$object = $this->callApi('PUTALREADYFORMATED', '/contacts/123456789', array("lastname" => "UpdatedLastName"));
		$this->assertEquals(404, $object['error']['code'] ?? null, 'Update of a nonexistent contact must return 404: '.var_export($object, true));

		// Clean up
		$this->callApi('DELETE', '/contacts/'.$contactid);
		$this->callApi('DELETE', '/thirdparties/'.$socid);
	}
}
