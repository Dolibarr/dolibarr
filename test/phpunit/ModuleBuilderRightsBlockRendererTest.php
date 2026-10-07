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
 *      \file       test/phpunit/ModuleBuilderRightsBlockRendererTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the rendering of permission checks in ModuleBuilder templates
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/RightsBlockRenderer.class.php';
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
class ModuleBuilderRightsBlockRendererTest extends CommonClassTest
{
	const TEMPLATE_DIR = DOL_DOCUMENT_ROOT.'/modulebuilder/template/';

	const OBJECT_CHECK = "hasRight('mymodule', 'myobject', ";

	/**
	 * @param string $file Template path relative to the template directory
	 * @return string Template content
	 */
	private function readTemplate(string $file): string
	{
		return (string) file_get_contents(self::TEMPLATE_DIR.$file);
	}

	/**
	 * @param string $code PHP file content
	 * @return void
	 */
	private function assertParses(string $code): void
	{
		try {
			token_get_all($code, TOKEN_PARSE);
		} catch (ParseError $e) {
			$this->fail('Rendered file does not parse: '.$e->getMessage().' line '.$e->getLine());
		}
		$this->addToAssertionCount(1);
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function templateProvider(): array
	{
		$files = array(
			'myobject_card.php', 'myobject_list.php', 'myobject_contact.php', 'myobject_document.php',
			'myobject_note.php', 'myobject_agenda.php', 'ajax/myobject.php', 'stats/myobject_index.php',
		);

		return array_combine($files, array_map(
			/**
			 * @param string $file Template path
			 * @return array{0:string}
			 */
			static function ($file) {
				return array($file);
			},
			$files
		));
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function pageWithOpenBranchProvider(): array
	{
		$pages = $this->templateProvider();
		unset($pages['ajax/myobject.php']);

		return $pages;
	}

	/**
	 * An object generated before rights modes existed gets exactly the checks it had.
	 *
	 * @dataProvider templateProvider
	 * @param string $file Template path
	 * @return void
	 */
	public function testLegacyOnlyStripsMarkers(string $file)
	{
		$template = $this->readTemplate($file);

		$this->assertSame(
			preg_replace('/^\h*\/\/ (BEGIN|END) MODULEBUILDER RIGHTS\h*\R/m', '', $template),
			RightsBlockRenderer::renderFile($template, null)
		);
	}

	/**
	 * @dataProvider pageWithOpenBranchProvider
	 * @param string $file Template path
	 * @return void
	 */
	public function testNoneRemovesEveryObjectRightCheck(string $file)
	{
		$rendered = RightsBlockRenderer::renderFile($this->readTemplate($file), RightsConfig::none());

		$this->assertStringNotContainsString("hasRight('mymodule'", $rendered);
		$this->assertStringNotContainsString('ENABLE_PERMISSION_CHECK', $rendered);
		$this->assertStringNotContainsString('$enablepermissioncheck', $rendered);
		$this->assertStringNotContainsString('MODULEBUILDER RIGHTS', $rendered);
		$this->assertParses($rendered);
	}

	/**
	 * @dataProvider templateProvider
	 * @param string $file Template path
	 * @return void
	 */
	public function testAutoEnforcesWithoutConstant(string $file)
	{
		$template = $this->readTemplate($file);
		$rendered = RightsBlockRenderer::renderFile($template, RightsConfig::auto());

		$this->assertStringNotContainsString('ENABLE_PERMISSION_CHECK', $rendered);
		$this->assertStringNotContainsString('$enablepermissioncheck', $rendered);
		$this->assertStringNotContainsString('MODULEBUILDER RIGHTS', $rendered);
		$this->assertGreaterThan(0, substr_count($rendered, self::OBJECT_CHECK));
		$this->assertSame(substr_count($template, self::OBJECT_CHECK), substr_count($rendered, self::OBJECT_CHECK));
		$this->assertParses($rendered);
	}

	/**
	 * @return void
	 */
	public function testCardChecksEachOperation()
	{
		$template = $this->readTemplate('myobject_card.php');

		$auto = RightsBlockRenderer::renderFile($template, RightsConfig::auto());
		$this->assertStringContainsString("\n\$permissiontoread = \$user->hasRight('mymodule', 'myobject', 'read');\n", $auto);
		$this->assertStringContainsString("\n\$permissiontodelete = \$user->hasRight('mymodule', 'myobject', 'delete') || (\$permissiontoadd", $auto);
		$this->assertStringContainsString("\n\$permissiondellink = \$user->hasRight('mymodule', 'myobject', 'write');", $auto);
		$this->assertStringNotContainsString('$permissiontoread = 1;', $auto);

		$none = RightsBlockRenderer::renderFile($template, RightsConfig::none());
		$this->assertStringContainsString("\n\$permissiontoread = 1;\n", $none);
		$this->assertStringContainsString("\n\$permissiondellink = 1;\n", $none);
		$this->assertStringNotContainsString('There is several ways to check permission', $none);
	}

	/**
	 * @return void
	 */
	public function testCustomMapsKeyAndCodes()
	{
		$config = RightsConfig::custom('parent', array('read' => 'read', 'write' => 'write', 'delete' => 'write'));
		$rendered = RightsBlockRenderer::renderFile($this->readTemplate('myobject_card.php'), $config);

		$this->assertStringContainsString("\$permissiontodelete = \$user->hasRight('mymodule', 'parent', 'write')", $rendered);
		$this->assertStringContainsString("\$permissiontoread = \$user->hasRight('mymodule', 'parent', 'read')", $rendered);
		$this->assertStringNotContainsString("'delete')", $rendered);
		$this->assertStringNotContainsString(self::OBJECT_CHECK, $rendered);
		$this->assertParses($rendered);
	}

	/**
	 * @return void
	 */
	public function testAjaxGuardFollowsTheMode()
	{
		$template = $this->readTemplate('ajax/myobject.php');

		$this->assertStringContainsString(
			"if (!\$user->hasRight('mymodule', 'myobject', 'write')) {\n\taccessforbidden();\n}",
			RightsBlockRenderer::renderFile($template, RightsConfig::auto())
		);
		$this->assertStringContainsString(
			"if (!\$user->hasRight('mymodule', 'myobject', 'read')) {",
			RightsBlockRenderer::renderFile($template, RightsConfig::custom('', array('read' => 'read', 'write' => 'read', 'delete' => 'read')))
		);
	}

	/**
	 * Other generators mark code inside the rights block: their inline markers must survive.
	 *
	 * @return void
	 */
	public function testInlineMarkersArePreserved()
	{
		$template = implode("\n", array(
			'<?php',
			'// BEGIN MODULEBUILDER RIGHTS',
			"\$enablepermissioncheck = getDolGlobalInt('MYMODULE_ENABLE_PERMISSION_CHECK');",
			'if ($enablepermissioncheck) {',
			"\t\$permissiontodelete = \$user->hasRight('mymodule', 'myobject', 'delete') /* BEGIN MODULEBUILDER STATUS */ || (\$permissiontoadd && \$object->status == 0) /* END MODULEBUILDER STATUS */;",
			'} else {',
			"\t\$permissiontodelete = 1;",
			'}',
			'// END MODULEBUILDER RIGHTS',
			'',
		));

		$rendered = RightsBlockRenderer::renderFile($template, RightsConfig::custom('', array('read' => 'read', 'write' => 'write', 'delete' => 'write')));

		$this->assertStringContainsString(
			"\$permissiontodelete = \$user->hasRight('mymodule', 'myobject', 'write') /* BEGIN MODULEBUILDER STATUS */ || (\$permissiontoadd && \$object->status == 0) /* END MODULEBUILDER STATUS */;",
			$rendered
		);
	}

	/**
	 * @return array<string,array{0:string,1:RightsConfig}>
	 */
	public function unexpectedShapeProvider(): array
	{
		$wrap = static function (string $inner): string {
			return "<?php\n// BEGIN MODULEBUILDER RIGHTS\n".$inner."// END MODULEBUILDER RIGHTS\n";
		};

		return array(
			'switch without else' => array($wrap("if (\$enablepermissioncheck) {\n\t\$permissiontoread = 1;\n}\n"), RightsConfig::auto()),
			'switch without else, mode none' => array($wrap("if (\$enablepermissioncheck) {\n\t\$permissiontoread = 1;\n}\n"), RightsConfig::none()),
			'check left by the open branch' => array(
				$wrap("if (\$enablepermissioncheck) {\n\t\$permissiontoread = 1;\n} else {\n\t\$permissiontoread = \$user->hasRight('mymodule', 'myobject', 'read');\n}\n"),
				RightsConfig::none()
			),
			'unmapped code' => array($wrap("\$permissiontoexport = \$user->hasRight('mymodule', 'myobject', 'export');\n"), RightsConfig::auto()),
			'unbalanced marker' => array("<?php\n// BEGIN MODULEBUILDER RIGHTS\n\$permissiontoread = 1;\n", RightsConfig::auto()),
			'unbalanced marker, crlf' => array("<?php\r\n// BEGIN MODULEBUILDER RIGHTS\r\n\$permissiontoread = 1;\r\n", RightsConfig::auto()),
			'guard without open branch, mode none' => array($wrap("if (!\$user->hasRight('mymodule', 'myobject', 'write')) {\n\taccessforbidden();\n}\n"), RightsConfig::none()),
			'double quoted check left by the open branch' => array(
				$wrap("if (\$enablepermissioncheck) {\n\t\$permissiontoread = 1;\n} else {\n\t\$permissiontoread = \$user->hasRight(\"mymodule\", \"myobject\", \"read\");\n}\n"),
				RightsConfig::none()
			),
			'unspaced check' => array($wrap("\$permissiontoread = \$user->hasRight('mymodule','myobject','read');\n"), RightsConfig::auto()),
			'rights property' => array($wrap("\$permissiontoread = \$user->rights->mymodule->myobject->read;\n"), RightsConfig::auto()),
		);
	}

	/**
	 * A block the renderer does not understand must stop the generation, never yield a page with unexpected checks.
	 *
	 * @dataProvider unexpectedShapeProvider
	 * @param string       $template Template content
	 * @param RightsConfig $config   Rights configuration
	 * @return void
	 */
	public function testUnexpectedShapeThrows(string $template, RightsConfig $config)
	{
		$this->expectException(\RuntimeException::class);
		RightsBlockRenderer::renderFile($template, $config);
	}

	/**
	 * @return void
	 */
	public function testFileWithoutMarkersIsUnchanged()
	{
		$content = $this->readTemplate('myobject_card.php');
		$content = (string) preg_replace('/^\h*\/\/ (BEGIN|END) MODULEBUILDER RIGHTS\h*\R/m', '', $content);

		$this->assertSame($content, RightsBlockRenderer::renderFile($content, RightsConfig::none()));
		$this->assertSame($content, RightsBlockRenderer::renderFile($content, RightsConfig::auto()));
	}

	/**
	 * @return void
	 */
	public function testRenderMenuPerms()
	{
		$this->assertSame('1', RightsBlockRenderer::renderMenuPerms(RightsConfig::none(), 'read'));
		$this->assertSame('$user->hasRight("mymodule", "myobject", "write")', RightsBlockRenderer::renderMenuPerms(RightsConfig::auto(), 'write'));
		$this->assertSame(
			'$user->hasRight("mymodule", "parent", "read")',
			RightsBlockRenderer::renderMenuPerms(RightsConfig::custom('parent', array('read' => 'read', 'write' => 'read', 'delete' => 'read')), 'write')
		);
	}
}
