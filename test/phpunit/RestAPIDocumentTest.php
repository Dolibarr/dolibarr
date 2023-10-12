<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/RestAPIDocumentTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php.
 */
global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/geturl.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';

if (empty($user->id)) {
	echo "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
$conf->global->MAIN_UMASK = '0666';

/**
 * Class for PHPUnit tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIDocumentTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;
	protected $api_url;
	protected $api_key;

	/**
	 * Constructor
	 * We save global variables into local variables.
	 *
	 * @param 	string	$name		Name
	 * @return RestAPIDocumentTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		echo __METHOD__.' db->type='.$db->type.' user->id='.$user->id;
		//print " - db ".$db->db;
		echo "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

		echo __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->rollback();

		echo __METHOD__."\n";
	}

	/**
	 * Init phpunit tests.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->api_url = DOL_MAIN_URL_ROOT.'/api/index.php';

		$login='admin';
		$password='admin';
		$url=$this->api_url.'/login?login='.$login.'&password='.$password;
		// Call the API login method to save api_key for this test class.
		// At first call, if token is not defined a random value is generated and returned.
		$result=getURLContent($url, 'GET', '', 1, array(), array('http', 'https'), 2);
		print __METHOD__." result = ".var_export($result, true)."\n";
		print __METHOD__." curl_error_no: ".$result['curl_error_no']."\n";
		$this->assertEquals($result['curl_error_no'], '');
		$object = json_decode($result['content'], true);	// If success content is just an id, if not an array

		$this->assertNotNull($object, "Parsing of json result must not be null");
		$this->assertNotEquals(500, (empty($object['error']['code']) ? 0 : $object['error']['code']), 'Error'.(empty($object['error']['message']) ? '' : ' '.$object['error']['message']));
		$this->assertEquals('200', $object['success']['code']);

		$this->api_key = $object['success']['token'];

		echo __METHOD__." api_key: $this->api_key \n";
	}

	/**
	 * End phpunit tests.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		echo __METHOD__."\n";
	}

	/**
	 * testPushDocument.
	 *
	 * @return int
	 */
	public function testPushDocument()
	{
		global $conf,$user,$langs,$db;

		$url = $this->api_url.'/documents/upload?api_key='.$this->api_key;

		echo __METHOD__.' Request POST url='.$url."\n";


		// Send to non existent directory

		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit1');

		//$data = '{ "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "mysubdir1/mysubdir2", "filecontent": "content text", "fileencoding": "" }';
		$data = array(
			'filename'=>"mynewfile.txt",
			'modulepart'=>"medias",
			'subdir'=>"tmpphpunit/tmpphpunit1",
			'filecontent'=>"content text",
			'fileencoding'=>"",
			'overwriteifexists'=>0,
			'createdirifnotexists'=>0
		);

		$param = '';
		foreach ($data as $key => $val) {
			$param .= '&'.$key.'='.urlencode($val);
		}

		$result = getURLContent($url, 'POST', $param, 1, array(), array('http', 'https'), 2);
		echo __METHOD__.' Result for sending document: '.var_export($result, true)."\n";
		echo __METHOD__.' curl_error_no: '.$result['curl_error_no']."\n";
		$object = json_decode($result['content'], true);
		$this->assertNotNull($object, 'Parsing of json result must not be null');
		$this->assertEquals('401', $result['http_code'], 'Return code is not 401');
		$this->assertEquals('401', (empty($object['error']['code']) ? '' : $object['error']['code']), 'Error code is not 401');


		// Send to existent directory

		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2');
		dol_mkdir(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2');

		$data = array(
			'filename'=>"mynewfile.txt",
			'modulepart'=>"medias",
			'ref'=>"",
			'subdir'=>"tmpphpunit/tmpphpunit2",
			'filecontent'=>"content text",
			'fileencoding'=>"",
			'overwriteifexists'=>0,
			'createdirifnotexists'=>0
		);

		$param = '';
		foreach ($data as $key => $val) {
			$param .= '&'.$key.'='.urlencode($val);
		}

		$result2 = getURLContent($url, 'POST', $param, 1, array(), array('http', 'https'), 2);
		echo __METHOD__.' Result for sending document: '.var_export($result2, true)."\n";
		echo __METHOD__.' curl_error_no: '.$result2['curl_error_no']."\n";
		$object2 = json_decode($result2['content'], true);
		//$this->assertNotNull($object2, 'Parsing of json result must not be null');
		$this->assertEquals('200', $result2['http_code'], 'Return code must be 200');
		$this->assertEquals($result2['curl_error_no'], '');
		$this->assertEquals($object2, 'mynewfile.txt', 'Must contains basename of file');


		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit3');

		$data = array(
			'filename'=>"mynewfile.txt",
			'modulepart'=>"medias",
			'ref'=>"",
			'subdir'=>"tmpphpunit/tmpphpunit3",
			'filecontent'=>"content text",
			'fileencoding'=>"",
			'overwriteifexists'=>0,
			'createdirifnotexists'=>1
		);

		$param = '';
		foreach ($data as $key => $val) {
			$param .= '&'.$key.'='.urlencode($val);
		}

		$result3 = getURLContent($url, 'POST', $param, 1, array(), array('http', 'https'), 2);
		echo __METHOD__.' Result for sending document: '.var_export($result3, true)."\n";
		echo __METHOD__.' curl_error_no: '.$result3['curl_error_no']."\n";
		$object3 = json_decode($result3['content'], true);
		//$this->assertNotNull($object2, 'Parsing of json result must not be null');
		$this->assertEquals('200', $result3['http_code'], 'Return code must be 200');
		$this->assertEquals($result3['curl_error_no'], '');
		$this->assertEquals($object3, 'mynewfile.txt', 'Must contains basename of file');


		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit');

		return 0;
	}
}
