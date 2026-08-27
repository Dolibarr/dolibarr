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
}
