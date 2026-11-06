<?php
/* Copyright (C) 2026		Quentin VIAL--GOUTEYRON	<quentin.vial-gouteyron@atm-consulting.fr>
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

/**
 *      \file       test/phpunit/MenusStandardLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/menu.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/menus/standard/eldy.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/menus/standard/auguria.lib.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class MenusStandardLibTest extends CommonClassTest
{
	/**
	 * Build the top menu of a standard menu manager with a module hooked on menuTopMenuItems.
	 *
	 * @param	string					$function		'print_eldy_menu' or 'print_auguria_menu'
	 * @param	int						$answer			Value returned by the hook
	 * @param	array<array<string,mixed>>	$entries	Entries returned by the hook into ->results
	 * @return	array<array<string,mixed>>				Entries of the top menu
	 */
	private function buildTopMenuWithHook($function, $answer, $entries)
	{
		global $hookmanager;

		$hook = new class {
			/** @var array<array<string,mixed>> */
			public $results = array();
			/** @var int */
			public $answer = 0;
			/** @var array<array<string,mixed>> */
			public $entries = array();
			/** @var array<string,mixed> */
			public $receivedparameters = array();
			/**
			 * @param array<string,mixed>	$parameters	Hook parameters
			 * @return int
			 */
			public function menuTopMenuItems($parameters)
			{
				$this->receivedparameters = $parameters;
				$this->results = $this->entries;
				return $this->answer;
			}
		};
		$hook->answer = $answer;
		$hook->entries = $entries;

		$savhookmanager = $hookmanager;
		$hookmanager = new HookManager($this->savdb);
		$hookmanager->contextarray = array('main');
		$hookmanager->hooks['main']['mytestmodule'] = $hook;
		$hookmanager->hooksSorted['main']['50:mytestmodule'] = $hook;

		$tabMenu = array();
		$menu = new Menu();
		$function($this->savdb, '', 0, $tabMenu, $menu, 1, 'top');

		$hookmanager = $savhookmanager;

		$this->assertSame('top', $hook->receivedparameters['mode'], 'The hook receives the mode of the menu');

		return $menu->liste;
	}

	/**
	 * A module adds an entry to the top menu, or replaces the whole top menu, with the hook menuTopMenuItems.
	 *
	 * @return void
	 */
	public function testTopMenuHookAddsAndReplacesEntries()
	{
		global $conf, $user, $langs;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;

		$entry = array('url' => '/mytestmodule/index.php', 'titre' => 'MyTestModule', 'level' => 0, 'enabled' => 1, 'target' => '', 'mainmenu' => 'mytestmodule', 'leftmenu' => '', 'position' => 1, 'id' => 'mainmenu', 'idsel' => 'mytestmodule', 'classname' => 'class="tmenu"', 'prefix' => '');

		foreach (array('print_eldy_menu', 'print_auguria_menu') as $function) {
			$withouthook = $this->buildTopMenuWithHook($function, 0, array());
			$this->assertNotEmpty($withouthook, $function.': the top menu has entries');

			$added = $this->buildTopMenuWithHook($function, 0, array($entry));
			$this->assertCount(count($withouthook) + 1, $added, $function.': hook returning 0 adds its entries');
			$index = array_search('mytestmodule', array_column($added, 'mainmenu'));
			$this->assertNotFalse($index, $function.': the added entry is in the top menu');
			foreach ($added as $key => $val) {
				if ($key < $index) {
					$this->assertLessThanOrEqual(1, $val['position'], $function.': the added entry is sorted on its position');
				} elseif ($key > $index) {
					$this->assertGreaterThanOrEqual(1, $val['position'], $function.': the added entry is sorted on its position');
				}
			}

			$replaced = $this->buildTopMenuWithHook($function, 1, array($entry));
			$this->assertCount(1, $replaced, $function.': hook returning 1 replaces the top menu');
			$this->assertSame('mytestmodule', $replaced[0]['mainmenu']);
		}
	}
}
