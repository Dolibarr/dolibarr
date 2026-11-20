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
 *      \file       test/phpunit/RestAPIExpenseReportTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

require_once __DIR__."/AbstractRestAPITest.php";
require_once __DIR__."/../../htdocs/expensereport/class/expensereport.class.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIExpenseReportTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('expensereport'), 'module expensereport must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string		$endpoint 	Endpoint - "expensereports/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'GET'|'POST'|'PUT'|'DELETE'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded (POST/PUT) or sent as query params (GET/DELETE)
	 * @param int			$expected_error	Expected error code
	 * @param ?string		$api_key	API key to use instead of the one of the admin user
	 *
	 * @return mixed	Decodes JSON value returned by the service
	 */
	private function getUrl($endpoint, $test_title = "", $method = "GET", $data = null, $expected_error = 0, $api_key = null)
	{
		$params = '';
		$body = null;
		if ($method === 'POST' || $method === 'PUT') {
			$body = json_encode($data);
		} elseif (!empty($data)) {
			$params = '?'.http_build_query($data);
		}

		$addheaders = array(
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: ".($api_key ?? $this->api_key)
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
	 * Create a draft expense report of the API user
	 *
	 * @param string	$test	Text to be added to output
	 * @return int				Id of the expense report
	 */
	private function createExpenseReport($test)
	{
		$result = $this->getUrl('users/info', $test, 'GET');
		$userid = (int) ($result['content']['id'] ?? 0);
		$this->assertGreaterThan(0, $userid, "$test Id of API user is not > 0");

		$now = dol_now();
		$data = array(
			"fk_user_author" => $userid,
			"date_debut" => $now,
			"date_fin" => $now,
			"note_private" => "Created by ".get_class($this),
		);
		$result = $this->getUrl('expensereports', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of expense report is not > 0");

		return (int) $result['content'];
	}

	/**
	 * Check the status of an expense report
	 *
	 * @param int		$id			Id of the expense report
	 * @param int		$status		Expected status
	 * @param string	$test		Text to be added to output
	 * @return void
	 */
	private function assertStatus($id, $status, $test)
	{
		$result = $this->getUrl('expensereports/'.$id, $test, 'GET');
		$this->assertEquals($status, $result['content']['status'] ?? null, "$test Status of expense report must be ".$status);
	}

	/**
	 * Check that every given status change is refused with a 403 and leaves the status unchanged
	 *
	 * @param int		$id			Id of the expense report
	 * @param string[]	$actions	Actions to try: validate, approve, deny, cancel, settodraft
	 * @param int		$status		Current status, that must not change
	 * @param string	$test		Text to be added to output
	 * @return void
	 */
	private function assertActionsRefused($id, $actions, $status, $test)
	{
		$data = array(
			'validate' => array("notrigger" => 0),
			'approve' => array("notrigger" => 0),
			'deny' => array("details" => "Refused by ".get_class($this), "notrigger" => 0),
			'cancel' => array("detail" => "Canceled by ".get_class($this), "notrigger" => 0),
			'settodraft' => array(),
		);
		foreach ($actions as $action) {
			$this->getUrl('expensereports/'.$id.'/'.$action, "$test ($action)", 'POST', $data[$action], 403);
			$this->assertStatus($id, $status, "$test ($action)");
		}
	}

	/**
	 * A draft can only be validated
	 *
	 * @return int	Id of the validated expense report
	 */
	public function testRestExpenseReportValidateDraft()
	{
		$test = "Validate a draft expense report";

		$id = $this->createExpenseReport($test);

		$this->assertActionsRefused($id, array('approve', 'deny', 'cancel'), ExpenseReport::STATUS_DRAFT, $test);

		$this->getUrl('expensereports/'.$id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, $test);

		return $id;
	}

	/**
	 * A validated report can be approved
	 *
	 * @depends testRestExpenseReportValidateDraft
	 *
	 * @param int $id	Id of the validated expense report
	 * @return int		Id of the approved expense report
	 */
	public function testRestExpenseReportApproveValidated($id)
	{
		$test = "Approve a validated expense report";

		$this->getUrl('expensereports/'.$id.'/approve', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_APPROVED, $test);

		return $id;
	}

	/**
	 * An approved report can not be validated again
	 *
	 * @depends testRestExpenseReportApproveValidated
	 *
	 * @param int $id	Id of the approved expense report
	 * @return int		Id of the paid expense report
	 */
	public function testRestExpenseReportApprovedRefused($id)
	{
		$test = "Change the status of an approved expense report";

		$this->assertActionsRefused($id, array('validate'), ExpenseReport::STATUS_APPROVED, $test);

		$this->getUrl('expensereports/'.$id.'/setpaid', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_CLOSED, $test);

		return $id;
	}

	/**
	 * A paid report can only be canceled, by a user who can approve or pay
	 *
	 * @depends testRestExpenseReportApprovedRefused
	 *
	 * @param int $id	Id of the paid expense report
	 * @return int		Id of the canceled expense report
	 */
	public function testRestExpenseReportPaidRefused($id)
	{
		$test = "Change the status of a paid expense report";

		$this->assertActionsRefused($id, array('validate', 'approve', 'deny', 'settodraft'), ExpenseReport::STATUS_CLOSED, $test);

		$this->getUrl('expensereports/'.$id.'/cancel', $test, 'POST', array("detail" => "Canceled by ".get_class($this), "notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_CANCELED, $test);

		return $id;
	}

	/**
	 * A canceled report can not change status
	 *
	 * @depends testRestExpenseReportPaidRefused
	 *
	 * @param int $id	Id of the canceled expense report
	 * @return void
	 */
	public function testRestExpenseReportCanceledRefused($id)
	{
		$test = "Change the status of a canceled expense report";

		$this->assertActionsRefused($id, array('validate', 'approve', 'deny', 'cancel', 'settodraft'), ExpenseReport::STATUS_CANCELED, $test);
	}

	/**
	 * A validated report can be set back to draft
	 *
	 * @return void
	 */
	public function testRestExpenseReportSetToDraftValidated()
	{
		$test = "Set a validated expense report back to draft";

		$id = $this->createExpenseReport($test);

		$this->getUrl('expensereports/'.$id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, $test);

		$this->getUrl('expensereports/'.$id.'/settodraft', $test, 'POST', array());
		$this->assertStatus($id, ExpenseReport::STATUS_DRAFT, $test);
	}

	/**
	 * A refused report can not be approved, but can be validated again
	 *
	 * @return void
	 */
	public function testRestExpenseReportValidateRefused()
	{
		$test = "Validate a refused expense report";

		$id = $this->createExpenseReport($test);

		$this->getUrl('expensereports/'.$id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->getUrl('expensereports/'.$id.'/deny', $test, 'POST', array("details" => "Refused by ".get_class($this), "notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_REFUSED, $test);

		$this->assertActionsRefused($id, array('approve', 'cancel', 'settodraft'), ExpenseReport::STATUS_REFUSED, $test);

		$this->getUrl('expensereports/'.$id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, $test);
	}
}
