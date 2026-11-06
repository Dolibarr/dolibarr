<?php
/* Copyright (C) 2026		Quentin VIAL--GOUTEYRON		<quentin.vial-gouteyron@atm-consulting.fr>
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
 *      \file       test/phpunit/ModulesBoxesTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test for ModeleBoxes::showBox()
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/boxes/modules_boxes.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ModulesBoxesTest extends CommonClassTest
{
	/**
	 * Return a box with no file cache, so showBox() always renders
	 *
	 * @return ModeleBoxes
	 */
	private function getTestBox()
	{
		global $conf, $db;

		$conf->global->MAIN_ACTIVATE_FILECACHE = 0;
		$conf->global->MAIN_ACTIVATE_FILECACHE_DELAY = 0;

		$box = new ModeleBoxes($db);
		$box->box_id = 999999;
		$box->msgNoRecords = 'NoRecordFound';

		return $box;
	}

	/**
	 * A box with a title and no line is rendered on its title line only
	 *
	 * @return void
	 */
	public function testShowBoxEmptyIsCompact()
	{
		$box = $this->getTestBox();
		$box->urltoaddentry = '/societe/card.php?action=create';

		$out = $box->showBox(array('text' => 'My box'), array(), 1);

		$this->assertStringContainsString('class="box divboxtable boxdraggable boxempty"', $out);
		$this->assertSame(1, preg_match('/<th[^>]*>.*class="boxemptymsg".*<\/th>/s', $out));
		$this->assertStringContainsString('href="/societe/card.php?action=create"', $out);
		$this->assertStringNotContainsString('<td', $out);
	}

	/**
	 * A box with lines keeps its usual rendering
	 *
	 * @return void
	 */
	public function testShowBoxWithLinesIsNotCompact()
	{
		$box = $this->getTestBox();

		$out = $box->showBox(array('text' => 'My box'), array(array(array('text' => 'Line 1'))), 1);

		$this->assertStringContainsString('class="box divboxtable boxdraggable"', $out);
		$this->assertStringNotContainsString('boxempty', $out);
		$this->assertStringContainsString('Line 1', $out);
	}

	/**
	 * A box without title shows no message, as before
	 *
	 * @return void
	 */
	public function testShowBoxWithoutTitleShowsNoMessage()
	{
		$box = $this->getTestBox();

		$out = $box->showBox(array(), array(), 1);

		$this->assertStringNotContainsString('boxempty', $out);
	}
}
