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
	 * @var int[]	Ids of the expense reports created for other users, to delete at end of the test
	 */
	private $reportstodelete = array();

	/**
	 * @var int[]	Ids of the users created by the test, to delete at end of the test
	 */
	private $userstodelete = array();

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
	 * Delete the users and expense reports created by the test. The API server does not see the transaction
	 * of the test class, so what is created for it is committed and is not removed by the final rollback.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		$reportstodelete = $this->reportstodelete;
		$userstodelete = $this->userstodelete;
		$this->reportstodelete = array();
		$this->userstodelete = array();
		foreach ($reportstodelete as $id) {
			$this->getUrl('expensereports/'.$id, "Delete expense report $id at end of the test", 'DELETE');
		}
		foreach ($userstodelete as $id) {
			$this->getUrl('users/'.$id, "Delete user $id at end of the test", 'DELETE');
		}

		parent::tearDown();
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

	/**
	 * Create an internal user whose supervisor is the admin user. The user is deleted at end of the test.
	 *
	 * @param string							$test	Text to be added to output
	 * @param array<array{0:string,1:string}>	$rights	List of array(module, perms) to add to the user
	 * @return array{0:int,1:string}					Id and API key of the user
	 */
	private function createUserOfAdmin($test, $rights)
	{
		global $db, $user;

		$password = 'Ab1-'.bin2hex(random_bytes(8));

		$tmpuser = new User($db);
		$tmpuser->login = 'testrestexpensereport'.mt_rand(0, 99999999);
		$tmpuser->lastname = $tmpuser->login;
		$tmpuser->entity = 1;
		$tmpuser->fk_user = $user->id;		// So the user is a subordinate of the admin user

		// The API server reads the user with its own connection, so it must be committed out of the transaction of the test class
		$db->commit();
		$id = $tmpuser->create($user);
		$resultpassword = ($id > 0 ? $tmpuser->setPassword($user, $password) : '');
		$tmpuser->addrights(0, 'user', 'self');		// Needed by the login API to generate the API key
		foreach ($rights as $right) {
			$tmpuser->addrights(0, $right[0], $right[1]);
		}
		$db->begin();

		$this->assertGreaterThan(0, $id, "$test Creation of user failed: ".$tmpuser->error);
		$this->assertNotEmpty($resultpassword, "$test Password of user not set: ".$tmpuser->error);
		array_unshift($this->userstodelete, $id);

		$result = $this->getUrl('login', $test, 'GET', array("login" => $tmpuser->login, "password" => $password));

		return array($id, (string) $result['content']['success']['token']);
	}

	/**
	 * Create two subordinates of the admin user, and an expense report of the first one, validated by himself.
	 * The second user can read all expense reports, so only the per-user checks of cancel and settodraft refuse him.
	 *
	 * @param string	$test	Text to be added to output
	 * @return array{0:int,1:string,2:string}	Id of the validated expense report, API key of its author, API key of the other user
	 */
	private function createReportValidatedBySubordinate($test)
	{
		list($authorid, $authorkey) = $this->createUserOfAdmin($test, array(array('expensereport', 'lire'), array('expensereport', 'creer')));
		list(, $otherkey) = $this->createUserOfAdmin($test, array(array('expensereport', 'lire'), array('expensereport', 'creer'), array('expensereport', 'readall')));

		$data = array(
			"fk_user_author" => $authorid,
			"date_debut" => dol_now(),
			"date_fin" => dol_now(),
			"note_private" => "Created by ".get_class($this),
		);
		$result = $this->getUrl('expensereports', $test, 'POST', $data);
		$id = (int) $result['content'];
		$this->assertGreaterThan(0, $id, "$test Id of expense report is not > 0");
		array_unshift($this->reportstodelete, $id);

		$this->getUrl('expensereports/'.$id.'/validate', "$test (validate by author)", 'POST', array("notrigger" => 0), 0, $authorkey);
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, $test);

		return array($id, $authorkey, $otherkey);
	}

	/**
	 * Only the author or the user who validated the report can cancel it (confirm_cancel of expensereport/card.php)
	 *
	 * @return void
	 */
	public function testRestExpenseReportCancelOnlyByAuthorOrValidator()
	{
		$test = "Cancel an expense report as another user";

		list($id, $authorkey, $otherkey) = $this->createReportValidatedBySubordinate($test);
		$data = array("detail" => "Canceled by ".get_class($this), "notrigger" => 0);

		// A user who can read the report but is neither its author nor its validator
		$this->getUrl('expensereports/'.$id.'/cancel', "$test (other user)", 'POST', $data, 403, $otherkey);
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, "$test (other user)");

		// The supervisor of the author is not enough to cancel
		$this->getUrl('expensereports/'.$id.'/cancel', "$test (supervisor)", 'POST', $data, 403);
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, "$test (supervisor)");

		$this->getUrl('expensereports/'.$id.'/cancel', "$test (author)", 'POST', $data, 0, $authorkey);
		$this->assertStatus($id, ExpenseReport::STATUS_CANCELED, "$test (author)");
	}

	/**
	 * Only the author, the user who validated the report or a supervisor of the author can set it to draft (confirm_setdraft of expensereport/card.php)
	 *
	 * @return void
	 */
	public function testRestExpenseReportSetToDraftOnlyByAuthorValidatorOrSupervisor()
	{
		$test = "Set an expense report to draft as another user";

		list($id, $authorkey, $otherkey) = $this->createReportValidatedBySubordinate($test);

		// A user who can read the report but is neither its author, its validator nor a supervisor of the author
		$this->getUrl('expensereports/'.$id.'/settodraft', "$test (other user)", 'POST', array(), 403, $otherkey);
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, "$test (other user)");

		$this->getUrl('expensereports/'.$id.'/settodraft', "$test (author)", 'POST', array(), 0, $authorkey);
		$this->assertStatus($id, ExpenseReport::STATUS_DRAFT, "$test (author)");

		$this->getUrl('expensereports/'.$id.'/validate', "$test (validate again by author)", 'POST', array("notrigger" => 0), 0, $authorkey);
		$this->assertStatus($id, ExpenseReport::STATUS_VALIDATED, $test);

		// The admin user is the supervisor of the author
		$this->getUrl('expensereports/'.$id.'/settodraft', "$test (supervisor)", 'POST', array(), 0);
		$this->assertStatus($id, ExpenseReport::STATUS_DRAFT, "$test (supervisor)");
	}
}
