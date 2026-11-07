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
 *      \file       test/phpunit/RestAPIAgendaEventsGetTest.php
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
class RestAPIAgendaEventsGetTest extends AbstractRestAPITest
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
	 * testRestAgendaEventGet
	 *
	 * @return void
	 */
	public function testRestAgendaEventGet()
	{
		$test = "Get an existing agenda event";

		$event_id = $this->createEvent($test);

		$result = $this->getUrl('agendaevents/'.$event_id, $test, 'GET');
		$this->assertEquals($event_id, (int) ($result['content']['id'] ?? 0), "$test Must return the requested event");
		$this->assertStringStartsWith("Test event ", (string) ($result['content']['label'] ?? ''), "$test Label must be returned");
		$this->assertEquals(1, (int) ($result['content']['userownerid'] ?? 0), "$test Owner must be returned");

		$this->getUrl('agendaevents/'.$event_id, "$test (cleanup)", 'DELETE');
	}

	/**
	 * testRestAgendaEventGetNotFound
	 *
	 * @return void
	 */
	public function testRestAgendaEventGetNotFound()
	{
		$test = "Get an agenda event that does not exist";

		$this->getUrl('agendaevents/999999999', $test, 'GET', null, 404);
	}

	/**
	 * testRestAgendaEventGetSpecimen
	 *
	 * The id 0 returns a specimen event
	 *
	 * @return void
	 */
	public function testRestAgendaEventGetSpecimen()
	{
		$test = "Get the specimen agenda event";

		$result = $this->getUrl('agendaevents/0', $test, 'GET');
		$this->assertEquals('Label of event Specimen', $result['content']['label'] ?? null, "$test Must return the specimen");
	}
}
