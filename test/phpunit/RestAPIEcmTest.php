<?php
/* Copyright (C) 2026 OpenAI
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

require_once __DIR__.'/AbstractRestAPITest.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/ecm/class/ecmfiles.class.php';
require_once dirname(__FILE__).'/../../htdocs/ecm/class/ecmdirectory.class.php';

/**
 * Class for PHPUnit tests of ECM API.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class RestAPIEcmTest extends AbstractRestAPITest
{
	/**
	 * @var string
	 */
	protected $testRootDir = 'tmpphpunit_api_ecm';

	/**
	 * Clean test data.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		parent::tearDown();

		$this->cleanupTestDirectory();
	}

	/**
	 * Test upload into a custom ECM directory.
	 *
	 * @return void
	 */
	public function testUploadToCustomDir()
	{
		$url = $this->api_url.'/ecm/uploadToCustomDir?api_key='.$this->api_key;
		$relativeDir = $this->testRootDir.'/alpha/beta';
		$filename = 'contract-test.txt';
		$fullFilePath = DOL_DATA_ROOT.'/ecm/'.$relativeDir.'/'.$filename;

		$this->cleanupTestDirectory();

		// Reject path traversal.
		$resultTraversal = $this->callApi(
			$url,
			array(
				'filename' => $filename,
				'filecontent' => 'content text',
				'fileencoding' => '',
				'target_dir' => $this->testRootDir.'/../escape',
				'createdirifnotexists' => 1,
				'overwriteifexists' => 0,
			)
		);
		$objectTraversal = json_decode($resultTraversal['content'], true);
		$this->assertNotNull($objectTraversal, 'Traversal response must be valid JSON');
		$this->assertEquals(400, $resultTraversal['http_code'], 'Traversal upload must return 400');
		$this->assertEquals('400', $objectTraversal['error']['code'], 'Traversal upload must expose REST 400 code');

		// Successful base64 upload with directory creation and indexing.
		$resultUpload = $this->callApi(
			$url,
			array(
				'filename' => $filename,
				'filecontent' => base64_encode("custom ecm api content\n"),
				'fileencoding' => 'base64',
				'target_dir' => $relativeDir,
				'createdirifnotexists' => 1,
				'overwriteifexists' => 0,
				'description' => 'PHPUnit upload',
				'keywords' => 'phpunit,ecm,api',
				'note_private' => 'created by RestAPIEcmTest',
			)
		);
		$objectUpload = json_decode($resultUpload['content'], true);
		$this->assertNotNull($objectUpload, 'Upload response must be valid JSON');
		$this->assertEquals(200, $resultUpload['http_code'], 'Upload must return 200');
		$this->assertEquals(0, $resultUpload['curl_error_no'], 'Upload must not raise curl error');
		$this->assertTrue(!empty($objectUpload['success']), 'Upload response must contain success=true');
		$this->assertEquals('ecm/'.$relativeDir.'/'.$filename, $objectUpload['full_relative_path'], 'Response must expose ECM-relative path');
		$this->assertTrue(file_exists(dol_osencode($fullFilePath)), 'Uploaded file must exist on disk');
		$this->assertEquals("custom ecm api content\n", (string) file_get_contents(dol_osencode($fullFilePath)), 'Uploaded file content must match');

		$ecmFile = new EcmFiles($this->savdb);
		$resultFetch = $ecmFile->fetch(0, '', 'ecm/'.$relativeDir.'/'.$filename);
		$this->assertGreaterThan(0, $resultFetch, 'Uploaded file must be indexed into llx_ecm_files');
		$this->assertEquals('ecm/'.$relativeDir, $ecmFile->filepath, 'Indexed filepath must match ECM directory');
		$this->assertEquals($filename, $ecmFile->filename, 'Indexed filename must match');
		$this->assertEquals('PHPUnit upload', $ecmFile->description, 'Indexed description must match');
		$this->assertEquals('phpunit,ecm,api', $ecmFile->keywords, 'Indexed keywords must match');

		$ecmDirectory = new EcmDirectory($this->savdb);
		$tree = $ecmDirectory->get_full_arbo(1);
		$foundDirectory = false;
		if (is_array($tree)) {
			foreach ($tree as $directoryInfo) {
				if (!empty($directoryInfo['fullrelativename']) && $directoryInfo['fullrelativename'] === $relativeDir) {
					$foundDirectory = true;
					break;
				}
			}
		}
		$this->assertTrue($foundDirectory, 'Custom ECM directory tree must be created in llx_ecm_directories');

		// Reject duplicate upload when overwrite is disabled.
		$resultDuplicate = $this->callApi(
			$url,
			array(
				'filename' => $filename,
				'filecontent' => 'different content',
				'fileencoding' => '',
				'target_dir' => $relativeDir,
				'createdirifnotexists' => 0,
				'overwriteifexists' => 0,
			)
		);
		$objectDuplicate = json_decode($resultDuplicate['content'], true);
		$this->assertNotNull($objectDuplicate, 'Duplicate upload response must be valid JSON');
		$this->assertEquals(400, $resultDuplicate['http_code'], 'Duplicate upload must return 400');
		$this->assertEquals('400', $objectDuplicate['error']['code'], 'Duplicate upload must expose REST 400 code');
	}

	/**
	 * Call API helper.
	 *
	 * @param string              $url
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	protected function callApi($url, array $data)
	{
		$param = '';
		foreach ($data as $key => $val) {
			$param .= '&'.$key.'='.urlencode((string) $val);
		}

		return getURLContent($url, 'POST', $param, 1, array(), array('http', 'https'), 2);
	}

	/**
	 * Delete temporary ECM directories created by this test.
	 *
	 * @return void
	 */
	protected function cleanupTestDirectory()
	{
		global $db, $user;

		dol_delete_dir_recursive(DOL_DATA_ROOT.'/ecm/'.$this->testRootDir);

		$ecmDirectory = new EcmDirectory($db);
		$tree = $ecmDirectory->get_full_arbo(1);
		if (!is_array($tree)) {
			return;
		}

		$directoriesToDelete = array();
		foreach ($tree as $directoryInfo) {
			if (!empty($directoryInfo['fullrelativename']) && ($directoryInfo['fullrelativename'] === $this->testRootDir || strpos($directoryInfo['fullrelativename'], $this->testRootDir.'/') === 0)) {
				$directoriesToDelete[(int) $directoryInfo['id']] = (int) $directoryInfo['id'];
			}
		}

		if (empty($directoriesToDelete)) {
			return;
		}

		rsort($directoriesToDelete);
		foreach ($directoriesToDelete as $directoryId) {
			$directory = new EcmDirectory($db);
			if ($directory->fetch($directoryId) > 0) {
				$directory->delete($user, 'databaseonly', 1);
			}
		}
	}
}
