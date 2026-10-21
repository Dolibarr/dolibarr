<?php
/* Copyright (C) 2026 ATM Consulting
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
 *      \file       test/phpunit/ReceptionLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test of the line cell helpers of reception.lib.php.
 *		\remarks	To run this script as CLI:  phpunit ReceptionLibTest.php
 */

global $conf,$user,$langs,$db;

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/reception.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/entrepot.class.php';
require_once dirname(__FILE__).'/../../htdocs/reception/class/reception.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.commande.dispatch.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ReceptionLibTest extends CommonClassTest
{
	/**
	 * @param	int		$receptionId	Id of the reception of the line
	 * @return	CommandeFournisseurDispatch
	 */
	private function makeLine(int $receptionId): CommandeFournisseurDispatch
	{
		$line = new CommandeFournisseurDispatch($this->savdb);
		$line->id = 11;
		$line->fk_reception = $receptionId;
		$line->fk_commandefourndet = 42;
		$line->fk_product = 0;
		$line->product_type = Product::TYPE_PRODUCT;
		$line->label = 'Free line';
		$line->description = 'Free description';
		$line->qty_asked = 8;

		return $line;
	}

	/**
	 * @return void
	 */
	public function testGetReceptionLineProductCellIsEmptyWithoutHead(): void
	{
		$out = getReceptionLineProductCell($this->savdb, new Form($this->savdb), $this->savlangs, $this->savlangs, $this->makeLine(1), 0, false);

		$this->assertSame("<td class=\"linecoldescription\"></td>\n", $out);
	}

	/**
	 * @return void
	 */
	public function testGetReceptionLineProductCellShowsFreeTextLabel(): void
	{
		$out = getReceptionLineProductCell($this->savdb, new Form($this->savdb), $this->savlangs, $this->savlangs, $this->makeLine(1), 0, true);

		$this->assertStringContainsString('<strong>Free line</strong>', $out);
	}

	/**
	 * Without purchase order there is no "Qty in other receptions" column.
	 *
	 * @return void
	 */
	public function testGetReceptionLineQtyOrderedCellsWithoutOriginHasOnlyQtyOrdered(): void
	{
		$out = getReceptionLineQtyOrderedCells($this->savdb, new Form($this->savdb), $this->savlangs, $this->makeLine(1), array(), 0, true);

		$this->assertSame('<td class="center linecolqty">8</td>', $out);
	}

	/**
	 * @return void
	 */
	public function testGetReceptionLineQtyOrderedCellsAreEmptyWithoutHead(): void
	{
		$out = getReceptionLineQtyOrderedCells($this->savdb, new Form($this->savdb), $this->savlangs, $this->makeLine(1), array(), 5, false);

		$this->assertSame('<td class="center linecolqty"></td><td class="center nowrap linecolqtyinotherreceptions"></td>', $out);
	}

	/**
	 * Quantities of the reception of the line itself are not counted.
	 *
	 * @return void
	 */
	public function testGetReceptionLineQtyOrderedCellsSumsOnlyOtherReceptions(): void
	{
		global $conf;

		$conf->cache['reception'][901] = new Reception($this->savdb);
		$conf->cache['reception'][902] = new Reception($this->savdb);
		$alreadySent = array(
			42 => array(
				1 => array('reception_ref' => 'R1', 'reception_id' => 901, 'warehouse' => 0, 'qty' => 3, 'date_valid' => '', 'date_delivery' => ''),
				2 => array('reception_ref' => 'R2', 'reception_id' => 902, 'warehouse' => 0, 'qty' => 2, 'date_valid' => '', 'date_delivery' => ''),
				3 => array('reception_ref' => 'R3', 'reception_id' => 900, 'warehouse' => 0, 'qty' => 100, 'date_valid' => '', 'date_delivery' => ''),
			),
			43 => array(
				4 => array('reception_ref' => 'R4', 'reception_id' => 901, 'warehouse' => 0, 'qty' => 50, 'date_valid' => '', 'date_delivery' => ''),
			),
		);

		$out = getReceptionLineQtyOrderedCells($this->savdb, new Form($this->savdb), $this->savlangs, $this->makeLine(900), $alreadySent, 5, true);
		unset($conf->cache['reception'][901], $conf->cache['reception'][902]);

		$this->assertSame(1, preg_match('/linecolqtyinotherreceptions">(<[^>]*>)*5\b/', $out));
	}
}
