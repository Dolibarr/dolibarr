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
 *      \file       test/phpunit/RestAPICategoriesTest.php
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
class RestAPICategoriesTest extends AbstractRestAPITest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('category'), 'module category must be enabled');
		self::assertTrue(isModEnabled('societe'), 'module societe must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * Call the API and check the error code
	 *
	 * @param string					$endpoint 	Endpoint - "categories/id"
	 * @param string					$test_title	Text to be added to output
	 * @param 'POST'|'GET'|'DELETE'	$method		Call Method
	 * @param mixed						$data		Data that will be JSON encoded
	 * @param int						$expected_error	Expected error code
	 *
	 * @return mixed	Decodes JSON value returned by the service
	 */
	private function getUrl($endpoint, $test_title = "", $method = "GET", $data = null, $expected_error = 0)
	{
		$params = '';
		$body = null;
		if ($method === 'POST') {
			$body = json_encode($data);
		} elseif (!empty($data)) {
			$params = '?'.http_build_query($data);
		}

		$addheaders = array(
			'Content-Type: application/json', 'Accept: application/json', "DOLAPIKEY: {$this->api_key}"
		);

		$url = $this->api_url.'/'.$endpoint.$params;

		$result = getURLContent($url, $method, $body, 1, $addheaders, array('http', 'https'), 2);

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
	 * Create a category
	 *
	 * @param string	$test	Text to be added to output
	 * @param int		$type	Type id of category (see Categorie::$MAP_ID, the API stores it as an integer)
	 * @return int				Id of the category
	 */
	private function createCategory($test, $type)
	{
		$data = array(
			"label" => "API test type ".$type." ".mt_rand(0, 99999999),
			"type" => $type,
		);
		$result = $this->getUrl('categories', $test, 'POST', $data);
		$this->assertGreaterThan(0, (int) $result['content'], "$test Id of category is not > 0");

		return (int) $result['content'];
	}

	/**
	 * testRestCategoryGetNotFound
	 *
	 * @return void
	 */
	public function testRestCategoryGetNotFound()
	{
		$test = "Get a category that does not exist";

		$this->getUrl('categories/999999999', $test, 'GET', null, 404);
	}

	/**
	 * testRestCategoryListForObjectBadType
	 *
	 * @return void
	 */
	public function testRestCategoryListForObjectBadType()
	{
		$test = "List categories of an object with an unsupported type";

		$this->getUrl('categories/object/notatype/1', $test, 'GET', null, 400);
	}

	/**
	 * testRestCategoryLinkUnlink
	 *
	 * @return void
	 */
	public function testRestCategoryLinkUnlink()
	{
		$test = "Link and unlink a third party to a customer category";

		$category_id = $this->createCategory($test, 2);	// customer

		$result = $this->getUrl('categories/'.$category_id.'/objects/customer/1', $test, 'POST', array());
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Link must succeed");

		$result = $this->getUrl('categories/object/customer/1', $test, 'GET');
		$this->assertContains($category_id, array_map('intval', array_column($result['content'], 'id')), "$test Category must be listed for the third party");

		$result = $this->getUrl('categories/'.$category_id.'/objects/customer/1', $test, 'DELETE');
		$this->assertEquals(200, $result['content']['success']['code'] ?? null, "$test Unlink must succeed");

		$this->getUrl('categories/'.$category_id, $test, 'DELETE');
	}

	/**
	 * testRestCategoryUnlinkWrongType
	 *
	 * @return void
	 */
	public function testRestCategoryUnlinkWrongType()
	{
		$test = "Unlink a third party from a product category";

		$category_id = $this->createCategory($test, 0);	// product

		$this->getUrl('categories/'.$category_id.'/objects/customer/1', $test, 'DELETE', null, 400);

		$this->getUrl('categories/'.$category_id, $test, 'DELETE');
	}

	/**
	 * testRestCategoryUnlinkNotFound
	 *
	 * @return void
	 */
	public function testRestCategoryUnlinkNotFound()
	{
		$test = "Unlink an object from a category that does not exist";

		$this->getUrl('categories/999999999/objects/customer/1', $test, 'DELETE', null, 404);
	}
}
