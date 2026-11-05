<?php
/* Copyright (C) 2026		William Mead			<william@m34d.com>
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
 *      \file       test/phpunit/RestAPIContractTest.php
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
class RestAPIContractTest extends AbstractRestAPITest
{
	/**
	 * testRestContractCreateMissingCommercial
	 *
	 * Creating a contract without one of the two sales representatives is a 400 naming the field.
	 *
	 * @return void
	 */
	public function testRestContractCreateMissingCommercial()
	{
		$addheaders = array('Content-Type: application/json', 'Accept: application/json', 'DOLAPIKEY: '.$this->api_key);
		$data = array('socid' => 1, 'date_contrat' => time(), 'commercial_signature_id' => 1, 'commercial_suivi_id' => 1);

		foreach (array('commercial_signature_id', 'commercial_suivi_id') as $field) {
			$body = $data;
			unset($body[$field]);

			$result = getURLContent($this->api_url.'/contracts', 'POST', json_encode($body), 1, $addheaders, array('http', 'https'), 2);
			$this->assertEquals(0, $result['curl_error_no'], "POST without $field must not have a curl error");
			$object = json_decode($result['content'], true);
			$this->assertNotNull($object, "POST without $field - Parsing of json result must not be null: ".$result['content']);

			$this->assertEquals(400, $object['error']['code'] ?? 0, "POST without $field must be refused with a 400: ".$result['content']);
			$this->assertStringContainsString($field, $object['error']['message'] ?? '', "The error must name the missing field");
		}
	}
}
