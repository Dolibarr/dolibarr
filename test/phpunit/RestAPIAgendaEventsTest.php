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
 *      \file       test/phpunit/RestAPIAgendaEventsTest.php
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
class RestAPIAgendaEventsTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('agenda'), 'module agenda must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string		$endpoint 	Endpoint - "agendaevents/id"
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
	 * Create an agenda event owned by the admin user
	 *
	 * @param string	$test	Text to be added to output
	 * @return int				Id of the agenda event
	 */
	private function createEvent($test)
	{
		$data = array(
			"userownerid" => 1,
			"type_code" => "AC_OTH",
			"label" => "Test event ".mt_rand(0, 99999999),
			"datep" => dol_now(),
		);
		$result = $this->getUrl('agendaevents', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of event is not > 0");

		return (int) $result['content'];
	}

	/**
	 * testRestAgendaEventListPagination
	 *
	 * @return void
	 */
	public function testRestAgendaEventListPagination()
	{
		$test = "List agenda events with pagination data";

		$first_id = $this->createEvent("$test (create first event)");
		$second_id = $this->createEvent("$test (create second event)");

		try {
			$filter = array(
				'sqlfilters' => "(t.id:=:".$first_id.") or (t.id:=:".$second_id.")",
				'pagination_data' => 'true',
			);

			// limit=0 means no limit, it must not fail with a division by zero
			$result = $this->getUrl('agendaevents', "$test (limit=0)", 'GET', $filter + array('limit' => 0));
			$this->assertCount(2, $result['content']['data'] ?? array(), "$test (limit=0) All events must be returned");
			$this->assertEquals(2, $result['content']['pagination']['total'] ?? null, "$test (limit=0) Total must be 2");
			$this->assertEquals(1, $result['content']['pagination']['page_count'] ?? null, "$test (limit=0) All events are on 1 page");
			$this->assertEquals(0, $result['content']['pagination']['limit'] ?? null, "$test (limit=0) Limit must be returned");

			$result = $this->getUrl('agendaevents', "$test (limit=1)", 'GET', $filter + array('limit' => 1));
			$this->assertCount(1, $result['content']['data'] ?? array(), "$test (limit=1) Only 1 event must be returned");
			$this->assertEquals(2, $result['content']['pagination']['total'] ?? null, "$test (limit=1) Total must be 2");
			$this->assertEquals(2, $result['content']['pagination']['page_count'] ?? null, "$test (limit=1) Events are on 2 pages");
		} finally {
			$this->getUrl('agendaevents/'.$first_id, "$test (cleanup first event)", 'DELETE');
			$this->getUrl('agendaevents/'.$second_id, "$test (cleanup second event)", 'DELETE');
		}
	}

	/**
	 * testRestAgendaEventPost
	 *
	 * @return void
	 */
	public function testRestAgendaEventPost()
	{
		$test = "Create agenda event";

		$label = "Created event ".mt_rand(0, 99999999);
		$data = array(
			"userownerid" => 1,
			"type_code" => "AC_OTH",
			"label" => $label,
			"datep" => dol_now(),
		);
		$result = $this->getUrl('agendaevents', $test, 'POST', $data);
		$event_id = (int) $result['content'];
		$this->assertGreaterThan(0, $event_id, "$test Id of event is not > 0");

		$result = $this->getUrl('agendaevents/'.$event_id, "$test (read after create)", 'GET');
		$this->assertEquals($label, $result['content']['label'] ?? null, "$test Label must be stored");
		$this->assertEquals(1, (int) ($result['content']['userownerid'] ?? 0), "$test Owner must be stored");

		$this->getUrl('agendaevents/'.$event_id, "$test (cleanup)", 'DELETE');
	}

	/**
	 * testRestAgendaEventPostMissingFields
	 *
	 * @return void
	 */
	public function testRestAgendaEventPostMissingFields()
	{
		$test = "Create agenda event with missing mandatory fields";

		$this->getUrl('agendaevents', "$test (empty body)", 'POST', array(), 400);
		$this->getUrl('agendaevents', "$test (no userownerid)", 'POST', array("type_code" => "AC_OTH", "label" => "No owner"), 400);
		$this->getUrl('agendaevents', "$test (no type_code)", 'POST', array("userownerid" => 1, "label" => "No type"), 400);
	}

	/**
	 * testRestAgendaEventPostThirdparty
	 *
	 * @return void
	 */
	public function testRestAgendaEventPostThirdparty()
	{
		$test = "Create agenda event linked to a third party";

		$result = $this->getUrl('thirdparties', "$test (create third party)", 'POST', array("name" => "testRestAgendaThirdparty".mt_rand()));
		$socid = (int) $result['content'];
		$this->assertGreaterThan(0, $socid, "$test Id of third party is not > 0");

		$data = array(
			"userownerid" => 1,
			"type_code" => "AC_OTH",
			"label" => "Event of third party ".mt_rand(0, 99999999),
			"datep" => dol_now(),
			"socid" => $socid,
		);
		$result = $this->getUrl('agendaevents', $test, 'POST', $data);
		$event_id = (int) $result['content'];
		$this->assertGreaterThan(0, $event_id, "$test Id of event is not > 0");

		$result = $this->getUrl('agendaevents/'.$event_id, "$test (read after create)", 'GET');
		$this->assertEquals($socid, (int) ($result['content']['socid'] ?? 0), "$test Third party must be stored");

		$this->getUrl('agendaevents/'.$event_id, "$test (cleanup event)", 'DELETE');
		$this->getUrl('thirdparties/'.$socid, "$test (cleanup third party)", 'DELETE');
	}

	/**
	 * testRestAgendaEventPostExtrafields
	 *
	 * @return void
	 */
	public function testRestAgendaEventPostExtrafields()
	{
		$test = "Create agenda event with extrafields";

		$attrname = "testrestpost".mt_rand(0, 99999999);
		$extrafield = array(
			"label" => "Test REST post",
			"type" => "varchar",
			"size" => "255",
			"pos" => 100,
			"alwayseditable" => 1,
			"default" => "",
			"totalizable" => 0,
			"printable" => 0,
			"showintooltip" => 0,
			"required" => 0,
			"langfile" => "",
			"computed" => "",
			"unique" => 0,
			"param" => "",
			"perms" => "",
			"list" => "1",
			"help" => "",
		);
		$this->getUrl('setup/extrafields/actioncomm/'.$attrname, "$test (create extrafield)", 'POST', $extrafield);

		try {
			$data = array(
				"userownerid" => 1,
				"type_code" => "AC_OTH",
				"label" => "Event with extrafield ".mt_rand(0, 99999999),
				"datep" => dol_now(),
				"array_options" => array("options_".$attrname => "<b>Extra value</b>"),
			);
			$result = $this->getUrl('agendaevents', $test, 'POST', $data);
			$event_id = (int) $result['content'];
			$this->assertGreaterThan(0, $event_id, "$test Id of event is not > 0");

			$result = $this->getUrl('agendaevents/'.$event_id, "$test (read after create)", 'GET');
			$this->assertEquals("Extra value", $result['content']['array_options']['options_'.$attrname] ?? null, "$test Extrafield must be sanitized and stored");

			$this->getUrl('agendaevents/'.$event_id, "$test (cleanup event)", 'DELETE');

			$data["array_options"] = array("options_bad key" => "Value");
			$this->getUrl('agendaevents', "$test (bad extrafield name)", 'POST', $data, 400);
		} finally {
			$this->getUrl('setup/extrafields/actioncomm/'.$attrname, "$test (cleanup extrafield)", 'DELETE');
		}
	}

	/**
	 * testRestAgendaEventDelete
	 *
	 * @return int	Id of the deleted agenda event
	 */
	public function testRestAgendaEventDelete()
	{
		$test = "Delete agenda event";

		$event_id = $this->createEvent($test);

		$result = $this->getUrl('agendaevents/'.$event_id, $test, 'DELETE');
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Delete must succeed");

		$this->getUrl('agendaevents/'.$event_id, "$test (read after delete)", 'GET', null, 404);

		return $event_id;
	}

	/**
	 * testRestAgendaEventDeleteAgain
	 *
	 * @depends testRestAgendaEventDelete
	 *
	 * @param int $event_id	Id of the deleted agenda event
	 * @return void
	 */
	public function testRestAgendaEventDeleteAgain($event_id)
	{
		$test = "Delete an already deleted agenda event";

		$this->getUrl('agendaevents/'.$event_id, $test, 'DELETE', null, 404);
	}

	/**
	 * testRestAgendaEventDeleteNotFound
	 *
	 * @return void
	 */
	public function testRestAgendaEventDeleteNotFound()
	{
		$test = "Delete an agenda event that does not exist";

		$this->getUrl('agendaevents/999999999', $test, 'DELETE', null, 404);
	}
}
