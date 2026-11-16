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
 *      \file       test/phpunit/CommonFieldsViewTplTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/commonobject.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';

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
class CommonFieldsViewTplTest extends CommonClassTest
{
	/**
	 * Output commonfields_view.tpl.php for an object with one field in each column and a module hooked on printFieldView.
	 *
	 * @param	int		$answer		Value returned by the hook
	 * @return	array{0:string,1:array<array<string,mixed>>}	Output of the template and parameters received by the hook
	 */
	private function showFieldsWithHook($answer)
	{
		$conf = $this->savconf;
		$langs = $this->savlangs;
		$db = $this->savdb;
		$action = '';
		$form = new Form($db);
		$keyforbreak = 'note';

		$object = new class ($db) extends CommonObject {
			/** @var string */
			public $label = 'MyLabelValue';
			/** @var string */
			public $note = 'MyNoteValue';
			/**
			 * @param DoliDB $db Database handler
			 */
			public function __construct($db)
			{
				$this->db = $db;
				$this->fields = array(
					'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'position' => 10),
					'note' => array('type' => 'varchar(255)', 'label' => 'Note', 'enabled' => 1, 'visible' => 1, 'position' => 20),
				);
			}
		};

		$hook = new class {
			/** @var string */
			public $resprints = '';
			/** @var int */
			public $answer = 0;
			/** @var array<array<string,mixed>> */
			public $received = array();
			/**
			 * @param array<string,mixed>	$parameters	Hook parameters
			 * @return int
			 */
			public function printFieldView($parameters)
			{
				$this->received[] = $parameters;
				$this->resprints = '<tr class="hooked_'.$parameters['key'].'"><td>Hooked</td></tr>';
				return $this->answer;
			}
		};
		$hook->answer = $answer;

		$hookmanager = new HookManager($db);
		$hookmanager->contextarray = array('mytestcard');
		$hookmanager->hooks['mytestcard']['mytestmodule'] = $hook;
		$hookmanager->hooksSorted['mytestcard']['50:mytestmodule'] = $hook;

		ob_start();
		include dirname(__FILE__).'/../../htdocs/core/tpl/commonfields_view.tpl.php';
		$out = (string) ob_get_clean();

		return array($out, $hook->received);
	}

	/**
	 * A module can print content before the line of a field, or replace it, with the hook printFieldView.
	 *
	 * @return void
	 */
	public function testPrintFieldViewHook()
	{
		list($out, $received) = $this->showFieldsWithHook(0);
		$this->assertSame(array('label', 'note'), array_column($received, 'key'), 'The hook is called once per shown field');
		$this->assertSame(array('left', 'right'), array_column($received, 'column'));
		$this->assertSame('MyNoteValue', $received[1]['value']);
		$this->assertLessThan(strpos($out, 'fieldname_label'), strpos($out, 'hooked_label'), 'Hook returning 0 prints before the line of the field');
		$this->assertLessThan(strpos($out, 'fieldname_note'), strpos($out, 'hooked_note'));
		$this->assertStringContainsString('MyLabelValue', $out);
		$this->assertStringContainsString('MyNoteValue', $out);

		list($out) = $this->showFieldsWithHook(1);
		$this->assertStringContainsString('hooked_label', $out, 'Hook returning 1 replaces the line of the field');
		$this->assertStringContainsString('hooked_note', $out);
		$this->assertStringNotContainsString('MyLabelValue', $out);
		$this->assertStringNotContainsString('MyNoteValue', $out);
	}
}
