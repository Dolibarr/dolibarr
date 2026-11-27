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
		self::assertTrue(isModEnabled('expensereport'), 'module expensereport must be enabled');
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
	 * Create an internal user whose supervisor is the admin user. It is deleted at end of the test.
	 *
	 * @param string							$test	Text to be added to output
	 * @param array<array{0:string,1:string}>	$rights	List of array(module, perms) to add to the user
	 * @return array{0:int,1:string}					Id and API key of the user
	 */
	private function createUser($test, $rights = array())
	{
		global $db, $user;

		$password = 'Ab1-'.bin2hex(random_bytes(8));

		$tmpuser = new User($db);
		$tmpuser->login = 'testrestexpensereport'.mt_rand(0, 99999999);
		$tmpuser->lastname = $tmpuser->login;
		$tmpuser->entity = 1;
		$tmpuser->fk_user = $user->id;		// So the user is in the hierarchy of the admin user

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
		array_unshift($this->objectstodelete, 'users/'.$id);

		$result = $this->getUrl('login', $test, 'GET', array("login" => $tmpuser->login, "password" => $password));

		return array($id, (string) $result['content']['success']['token']);
	}

	/**
	 * Create a draft expense report for a user, with the admin user. It is deleted at end of the test.
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$fk_user_author		Id of the user the expense report is for
	 * @return int							Id of the expense report
	 */
	private function createExpenseReportForUser($test, $fk_user_author)
	{
		$data = array(
			"fk_user_author" => $fk_user_author,
			"date_debut" => dol_now() - 86400,
			"date_fin" => dol_now(),
		);
		$result = $this->getUrl('expensereports', $test, 'POST', $data);
		$id = (int) $result['content'];
		$this->assertGreaterThan(0, $id, "$test Id of expense report is not > 0");
		array_unshift($this->objectstodelete, 'expensereports/'.$id);

		return $id;
	}

	/**
	 * Read the user an expense report is for, with the admin user
	 *
	 * @param string	$test	Text to be added to output
	 * @param int		$id		Id of the expense report
	 * @return int				Id of the user the expense report is for
	 */
	private function getAuthor($test, $id)
	{
		$result = $this->getUrl('expensereports/'.$id, $test, 'GET');

		return (int) ($result['content']['fk_user_author'] ?? 0);
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
	 * testRestExpenseReportPutAuthorInHierarchy
	 *
	 * @return void
	 */
	public function testRestExpenseReportPutAuthorInHierarchy()
	{
		global $user;

		$test = "Move an expense report to a user of the hierarchy";

		list($subordinate_id) = $this->createUser($test);
		$id = $this->createExpenseReportForUser($test, $user->id);

		$result = $this->getUrl('expensereports/'.$id, $test, 'PUT', array("fk_user_author" => $subordinate_id));
		$this->assertEquals($subordinate_id, (int) ($result['content']['fk_user_author'] ?? 0), "$test Update must return the new user");

		$this->assertEquals($subordinate_id, $this->getAuthor("$test (read after update)", $id), "$test The new user must be stored");
	}

	/**
	 * testRestExpenseReportPutAuthorOutsideHierarchy
	 *
	 * @return void
	 */
	public function testRestExpenseReportPutAuthorOutsideHierarchy()
	{
		global $user;

		$test = "Move an expense report to a user outside the hierarchy";

		list($restricted_id, $api_key) = $this->createUser($test, array(array('expensereport', 'lire'), array('expensereport', 'creer')));
		$id = $this->createExpenseReportForUser($test, $restricted_id);

		// The admin user is the supervisor of the restricted user, so not in his hierarchy
		$this->getUrl('expensereports/'.$id, $test, 'PUT', array("fk_user_author" => $user->id), 403, $api_key);
		$this->assertEquals($restricted_id, $this->getAuthor("$test (read after refused update)", $id), "$test The user must not change");

		// Sending the current user is not a change, so the update is allowed
		$note = "Updated note ".mt_rand(0, 99999999);
		$result = $this->getUrl('expensereports/'.$id, "$test (same user)", 'PUT', array("fk_user_author" => $restricted_id, "note_public" => $note), 0, $api_key);
		$this->assertEquals($note, $result['content']['note_public'] ?? null, "$test The note must be updated");
		$this->assertEquals($restricted_id, (int) ($result['content']['fk_user_author'] ?? 0), "$test The user must not change");
	}

	/**
	 * testRestExpenseReportPutAuthorNotFound
	 *
	 * @return void
	 */
	public function testRestExpenseReportPutAuthorNotFound()
	{
		global $user;

		$test = "Move an expense report to a user that does not exist";

		$id = $this->createExpenseReportForUser($test, $user->id);

		$this->getUrl('expensereports/'.$id, $test, 'PUT', array("fk_user_author" => 999999999), 400);
		$this->assertEquals($user->id, $this->getAuthor("$test (read after refused update)", $id), "$test The user must not change");
	}

	/**
	 * Create an approved expense report of 10 with a payment of 4, so it stays approved
	 *
	 * @param string	$test	Text to be added to output
	 * @return array{0:int,1:int,2:array<string,mixed>}	Id of the expense report, id of the payment, payment read by the API
	 */
	private function createExpenseReportWithPayment($test)
	{
		global $db, $conf;

		// Any active expense type and payment type, without assuming a given code exists in the dictionaries
		$fk_c_type_fees = 0;
		$resql = $db->query("SELECT id FROM ".MAIN_DB_PREFIX."c_type_fees WHERE active = 1 ORDER BY id");
		if ($resql && ($obj = $db->fetch_object($resql))) {
			$fk_c_type_fees = (int) $obj->id;
		}
		$this->assertGreaterThan(0, $fk_c_type_fees, "$test No active expense type");
		$fk_typepayment = 0;
		$resql = $db->query("SELECT id FROM ".MAIN_DB_PREFIX."c_paiement WHERE active = 1 ORDER BY id");
		if ($resql && ($obj = $db->fetch_object($resql))) {
			$fk_typepayment = (int) $obj->id;
		}
		$this->assertGreaterThan(0, $fk_typepayment, "$test No active payment type");

		$result = $this->getUrl('users/info', $test, 'GET');
		$userid = (int) ($result['content']['id'] ?? 0);

		$id = $this->createExpenseReport($test);
		$line = array(
			"date" => dol_now(),
			"fk_c_type_fees" => $fk_c_type_fees,
			"qty" => 1,
			"value_unit" => 10,
			"vatrate" => 0,
			"comments" => "Line created by ".get_class($this),
		);
		$this->getUrl('expensereports/'.$id.'/line', $test, 'POST', $line);
		$this->getUrl('expensereports/'.$id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->getUrl('expensereports/'.$id.'/approve', $test, 'POST', array("notrigger" => 0));
		$this->assertStatus($id, ExpenseReport::STATUS_APPROVED, $test);

		$payment = array(
			"fk_typepayment" => $fk_typepayment,
			"datep" => dol_now(),
			"amounts" => array($userid => 4),
			"num_payment" => "UPD-1",
			"note_public" => "Payment created by ".get_class($this),
		);
		if (isModEnabled('bank')) {
			$ref = "EU".mt_rand(0, 99999999);	// ref is 12 chars max, ref and label must be unique
			$account = array(
				"ref" => $ref,
				"label" => "testRestExpenseReportUpdatePayment ".$ref,
				"type" => 1,
				"currency_code" => $conf->currency,
				"country_id" => 1,
			);
			$result = $this->getUrl('bankaccounts', $test, 'POST', $account);
			$this->assertGreaterThan(0, (int) $result['content'], "$test Id of bank account is not > 0");
			$payment["accountid"] = (int) $result['content'];
		}
		$result = $this->getUrl('expensereports/'.$id.'/payments', $test, 'POST', $payment);
		$pid = (int) $result['content'];
		$this->assertGreaterThan(0, $pid, "$test Id of payment is not > 0");
		$this->assertStatus($id, ExpenseReport::STATUS_APPROVED, $test);

		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');

		return array($id, $pid, $result['content']);
	}

	/**
	 * The date, number and note of a payment can be changed
	 *
	 * @return void
	 */
	public function testRestExpenseReportUpdatePayment()
	{
		$test = "Update the number, date and note of a payment";

		list($id, $pid, $before) = $this->createExpenseReportWithPayment($test);

		$datep = (int) $before['datep'] - 86400;
		$data = array(
			"num_payment" => "UPD-2",
			"datep" => $datep,
			"note_public" => "Payment updated by ".get_class($this),
		);
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', $data);

		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');
		$this->assertEquals("UPD-2", $result['content']['num_payment'] ?? null, "$test Number of payment");
		$this->assertEquals($datep, (int) ($result['content']['datep'] ?? 0), "$test Date of payment");
		$this->assertEquals("Payment updated by ".get_class($this), $result['content']['note_public'] ?? null, "$test Note of payment");
		$this->assertEquals(4, (float) ($result['content']['amount'] ?? 0), "$test Amount of payment");

		// A field not sent keeps its value
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', array("num_payment" => "UPD-3"));
		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');
		$this->assertEquals("UPD-3", $result['content']['num_payment'] ?? null, "$test Number of payment");
		$this->assertEquals("Payment updated by ".get_class($this), $result['content']['note_public'] ?? null, "$test Note of payment must be kept");
	}

	/**
	 * The fields set by the server can not be changed by the request
	 *
	 * @return void
	 */
	public function testRestExpenseReportUpdatePaymentServerFields()
	{
		$test = "Update a payment with fields set by the server";

		list($id, $pid, $before) = $this->createExpenseReportWithPayment($test);

		$data = $before;	// The whole payment as read, as a client doing GET then PUT does
		$data["fk_bank"] = (int) ($before['bank_line'] ?? 0) + 1000;	// fk_bank is not in the GET result, bank_line is the same value
		$data["fk_user_creat"] = (int) ($before['fk_user_creat'] ?? 0) + 1000;
		$data["fk_expensereport"] = $id + 1000;
		$data["datec"] = (int) ($before['datec'] ?? 0) - 86400;
		$data["num_payment"] = "UPD-4";
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', $data);

		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');
		$this->assertEquals("UPD-4", $result['content']['num_payment'] ?? null, "$test Number of payment");
		$this->assertEquals((int) ($before['bank_line'] ?? 0), (int) ($result['content']['bank_line'] ?? 0), "$test Bank line of payment must not change");
		$this->assertEquals((int) ($before['fk_user_creat'] ?? 0), (int) ($result['content']['fk_user_creat'] ?? 0), "$test Creator of payment must not change");
		$this->assertEquals($id, (int) ($result['content']['fk_expensereport'] ?? 0), "$test Expense report of payment must not change");
		$this->assertEquals((int) ($before['datec'] ?? 0), (int) ($result['content']['datec'] ?? 0), "$test Creation date of payment must not change");
	}

	/**
	 * The amount and type of a payment can not be changed
	 *
	 * @return void
	 */
	public function testRestExpenseReportUpdatePaymentAmount()
	{
		$test = "Update the amount of a payment";

		list($id, $pid, $before) = $this->createExpenseReportWithPayment($test);

		// Paying the remainder by raising the payment, or lowering it, is refused
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', array("amount" => 10), 400);
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', array("amount" => 1), 400);
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', array("fk_typepayment" => (int) $before['fk_typepayment'] + 1000), 400);

		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');
		$this->assertEquals(4, (float) ($result['content']['amount'] ?? 0), "$test Amount of payment must not change");
		$this->assertEquals((int) $before['fk_typepayment'], (int) ($result['content']['fk_typepayment'] ?? 0), "$test Type of payment must not change");
		$this->assertStatus($id, ExpenseReport::STATUS_APPROVED, $test);

		// The same amount, as sent back by a client doing GET then PUT, is accepted
		$this->getUrl('expensereports/'.$id.'/payments/'.$pid, $test, 'PUT', array("amount" => $before['amount'], "num_payment" => "UPD-5"));
		$result = $this->getUrl('expensereports/payments/'.$pid, $test, 'GET');
		$this->assertEquals("UPD-5", $result['content']['num_payment'] ?? null, "$test Number of payment");
	}

	/**
	 * Getting an expense report that does not exist returns a 404
	 *
	 * @return void
	 */
	public function testRestExpenseReportGetNotFound()
	{
		$test = "Get an expense report that does not exist";

		$this->getUrl('expensereports/999999999', $test, 'GET', null, 404);
	}

	/**
	 * Getting or updating a payment that does not exist returns a 404 about the payment
	 *
	 * @return void
	 */
	public function testRestExpenseReportPaymentNotFound()
	{
		$test = "Get a payment of expense report that does not exist";

		$result = $this->getUrl('expensereports/payments/999999999', $test, 'GET', null, 404);
		$this->assertStringContainsString('paymentExpenseReport not found', (string) ($result['content']['error']['message'] ?? ''), "$test The error must be about the payment");

		$test = "Update a payment of expense report that does not exist";

		$result = $this->getUrl('expensereports/999999999/payments/999999999', $test, 'PUT', array("amount" => 1), 404);
		$this->assertStringContainsString('Payment of expense report not found', (string) ($result['content']['error']['message'] ?? ''), "$test The error must be about the payment");
	}
}
