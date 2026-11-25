<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 */

/**
 *      \file       test/phpunit/AiMcpApiBridgeDocumentsTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the documents tools of the MCP API bridge
 *                  (ai/tools/api_bridge.class.php): the download tool reuses
 *                  GET /documents/download of the REST API, the upload tool
 *                  POST /documents/upload behind the write confirmation gate.
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcptool.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/tools/api_bridge.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpApiBridgeDocumentsTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpApiBridgeDocumentsTest extends CommonClassTest
{
	/**
	 * Definitions of the bridge as a name => definition map.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function defsByName()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$map = array();
		foreach ($bridge->getDefinitions() as $def) {
			$name = isset($def['name']) ? $def['name'] : (isset($def['function']['name']) ? $def['function']['name'] : '');
			if ($name !== '') {
				$map[$name] = $def;
			}
		}

		return $map;
	}

	/**
	 * The documents tools are split in three groups, one per file area, all
	 * calling the REST class documents. The single group of before is gone.
	 *
	 * @return void
	 */
	public function testToolsAreSplitByArea()
	{
		if (!isModEnabled('api')) {
			$this->markTestSkipped('API module disabled: the documents endpoint is not discovered.');
		}

		$map = $this->defsByName();
		foreach (array('api_documents_objects_list', 'api_documents_objects_download', 'api_documents_objects_upload', 'api_documents_medias_list', 'api_documents_medias_download', 'api_documents_medias_upload') as $toolname) {
			$this->assertArrayHasKey($toolname, $map, 'The bridge must expose '.$toolname);
		}
		if (isModEnabled('ecm')) {
			$this->assertArrayHasKey('api_documents_manual_list', $map, 'The bridge must expose api_documents_manual_list');
			$this->assertArrayHasKey('api_documents_manual_download', $map, 'The bridge must expose api_documents_manual_download');
			$this->assertArrayHasKey('api_documents_manual_upload', $map, 'The bridge must expose api_documents_manual_upload');
		}
		foreach (array('api_documents_list', 'api_documents_download', 'api_documents_upload') as $toolname) {
			$this->assertArrayNotHasKey($toolname, $map, 'The former tool '.$toolname.' must be gone');
		}
	}

	/**
	 * The list tools of the areas not linked to an object read a directory:
	 * the area is forced, the caller gives the directory.
	 *
	 * @return void
	 */
	public function testDirectoryListTools()
	{
		if (!isModEnabled('api')) {
			$this->markTestSkipped('API module disabled: the documents endpoint is not discovered.');
		}

		$map = $this->defsByName();
		$schema = $map['api_documents_medias_list']['inputSchema'];
		$this->assertArrayHasKey('subdir', $schema['properties']);
		$this->assertArrayHasKey('search', $schema['properties']);
		$this->assertArrayNotHasKey('modulepart', $schema['properties'], 'The medias tool forces modulepart');
		$this->assertArrayNotHasKey('requestState', $schema['properties'], 'A list is a read: no confirmation state');

		global $db, $conf, $user;
		$bridge = new ToolApiBridge($db, $user, $conf);
		$output = $bridge->execute('api_documents_medias_list', array('subdir' => '../'));
		$this->assertSame(400, $output['http_status'] ?? 0, 'A directory out of the area must be refused');
	}

	/**
	 * A download is a read: the objects tool asks for the area, the medias tool
	 * forces it, so modulepart is not in its schema.
	 *
	 * @return void
	 */
	public function testDownloadTools()
	{
		if (!isModEnabled('api')) {
			$this->markTestSkipped('API module disabled: the documents endpoint is not discovered.');
		}

		$map = $this->defsByName();

		$props = $map['api_documents_objects_download']['inputSchema']['properties'];
		$this->assertArrayHasKey('modulepart', $props);
		$this->assertArrayHasKey('original_file', $props);
		$this->assertArrayNotHasKey('requestState', $props, 'A download is a read: no confirmation state');

		$schema = $map['api_documents_medias_download']['inputSchema'];
		$this->assertArrayHasKey('original_file', $schema['properties']);
		$this->assertArrayNotHasKey('modulepart', $schema['properties'], 'The medias tool forces modulepart');
		$this->assertNotContains('modulepart', $schema['required'] ?? array());
	}

	/**
	 * The upload tools are writes: flat fields of the REST method, required
	 * fields, and the confirmation state. Only the objects tool takes a ref.
	 *
	 * @return void
	 */
	public function testUploadToolsAreExposedWithWriteGate()
	{
		if (!isModEnabled('api')) {
			$this->markTestSkipped('API module disabled: the documents endpoint is not discovered.');
		}

		$map = $this->defsByName();

		$schema = $map['api_documents_objects_upload']['inputSchema'];
		foreach (array('filename', 'modulepart', 'ref', 'filecontent', 'fileencoding') as $pname) {
			$this->assertArrayHasKey($pname, $schema['properties'], 'The objects upload schema must document '.$pname);
		}
		$this->assertContains('filename', $schema['required']);
		$this->assertContains('modulepart', $schema['required']);
		$this->assertContains('ref', $schema['required']);
		$this->assertArrayHasKey('requestState', $schema['properties'], 'The upload is a write: it must be confirmation-gated');

		$schema = $map['api_documents_medias_upload']['inputSchema'];
		foreach (array('filename', 'subdir', 'filecontent', 'fileencoding') as $pname) {
			$this->assertArrayHasKey($pname, $schema['properties'], 'The medias upload schema must document '.$pname);
		}
		$this->assertArrayNotHasKey('modulepart', $schema['properties'], 'The medias tool forces modulepart');
		$this->assertArrayNotHasKey('ref', $schema['properties'], 'A media is not attached to an object');
		$this->assertContains('subdir', $schema['required']);
		$this->assertArrayHasKey('requestState', $schema['properties'], 'The upload is a write: it must be confirmation-gated');
	}

	/**
	 * The rights of the tools are enforced downstream: which permission an
	 * upload or a download needs depends on the area, so no single Dolibarr
	 * right can be declared here.
	 *
	 * @return void
	 */
	public function testRightsAreEnforcedDownstream()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		foreach (array('api_documents_objects_download', 'api_documents_objects_upload', 'api_documents_medias_download', 'api_documents_medias_upload') as $toolname) {
			$this->assertSame(
				McpTool::RIGHTS_ENFORCED_DOWNSTREAM,
				$bridge->getRequiredRights($toolname),
				$toolname.' must not declare a right: the API checks it per area'
			);
		}
	}

	/**
	 * The confirmation of an upload names the file and the area it goes into,
	 * also when the area is forced by the tool and not sent by the caller.
	 *
	 * @return void
	 */
	public function testUploadConfirmationNamesFileAndArea()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$preview = $bridge->writeConfirmationPreview(
			'api_documents_medias_upload',
			array('filename' => 'photo.png', 'subdir' => 'image/mywebsite', 'fileencoding' => 'base64')
		);

		$this->assertNotSame(McpTool::NO_WRITE, $preview, 'An upload must be confirmation-gated');
		$this->assertStringContainsString('photo.png', (string) $preview);
		$this->assertStringContainsString('medias', (string) $preview, 'The area forced by the tool must be named');
	}

	/**
	 * An upload without its required arguments is refused before any
	 * confirmation is issued.
	 *
	 * @return void
	 */
	public function testUploadPrecheckRefusesMissingArguments()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		$refusal = $bridge->writePrecheck('api_documents_medias_upload', array('subdir' => 'image/mywebsite'));
		$this->assertNotSame('', $refusal, 'An upload without a filename must be refused before the confirmation');

		$refusal = $bridge->writePrecheck('api_documents_objects_upload', array('filename' => 'a.txt', 'modulepart' => 'invoice'));
		$this->assertNotSame('', $refusal, 'An upload on an object without its ref must be refused before the confirmation');
	}
}
