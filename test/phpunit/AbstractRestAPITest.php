<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/AbstractRestAPITest.php
 *      \ingroup    test
 *      \brief      Abstract Class for Rest API Tests
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/geturl.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
$conf->global->MAIN_UMASK = '0666';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
abstract class AbstractRestAPITest extends CommonClassTest
{
	protected $api_url;
	protected $api_key;

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::assertTrue(isModEnabled('api'), " module api must be enabled.");
	}

	/**
	 * Init phpunit tests
	 *
	 * @return  void
	*/
	protected function setUp(): void
	{
		parent::setUp();

		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->api_url = DOL_MAIN_URL_ROOT.'/api/index.php';
		$addheaders = array('Content-Type: application/json', 'Accept: application/json');

		$method = get_called_class()."::".__FUNCTION__;
		$test = "API Test Setup - ";
		$login = 'admin';
		$password = 'admin';
		$url = $this->api_url.'/login?login='.$login.'&password='.$password;
		// Call the API login method to save api_key for this test class.
		// At first call, if token is not defined a random value is generated and returned.
		$result = getURLContent($url, 'GET', '', 1, $addheaders, array('http', 'https'), 2);
		print "$method result = ".var_export($result, true)."\n";
		print "$method curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals('', $result['curl_error_no'], "$test Should not have a curl error");
		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array

		$this->assertNotNull($object, "$test Parsing of JSON result must not be null");
		$this->assertNotEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), "$test Error".(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
		$this->assertEquals('200', $object['success']['code']);

		$this->api_key = $object['success']['token'];

		print "$method api_key: $this->api_key \n";
	}
}
