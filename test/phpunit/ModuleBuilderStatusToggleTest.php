<?php
/* Copyright (C) 2026 Quentin VIAL--GOUTEYRON <quentin.vial-gouteyron@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/ModuleBuilderStatusToggleTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for the "manage statuses" toggle (status-code prune) in ModuleBuilder.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderStatusToggleTest
 *
 * Tests that the STATUS-anchored regions of the ModuleBuilder templates are pruned
 * correctly (no residual status reference, still PHP-valid) and that the markers are
 * well-formed (balanced, never nested).
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderStatusToggleTest extends CommonClassTest
{
	/**
	 * @var string Absolute path to the object class template.
	 */
	const CLASS_TPL = __DIR__.'/../../htdocs/modulebuilder/template/class/myobject.class.php';

	/**
	 * @var string Absolute path to the object card template.
	 */
	const CARD_TPL = __DIR__.'/../../htdocs/modulebuilder/template/myobject_card.php';

	/**
	 * @var string Temporary module directory, removed in tearDown()
	 */
	private $tmpDir = '';

	/**
	 * Copy both templates into a temporary module tree, prune it with pruneModuleBuilderStatusRegions()
	 * and return the path of the pruned copy of $srcTpl.
	 *
	 * @param	string	$srcTpl		self::CLASS_TPL or self::CARD_TPL
	 * @return	string				Absolute path to the pruned copy
	 */
	private function pruneToTmp($srcTpl)
	{
		$this->tmpDir = sys_get_temp_dir().'/mbstatus_'.getmypid();
		dol_mkdir($this->tmpDir.'/class');
		$classFile = $this->tmpDir.'/class/myobject.class.php';
		$cardFile = $this->tmpDir.'/myobject_card.php';
		copy(self::CLASS_TPL, $classFile);
		copy(self::CARD_TPL, $cardFile);
		$this->assertSame(0, pruneModuleBuilderStatusRegions($this->tmpDir, 'MyObject'), 'pruneModuleBuilderStatusRegions() failed');
		return ($srcTpl === self::CARD_TPL ? $cardFile : $classFile);
	}

	/**
	 * Remove the temporary module tree, even when an assertion failed.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		if ($this->tmpDir !== '' && is_dir($this->tmpDir)) {
			$nbdeleted = 0;
			dol_delete_dir_recursive($this->tmpDir, 0, 1, 0, $nbdeleted, 0, 1);
		}
		$this->tmpDir = '';
		parent::tearDown();
	}

	/**
	 * After prune, the class template must contain no residual status reference.
	 *
	 * @return void
	 */
	public function testPruneRemovesAllStatusRegionsInClass()
	{
		$tmp = $this->pruneToTmp(self::CLASS_TPL);
		$content = file_get_contents($tmp);
		$this->assertSame(0, preg_match('/STATUS_|->status\b|LibStatut|getLabelStatus/', $content), 'Residual status reference found in pruned class');
	}

	/**
	 * After prune, the status field entry must be gone from the $fields array.
	 *
	 * @return void
	 */
	public function testPruneRemovesStatusFieldEntry()
	{
		$tmp = $this->pruneToTmp(self::CLASS_TPL);
		$content = file_get_contents($tmp);
		$this->assertSame(0, preg_match("/'status'\s*=>\s*array/", $content), "Status field entry still present in \$fields after prune");
	}

	/**
	 * The pruned class template must still be valid PHP.
	 *
	 * @return void
	 */
	public function testPrunedClassIsPhpValid()
	{
		$tmp = $this->pruneToTmp(self::CLASS_TPL);
		$out = array();
		$code = 0;
		exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
		$this->assertSame(0, $code, 'Pruned class fails php -l: '.implode("\n", $out));
	}

	/**
	 * Without prune (status ON), all status regions must remain in the class template.
	 *
	 * @return void
	 */
	public function testStatusOnKeepsAllRegions()
	{
		$content = file_get_contents(self::CLASS_TPL);
		$this->assertStringContainsString('STATUS_VALIDATED', $content);
		foreach (array('function validate', 'function setDraft', 'function cancel', 'function reopen') as $needle) {
			$this->assertStringContainsString($needle, $content);
		}
	}

	/**
	 * After prune, the card template must contain no residual status reference.
	 *
	 * @return void
	 */
	public function testPruneRemovesCardStatusSites()
	{
		$tmp = $this->pruneToTmp(self::CARD_TPL);
		$content = file_get_contents($tmp);
		// Runtime status references only. The "// Actions ... confirm_setdraft, confirm_reopen" comment
		// documents the core actions_addupdatedelete.inc.php include and is intentionally kept:
		// those core actions exist regardless of this feature and are simply never triggered without buttons.
		$this->assertSame(0, preg_match('/->status\b|STATUS_DRAFT\b|STATUS_VALIDATED\b|STATUS_CANCELED\b|STATUS_ENABLED\b/', $content), 'Residual status reference found in pruned card');
	}

	/**
	 * The pruned card template must still be valid PHP.
	 *
	 * @return void
	 */
	public function testPrunedCardIsPhpValid()
	{
		$tmp = $this->pruneToTmp(self::CARD_TPL);
		$out = array();
		$code = 0;
		exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
		$this->assertSame(0, $code, 'Pruned card fails php -l: '.implode("\n", $out));
	}

	/**
	 * STATUS markers must be balanced and never nested in either template.
	 *
	 * @return void
	 */
	public function testNoNestedStatusMarkers()
	{
		foreach (array(self::CLASS_TPL, self::CARD_TPL) as $tpl) {
			$content = file_get_contents($tpl);
			$depth = 0;
			$maxdepth = 0;
			$tokens = preg_split('/(\/\*\s*(?:BEGIN|END) MODULEBUILDER STATUS\s*\*\/)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
			foreach ($tokens as $token) {
				if (strpos($token, 'BEGIN MODULEBUILDER STATUS') !== false) {
					$depth++;
					$maxdepth = max($maxdepth, $depth);
				} elseif (strpos($token, 'END MODULEBUILDER STATUS') !== false) {
					$depth--;
				}
			}
			$this->assertSame(0, $depth, 'Unbalanced STATUS markers in '.basename($tpl));
			$this->assertLessThanOrEqual(1, $maxdepth, 'Nested STATUS markers in '.basename($tpl));
		}
	}
}
