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
 * \file    htdocs/modulebuilder/test/phpunit/ModuleBuilderStatusToggleTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for the "manage statuses" toggle (status-code prune) in ModuleBuilder.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Class ModuleBuilderStatusToggleTest
 *
 * Tests that the STATUS-anchored regions of the ModuleBuilder templates are pruned
 * correctly (no residual status reference, still PHP-valid) and that the markers are
 * well-formed (balanced, never nested).
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ModuleBuilderStatusToggleTest extends PHPUnit\Framework\TestCase // @phan-suppress-current-line PhanUndeclaredExtendedClass
{
	/**
	 * @var string Regex pruning every BEGIN/END MODULEBUILDER STATUS region (non-greedy).
	 */
	const STATUS_PRUNE_PATTERN = '/\h*\/\*\s*BEGIN MODULEBUILDER STATUS\s*\*\/.*?\/\*\s*END MODULEBUILDER STATUS\s*\*\/\s*/s';

	/**
	 * @var string Absolute path to the object class template.
	 */
	const CLASS_TPL = __DIR__.'/../../template/class/myobject.class.php';

	/**
	 * @var string Absolute path to the object card template.
	 */
	const CARD_TPL = __DIR__.'/../../template/myobject_card.php';

	/**
	 * Copy a template to a tmp file, prune its STATUS regions, return the tmp path.
	 *
	 * @param	string	$srcTpl		Absolute path to the source template
	 * @return	string				Absolute path to the pruned tmp file (caller must unlink)
	 */
	private function pruneToTmp($srcTpl)
	{
		$tmp = tempnam(sys_get_temp_dir(), 'mbstatus').'.php';
		copy($srcTpl, $tmp);
		removePatternFromFile($tmp, self::STATUS_PRUNE_PATTERN);
		return $tmp;
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
		unlink($tmp);
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
		unlink($tmp);
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
		unlink($tmp);
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
