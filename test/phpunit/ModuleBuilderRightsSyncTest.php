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
 *      \file       test/phpunit/ModuleBuilderRightsSyncTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ModuleBuilder permissions sync service
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/SyncReport.class.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/RightsSyncCommand.class.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/PermissionsBlock.class.php';
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
class ModuleBuilderRightsSyncTest extends CommonClassTest
{
	/** @var string[] Fixture files to remove on tearDown */
	private $fixtures = array();

	/**
	 * Build a throwaway descriptor whose permissions block holds $inner.
	 *
	 * @param string $inner Raw content to put between the BEGIN and END markers
	 * @return string Path to the fixture file
	 */
	private function makeDescriptorFixture(string $inner): string
	{
		$dir = sys_get_temp_dir().'/mbrightssync'.getmypid();
		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}
		$path = $dir.'/modFixture'.uniqid().'.php';
		$content = "<?php\nclass modFixture\n{\n\tpublic \$rights = array();\n\tpublic \$numero = 500000;\n\n\tpublic function initRights()\n\t{\n\t\t\$r = 0;\n\t\t"
			.PermissionsBlock::BEGIN_MARKER."\n".$inner."\t\t".PermissionsBlock::END_MARKER."\n\t}\n}\n";
		file_put_contents($path, $content);
		$this->fixtures[] = $path;
		return $path;
	}

	/**
	 * Check that a rendered permissions block is valid PHP.
	 *
	 * @param string $renderedBlock Block content produced by PermissionsBlock::render()
	 * @return int 0 when the block parses
	 */
	private function lintRenderedBlock(string $renderedBlock): int
	{
		if (!function_exists('exec')) {
			$this->markTestSkipped('exec() is disabled, cannot lint the generated block');
		}
		$dir = sys_get_temp_dir().'/mbrightssync'.getmypid();
		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}
		$tmp = $dir.'/rendered'.uniqid().'.php';
		$this->fixtures[] = $tmp;
		file_put_contents($tmp, "<?php\nclass T { public \$rights = array(); public \$numero = 500000; function f() { \$r = 0;\n".$renderedBlock."} }\n");

		$output = array();
		$code = 0;
		exec('php -l '.escapeshellarg($tmp).' 2>&1', $output, $code);
		if ($code !== 0) {
			$this->fail('Rendered block does not parse: '.implode("\n", $output));
		}

		return $code;
	}

	/**
	 * Remove fixture files created by makeDescriptorFixture().
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
	 * A report with conflicts reports no write and maps to the legacy -1 return code.
	 *
	 * @return void
	 */
	public function testSyncReportConflictSemantics()
	{
		$clean = new SyncReport(12, 0, array(), array());
		$this->assertFalse($clean->hasConflicts());
		$this->assertFalse($clean->hasWarnings());
		$this->assertFalse($clean->isNoop());
		$this->assertSame(1, $clean->toLegacyReturnCode());

		$blocked = new SyncReport(0, 0, array('dynamic code detected'), array());
		$this->assertTrue($blocked->hasConflicts());
		$this->assertTrue($blocked->isNoop());
		$this->assertSame(-1, $blocked->toLegacyReturnCode());

		// A warning is non-blocking: the write happened, the legacy code stays 1
		$warned = new SyncReport(12, 0, array(), array('right #0 carries unsupported index 2'));
		$this->assertTrue($warned->hasWarnings());
		$this->assertFalse($warned->hasConflicts());
		$this->assertSame(1, $warned->toLegacyReturnCode());

		$skippedReport = new SyncReport(0, 1, array(), array());
		$this->assertSame(1, $skippedReport->skipped);
		$this->assertTrue($skippedReport->isNoop());
	}

	/**
	 * Each factory pins one scope/action pair, and the constructor refuses an incomplete command.
	 *
	 * @return void
	 */
	public function testRightsSyncCommandFactories()
	{
		$perms = array(array(1 => 'Read', 4 => 'myobject', 5 => 'read'));

		$cmd = RightsSyncCommand::forObjectCreation('MyModule', '/tmp/modMyModule.class.php', $perms, 'MyObject');
		$this->assertSame(RightsSyncCommand::SCOPE_OBJECT, $cmd->scope);
		$this->assertSame(RightsSyncCommand::ACTION_ADD, $cmd->actionType);
		$this->assertSame('MyObject', $cmd->objectName);
		$this->assertNull($cmd->rightKey);

		$cmd = RightsSyncCommand::forObjectDeletion('MyModule', '/tmp/modMyModule.class.php', $perms, 'MyObject');
		$this->assertSame(RightsSyncCommand::SCOPE_OBJECT, $cmd->scope);
		$this->assertSame(RightsSyncCommand::ACTION_DELETE, $cmd->actionType);

		$cmd = RightsSyncCommand::forRightAddition('MyModule', '/tmp/modMyModule.class.php', $perms, 'myobject', 'Export MyObject', 'export');
		$this->assertSame(RightsSyncCommand::SCOPE_RIGHT, $cmd->scope);
		$this->assertSame(RightsSyncCommand::ACTION_ADD, $cmd->actionType);
		$this->assertSame('export', $cmd->rightCrud);

		$cmd = RightsSyncCommand::forRightUpdate('MyModule', '/tmp/modMyModule.class.php', $perms, 0, 'myobject', 'Read it', 'read');
		$this->assertSame(RightsSyncCommand::ACTION_UPDATE, $cmd->actionType);
		$this->assertSame(0, $cmd->rightKey);

		$cmd = RightsSyncCommand::forRightDeletion('MyModule', '/tmp/modMyModule.class.php', $perms, 0);
		$this->assertSame(RightsSyncCommand::ACTION_DELETE, $cmd->actionType);
		$this->assertSame('', $cmd->objectName);

		// An empty module name is never a valid target
		$this->expectException(\InvalidArgumentException::class);
		RightsSyncCommand::forRightDeletion('', '/tmp/modMyModule.class.php', $perms, 0);
	}

	/**
	 * A descriptor with no permissions markers is never rewritten.
	 *
	 * @return void
	 */
	public function testPermissionsBlockRefusesFileWithoutMarkers()
	{
		$dir = sys_get_temp_dir().'/mbrightssync'.getmypid();
		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}
		$path = $dir.'/modNoMarkers'.uniqid().'.php';
		$this->fixtures[] = $path;
		file_put_contents($path, "<?php\nclass modNoMarkers {}\n");

		$this->expectException(\RuntimeException::class);
		PermissionsBlock::fromFile($path);
	}

	/**
	 * The commented-out template block of a brand new module is not a conflict.
	 *
	 * @return void
	 */
	public function testPermissionsBlockAcceptsCommentedTemplate()
	{
		$inner = "\t\t/*\n\t\t\$o = 1;\n\t\t\$this->rights[\$r][0] = \$this->numero . sprintf(\"%02d\", (\$o * 10) + 1);\n"
			."\t\t\$this->rights[\$r][1] = 'Read objects of MyModule';\n\t\t\$r++;\n\t\t*/\n";
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture($inner));

		$this->assertSame(array(), $block->detectTextConflicts());
	}

	/**
	 * A generated block made only of rights assignments is rewritable.
	 *
	 * @return void
	 */
	public function testPermissionsBlockAcceptsGeneratedBlock()
	{
		$inner = "\t\t\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', (0 * 10) + 0 + 1);\n"
			."\t\t\$this->rights[\$r][1] = 'Read myobject object of Mymodule';\n"
			."\t\t\$this->rights[\$r][4] = 'myobject';\n"
			."\t\t\$this->rights[\$r][5] = 'read'; // trailing comment is fine\n"
			."\t\t\$r++;\n";
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture($inner));

		$this->assertSame(array(), $block->detectTextConflicts());
	}

	/**
	 * Dynamically built rights would be flattened into static lines, so the block is refused.
	 *
	 * @return void
	 */
	public function testPermissionsBlockRefusesDynamicRights()
	{
		$inner = "\t\tforeach (array('read', 'write') as \$crud) {\n"
			."\t\t\t\$this->rights[\$r][5] = \$crud;\n\t\t\t\$r++;\n\t\t}\n";
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture($inner));

		$conflicts = $block->detectTextConflicts();
		$this->assertNotEmpty($conflicts);
		$this->assertStringContainsString('foreach', $conflicts[0]);
	}

	/**
	 * A translated label cannot be reproduced by the renderer, so the block is refused.
	 *
	 * @return void
	 */
	public function testPermissionsBlockRefusesTranslatedLabel()
	{
		$inner = "\t\t\$this->rights[\$r][1] = \$langs->trans('ReadMyObject');\n\t\t\$r++;\n";
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture($inner));

		$conflicts = $block->detectTextConflicts();
		$this->assertNotEmpty($conflicts);
		$this->assertStringContainsString('$langs', $conflicts[0]);
	}

	/**
	 * Rendering groups rights per object and numbers them read/write/delete within each group.
	 *
	 * @return void
	 */
	public function testRenderNumbersRightsPerObject()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));
		$rendered = $block->render(array(
			array(1 => 'Read alpha', 4 => 'alpha', 5 => 'read'),
			array(1 => 'Delete alpha', 4 => 'alpha', 5 => 'delete'),
			array(1 => 'Read beta', 4 => 'beta', 5 => 'read'),
		));

		// First object gets stride 0, second gets stride 1
		$this->assertStringContainsString("sprintf('%02d', (0 * 10) + 0 + 1)", $rendered);
		$this->assertStringContainsString("sprintf('%02d', (0 * 10) + 2 + 1)", $rendered);
		$this->assertStringContainsString("sprintf('%02d', (1 * 10) + 0 + 1)", $rendered);
		// read comes before delete inside a group
		$this->assertLessThan(strpos($rendered, 'Delete alpha'), strpos($rendered, 'Read alpha'));
		$this->assertSame(3, substr_count($rendered, '$r++;'));
	}

	/**
	 * A label carrying an apostrophe must not break the generated descriptor.
	 *
	 * @return void
	 */
	public function testRenderEscapesLabels()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));
		$rendered = $block->render(array(
			array(1 => "Read l'objet", 4 => 'alpha', 5 => 'read'),
		));

		$this->assertStringContainsString("Read l\\'objet", $rendered);
		$this->assertSame(0, $this->lintRenderedBlock($rendered));
	}

	/**
	 * A label crafted to close the string literal stays inert data.
	 *
	 * @return void
	 */
	public function testRenderKeepsCraftedLabelInert()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));
		$rendered = $block->render(array(
			array(1 => "x'; echo 'pwned", 4 => 'alpha', 5 => 'read'),
		));

		$this->assertSame(0, $this->lintRenderedBlock($rendered));
		$this->assertStringNotContainsString("= 'x'; echo 'pwned';", $rendered);
	}

	/**
	 * Legacy indexes 2 and 3 are dropped with a warning instead of corrupting the descriptor.
	 *
	 * @return void
	 */
	public function testLegacyIndexesAreNormalizedWithAWarning()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));
		$permissions = array(
			array(1 => 'Read alpha', 2 => 'r', 3 => 0, 4 => 'alpha', 5 => 'read'),
		);

		$warnings = $block->detectRightsShapeWarnings($permissions);
		$this->assertNotEmpty($warnings);
		$this->assertStringContainsString('2', $warnings[0]);

		$rendered = $block->render($permissions);
		$this->assertStringNotContainsString('[2]', $rendered);
		$this->assertStringNotContainsString('[3]', $rendered);
		$this->assertSame(1, substr_count($rendered, '$r++;'));
		$this->assertSame(0, $this->lintRenderedBlock($rendered));
	}

	/**
	 * A right missing its object or crud cannot be rendered at all.
	 *
	 * @return void
	 */
	public function testRightMissingObjectOrCrudIsAConflict()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));

		$this->assertNotEmpty($block->detectRightsShapeConflicts(array(array(1 => 'Orphan'))));
		$this->assertSame(array(), $block->detectRightsShapeConflicts(array(array(1 => 'Ok', 4 => 'alpha', 5 => 'read'))));
	}

	/**
	 * Two rights of one object never collide on the same permission id.
	 *
	 * @return void
	 */
	public function testOffsetsStayUniqueWithCustomAndDuplicateCruds()
	{
		$block = PermissionsBlock::fromFile($this->makeDescriptorFixture(''));
		$rendered = $block->render(array(
			array(1 => 'Read alpha', 4 => 'alpha', 5 => 'read'),
			array(1 => 'Export alpha', 4 => 'alpha', 5 => 'export'),
			array(1 => 'Read alpha again', 4 => 'alpha', 5 => 'read'),
		));

		$matches = array();
		preg_match_all('/\+ (\d+) \+ 1\)/', $rendered, $matches);
		$this->assertSame(count($matches[1]), count(array_unique($matches[1])));
		$this->assertSame(3, substr_count($rendered, '$r++;'));
	}

	/**
	 * Writing replaces the block in one pass and leaves exactly one marker pair.
	 *
	 * @return void
	 */
	public function testWriteReplacesBlockInOnePass()
	{
		$path = $this->makeDescriptorFixture("\t\t// nothing yet\n");
		$block = PermissionsBlock::fromFile($path);
		$rendered = $block->render(array(array(1 => 'Read alpha', 4 => 'alpha', 5 => 'read')));

		$this->assertSame(1, $block->write($rendered));

		$after = (string) file_get_contents($path);
		$this->assertSame(1, substr_count($after, PermissionsBlock::BEGIN_MARKER));
		$this->assertSame(1, substr_count($after, PermissionsBlock::END_MARKER));
		$this->assertStringContainsString("\$this->rights[\$r][4] = 'alpha';", $after);
		$this->assertStringNotContainsString('// nothing yet', $after);

		// The block just written must itself be rewritable
		$reread = PermissionsBlock::fromFile($path);
		$this->assertSame(array(), $reread->detectTextConflicts());
	}

	/**
	 * A __(Key)__ or __[obj]__ pattern elsewhere in the descriptor must survive the write untouched.
	 *
	 * @return void
	 */
	public function testWriteDoesNotSubstituteTranslationPatterns()
	{
		$path = $this->makeDescriptorFixture('');
		$original = (string) file_get_contents($path);
		file_put_contents($path, str_replace(
			'class modFixture',
			"// descriptionlong: __(SomeTranslationKey)__\n// object: __[llx_societe:Societe:fetch:1]__\nclass modFixture",
			$original
		));

		$block = PermissionsBlock::fromFile($path);
		$block->write($block->render(array(array(1 => 'Read alpha', 4 => 'alpha', 5 => 'read'))));

		$after = (string) file_get_contents($path);
		$this->assertStringContainsString('__(SomeTranslationKey)__', $after);
		$this->assertStringContainsString('__[llx_societe:Societe:fetch:1]__', $after);
	}

	/**
	 * Whatever a label contains, reading the written descriptor back yields it verbatim.
	 *
	 * @return void
	 */
	public function testWrittenLabelsSurviveRoundTrip()
	{
		if (!function_exists('exec')) {
			$this->markTestSkipped('exec() is disabled, cannot lint the generated descriptor');
		}

		$labels = array(
			'Cost $1 per unit',
			"Backref \\1 test",
			"Read l'objet",
			'Path C:\\temp',
			"x'; echo 'pwned",
		);

		$dir = sys_get_temp_dir().'/mbrightssync'.getmypid();
		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}

		foreach ($labels as $i => $label) {
			$class = 'modRoundTrip'.getmypid().'x'.$i;
			$path = $dir.'/'.$class.'.php';
			$this->fixtures[] = $path;
			file_put_contents($path, "<?php\nclass ".$class." {\n\tpublic \$rights = array();\n\tpublic \$numero = 500000;\n\tpublic function initRights() {\n\t\t\$r = 0;\n\t\t"
				.PermissionsBlock::BEGIN_MARKER."\n\t\t".PermissionsBlock::END_MARKER."\n\t}\n}\n");

			$block = PermissionsBlock::fromFile($path);
			$block->write($block->render(array(array(1 => $label, 4 => 'alpha', 5 => 'read'))));

			$output = array();
			$code = 0;
			exec('php -l '.escapeshellarg($path).' 2>&1', $output, $code);
			$this->assertSame(0, $code, 'Generated descriptor does not parse: '.implode("\n", $output));

			require $path;
			$descriptor = new $class();
			$descriptor->initRights();
			$this->assertSame($label, $descriptor->rights[0][1]);
		}
	}

	/**
	 * Writing an empty block clears the section but keeps its markers.
	 *
	 * @return void
	 */
	public function testWriteEmptyBlockKeepsMarkers()
	{
		$path = $this->makeDescriptorFixture("\t\t\$this->rights[\$r][5] = 'read';\n\t\t\$r++;\n");
		$block = PermissionsBlock::fromFile($path);

		$this->assertSame(1, $block->write(''));

		$after = (string) file_get_contents($path);
		$this->assertStringNotContainsString('$this->rights', $after);
		$this->assertSame(2, substr_count($after, 'MODULEBUILDER PERMISSIONS'));
	}
}
