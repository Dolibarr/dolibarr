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
 * \file    test/phpunit/ModuleBuilderMulticompanyTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for the ModuleBuilder multicompany option: the anchored ismultientitymanaged
 *          property of the main object class can be switched to 1, while the MyObjectLine class
 *          property (whose table has no entity column) stays at 0.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderMulticompanyTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderMulticompanyTest extends CommonClassTest
{
	/**
	 * @var string Absolute path to the object class template.
	 */
	const CLASS_TPL = __DIR__.'/../../htdocs/modulebuilder/template/class/myobject.class.php';

	/**
	 * Regex pattern used by modulebuilder/index.php to switch the multicompany flag on at generation.
	 *
	 * @var string
	 */
	const MULTICOMPANY_PATTERN = '/\/\* BEGIN MODULEBUILDER MULTICOMPANY \*\/\s*public \$ismultientitymanaged = 0;\s*\/\* END MODULEBUILDER MULTICOMPANY \*\//';

	/**
	 * The template must wrap the main object ismultientitymanaged property in MULTICOMPANY anchors, set to 0.
	 *
	 * @return void
	 */
	public function testTemplateHasMulticompanyAnchorOff()
	{
		$content = file_get_contents(self::CLASS_TPL);
		$this->assertSame(1, preg_match(self::MULTICOMPANY_PATTERN, $content), 'Main object class must have the MULTICOMPANY anchor with ismultientitymanaged = 0');
		// Two declarations exist by default (main object + MyObjectLine), both off.
		$this->assertSame(2, substr_count($content, 'ismultientitymanaged = 0;'), 'Both the object and its line class should default ismultientitymanaged to 0');
	}

	/**
	 * Applying the generation substitution must switch only the anchored (main object) property to 1,
	 * leaving the MyObjectLine property at 0 (its table has no entity column).
	 *
	 * @return void
	 */
	public function testMulticompanySwitchTargetsOnlyMainClass()
	{
		$content = file_get_contents(self::CLASS_TPL);
		$replacement = "/* BEGIN MODULEBUILDER MULTICOMPANY */\n\tpublic \$ismultientitymanaged = 1;\n\t/* END MODULEBUILDER MULTICOMPANY */";
		$count = 0;
		$result = preg_replace(self::MULTICOMPANY_PATTERN, $replacement, $content, -1, $count);

		$this->assertSame(1, $count, 'Exactly one (anchored) property must be switched');
		$this->assertSame(1, substr_count($result, 'ismultientitymanaged = 1;'), 'Only the main object class must be multi-entity managed');
		$this->assertSame(1, substr_count($result, 'ismultientitymanaged = 0;'), 'The MyObjectLine class property must stay at 0');
	}

	/**
	 * The class file with multicompany switched on must remain valid PHP.
	 *
	 * @return void
	 */
	public function testSwitchedClassIsValidPhp()
	{
		$content = file_get_contents(self::CLASS_TPL);
		$replacement = "/* BEGIN MODULEBUILDER MULTICOMPANY */\n\tpublic \$ismultientitymanaged = 1;\n\t/* END MODULEBUILDER MULTICOMPANY */";
		$result = preg_replace(self::MULTICOMPANY_PATTERN, $replacement, $content);

		$tmp = tempnam(sys_get_temp_dir(), 'mbmc').'.php';
		file_put_contents($tmp, $result);
		$output = array();
		$ret = 0;
		exec('php -l '.escapeshellarg($tmp).' 2>&1', $output, $ret);
		@unlink($tmp);
		$this->assertSame(0, $ret, 'Class with multicompany enabled must be valid PHP: '.implode("\n", $output));
	}
}
