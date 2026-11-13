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
 *      \file       test/phpunit/RestAPITicketTest.php
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
class RestAPITicketTest extends AbstractRestAPITest
{
	/**
	 * @var string[]	API paths of the objects to delete at end of the test, the last created first
	 */
	private $objectstodelete = array();

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('ticket'), 'module ticket must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Delete objects created by the test. The API server does not see the transaction of the test class,
	 * so what is created for it is committed and is not removed by the final rollback.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$objectstodelete = $this->objectstodelete;
		$this->objectstodelete = array();
		foreach ($objectstodelete as $path) {
			$this->getUrl($path, "Delete $path at end of the test", 'DELETE');
		}

		parent::tearDown();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string		$endpoint 	Endpoint - "tickets/id"
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
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: ".$this->api_key
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
	 * Create a ticket. It is deleted at end of the test.
	 *
	 * @param string	$test	Text to be added to output
	 * @return int				Id of the ticket
	 */
	private function createTicket($test)
	{
		$data = array(
			"subject" => "Ticket created via the REST API",
			"message" => "Message of the ticket created via the REST API",
		);
		$result = $this->getUrl('tickets', $test, 'POST', $data);
		$ticket_id = (int) $result['content'];
		$this->assertGreaterThan(0, $ticket_id, "$test Id of ticket is not > 0");
		array_unshift($this->objectstodelete, 'tickets/'.$ticket_id);

		return $ticket_id;
	}

	/**
	 * Get the ids of the contacts linked to a ticket for a source
	 *
	 * @param string				$test		Text to be added to output
	 * @param int					$ticket_id	Id of the ticket
	 * @param 'internal'|'external'	$source		Source of the contacts
	 * @return int[]							Ids of the users (internal) or contacts (external)
	 */
	private function getTicketContactIds($test, $ticket_id, $source)
	{
		$result = $this->getUrl('tickets/'.$ticket_id, $test, 'GET', array('contact_list' => 1));
		$key = ($source === 'internal') ? 'contacts_ids_internal' : 'contacts_ids';
		$ids = array_map('intval', (array) ($result['content'][$key] ?? array()));
		sort($ids);

		return $ids;
	}

	/**
	 * Unlink internal and external contacts linked to a ticket
	 *
	 * @return void
	 */
	public function testRestTicketDeleteLinkedContact()
	{
		$test = "Unlink internal and external contacts of a ticket";

		$ticket_id = $this->createTicket($test);

		$result = $this->getUrl('contacts', $test, 'POST', array("lastname" => "testRestTicket".mt_rand()));
		$contact_id = (int) $result['content'];
		$this->assertGreaterThan(0, $contact_id, "$test Id of contact is not > 0");
		array_push($this->objectstodelete, 'contacts/'.$contact_id);

		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'POST', array("source" => "internal"));
		$this->getUrl('tickets/'.$ticket_id.'/contact/'.$contact_id.'/SUPPORTCLI', $test, 'POST', array("source" => "external"));
		$this->assertContains(1, $this->getTicketContactIds($test, $ticket_id, 'internal'), "$test The internal contact must be linked");
		$this->assertContains($contact_id, $this->getTicketContactIds($test, $ticket_id, 'external'), "$test The external contact must be linked");

