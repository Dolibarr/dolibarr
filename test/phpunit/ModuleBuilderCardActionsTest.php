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
 * \file    test/phpunit/ModuleBuilderCardActionsTest.php
 * \ingroup modulebuilder
 * \brief   PHPUnit test for ModuleBuilder card action buttons selection (#32460 item 10): unselected
 *          standalone actions (send/modify/clone/delete) must be pruned from the generated card page.
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/modulebuilder.lib.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/NamingContract.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class ModuleBuilderCardActionsTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanTypeMismatchArgument
 */
class ModuleBuilderCardActionsTest extends CommonClassTest
{
	/**
	 * Substitute the real card template with a NamingContract and return the generated file path.
	 *
	 * @param	string	$base	Temp dir to write into
	 * @return	string			Path of the generated card file
	 */
	private function generateCard($base)
	{
		$nc = new NamingContract('cardactmod', 'CardObj');
		dol_delete_dir_recursive($base);
		dol_mkdir($base);
		$cardfile = $base.'/cardobj_card.php';
		file_put_contents($cardfile, $nc->applyTo(file_get_contents(DOL_DOCUMENT_ROOT.'/modulebuilder/template/myobject_card.php')));
		return $cardfile;
	}

	/**
	 * Real generation path: substitute the template, then prune the actions the user did not select with
	 * removePatternFromFile() (exactly what initobject does). This is the regression guard that matters:
	 * it proves the template anchors wrap each button (and its helper lines) tightly enough that pruning an
	 * action leaves no orphan variable and keeps the generated card valid PHP, while selected actions and
	 * the unselected actions' anchors behave as expected. The initobject page action itself is not
	 * unit-testable, so the prune mechanism it relies on is exercised here on the real template.
	 *
	 * @return void
	 */
	public function testUnselectedCardActionsArePruned()
	{
		$base = sys_get_temp_dir().'/mbcardact_prune_'.getmypid();
		$cardfile = $this->generateCard($base);

		// User keeps only send + modify; clone + delete must be pruned.
		$enabled = filterEnabledTabs(array('send', 'modify'), getModuleBuilderCardActions());
		foreach (getModuleBuilderCardActions() as $actkey => $actinfo) {
			if (!in_array($actkey, $enabled, true)) {
				$this->assertTrue(removePatternFromFile($cardfile, '/\h*\/\/ BEGIN MODULEBUILDER ACTIONBUTTON '.$actinfo['marker'].'.*?\/\/ END MODULEBUILDER ACTIONBUTTON '.$actinfo['marker'].'\s*/s'), 'Prune '.$actkey);
			}
		}

		$out = file_get_contents($cardfile);

		// Kept actions still present.
		$this->assertStringContainsString('MODULEBUILDER ACTIONBUTTON SEND', $out, 'send kept');
		$this->assertStringContainsString('MODULEBUILDER ACTIONBUTTON MODIFY', $out, 'modify kept');
		$this->assertStringContainsString("trans('SendMail')", $out, 'send button kept');

		// Pruned actions fully gone, including their helper setup lines (no orphan variables).
		$this->assertStringNotContainsString('MODULEBUILDER ACTIONBUTTON CLONE', $out, 'clone marker pruned');
		$this->assertStringNotContainsString('MODULEBUILDER ACTIONBUTTON DELETE', $out, 'delete marker pruned');
		// '&action=clone&token=' is unique to the clone button (the confirm_clone popup uses a different URL).
		$this->assertStringNotContainsString('&action=clone&token=', $out, 'clone button pruned');
		$this->assertStringNotContainsString('$deleteUrl', $out, 'delete helper vars pruned (no orphan)');

		// Generated file is still valid PHP.
		$output = array();
		$ret = 0;
		exec('php -l '.escapeshellarg($cardfile).' 2>&1', $output, $ret);
		$this->assertSame(0, $ret, 'Pruned card page is valid PHP: '.implode("\n", $output));

		dol_delete_dir_recursive($base);
	}
}
