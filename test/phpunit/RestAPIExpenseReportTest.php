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
	 * Create a draft expense report with the admin user. It is deleted at end of the test.
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$fk_user_author		Id of the user the expense report is for
	 * @return int							Id of the expense report
	 */
	private function createExpenseReport($test, $fk_user_author)
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
	 * testRestExpenseReportPutAuthorInHierarchy
	 *
	 * @return void
	 */
	public function testRestExpenseReportPutAuthorInHierarchy()
	{
		global $user;

		$test = "Move an expense report to a user of the hierarchy";

		list($subordinate_id) = $this->createUser($test);
		$id = $this->createExpenseReport($test, $user->id);

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
		$id = $this->createExpenseReport($test, $restricted_id);

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

		$id = $this->createExpenseReport($test, $user->id);

		$this->getUrl('expensereports/'.$id, $test, 'PUT', array("fk_user_author" => 999999999), 400);
		$this->assertEquals($user->id, $this->getAuthor("$test (read after refused update)", $id), "$test The user must not change");
	}
}
