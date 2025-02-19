<?php
/* Copyright (C) 2010 		Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2023 		Alexandre Janniaux   	<alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

require_once __DIR__."/AbstractRestAPITest.php";
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';

/**
 * Class for PHPUnit tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIDocumentTest extends AbstractRestAPITest
{
	protected $api_url;
	protected $api_key;

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
			'filename' => "mynewfile.txt",
			'modulepart' => "medias",
			'subdir' => "tmpphpunit/tmpphpunit1",
			'filecontent' => "content text",
			'fileencoding' => "",
			'overwriteifexists' => 0,
			'createdirifnotexists' => 0,
			'position' => 0,
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
		$this->assertEquals('400', $result['http_code'], 'Test to push a document on a non existing dir does not return 400');
		$this->assertEquals('400', (empty($object['error']['code']) ? '' : $object['error']['code']), 'Test to push a document on a non existing dir does not return 400');


		// Send to existent directory

		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2');
		dol_mkdir(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2');

		$data = array(
			'filename' => "mynewfile.txt",
			'modulepart' => "medias",
			'ref' => "",
			'subdir' => "tmpphpunit/tmpphpunit2",
			'filecontent' => "content text",
			'fileencoding' => "",
			'overwriteifexists' => 0,
			'createdirifnotexists' => 0,
			'position' => 0,
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
			'filename' => "mynewfile.txt",
			'modulepart' => "medias",
			'ref' => "",
			'subdir' => "tmpphpunit/tmpphpunit3",
			'filecontent' => "content text",
			'fileencoding' => "",
			'overwriteifexists' => 0,
			'createdirifnotexists' => 1,
			'position' => 0,
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