		$result = $this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'DELETE', array("source" => "internal"));
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Unlinking the internal contact must succeed");
		$this->assertNotContains(1, $this->getTicketContactIds($test, $ticket_id, 'internal'), "$test The internal contact must no longer be linked");
		$this->assertContains($contact_id, $this->getTicketContactIds($test, $ticket_id, 'external'), "$test The external contact must still be linked");

		$result = $this->getUrl('tickets/'.$ticket_id.'/contact/'.$contact_id.'/SUPPORTCLI', $test, 'DELETE', array("source" => "external"));
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Unlinking the external contact must succeed");
		$this->assertNotContains($contact_id, $this->getTicketContactIds($test, $ticket_id, 'external'), "$test The external contact must no longer be linked");

		// Unlinking again the same contact must fail, it is no longer linked
		$this->getUrl('tickets/'.$ticket_id.'/contact/'.$contact_id.'/SUPPORTCLI', $test, 'DELETE', array("source" => "external"), 404);
	}

	/**
	 * Update a ticket, fields set by core and workflow fields must not be changed
	 *
	 * @return void
	 */
	public function testRestTicketUpdate()
	{
		$test = "Update a ticket";

		$ticket_id = $this->createTicket($test);
		$result = $this->getUrl('tickets/'.$ticket_id, $test);
		$track_id = (string) $result['content']['track_id'];
		$status = (int) $result['content']['status'];
		$this->assertNotEmpty($track_id, "$test The ticket must have a track id");

		$data = array(
			"subject" => "Ticket updated via the REST API",
			"track_id" => "testRestTicketTrackId".mt_rand(),
			"status" => $status,
		);
		$result = $this->getUrl('tickets/'.$ticket_id, $test, 'PUT', $data);
		$this->assertEquals("Ticket updated via the REST API", $result['content']['subject'] ?? null, "$test The subject must be returned updated");

		$result = $this->getUrl('tickets/'.$ticket_id, $test);
		$this->assertEquals("Ticket updated via the REST API", $result['content']['subject'] ?? null, "$test The subject must be saved");
		$this->assertEquals($track_id, $result['content']['track_id'] ?? null, "$test The track id must not be changed");

		// The status and the assigned user have their own requests
		$this->getUrl('tickets/'.$ticket_id, "$test with another status", 'PUT', array("status" => ($status == 8 ? 9 : 8)), 400);
		$this->getUrl('tickets/'.$ticket_id, "$test with another assigned user", 'PUT', array("fk_user_assign" => ((int) ($result['content']['fk_user_assign'] ?? 0) == 1 ? 0 : 1)), 400);
		$result = $this->getUrl('tickets/'.$ticket_id, $test);
		$this->assertEquals($status, (int) $result['content']['status'], "$test The status must not be changed");
	}

	/**
	 * Update the thirdparty of a ticket with socid or fk_soc
	 *
	 * @return void
	 */
	public function testRestTicketUpdateThirdparty()
	{
		$test = "Update the thirdparty of a ticket";

		$ticket_id = $this->createTicket($test);

		$result = $this->getUrl('thirdparties', $test, 'POST', array("name" => "testRestTicketUpdate".mt_rand()));
		$socid = (int) $result['content'];
		$this->assertGreaterThan(0, $socid, "$test Id of thirdparty is not > 0");
		array_push($this->objectstodelete, 'thirdparties/'.$socid);

		$this->getUrl('tickets/'.$ticket_id, "$test with socid", 'PUT', array("socid" => $socid));
		$result = $this->getUrl('tickets/'.$ticket_id, "$test with socid");
		$this->assertEquals($socid, (int) ($result['content']['fk_soc'] ?? 0), "$test with socid The ticket must be linked to the thirdparty");

		$this->getUrl('tickets/'.$ticket_id, "$test with an unknown fk_soc", 'PUT', array("fk_soc" => 999999999), 404);
		$this->getUrl('tickets/'.$ticket_id, "$test with different socid and fk_soc", 'PUT', array("socid" => $socid, "fk_soc" => 999999999), 400);
		$result = $this->getUrl('tickets/'.$ticket_id, $test);
		$this->assertEquals($socid, (int) ($result['content']['fk_soc'] ?? 0), "$test The thirdparty must not be changed by refused requests");

		$this->getUrl('tickets/'.$ticket_id, "$test with fk_soc 0", 'PUT', array("fk_soc" => 0));
		$result = $this->getUrl('tickets/'.$ticket_id, "$test with fk_soc 0");
		$this->assertEmpty($result['content']['fk_soc'] ?? null, "$test with fk_soc 0 The ticket must no longer be linked to the thirdparty");
	}

	/**
	 * Update a ticket that does not exist
	 *
	 * @return void
	 */
	public function testRestTicketUpdateUnknownTicket()
	{
		$test = "Update a ticket that does not exist";

		$this->getUrl('tickets/999999999', $test, 'PUT', array("subject" => "Ticket updated via the REST API"), 404);
	}

	/**
	 * Unlink a contact that is not linked to a ticket
	 *
	 * @return void
	 */
	public function testRestTicketDeleteNotLinkedContact()
	{
		$test = "Unlink a contact that is not linked to a ticket";

		$ticket_id = $this->createTicket($test);

		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'DELETE', array("source" => "internal"), 404);

		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'POST', array("source" => "internal"));
		// Linked as internal: unlinking it as external must not unlink it
		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'DELETE', array("source" => "external"), 404);
		$this->assertContains(1, $this->getTicketContactIds($test, $ticket_id, 'internal'), "$test The internal contact must still be linked");
	}

	/**
	 * Unlink a contact with a source that is neither internal nor external
	 *
	 * @return void
	 */
	public function testRestTicketDeleteContactBadSource()
	{
		$test = "Unlink a contact of a ticket with a bad source";

		$ticket_id = $this->createTicket($test);

		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'POST', array("source" => "internal"));
		$this->getUrl('tickets/'.$ticket_id.'/contact/1/SUPPORTTEC', $test, 'DELETE', array("source" => "badsource"), 400);
		$this->assertContains(1, $this->getTicketContactIds($test, $ticket_id, 'internal'), "$test The internal contact must still be linked");
	}

	/**
	 * Unlink a contact of a ticket that does not exist
	 *
	 * @return void
	 */
	public function testRestTicketDeleteContactUnknownTicket()
	{
		$test = "Unlink a contact of a ticket that does not exist";

		$this->getUrl('tickets/999999999/contact/1/SUPPORTTEC', $test, 'DELETE', array("source" => "internal"), 404);
	}
}
