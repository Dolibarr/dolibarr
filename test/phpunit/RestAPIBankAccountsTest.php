<?php
/* Copyright (C) 2026		Pierre Grasswill		<da.grumpf@gmail.com>
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
 *      \file       test/phpunit/RestAPIBankAccountsTest.php
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
class RestAPIBankAccountsTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('bank'), 'module bank must be enabled');
		self::assertTrue(isModEnabled('category'), 'module category must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 *
	 * @param string		$endpoint 	Endpoint - "bankaccounts/id"
	 * @param string		$test_title	Text to be added to output
	 * @param 'GET'|'POST'|'PUT'|'DELETE'	$method		Call Method
	 * @param mixed			$data		Data that will be JSON encoded (POST/PUT) or sent as query params (GET/DELETE)
	 * @param int			$expected_error	Expected error code
	 * @param ?string		$api_key	API key to use instead of the one of the admin user
	 *
	 * @return mixed	Decoded JSON value returned by the service
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
	 * Create a bank account that can be reconciled
	 *
	 * @param string	$test	Text to be added to output
	 * @return int		Id of created account
	 */
	private function createAccount($test)
	{
		$ref = "TR".mt_rand(0, 99999999);	// ref is 12 chars max, ref and label must be unique
		$data = array(
			"ref" => $ref,
			"label" => "testRestBankReconcile ".$ref,
			"type" => 1,
			"currency_code" => "EUR",
			"country_id" => 1,
			"rappro" => 1,
		);
		$result = $this->getUrl('bankaccounts', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of account is not > 0");

		return (int) $result['content'];
	}

	/**
	 * Add a line to a bank account
	 *
	 * @param int		$account_id	Id of account
	 * @param string	$test		Text to be added to output
	 * @param int		$category	Id of a bank category to set on the line
	 * @return int		Id of created line
	 */
	private function createLine($account_id, $test, $category = 0)
	{
		$data = array(
			"date" => dol_now(),
			"type" => "VIR",
			"label" => "testRestBankReconcile line",
			"amount" => 10,
			"category" => $category,
		);
		$result = $this->getUrl('bankaccounts/'.$account_id.'/lines', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of line is not > 0");

		return (int) $result['content'];
	}

	/**
	 * Return the lines of an account, indexed by id
	 *
	 * @param int		$account_id	Id of account
	 * @param string	$test		Text to be added to output
	 * @return array<int,array<string,mixed>>
	 */
	private function getLines($account_id, $test)
	{
		$result = $this->getUrl('bankaccounts/'.$account_id.'/lines', $test, 'GET');
		$lines = array();
		foreach ($result['content'] as $line) {
			$lines[(int) $line['id']] = $line;
		}

		return $lines;
	}

	/**
	 * testRestBankAccountReconcile
	 *
	 * @return array{account:int,other:int,lines:int[],otherline:int}
	 */
	public function testRestBankAccountReconcile()
	{
		$test = "Reconcile bank lines";

		$account_id = $this->createAccount($test);
		$other_id = $this->createAccount($test);
		$line1 = $this->createLine($account_id, $test);
		$line2 = $this->createLine($account_id, $test);
		$otherline = $this->createLine($other_id, $test);

		$data = array("num_releve" => "T202609", "lines" => array($line1, $line2));
		$result = $this->getUrl('bankaccounts/'.$account_id.'/reconcile', $test, 'POST', $data);
		$this->assertEquals(array($line1, $line2), $result['content']['lines'] ?? null, "$test Reconciled lines must be returned");

		$lines = $this->getLines($account_id, $test);
		foreach (array($line1, $line2) as $line_id) {
			$this->assertEquals(1, $lines[$line_id]['rappro'] ?? null, "$test Line $line_id must be reconciled");
			$this->assertEquals("T202609", $lines[$line_id]['num_releve'] ?? null, "$test Line $line_id must have the statement number");
		}

		// Replaying the same call is accepted
		$this->getUrl('bankaccounts/'.$account_id.'/reconcile', "$test (replay)", 'POST', $data);

		return array('account' => $account_id, 'other' => $other_id, 'lines' => array($line1, $line2), 'otherline' => $otherline);
	}

	/**
	 * testRestBankAccountReconcileOtherAccount
	 *
	 * @depends testRestBankAccountReconcile
	 *
	 * @param array{account:int,other:int,lines:int[],otherline:int} $ids Ids created by the previous test
	 * @return array{account:int,other:int,lines:int[],otherline:int}
	 */
	public function testRestBankAccountReconcileOtherAccount($ids)
	{
		$test = "Reconcile a line of another account";

		$freeline = $this->createLine($ids['account'], $test);

		$data = array("num_releve" => "T202610", "lines" => array($freeline, $ids['otherline']));
		$this->getUrl('bankaccounts/'.$ids['account'].'/reconcile', $test, 'POST', $data, 400);

		// Nothing must have been written, not even the valid line checked first
		$lines = $this->getLines($ids['account'], $test);
		$this->assertEquals(0, $lines[$freeline]['rappro'] ?? null, "$test Line $freeline must not be reconciled");
		$lines = $this->getLines($ids['other'], $test);
		$this->assertEquals(0, $lines[$ids['otherline']]['rappro'] ?? null, "$test Line of other account must not be reconciled");

		return $ids;
	}

	/**
	 * testRestBankAccountReconcileOtherStatement
	 *
	 * @depends testRestBankAccountReconcileOtherAccount
	 *
	 * @param array{account:int,other:int,lines:int[],otherline:int} $ids Ids created by the previous test
	 * @return array{account:int,other:int,lines:int[],otherline:int}
	 */
	public function testRestBankAccountReconcileOtherStatement($ids)
	{
		$test = "Reconcile a line already reconciled with another statement";

		$data = array("num_releve" => "T202611", "lines" => $ids['lines']);
		$this->getUrl('bankaccounts/'.$ids['account'].'/reconcile', $test, 'POST', $data, 409);

		$lines = $this->getLines($ids['account'], $test);
		$this->assertEquals("T202609", $lines[$ids['lines'][0]]['num_releve'] ?? null, "$test Line must stay in its statement");

		return $ids;
	}

	/**
	 * testRestBankAccountReconcileCategory
	 *
	 * @depends testRestBankAccountReconcileOtherStatement
	 *
	 * @param array{account:int,other:int,lines:int[],otherline:int} $ids Ids created by the previous test
	 * @return array{account:int,other:int,lines:int[],otherline:int}
	 */
	public function testRestBankAccountReconcileCategory($ids)
	{
		$test = "Reconcile with a bank category";

		$result = $this->getUrl('categories', $test, 'POST', array("label" => "testRestBankReconcile".mt_rand(), "type" => 8));
		$cat_id = (int) $result['content'];
		$this->assertGreaterThan(0, $cat_id, "$test Category must be created");

		// One line has the category already: adding it again must not break the transaction
		$line1 = $this->createLine($ids['account'], $test, $cat_id);
		$line2 = $this->createLine($ids['account'], $test);

		$this->getUrl('bankaccounts/'.$ids['account'].'/reconcile', "$test (unknown category)", 'POST', array("num_releve" => "T202612", "lines" => array($line1, $line2), "catid" => 999999999), 400);

		$this->getUrl('bankaccounts/'.$ids['account'].'/reconcile', $test, 'POST', array("num_releve" => "T202612", "lines" => array($line1, $line2), "catid" => $cat_id));

		$lines = $this->getLines($ids['account'], $test);
		$this->assertEquals(1, $lines[$line1]['rappro'] ?? null, "$test Line $line1 must be reconciled");
		$this->assertEquals(1, $lines[$line2]['rappro'] ?? null, "$test Line $line2 must be reconciled");

		return $ids;
	}
}
