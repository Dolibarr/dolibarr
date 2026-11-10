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
 *      \file       test/phpunit/RestAPIInterventionTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

require_once __DIR__."/AbstractRestAPITest.php";
require_once __DIR__."/../../htdocs/fichinter/class/fichinter.class.php";

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIInterventionTest extends AbstractRestAPITest
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
		self::assertTrue(isModEnabled('intervention'), 'module intervention must be enabled');
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
	 * @param string		$endpoint 	Endpoint - "interventions/id"
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
	 * Create a draft intervention
	 *
	 * @param string				$test	Text to be added to output
	 * @param array<string,mixed>	$data	Other fields of the intervention
	 * @return int							Id of the intervention
	 */
	private function createIntervention($test, $data = array())
	{
		$data = array_merge(array(
			"socid" => 1,
			"fk_project" => 0,
			"description" => "Intervention created via the REST API",
		), $data);
		$result = $this->getUrl('interventions', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of intervention is not > 0");

		return (int) $result['content'];
	}

	/**
	 * Data of a line to add to an intervention
	 *
	 * @param array<string,mixed>	$data	Fields overriding the default ones
	 * @return array<string,mixed>			Line data
	 */
	private function lineData($data = array())
	{
		return array_merge(array(
			"description" => "Line added via the REST API",
			"date" => 1579089600,	// 2020-01-15 12:00:00 UTC
			"duration" => 3600,
		), $data);
	}

	/**
	 * Create a draft intervention with a line. It is deleted at end of the test.
	 *
	 * @param string	$test	Text to be added to output
	 * @return array{0:int,1:int}	Id of the intervention and id of the line
	 */
	private function createInterventionWithLine($test)
	{
		$intervention_id = $this->createIntervention($test);
		array_unshift($this->objectstodelete, 'interventions/'.$intervention_id);

		$result = $this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'POST', $this->lineData());
		$line_id = (int) $result['content'];
		$this->assertGreaterThan(0, $line_id, "$test Id of line is not > 0");

		return array($intervention_id, $line_id);
	}

	/**
	 * Validate an intervention
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @return void
	 */
	private function validateIntervention($test, $intervention_id)
	{
		$this->getUrl('interventions/'.$intervention_id.'/validate', $test, 'POST', array("notrigger" => 0));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_VALIDATED, $result['content']['status'] ?? null, "$test Intervention must be validated");
	}

	/**
	 * Read a line of an intervention with the admin user
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @param int		$line_id			Id of the line
	 * @return array<string,mixed>			Line data
	 */
	private function getLine($test, $intervention_id, $line_id)
	{
		$result = $this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'GET');
		$lines = array_column($result['content'], null, 'id');
		$this->assertArrayHasKey($line_id, $lines, "$test The line must be returned");

		return $lines[$line_id];
	}

	/**
	 * Check a line still has the values of lineData()
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @param int		$line_id			Id of the line
	 * @return void
	 */
	private function assertLineUnchanged($test, $intervention_id, $line_id)
	{
		$line = $this->getLine($test, $intervention_id, $line_id);
		$this->assertEquals("Line added via the REST API", $line['desc'] ?? null, "$test Line description must not change");
		$this->assertEquals(3600, $line['duration'] ?? null, "$test Line duration must not change");
	}

	/**
	 * Create an internal user that has only the given permissions. It is deleted at end of the test.
	 *
	 * @param string							$test	Text to be added to output
	 * @param array<array{0:string,1:string}>	$rights	List of array(module, perms) to add to the user
	 * @return string									API key of the user
	 */
	private function createRestrictedUser($test, $rights)
	{
		global $db, $user;

		$password = 'Ab1-'.bin2hex(random_bytes(8));

		$tmpuser = new User($db);
		$tmpuser->login = 'testrestintervention'.mt_rand(0, 99999999);
		$tmpuser->lastname = $tmpuser->login;
		$tmpuser->entity = 1;

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

		return $result['content']['success']['token'];
	}

	/**
	 * testRestInterventionPostLine
	 *
	 * @return int	Id of the draft intervention
	 */
	public function testRestInterventionPostLine()
	{
		$test = "Add a line to a draft intervention";

		$intervention_id = $this->createIntervention($test);

		$result = $this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'POST', $this->lineData());
		$line_id = (int) $result['content'];
		$this->assertGreaterThan(0, $line_id, "$test Id of line is not > 0");

		$result = $this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'GET');
		$lines = array_column($result['content'], null, 'id');
		$this->assertArrayHasKey($line_id, $lines, "$test The new line must be returned");
		$this->assertEquals("Line added via the REST API", $lines[$line_id]['desc'] ?? null, "$test Line description must be kept");
		$this->assertEquals(3600, $lines[$line_id]['duration'] ?? null, "$test Line duration must be kept");
		$this->assertEquals(1579089600, $lines[$line_id]['date'] ?? null, "$test Line date must be kept");

		return $intervention_id;
	}

	/**
	 * testRestInterventionPostLineKeepsHeader
	 *
	 * @depends testRestInterventionPostLine
	 *
	 * @param int $intervention_id	Id of the draft intervention
	 * @return void
	 */
	public function testRestInterventionPostLineKeepsHeader($intervention_id)
	{
		$test = "Adding a line must not change the intervention";

		$this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'POST', $this->lineData(array("description" => "Another line")));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals("Intervention created via the REST API", $result['content']['description'] ?? null, "$test The intervention description must not change");
	}

	/**
	 * testRestInterventionPostLineMissingField
	 *
	 * @depends testRestInterventionPostLine
	 *
	 * @param int $intervention_id	Id of the draft intervention
	 * @return void
	 */
	public function testRestInterventionPostLineMissingField($intervention_id)
	{
		$test = "Add a line without duration to an intervention";

		$data = $this->lineData();
		unset($data['duration']);
		$this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'POST', $data, 400);
	}

	/**
	 * testRestInterventionPostLineNotFound
	 *
	 * @return void
	 */
	public function testRestInterventionPostLineNotFound()
	{
		$test = "Add a line to an intervention that does not exist";

		$this->getUrl('interventions/999999999/lines', $test, 'POST', $this->lineData(), 404);
	}

	/**
	 * testRestInterventionPostLineValidated
	 *
	 * @return void
	 */
	public function testRestInterventionPostLineValidated()
	{
		$test = "Add a line to a validated intervention";

		$intervention_id = $this->createIntervention($test);

		$this->getUrl('interventions/'.$intervention_id.'/validate', $test, 'POST', array("notrigger" => 0));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_VALIDATED, $result['content']['status'] ?? null, "$test Intervention must be validated");

		$this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'POST', $this->lineData(), 403);

		$result = $this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'GET');
		$this->assertCount(0, $result['content'], "$test No line must be added");
	}

	/**
	 * testRestInterventionPutLine
	 *
	 * @return void
	 */
	public function testRestInterventionPutLine()
	{
		$test = "Update a line of a draft intervention with the create permission";

		list($intervention_id, $line_id) = $this->createInterventionWithLine($test);
		$api_key = $this->createRestrictedUser($test, array(array('ficheinter', 'lire'), array('ficheinter', 'creer'), array('societe', 'client')));

		$data = array(
			"description" => "Line updated via the REST API",
			"date" => 1579176000,	// 2020-01-16 12:00:00 UTC
			"duration" => 7200,
		);
		$this->getUrl('interventions/'.$intervention_id.'/lines/'.$line_id, $test, 'PUT', $data, 0, $api_key);

		$line = $this->getLine($test, $intervention_id, $line_id);
		$this->assertEquals("Line updated via the REST API", $line['desc'] ?? null, "$test Line description must be updated");
		$this->assertEquals(7200, $line['duration'] ?? null, "$test Line duration must be updated");
		$this->assertEquals(1579176000, $line['date'] ?? null, "$test Line date must be updated");
	}

	/**
	 * testRestInterventionPutLineReadOnlyUser
	 *
	 * @return void
	 */
	public function testRestInterventionPutLineReadOnlyUser()
	{
		$test = "Update a line of an intervention with the read permission only";

		list($intervention_id, $line_id) = $this->createInterventionWithLine($test);
		// The user can see all third parties, so it has access to the intervention and only the permission can refuse it
		$api_key = $this->createRestrictedUser($test, array(array('ficheinter', 'lire'), array('societe', 'client')));

		$this->getUrl('interventions/'.$intervention_id.'/lines', $test, 'GET', null, 0, $api_key);

		$data = array("description" => "Line updated without permission", "duration" => 7200);
		$this->getUrl('interventions/'.$intervention_id.'/lines/'.$line_id, $test, 'PUT', $data, 403, $api_key);

		$this->assertLineUnchanged($test, $intervention_id, $line_id);
	}

	/**
	 * testRestInterventionPutLineValidated
	 *
	 * @return void
	 */
	public function testRestInterventionPutLineValidated()
	{
		$test = "Update a line of a validated intervention";

		list($intervention_id, $line_id) = $this->createInterventionWithLine($test);
		$this->validateIntervention($test, $intervention_id);

		$data = array("description" => "Line updated after validation", "duration" => 7200);
		$this->getUrl('interventions/'.$intervention_id.'/lines/'.$line_id, $test, 'PUT', $data, 403);

		$this->assertLineUnchanged($test, $intervention_id, $line_id);
	}

	/**
	 * testRestInterventionPutLineNoAccessToIntervention
	 *
	 * @return void
	 */
	public function testRestInterventionPutLineNoAccessToIntervention()
	{
		$test = "Update a line of a validated intervention the user has no access to";

		list($intervention_id, $line_id) = $this->createInterventionWithLine($test);
		$this->validateIntervention($test, $intervention_id);
		// The user cannot see all third parties and is not a representative of the third party of the intervention
		$api_key = $this->createRestrictedUser($test, array(array('ficheinter', 'lire'), array('ficheinter', 'creer')));

		$data = array("description" => "Line updated without access", "duration" => 7200);
		$result = $this->getUrl('interventions/'.$intervention_id.'/lines/'.$line_id, $test, 'PUT', $data, 403, $api_key);

		$message = $result['content']['error']['message'] ?? '';
		$this->assertStringContainsString('Access not allowed', $message, "$test Access must be refused first");
		$this->assertStringNotContainsString('draft status', $message, "$test The status must not be revealed");

		$this->assertLineUnchanged($test, $intervention_id, $line_id);
	}

	/**
	 * testRestInterventionDelete
	 *
	 * @return void
	 */
	public function testRestInterventionDelete()
	{
		$test = "Delete an intervention";

		$intervention_id = $this->createIntervention($test);
		array_unshift($this->objectstodelete, 'interventions/'.$intervention_id);

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'DELETE');
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Delete must succeed");
		$this->assertEquals('Intervention deleted', $result['content']['success']['message'] ?? null, "$test Delete must succeed");

		// Already deleted, so tearDown must not delete it again
		$this->objectstodelete = array_values(array_diff($this->objectstodelete, array('interventions/'.$intervention_id)));

		$this->getUrl('interventions/'.$intervention_id, $test, 'GET', null, 404);
	}

	/**
	 * testRestInterventionDeleteWithoutPermission
	 *
	 * @return void
	 */
	public function testRestInterventionDeleteWithoutPermission()
	{
		$test = "Delete an intervention without the delete permission";

		$intervention_id = $this->createIntervention($test);
		array_unshift($this->objectstodelete, 'interventions/'.$intervention_id);
		// The user can see all third parties, so it has access to the intervention and only the permission can refuse it
		$api_key = $this->createRestrictedUser($test, array(array('ficheinter', 'lire'), array('ficheinter', 'creer'), array('societe', 'client')));

		$this->getUrl('interventions/'.$intervention_id, $test, 'DELETE', null, 403, $api_key);

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals($intervention_id, $result['content']['id'] ?? null, "$test The intervention must still exist");
	}

	/**
	 * testRestInterventionDeleteNotFound
	 *
	 * @return void
	 */
	public function testRestInterventionDeleteNotFound()
	{
		$test = "Delete an intervention that does not exist";

		$this->getUrl('interventions/999999999', $test, 'DELETE', null, 404);
	}

	/**
	 * Close an intervention
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @return void
	 */
	private function closeIntervention($test, $intervention_id)
	{
		$this->getUrl('interventions/'.$intervention_id.'/close', $test, 'POST', array("notrigger" => 0));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $result['content']['status'] ?? null, "$test Intervention must be closed");
	}

	/**
	 * Call the API with POST and check the answer is 304 Not Modified, which has no JSON body
	 *
	 * @param string				$endpoint	Endpoint - "interventions/id/close"
	 * @param string				$test_title	Text to be added to output
	 * @param array<string,mixed>	$data		Data that will be JSON encoded
	 * @return void
	 */
	private function assertPostNotModified($endpoint, $test_title, $data)
	{
		$addheaders = array(
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: ".$this->api_key
		);

		$result = getURLContent($this->api_url.'/'.$endpoint, 'POST', json_encode($data), 1, $addheaders, array('http', 'https'), 2);

		$this->assertEquals(0, $result['curl_error_no'], $test_title." Should not have a curl error");
		$this->assertEquals(304, $result['http_code'], $test_title." HTTP code must be 304".PHP_EOL.json_encode($result, JSON_PRETTY_PRINT));
	}

	/**
	 * testRestInterventionValidateAndClose
	 *
	 * @return void
	 */
	public function testRestInterventionValidateAndClose()
	{
		$test = "Validate then close an intervention";

		list($intervention_id) = $this->createInterventionWithLine($test);

		$result = $this->getUrl('interventions/'.$intervention_id.'/validate', $test, 'POST', array("notrigger" => 0));
		$this->assertEquals(Fichinter::STATUS_VALIDATED, $result['content']['status'] ?? null, "$test Validate must return the validated intervention");
		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_VALIDATED, $result['content']['status'] ?? null, "$test Intervention must be validated");
		$this->assertStringNotContainsString('PROV', $result['content']['ref'] ?? 'PROV', "$test Validated intervention must have a definitive ref");

		$result = $this->getUrl('interventions/'.$intervention_id.'/close', $test, 'POST', array("notrigger" => 0));
		$this->assertEquals(Fichinter::STATUS_CLOSED, $result['content']['status'] ?? null, "$test Close must return the closed intervention");
		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $result['content']['status'] ?? null, "$test Intervention must be closed");
	}

	/**
	 * testRestInterventionValidateAlreadyValidated
	 *
	 * @return void
	 */
	public function testRestInterventionValidateAlreadyValidated()
	{
		$test = "Validate an intervention already validated";

		list($intervention_id) = $this->createInterventionWithLine($test);
		$this->validateIntervention($test, $intervention_id);

		$this->assertPostNotModified('interventions/'.$intervention_id.'/validate', $test, array("notrigger" => 0));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_VALIDATED, $result['content']['status'] ?? null, "$test Intervention must stay validated");
	}

	/**
	 * testRestInterventionCloseDraft
	 *
	 * @return void
	 */
	public function testRestInterventionCloseDraft()
	{
		$test = "Close a draft intervention";

		list($intervention_id) = $this->createInterventionWithLine($test);

		$result = $this->getUrl('interventions/'.$intervention_id.'/close', $test, 'POST', array("notrigger" => 0), 403);
		$this->assertStringContainsString('draft status', $result['content']['error']['message'] ?? '', "$test The status must be the reason of the refusal");

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_DRAFT, $result['content']['status'] ?? null, "$test Intervention must stay draft");
	}

	/**
	 * testRestInterventionCloseAlreadyClosed
	 *
	 * @return void
	 */
	public function testRestInterventionCloseAlreadyClosed()
	{
		$test = "Close an intervention already closed";

		list($intervention_id) = $this->createInterventionWithLine($test);
		$this->validateIntervention($test, $intervention_id);
		$this->closeIntervention($test, $intervention_id);

		$this->assertPostNotModified('interventions/'.$intervention_id.'/close', $test, array("notrigger" => 0));

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $result['content']['status'] ?? null, "$test Intervention must stay closed");
	}

	/**
	 * testRestInterventionValidateClosed
	 *
	 * @return void
	 */
	public function testRestInterventionValidateClosed()
	{
		$test = "Validate a closed intervention";

		list($intervention_id) = $this->createInterventionWithLine($test);
		$this->validateIntervention($test, $intervention_id);
		$this->closeIntervention($test, $intervention_id);
		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$ref = $result['content']['ref'] ?? null;

		$result = $this->getUrl('interventions/'.$intervention_id.'/validate', $test, 'POST', array("notrigger" => 0), 403);
		$this->assertStringContainsString('draft status', $result['content']['error']['message'] ?? '', "$test The status must be the reason of the refusal");

		$result = $this->getUrl('interventions/'.$intervention_id, $test, 'GET');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $result['content']['status'] ?? null, "$test Intervention must stay closed");
		$this->assertEquals($ref, $result['content']['ref'] ?? null, "$test Ref must not change");
	}

	/**
	 * Create a draft intervention. It is deleted at end of the test.
	 *
	 * @param string	$test	Text to be added to output
	 * @return int				Id of the intervention
	 */
	private function createInterventionToDelete($test)
	{
		$intervention_id = $this->createIntervention($test);
		array_unshift($this->objectstodelete, 'interventions/'.$intervention_id);

		return $intervention_id;
	}

	/**
	 * Link a contact to an intervention
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @param int		$fk_socpeople		Id of the user (internal) or of the contact (external)
	 * @param string	$type_contact		Code of the type of contact
	 * @param string	$source				internal or external
	 * @param int		$expected_error		Expected error code
	 * @return mixed						Decoded JSON value returned by the service
	 */
	private function addInterventionContact($test, $intervention_id, $fk_socpeople, $type_contact, $source, $expected_error = 0)
	{
		$data = array("fk_socpeople" => $fk_socpeople, "type_contact" => $type_contact, "source" => $source);

		return $this->getUrl('interventions/'.$intervention_id.'/contacts', $test, 'POST', $data, $expected_error);
	}

	/**
	 * Get the contacts linked to an intervention, as "source:id:code" strings
	 *
	 * @param string	$test				Text to be added to output
	 * @param int		$intervention_id	Id of the intervention
	 * @return string[]						Sorted list of "source:id:code"
	 */
	private function getInterventionContacts($test, $intervention_id)
	{
		$result = $this->getUrl('interventions/'.$intervention_id.'/contacts', $test, 'GET');
		$this->assertIsArray($result['content'], "$test The contacts must be an array");

		$contacts = array();
		foreach ($result['content'] as $contact) {
			$contacts[] = $contact['source'].':'.$contact['id'].':'.$contact['code'];
		}
		sort($contacts);

		return $contacts;
	}

	/**
	 * testRestInterventionContacts
	 *
	 * @return void
	 */
	public function testRestInterventionContacts()
	{
		$test = "Link and unlink internal and external contacts of an intervention";

		// Created before the intervention, so tearDown deletes the intervention first
		$result = $this->getUrl('contacts', $test, 'POST', array("lastname" => "testRestIntervention".mt_rand()));
		$contact_id = (int) $result['content'];
		$this->assertGreaterThan(0, $contact_id, "$test Id of contact is not > 0");
		array_unshift($this->objectstodelete, 'contacts/'.$contact_id);

		$intervention_id = $this->createInterventionToDelete($test);

		$result = $this->addInterventionContact($test, $intervention_id, 1, 'INTERREPFOLL', 'internal');
		$this->assertEquals($intervention_id, $result['content']['id'] ?? null, "$test Add must return the intervention");
		$this->addInterventionContact($test, $intervention_id, $contact_id, 'CUSTOMER', 'external');

		$expected = array('external:'.$contact_id.':CUSTOMER', 'internal:1:INTERREPFOLL');
		$this->assertEquals($expected, $this->getInterventionContacts($test, $intervention_id), "$test Both contacts must be listed");

		$result = $this->getUrl('interventions/'.$intervention_id.'/contact/1/INTERREPFOLL', $test, 'DELETE');
		$this->assertEquals($intervention_id, $result['content']['id'] ?? null, "$test Delete must return the intervention");

		$expected = array('external:'.$contact_id.':CUSTOMER');
		$this->assertEquals($expected, $this->getInterventionContacts($test, $intervention_id), "$test The deleted contact must no longer be listed");
	}

	/**
	 * testRestInterventionAddContactTwice
	 *
	 * @return void
	 */
	public function testRestInterventionAddContactTwice()
	{
		$test = "Link the same contact twice with the same type to an intervention";

		$intervention_id = $this->createInterventionToDelete($test);

		$this->addInterventionContact($test, $intervention_id, 1, 'INTERREPFOLL', 'internal');
		$this->addInterventionContact("$test (again)", $intervention_id, 1, 'INTERREPFOLL', 'internal', 400);

		$this->assertEquals(array('internal:1:INTERREPFOLL'), $this->getInterventionContacts($test, $intervention_id), "$test The contact must be listed once");
	}

	/**
	 * testRestInterventionAddContactWrongType
	 *
	 * @return void
	 */
	public function testRestInterventionAddContactWrongType()
	{
		$test = "Link a contact with a type that does not exist to an intervention";

		$intervention_id = $this->createInterventionToDelete($test);

		$this->addInterventionContact($test, $intervention_id, 1, 'NOTATYPEOFCONTACT', 'internal', 400);

		$this->assertEquals(array(), $this->getInterventionContacts($test, $intervention_id), "$test No contact must be linked");
	}

	/**
	 * testRestInterventionDeleteContactNotLinked
	 *
	 * @return void
	 */
	public function testRestInterventionDeleteContactNotLinked()
	{
		$test = "Unlink a contact that is not linked to an intervention";

		$intervention_id = $this->createInterventionToDelete($test);

		$this->getUrl('interventions/'.$intervention_id.'/contact/1/INTERREPFOLL', $test, 'DELETE', null, 404);
	}
}
