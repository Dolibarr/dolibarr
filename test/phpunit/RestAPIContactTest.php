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

		// The 3 letters code is tested only if code_iso is set (it is empty on databases created with old versions, like the pgsql CI one)
		global $db;
		$countrycodes = array('FR');
		if (dol_getIdFromCode($db, 'FRA', 'c_country', 'code_iso', 'rowid') > 0) {
			$countrycodes[] = 'FRA';
		}

		try {
			foreach ($countrycodes as $country_code) {
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
	 * testRestListContacts
	 *
	 * @return void
	 */
	public function testRestListContacts()
	{
		$addheaders = array('Content-Type: application/json');

		// Create 2 contacts with a unique lastname so we can filter the list on them
		$lastname = 'testRestListContacts'.mt_rand();
		$ids = array();
		for ($i = 0; $i < 2; $i++) {
			$body = json_encode(array("firstname" => "firstname".$i, "lastname" => $lastname));
			$result = getURLContent($this->api_url.'/contacts?api_key='.$this->api_key, 'POST', $body, 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$object = json_decode($result['content'], true);
			$this->assertGreaterThan(0, (int) $object, 'Creation of contact failed: '.$result['content']);
			$ids[] = (int) $object;
		}

		try {
			$urlbase = $this->api_url.'/contacts?api_key='.$this->api_key.'&sqlfilters='.urlencode("(t.lastname:=:'".$lastname."')");

			// limit=0 means no limit: must not fail with a division by zero, everything is on 1 page
			$url = $urlbase.'&limit=0&pagination_data=true';
			print __METHOD__." Request GET url=".$url."\n";
			$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$this->assertEquals(200, $result['http_code'], 'Return code must be 200: '.$result['content']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertCount(2, $object['data']);
			$this->assertEquals(2, $object['pagination']['total']);
			$this->assertEquals(1, $object['pagination']['page_count']);

			// limit=1: 2 contacts on 2 pages
			$url = $urlbase.'&limit=1&pagination_data=true';
			print __METHOD__." Request GET url=".$url."\n";
			$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$this->assertEquals(200, $result['http_code'], 'Return code must be 200: '.$result['content']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertCount(1, $object['data']);
			$this->assertEquals(2, $object['pagination']['total']);
			$this->assertEquals(2, $object['pagination']['page_count']);

			// Roles are loaded only if includeroles is set (fetchRoles() sets roles to an array, even if empty)
			$url = $urlbase.'&includeroles=0';
			print __METHOD__." Request GET url=".$url."\n";
			$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$this->assertEquals(200, $result['http_code'], 'Return code must be 200: '.$result['content']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertCount(2, $object);
			foreach ($object as $contact) {
				$this->assertFalse(isset($contact['roles']), 'Roles must not be loaded when includeroles=0');
			}

			$url = $urlbase.'&includeroles=1';
			print __METHOD__." Request GET url=".$url."\n";
			$result = getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$this->assertEquals(200, $result['http_code'], 'Return code must be 200: '.$result['content']);
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "Parsing of json result must not be null");
			$this->assertCount(2, $object);
			foreach ($object as $contact) {
				$this->assertTrue(isset($contact['roles']) && is_array($contact['roles']), 'Roles must be loaded when includeroles=1');
			}
		} finally {
			// Delete the contacts, even if an assertion failed
			foreach ($ids as $id) {
				getURLContent($this->api_url.'/contacts/'.$id.'?api_key='.$this->api_key, 'DELETE', '', 1, $addheaders, array('http', 'https'), 2);
			}
		}
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

	/**
	 * testRestUpdateContactNoEmail
	 *
	 * Set no_email on a contact with PUT, check it is saved with a GET, then unset it.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestUpdateContactNoEmail()
	{
		if (!isModEnabled('mailing')) {
			$this->markTestSkipped('Module mailing must be enabled to test no_email');
		}

		$email = 'testrestcontactnoemail'.mt_rand().'@example.com';
		$contactid = $this->callApi('POST', '/contacts', array("firstname" => "testRestContactNoEmail", "lastname" => "testRestContact", "email" => $email));
		$this->assertIsInt($contactid, 'Contact creation failed: '.var_export($contactid, true));

		try {
			// Set no_email
			$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("no_email" => 1));
			$this->assertEquals(1, $object['no_email'] ?? null, 'Update of no_email failed: '.var_export($object, true));

			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertEquals(1, $object['no_email'] ?? null, 'no_email=1 must be saved: '.var_export($object, true));

			// Unset no_email
			$object = $this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("no_email" => 0));
			$this->assertEquals(0, $object['no_email'] ?? null, 'Update of no_email failed: '.var_export($object, true));

			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertArrayHasKey('no_email', $object, 'GET must return no_email: '.var_export($object, true));
			$this->assertEquals(0, $object['no_email'], 'no_email=0 must be saved: '.var_export($object, true));
		} finally {
			// Clean up, even if an assertion failed
			$this->callApi('PUTALREADYFORMATED', '/contacts/'.$contactid, array("no_email" => 0));
			$this->callApi('DELETE', '/contacts/'.$contactid);
		}
	}

	/**
	 * testRestCreateUserFromContact
	 *
	 * Create a user from a contact, then check the errors for missing fields, an unknown contact,
	 * a contact that already has a user and a login that is already used.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestCreateUserFromContact()
	{
		$login = 'testrestcontact'.mt_rand();
		$password = 'Test-'.mt_rand().'-Pass!aA1';
		$contactids = array();
		$userids = array();

		try {
			$contactid = $this->callApi('POST', '/contacts', array("lastname" => "testRestCreateUser".mt_rand()));
			$this->assertIsInt($contactid, 'Contact creation failed: '.var_export($contactid, true));
			$contactids[] = $contactid;
			$othercontactid = $this->callApi('POST', '/contacts', array("lastname" => "testRestCreateUser".mt_rand()));
			$this->assertIsInt($othercontactid, 'Contact creation failed: '.var_export($othercontactid, true));
			$contactids[] = $othercontactid;

			// Missing fields
			$object = $this->callApi('POST', '/contacts/'.$contactid.'/createUser', array("password" => $password));
			$this->assertEquals(400, $object['error']['code'] ?? null, 'A missing login must return 400: '.var_export($object, true));
			$object = $this->callApi('POST', '/contacts/'.$contactid.'/createUser', array("login" => $login));
			$this->assertEquals(400, $object['error']['code'] ?? null, 'A missing password must return 400: '.var_export($object, true));

			// Nonexistent contact
			$object = $this->callApi('POST', '/contacts/123456789/createUser', array("login" => $login, "password" => $password));
			$this->assertEquals(404, $object['error']['code'] ?? null, 'A nonexistent contact must return 404: '.var_export($object, true));

			// Create the user and find it linked to the contact
			$userid = $this->callApi('POST', '/contacts/'.$contactid.'/createUser', array("login" => $login, "password" => $password));
			$this->assertIsInt($userid, 'User creation failed: '.var_export($userid, true));
			$userids[] = $userid;
			$this->assertGreaterThan(0, $userid);
			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertEquals($userid, $object['user_id'] ?? null, 'The user must be linked to the contact');
			$this->assertEquals($login, $object['user_login'] ?? null, 'The login of the user must be the requested one');

			// The requested password must be set. The user may still get a 403 if it has no right to generate its API token.
			$result = getURLContent($this->api_url.'/login?login='.urlencode($login).'&password='.urlencode($password), 'GET', '', 1, array('Accept: application/json'), array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no']);
			$this->assertStringNotContainsString('Access denied', $result['content'], 'The user must be able to log in with the requested password');

			// A contact has only one user
			$object = $this->callApi('POST', '/contacts/'.$contactid.'/createUser', array("login" => $login.'b', "password" => $password));
			if (is_int($object)) {
				$userids[] = $object;
			}
			$this->assertEquals(409, $object['error']['code'] ?? null, 'A contact that already has a user must return 409: '.var_export($object, true));

			// A login can't be used twice, and nothing must be linked to the contact
			$object = $this->callApi('POST', '/contacts/'.$othercontactid.'/createUser', array("login" => $login, "password" => $password));
			if (is_int($object)) {
				$userids[] = $object;
			}
			$this->assertEquals(409, $object['error']['code'] ?? null, 'A login already used must return 409: '.var_export($object, true));
			$object = $this->callApi('GET', '/contacts/'.$othercontactid);
			$this->assertEmpty($object['user_id'] ?? null, 'No user must be linked to the contact after a failure');
		} finally {
			foreach ($userids as $userid) {
				getURLContent($this->api_url.'/users/'.$userid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
			foreach ($contactids as $contactid) {
				getURLContent($this->api_url.'/contacts/'.$contactid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
		}
	}

	/**
	 * testRestCreateUserFromContactWeakPassword
	 *
	 * A password rejected by the password policy must return 500 and leave no user behind.
	 * Skipped when the database of the test sets the 'none' password policy, like the demo database used by CI.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestCreateUserFromContactWeakPassword()
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

		// Read the value from the database: it is the one used by the API, and other tests change the one of $conf
		if (strtolower(dolibarr_get_const($db, 'USER_PASSWORD_GENERATED')) == 'none') {
			$this->markTestSkipped("The password policy of this database is 'none', it accepts any password");
		}

		$contactid = 0;
		$userid = 0;

		try {
			$contactid = $this->callApi('POST', '/contacts', array("lastname" => "testRestCreateUser".mt_rand()));
			$this->assertIsInt($contactid, 'Contact creation failed: '.var_export($contactid, true));

			$object = $this->callApi('POST', '/contacts/'.$contactid.'/createUser', array("login" => 'testrestcontact'.mt_rand(), "password" => 'a'));
			if (is_int($object)) {
				$userid = $object;
			}
			$this->assertEquals(500, $object['error']['code'] ?? null, 'A rejected password must return 500: '.var_export($object, true));

			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertEmpty($object['user_id'] ?? null, 'No user must be created with a rejected password');
		} finally {
			if ($userid > 0) {
				getURLContent($this->api_url.'/users/'.$userid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
			if ($contactid > 0) {
				getURLContent($this->api_url.'/contacts/'.$contactid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
		}
	}

	/**
	 * testRestDeleteContact
	 *
	 * Delete a contact, then check the errors for a deleted contact, a nonexistent contact and the id 0.
	 *
	 * @return	void
	 *
	 * @depends testRestGetContact
	 * The depends says test is run only if previous is ok
	 */
	public function testRestDeleteContact()
	{
		$contactid = 0;

		try {
			$contactid = $this->callApi('POST', '/contacts', array("lastname" => "testRestDeleteContact".mt_rand()));
			$this->assertIsInt($contactid, 'Contact creation failed: '.var_export($contactid, true));

			// Delete the contact
			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertEquals($contactid, $object['id'] ?? null, 'The contact must exist before the delete: '.var_export($object, true));
			$object = $this->callApi('DELETE', '/contacts/'.$contactid);
			$this->assertEquals(200, $object['success']['code'] ?? null, 'Delete of the contact failed: '.var_export($object, true));

			// A deleted contact is not found anymore
			$object = $this->callApi('GET', '/contacts/'.$contactid);
			$this->assertEquals(404, $object['error']['code'] ?? null, 'Get of a deleted contact must return 404: '.var_export($object, true));
			$object = $this->callApi('DELETE', '/contacts/'.$contactid);
			$this->assertEquals(404, $object['error']['code'] ?? null, 'Delete of a deleted contact must return 404: '.var_export($object, true));

			// Nonexistent contact
			$object = $this->callApi('DELETE', '/contacts/123456789');
			$this->assertEquals(404, $object['error']['code'] ?? null, 'Delete of a nonexistent contact must return 404: '.var_export($object, true));

			// Contact::fetch() returns -1 for the id 0: nothing must be deleted on an empty contact
			$object = $this->callApi('DELETE', '/contacts/0');
			$this->assertEquals(500, $object['error']['code'] ?? null, 'Delete of the id 0 must return 500: '.var_export($object, true));

			// The id 0 of the get route returns the specimen, without calling Contact::fetch()
			$object = $this->callApi('GET', '/contacts/0');
			$this->assertEquals('DOLIBARR', $object['lastname'] ?? null, 'Get of the id 0 must return the specimen: '.var_export($object, true));
		} finally {
			if ($contactid > 0) {
				getURLContent($this->api_url.'/contacts/'.$contactid.'?api_key='.$this->api_key, 'DELETE', '', 1, array(), array('http', 'https'), 2);
			}
		}
	}
}
