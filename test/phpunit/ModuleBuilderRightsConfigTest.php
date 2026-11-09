<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 *      \file       test/phpunit/ModuleBuilderRightsConfigTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ModuleBuilder rights generation settings
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/RightsGenerationMode.class.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/RightsConfig.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderRightsConfigTest extends CommonClassTest
{
	/** @var string[] Fixture files to remove on tearDown */
	private $fixtures = array();

	/**
	 * Write a throwaway generated class file.
	 *
	 * @param string $body Content placed inside the class
	 * @return string Path to the fixture file
	 */
	private function makeClassFixture(string $body): string
	{
		$dir = sys_get_temp_dir().'/mbrightsconfig'.getmypid();
		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}
		$path = $dir.'/myobject'.uniqid().'.class.php';
		file_put_contents($path, "<?php\nclass MyObject extends CommonObject\n{\n".$body."}\n");
		$this->fixtures[] = $path;
		return $path;
	}

	/**
	 * Remove fixture files.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		foreach ($this->fixtures as $path) {
			if (file_exists($path)) {
				unlink($path);
			}
		}
		$this->fixtures = array();
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testModeConstants()
	{
		$this->assertSame(array('none', 'auto', 'custom'), RightsGenerationMode::all());
		$this->assertTrue(RightsGenerationMode::isValid(RightsGenerationMode::AUTO));
		$this->assertFalse(RightsGenerationMode::isValid('AUTO'));
		$this->assertFalse(RightsGenerationMode::isValid(''));
	}

	/**
	 * @return void
	 */
	public function testNoneGeneratesNothing()
	{
		$config = RightsConfig::none();

		$this->assertSame(RightsGenerationMode::NONE, $config->getMode());
		$this->assertFalse($config->generatesRights());
		$this->assertSame(array(), $config->getGeneratedCodes());
		$this->assertSame('', $config->getRightsKeyOverride());

		$this->expectException(\LogicException::class);
		$config->getCodeFor('read');
	}

	/**
	 * @return void
	 */
	public function testAutoIsIdentity()
	{
		$config = RightsConfig::auto();

		$this->assertTrue($config->generatesRights());
		foreach (RightsConfig::OPERATIONS as $operation) {
			$this->assertSame($operation, $config->getCodeFor($operation));
		}
		$this->assertSame(array('read', 'write', 'delete'), $config->getGeneratedCodes());
		$this->assertSame('', $config->getRightsKeyOverride());
	}

	/**
	 * @return void
	 */
	public function testCustomMapDerivesGeneratedCodes()
	{
		$config = RightsConfig::custom('', array('delete' => 'write', 'write' => 'write', 'read' => 'read'));
		$this->assertSame(array('read', 'write'), $config->getGeneratedCodes());
		$this->assertSame('write', $config->getCodeFor('delete'));

		$config = RightsConfig::custom('', array('read' => 'read', 'write' => 'read', 'delete' => 'read'));
		$this->assertSame(array('read'), $config->getGeneratedCodes());

		$config = RightsConfig::custom('', array('read' => 'delete', 'write' => 'read', 'delete' => 'read'));
		$this->assertSame(array('read', 'delete'), $config->getGeneratedCodes());
	}

	/**
	 * @return void
	 */
	public function testCustomKeyOverride()
	{
		$identity = array('read' => 'read', 'write' => 'write', 'delete' => 'delete');

		$this->assertSame('parent', RightsConfig::custom('parent', $identity)->getRightsKeyOverride());
		$this->assertSame('', RightsConfig::custom('', $identity)->getRightsKeyOverride());
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function invalidKeyProvider(): array
	{
		return array(
			'uppercase' => array('Parent'),
			'leading digit' => array('1abc'),
			'dash' => array('a-b'),
			'quote' => array("a'b"),
			'parenthesis' => array('a)'),
			'space' => array('my key'),
			'object placeholder' => array('xmyobjectx'),
			'module placeholder' => array('mymodulex'),
			'too long' => array('a'.str_repeat('b', 64)),
			'trailing newline' => array("parent\n"),
		);
	}

	/**
	 * A key ends up in generated PHP and is rewritten by the placeholder substitution: only a plain identifier is accepted.
	 *
	 * @dataProvider invalidKeyProvider
	 * @param string $key Rejected key
	 * @return void
	 */
	public function testCustomRejectsInvalidKey(string $key)
	{
		$this->expectException(\InvalidArgumentException::class);
		RightsConfig::custom($key, array('read' => 'read', 'write' => 'write', 'delete' => 'delete'));
	}

	/**
	 * @return array<string,array{0:array<mixed>}>
	 */
	public function invalidMapProvider(): array
	{
		return array(
			'missing operation' => array(array('read' => 'read', 'write' => 'write')),
			'extra operation' => array(array('read' => 'read', 'write' => 'write', 'delete' => 'delete', 'export' => 'read')),
			'unknown code' => array(array('read' => 'read', 'write' => 'admin', 'delete' => 'delete')),
			'non string code' => array(array('read' => 'read', 'write' => 0, 'delete' => 'delete')),
			'empty' => array(array()),
		);
	}

	/**
	 * Every operation must be checked by one of the generated codes, never by a right nobody can hold.
	 *
	 * @dataProvider invalidMapProvider
	 * @param array<mixed> $map Rejected map
	 * @return void
	 */
	public function testCustomRejectsInvalidMap(array $map)
	{
		$this->expectException(\InvalidArgumentException::class);
		RightsConfig::custom('', $map);
	}

	/**
	 * @return void
	 */
	public function testGetCodeForRejectsUnknownOperation()
	{
		$this->expectException(\InvalidArgumentException::class);
		RightsConfig::auto()->getCodeFor('export');
	}

	/**
	 * @return void
	 */
	public function testJsonRoundTrip()
	{
		$configs = array(
			RightsConfig::none(),
			RightsConfig::auto(),
			RightsConfig::custom('parent', array('read' => 'read', 'write' => 'write', 'delete' => 'write')),
		);
		foreach ($configs as $config) {
			$json = $config->toJson();
			$this->assertSame($json, RightsConfig::fromJson($json)->toJson());
			$this->assertStringNotContainsString("'", $json);
			$this->assertStringNotContainsString('\\', $json);
		}
		$this->assertSame('{"mode":"none","key":"","map":{}}', RightsConfig::none()->toJson());
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function forgedJsonProvider(): array
	{
		return array(
			'not json' => array('mode=auto'),
			'not an object' => array('"auto"'),
			'unknown mode' => array('{"mode":"root","key":"","map":{}}'),
			'missing field' => array('{"mode":"none","key":""}'),
			'extra field' => array('{"mode":"none","key":"","map":{},"admin":1}'),
			'map in none' => array('{"mode":"none","key":"","map":{"read":"read","write":"write","delete":"delete"}}'),
			'key in auto' => array('{"mode":"auto","key":"parent","map":{"read":"read","write":"write","delete":"delete"}}'),
			'auto not identity' => array('{"mode":"auto","key":"","map":{"read":"read","write":"read","delete":"read"}}'),
			'injected key' => array('{"mode":"custom","key":"a\');die();//","map":{"read":"read","write":"write","delete":"delete"}}'),
			'key not a string' => array('{"mode":"custom","key":12,"map":{"read":"read","write":"write","delete":"delete"}}'),
		);
	}

	/**
	 * The stored settings are read back from a generated file someone may have edited: anything unexpected is refused.
	 *
	 * @dataProvider forgedJsonProvider
	 * @param string $json Forged content
	 * @return void
	 */
	public function testFromJsonRejectsForgedContent(string $json)
	{
		$this->expectException(\InvalidArgumentException::class);
		RightsConfig::fromJson($json);
	}

	/**
	 * @return void
	 */
	public function testStoreOnTheClassTemplateAndParseBack()
	{
		$path = $this->makeClassFixture('');
		copy(DOL_DOCUMENT_ROOT.'/modulebuilder/template/class/myobject.class.php', $path);
		$this->assertSame('pending', modulebuilderGetRightsConfigState((string) file_get_contents($path)));

		$config = RightsConfig::custom('parent', array('read' => 'read', 'write' => 'write', 'delete' => 'write'));
		$this->assertSame(1, modulebuilderStoreRightsConfig($path, $config));

		$content = (string) file_get_contents($path);
		$this->assertSame('stored', modulebuilderGetRightsConfigState($content));
		$this->assertSame($config->toJson(), modulebuilderParseRightsConfig($content)->toJson());
		$this->assertStringNotContainsString('$rightsconfig', $content);
		$this->assertSame(0, (int) shell_exec('php -l '.escapeshellarg($path).' > /dev/null 2>&1; echo $?'));

		// Stored once: a second store has no markers to replace
		$this->assertSame(-1, modulebuilderStoreRightsConfig($path, $config));
	}

	/**
	 * @return void
	 */
	public function testStateOfClassContent()
	{
		$stored = modulebuilderRenderRightsConfigLine(RightsConfig::auto());

		$this->assertSame('legacy', modulebuilderGetRightsConfigState("<?php\nclass A {\n}\n"));
		$this->assertSame('pending', modulebuilderGetRightsConfigState("\t// BEGIN MODULEBUILDER RIGHTSCONFIG\n\t// END MODULEBUILDER RIGHTSCONFIG\n"));
		$this->assertSame('stored', modulebuilderGetRightsConfigState($stored));
		$this->assertSame('stored', modulebuilderGetRightsConfigState(str_replace("\n", "\r\n", $stored)));
		$this->assertSame('invalid', modulebuilderGetRightsConfigState($stored.$stored));
		$this->assertSame('invalid', modulebuilderGetRightsConfigState("\t// BEGIN MODULEBUILDER RIGHTSCONFIG\n".$stored));
		$this->assertSame('invalid', modulebuilderGetRightsConfigState("\t// MODULEBUILDER RIGHTSCONFIG broken\n"));
	}

	/**
	 * A class generated with Windows line endings stays readable.
	 *
	 * @return void
	 */
	public function testParseAcceptsCrlf()
	{
		$content = str_replace("\n", "\r\n", "<?php\nclass A\n{\n".modulebuilderRenderRightsConfigLine(RightsConfig::auto())."}\n");

		$this->assertSame(RightsConfig::auto()->toJson(), modulebuilderParseRightsConfig($content)->toJson());
	}

	/**
	 * A comment mentioning the property name of an older draft is not mistaken for tampering.
	 *
	 * @return void
	 */
	public function testParseIgnoresUnrelatedComments()
	{
		$content = "// keep \$rightsconfig in sync\n".modulebuilderRenderRightsConfigLine(RightsConfig::none());

		$this->assertSame(RightsConfig::none()->toJson(), modulebuilderParseRightsConfig($content)->toJson());
	}

	/**
	 * @return void
	 */
	public function testTamperedStoredConfigIsRefused()
	{
		$this->expectException(\InvalidArgumentException::class);
		modulebuilderParseRightsConfig("\t// MODULEBUILDER RIGHTSCONFIG {\"mode\":\"root\",\"key\":\"\",\"map\":{}}\n");
	}

	/**
	 * @return void
	 */
	public function testDuplicatedStoredConfigIsRefused()
	{
		$line = modulebuilderRenderRightsConfigLine(RightsConfig::auto());

		$this->expectException(\InvalidArgumentException::class);
		modulebuilderParseRightsConfig($line.$line);
	}

	/**
	 * New object and object whose generation stopped halfway: the posted settings apply everywhere and are stored.
	 *
	 * @return void
	 */
	public function testResolveNewOrPendingObjectUsesPostedConfig()
	{
		$posted = RightsConfig::auto();
		$pending = "\t// BEGIN MODULEBUILDER RIGHTSCONFIG\n\t// END MODULEBUILDER RIGHTSCONFIG\n";

		foreach (array(null, $pending) as $classContent) {
			$resolved = modulebuilderResolveRightsConfigs($posted, $classContent);
			$this->assertSame($posted, $resolved['page']);
			$this->assertSame($posted, $resolved['descriptor']);
			$this->assertTrue($resolved['store']);
		}
	}

	/**
	 * An existing object keeps the settings its pages were generated with, whatever is posted.
	 *
	 * @return void
	 */
	public function testResolveExistingObjectKeepsStoredConfig()
	{
		$resolved = modulebuilderResolveRightsConfigs(RightsConfig::auto(), modulebuilderRenderRightsConfigLine(RightsConfig::none()));

		$this->assertSame(RightsGenerationMode::NONE, $resolved['page']->getMode());
		$this->assertSame(RightsGenerationMode::NONE, $resolved['descriptor']->getMode());
		$this->assertFalse($resolved['store']);
	}

	/**
	 * An object generated before rights modes existed keeps its pages; only the descriptor follows the posted settings.
	 *
	 * @return void
	 */
	public function testResolveLegacyObjectKeepsItsPages()
	{
		$posted = RightsConfig::auto();
		$resolved = modulebuilderResolveRightsConfigs($posted, "<?php\nclass MyObject extends CommonObject\n{\n}\n");

		$this->assertNull($resolved['page']);
		$this->assertSame($posted, $resolved['descriptor']);
		$this->assertFalse($resolved['store']);
	}

	/**
	 * @return void
	 */
	public function testResolveRefusesTamperedObject()
	{
		$this->expectException(\InvalidArgumentException::class);
		modulebuilderResolveRightsConfigs(RightsConfig::auto(), "\t// MODULEBUILDER RIGHTSCONFIG {\"mode\":\"root\"}\n");
	}

	/**
	 * @return void
	 */
	public function testApplyRendersAFileAndReportsFailures()
	{
		$path = $this->makeClassFixture('');
		copy(DOL_DOCUMENT_ROOT.'/modulebuilder/template/myobject_list.php', $path);

		$this->assertSame(1, modulebuilderApplyRightsConfig($path, RightsConfig::auto()));
		$this->assertStringNotContainsString('MODULEBUILDER RIGHTS', (string) file_get_contents($path));
		$this->assertStringNotContainsString('$enablepermissioncheck', (string) file_get_contents($path));

		$broken = $this->makeClassFixture('');
		file_put_contents($broken, "<?php\n// BEGIN MODULEBUILDER RIGHTS\n\$permissiontoread = 1;\n");
		$this->assertSame(-1, modulebuilderApplyRightsConfig($broken, RightsConfig::auto()));
		$this->assertSame(-1, modulebuilderApplyRightsConfig(sys_get_temp_dir().'/does-not-exist-'.uniqid().'.php', RightsConfig::auto()));
	}
}
