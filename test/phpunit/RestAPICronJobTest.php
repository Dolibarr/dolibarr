<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *      \file       test/phpunit/RestAPICronJobTest.php
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
class RestAPICronJobTest extends AbstractRestAPITest
{
	/**
	 *
	 * @param string		$endpoint 	Endpoint - "cronjobs/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'GET'|'POST'|'PUT'|'DELETE'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded (POST/PUT) or sent as query params (GET/DELETE)
	 * @param int			$expected_error	Expected error code
	 *
	 * @return mixed	Decoded JSON value returned by the service
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
	 * testRestCronJobCreate
	 *
	 * @return int	Id of created cron job
	 */
	public function testRestCronJobCreate()
	{
		$test = "Create cron job";

		$data = array(
			"jobtype" => "method",
			"label" => "testRestCronJob".mt_rand(),
			"classesname" => "core/class/utils.class.php",
			"objectname" => "Utils",
			"methodename" => "purgeFiles",
			"params" => "tempfilesold",
			"datenextrun" => time(),
			"unitfrequency" => 86400,
			"frequency" => 1,
			"status" => 0,	// Disabled, so the real cron runner never picks this test job up on its own
		);

		$result = $this->getUrl('cronjobs', $test, 'POST', $data);

		$this->assertTrue(is_int($result['content']), "$test Result data is expected to be integer");
		$this->assertGreaterThan(0, $result['content'], "$test Id returned is not > 0");

		return $result['content'];
	}

	/**
	 * testRestCronJobGet
	 *
	 * @depends testRestCronJobCreate
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobGet($cronjob_id)
	{
		$test = "Get cron job";

		$result = $this->getUrl('cronjobs/'.$cronjob_id, $test, 'GET');

		$this->assertEquals($cronjob_id, $result['content']['id'] ?? null, "$test Id of fetched job must match");
		$this->assertEquals('purgeFiles', $result['content']['methodename'] ?? null, "$test methodename must match what was created");

		return $cronjob_id;
	}

	/**
	 * testRestCronJobGetNotFound
	 *
	 * @depends testRestCronJobGet
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobGetNotFound($cronjob_id)
	{
		$test = "Get non-existent cron job";

		$this->getUrl('cronjobs/99999999', $test, 'GET', null, 404);

		return $cronjob_id;
	}

	/**
	 * testRestCronJobList
	 *
	 * @depends testRestCronJobGetNotFound
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobList($cronjob_id)
	{
		$test = "List cron jobs";

		$result = $this->getUrl('cronjobs', $test, 'GET', array('sortfield' => 't.rowid', 'sortorder' => 'DESC', 'limit' => 100, 'status' => -1));

		$this->assertIsArray($result['content'], "$test Result must be an array");

		$found = false;
		foreach ($result['content'] as $line) {
			if (($line['id'] ?? null) == $cronjob_id) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, "$test Created cron job must be present in the list");

		return $cronjob_id;
	}

	/**
	 * testRestCronJobUpdate
	 *
	 * @depends testRestCronJobList
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobUpdate($cronjob_id)
	{
		$test = "Update cron job";

		$data = array(
			"label" => "testRestCronJobUpdated",
		);

		$result = $this->getUrl('cronjobs/'.$cronjob_id, $test, 'PUT', $data);

		$this->assertEquals('testRestCronJobUpdated', $result['content']['label'] ?? null, "$test Label must be updated");

		return $cronjob_id;
	}

	/**
	 * testRestCronJobRun
	 *
	 * @depends testRestCronJobUpdate
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobRun($cronjob_id)
	{
		$test = "Run cron job";

		// getUrl() puts POST $data only into the JSON body, never the query string, but run()'s
		// $securitykey is {@from query} — the environment's CRON_KEY (set by default in the CI
		// demo database dump) must be passed as a query param, not a body field.
		$endpoint = 'cronjobs/'.$cronjob_id.'/run';
		$cronkey = getDolGlobalString('CRON_KEY');
		if ($cronkey !== '') {
			$endpoint .= '?securitykey='.urlencode($cronkey);
		}
		$result = $this->getUrl($endpoint, $test, 'POST', array());

		$this->assertArrayHasKey('lastresult', $result['content'], "$test Response must contain lastresult, job must have been actually run");
		$this->assertNotEmpty($result['content']['datelastresult'] ?? null, "$test datelastresult must be set after running");

		return $cronjob_id;
	}

	/**
	 * testRestCronJobDelete
	 *
	 * @depends testRestCronJobRun
	 *
	 * @param int $cronjob_id Id of cron job that was created
	 * @return int
	 */
	public function testRestCronJobDelete($cronjob_id)
	{
		$test = "Delete cron job";

		$result = $this->getUrl('cronjobs/'.$cronjob_id, $test, 'DELETE');

		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Deletion must return success code 200");

		// Confirm it is really gone
		$this->getUrl('cronjobs/'.$cronjob_id, "Get deleted cron job", 'GET', null, 404);

		return $cronjob_id;
	}
}
