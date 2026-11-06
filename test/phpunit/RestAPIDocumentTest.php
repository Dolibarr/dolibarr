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
	 * @var string[]	API paths of the objects to delete at end of the test, the last created first
	 */
	private $objectstodelete = array();

	/**
	 * @var string[]	Directories to delete at end of the test
	 */
	private $dirstodelete = array();

	/**
	 * Delete objects and directories created by the test. The API server does not see the transaction of the
	 * test class, so what is created for it is committed and is not removed by the final rollback.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		foreach ($this->objectstodelete as $path) {
			$this->callApi('DELETE', $path, $this->api_key);
		}
		$this->objectstodelete = array();

		$countdeleted = 0;
		foreach ($this->dirstodelete as $dir) {
			dol_delete_dir_recursive($dir, 0, 0, 0, $countdeleted, 0);
		}
		$this->dirstodelete = array();

		parent::tearDown();
	}

	/**
	 * Call the API with the key of a user
	 *
	 * @param 'GET'|'POST'|'DELETE'	$method		Call method
	 * @param string				$path		Path of the API route, without leading /, with its query string if any
	 * @param string				$apikey		API key of the user
	 * @param mixed					$data		Data that will be JSON encoded (POST)
	 * @return array{code:int,content:mixed}	HTTP code and decoded JSON content
	 */
	private function callApi($method, $path, $apikey, $data = null)
	{
		$addheaders = array('Content-Type: application/json', 'Accept: application/json', 'DOLAPIKEY: '.$apikey);

		$result = getURLContent($this->api_url.'/'.$path, $method, ($data === null ? '' : json_encode($data)), 1, $addheaders, array('http', 'https'), 2);
		$this->assertEquals(0, $result['curl_error_no'], "$method $path must not have a curl error");

		return array('code' => (int) $result['http_code'], 'content' => json_decode($result['content'], true));
	}

	/**
	 * Create an object with the admin user. It is deleted at end of the test.
	 *
	 * @param string				$path	Path of the API route, without leading /
	 * @param array<string,mixed>	$data	Fields of the object
	 * @return int							Id of the object
	 */
	private function createObject($path, $data)
	{
		$result = $this->callApi('POST', $path, $this->api_key, $data);
		$this->assertEquals(200, $result['code'], "Creation on $path failed: ".var_export($result, true));
		$id = (int) $result['content'];
		$this->assertGreaterThan(0, $id, "Id of object created on $path is not > 0");
		array_unshift($this->objectstodelete, $path.'/'.$id);

		return $id;
	}

	/**
	 * Create an internal user that has only the given permissions, so not the one to see all third parties.
	 * It is deleted at end of the test.
	 *
	 * @param array<array{0:string,1:string}>	$rights		List of array(module, perms) to add to the user
	 * @return array{id:int,login:string,apikey:string}		Id, login and API key of the user
	 */
	private function createRestrictedUser($rights)
	{
		global $db, $user;

		$password = 'Ab1-'.bin2hex(random_bytes(8));

		$tmpuser = new User($db);
		$tmpuser->login = 'testrestdocument'.mt_rand(0, 99999999);
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

		$this->assertGreaterThan(0, $id, 'Creation of user failed: '.$tmpuser->error);
		$this->assertNotEmpty($resultpassword, 'Password of user not set: '.$tmpuser->error);
		array_unshift($this->objectstodelete, 'users/'.$id);

		$result = $this->callApi('GET', 'login?login='.urlencode($tmpuser->login).'&password='.urlencode($password), '');
		$this->assertEquals(200, $result['code'], 'Login of user failed: '.var_export($result, true));

		return array('id' => (int) $id, 'login' => $tmpuser->login, 'apikey' => $result['content']['success']['token']);
	}

	/**
	 * Add a file into the directory of an object. The directory is deleted at end of the test.
	 *
	 * @param string	$dir		Directory of the object
	 * @param string	$filename	Name of the file
	 * @return void
	 */
	private function addFile($dir, $filename)
	{
		dol_mkdir($dir);
		$this->dirstodelete[] = $dir;
		$this->assertNotFalse(file_put_contents($dir.'/'.$filename, 'content text'), "File must be written into $dir");
	}

	/**
	 * Create an intervention on a third party, with a file
	 *
	 * @param int		$socid		Id of the third party
	 * @param string	$filename	Name of the file
	 * @return array{id:int,ref:string}	Id and ref of the intervention
	 */
	private function createInterventionWithFile($socid, $filename)
	{
		global $conf;

		$id = $this->createObject('interventions', array("socid" => $socid, "fk_project" => 0, "description" => "Intervention with a file"));

		$result = $this->callApi('GET', 'interventions/'.$id, $this->api_key);
		$this->assertEquals(200, $result['code'], 'Intervention must be found');
		$ref = $result['content']['ref'];

		$this->addFile($conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($ref), $filename);

		return array('id' => $id, 'ref' => $ref);
	}

	/**
	 * Create an event, with a file
	 *
	 * @param int		$userownerid	Id of the owner of the event
	 * @param string	$filename		Name of the file
	 * @return int						Id of the event
	 */
	private function createEventWithFile($userownerid, $filename)
	{
		global $conf;

		$id = $this->createObject('agendaevents', array("userownerid" => $userownerid, "type_code" => "AC_OTH", "label" => "Event with a file", "datep" => dol_now()));

		$this->addFile($conf->agenda->dir_output.'/'.$id, $filename);

		return $id;
	}

	/**
	 * Check the documents route returns a file
	 *
	 * @param string	$query		Query string of the route
	 * @param string	$apikey		API key of the user
	 * @param string	$filename	Name of the file expected
	 * @return void
	 */
	private function assertDocumentListed($query, $apikey, $filename)
	{
		$result = $this->callApi('GET', 'documents?'.$query, $apikey);
		$this->assertEquals(200, $result['code'], "List of documents with $query must work: ".var_export($result, true));
		$this->assertContains($filename, array_column($result['content'], 'name'), "List of documents with $query must return the file");
	}

	/**
	 * Check the documents route refuses the access. The answer must be JSON, without an error page printed into it.
	 *
	 * @param string	$query		Query string of the route
	 * @param string	$apikey		API key of the user
	 * @return void
	 */
	private function assertDocumentRefused($query, $apikey)
	{
		$result = $this->callApi('GET', 'documents?'.$query, $apikey);
		$this->assertEquals(403, $result['code'], "List of documents with $query must be refused: ".var_export($result, true));
		$this->assertNotNull($result['content'], "Refusal with $query must be valid JSON");
	}

	/**
	 * A user without the permission to see all third parties lists the documents of the interventions
	 * of their third parties only.
	 *
	 * @return void
	 */
	public function testListDocumentsRestrictedUserOnIntervention()
	{
		$restricteduser = $this->createRestrictedUser(array(array('ficheinter', 'lire')));

		$socidofuser = $this->createObject('thirdparties', array("name" => "testRestDocumentOfUser".mt_rand()));
		$socidofother = $this->createObject('thirdparties', array("name" => "testRestDocumentOfOther".mt_rand()));

		$result = $this->callApi('POST', 'thirdparties/'.$socidofuser.'/representative/'.$restricteduser['id'], $this->api_key);
		$this->assertEquals(200, $result['code'], 'User must become representative of the third party');

		$interventionofuser = $this->createInterventionWithFile($socidofuser, 'fileofuser.txt');
		$interventionofother = $this->createInterventionWithFile($socidofother, 'fileofother.txt');

		$this->assertDocumentListed('modulepart=intervention&id='.$interventionofuser['id'], $restricteduser['apikey'], 'fileofuser.txt');
		$this->assertDocumentRefused('modulepart=intervention&id='.$interventionofother['id'], $restricteduser['apikey']);
	}

	/**
	 * A user that can only read their own events lists the documents of their events only. Checks too that
	 * an event is found with the primary key of its table, that is not rowid.
	 *
	 * @return void
	 */
	public function testListDocumentsRestrictedUserOnEvent()
	{
		$restricteduser = $this->createRestrictedUser(array(array('agenda', 'myactions')));

		$eventofuser = $this->createEventWithFile($restricteduser['id'], 'fileofuser.txt');
		$eventofother = $this->createEventWithFile(1, 'fileofother.txt');

		$this->assertDocumentListed('modulepart=event&id='.$eventofuser, $restricteduser['apikey'], 'fileofuser.txt');
		$this->assertDocumentRefused('modulepart=event&id='.$eventofother, $restricteduser['apikey']);
	}

	/**
	 * Aliases of modulepart: 'intervention' and 'event' were resolved to a class that does not exist (error 404),
	 * 'fichinter' was refused by the check of permission on module (error 500).
	 *
	 * @return void
	 */
	public function testListDocumentsAliases()
	{
		$intervention = $this->createInterventionWithFile(1, 'intervention.txt');
		$eventid = $this->createEventWithFile(1, 'event.txt');

		foreach (array('intervention', 'fichinter', 'ficheinter') as $modulepart) {
			$this->assertDocumentListed('modulepart='.$modulepart.'&id='.$intervention['id'], $this->api_key, 'intervention.txt');
		}
		$this->assertDocumentListed('modulepart=intervention&ref='.urlencode($intervention['ref']), $this->api_key, 'intervention.txt');

		foreach (array('event', 'agenda', 'action', 'actioncomm') as $modulepart) {
			$this->assertDocumentListed('modulepart='.$modulepart.'&id='.$eventid, $this->api_key, 'event.txt');
		}
	}

	/**
	 * A user without the permission to read users lists their own documents by ref, but not the ones of another user.
	 *
	 * @return void
	 */
	public function testListDocumentsOfOwnUserByRef()
	{
		global $conf;

		$restricteduser = $this->createRestrictedUser(array());
		$this->addFile($conf->user->dir_output.'/'.$restricteduser['id'], 'fileofuser.txt');

		$this->assertDocumentListed('modulepart=user&ref='.urlencode($restricteduser['login']), $restricteduser['apikey'], 'fileofuser.txt');
		$this->assertDocumentRefused('modulepart=user&ref=admin', $restricteduser['apikey']);
	}

	/**
	 * testAgendaModulepartAliases
	 *
	 * The aliases of modulepart for events must return a REST error on a file that does not exist, and not stop on an HTML error page.
	 *
	 * @return void
	 */
	public function testAgendaModulepartAliases()
	{
		if (!isModEnabled('agenda')) {
			return;
		}

		foreach (array('action', 'event', 'agenda') as $modulepart) {
			$param = 'modulepart='.urlencode($modulepart).'&original_file='.urlencode('tmpphpunit/notexistingfile.pdf').'&api_key='.$this->api_key;

			foreach (array('GET' => '/documents/download', 'DELETE' => '/documents') as $method => $route) {
				$url = $this->api_url.$route.'?'.$param;
				echo __METHOD__.' Request '.$method.' url='.$url."\n";

				$result = getURLContent($url, $method, '', 1, array(), array('http', 'https'), 2);
				$object = json_decode($result['content'], true);
				$this->assertNotNull($object, "$method $route with modulepart $modulepart must return json");
				$this->assertEquals(404, $result['http_code'], "$method $route with modulepart $modulepart on a not found file must return 404");
			}
		}
	}

	/**
	 * testPushDocument.
	 *
	 * @return int
	 */
	public function testPushDocument()
	{
		$url = $this->api_url.'/documents/upload?api_key='.$this->api_key;

		echo __METHOD__.' Request POST url='.$url."\n";


		// Send to non existent directory

		// Cleanups use $indexdatabase=0: deleting llx_ecm_files rows inside the uncommitted test transaction would lock them for the API server
		$countdeleted = 0;
		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit1', 0, 0, 0, $countdeleted, 0);

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
		$this->assertEquals(400, $result['http_code'], 'Test to push a document on a non existing dir does not return 400');
		$this->assertEquals('400', (empty($object['error']['code']) ? '' : $object['error']['code']), 'Test to push a document on a non existing dir does not return 400');


		// Send to existent directory

		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2', 0, 0, 0, $countdeleted, 0);
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
		$this->assertEquals(0, $result2['curl_error_no']);
		$this->assertEquals($object2, 'mynewfile.txt', 'Must contains basename of file');


		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit3', 0, 0, 0, $countdeleted, 0);

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
		$this->assertEquals(0, $result3['curl_error_no']);
		$this->assertEquals($object3, 'mynewfile.txt', 'Must contains basename of file');


		dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit', 0, 0, 0, $countdeleted, 0);

		return 0;
	}
}
