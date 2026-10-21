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
 *      \file       test/phpunit/ProductLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test of the lot/serial lines toggle helpers of product.lib.php.
 *		\remarks	To run this script as CLI:  phpunit ProductLibTest.php
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
require_once dirname(__FILE__).'/../../htdocs/core/lib/product.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/stock/class/productlot.class.php';
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
class ProductLibTest extends CommonClassTest
{
	/**
	 * @return void
	 */
	public function testGetBatchLinesToggleHeaderOffersHideWhenShownByDefault(): void
	{
		$langs = $this->savlangs;

		$out = getBatchLinesToggleHeader($langs, true);

		$this->assertSame(1, preg_match('/id="batchlines_show_all"[^>]*class="[^"]*hideobject/', $out));
		$this->assertSame(0, preg_match('/id="batchlines_hide_all"[^>]*class="[^"]*hideobject/', $out));
	}

	/**
	 * @return void
	 */
	public function testGetBatchLinesToggleHeaderOffersShowWhenHiddenByDefault(): void
	{
		$langs = $this->savlangs;

		$out = getBatchLinesToggleHeader($langs, false);

		$this->assertSame(0, preg_match('/id="batchlines_show_all"[^>]*class="[^"]*hideobject/', $out));
		$this->assertSame(1, preg_match('/id="batchlines_hide_all"[^>]*class="[^"]*hideobject/', $out));
	}

	/**
	 * Translations are already HTML encoded by Translate::trans() and must not be encoded twice.
	 *
	 * @return void
	 */
	public function testGetBatchLinesToggleHeaderDoesNotEncodeTranslationsTwice(): void
	{
		$langs = $this->savlangs;
		$savedTranslation = $langs->tab_translate['HideLots'] ?? null;
		$langs->tab_translate['HideLots'] = 'Hide & fold lots';

		$out = getBatchLinesToggleHeader($langs, true);
		$langs->tab_translate['HideLots'] = $savedTranslation;

		$this->assertStringContainsString('Hide &amp; fold lots', $out);
		$this->assertStringNotContainsString('&amp;amp;', $out);
	}

	/**
	 * @return void
	 */
	public function testGetBatchLineToggleLinkIconFollowsDefaultState(): void
	{
		$shown = getBatchLineToggleLink('exp12', true);
		$hidden = getBatchLineToggleLink('exp12', false);

		$this->assertStringContainsString('data-batchtoggle="exp12"', $shown);
		$this->assertSame(1, preg_match('/fa-plus-square[^"]*batchlines_icon_collapsed hideobject/', $shown));
		$this->assertSame(0, preg_match('/batchlines_icon_expanded hideobject/', $shown));
		$this->assertSame(1, preg_match('/fa-minus-square[^"]*batchlines_icon_expanded hideobject/', $hidden));
		$this->assertSame(0, preg_match('/batchlines_icon_collapsed hideobject/', $hidden));
	}

	/**
	 * @return void
	 */
	public function testGetBatchLineChildRowAttributesHidesRowOnlyWhenHiddenByDefault(): void
	{
		$this->assertSame('data-batchgroup="rcp7"', getBatchLineChildRowAttributes('rcp7', true));
		$this->assertSame('data-batchgroup="rcp7" style="display: none"', getBatchLineChildRowAttributes('rcp7', false));
	}

	/**
	 * The group key must never break out of its HTML attribute.
	 *
	 * @return void
	 */
	public function testGroupKeyIsEscaped(): void
	{
		$key = '"><script>x</script>';

		$this->assertStringNotContainsString('<script>', getBatchLineToggleLink($key, true));
		$this->assertStringNotContainsString('<script>', getBatchLineChildRowAttributes($key, true));
	}

	/**
	 * @return void
	 */
	public function testGetBatchLotNomUrlLinksToRecordedLot(): void
	{
		global $conf;
		$db = $this->savdb;
		$user = $this->savuser;
		$langs = $this->savlangs;

		$product = new Product($db);
		$product->ref = 'PHPUNIT_BATCHLINES_'.dol_print_date(dol_now(), 'dayhourlog');
		$product->label = $product->ref;
		$product->status_batch = 2;
		$this->assertGreaterThan(0, $product->create($user));

		$productlot = new Productlot($db);
		$productlot->fk_product = $product->id;
		$productlot->batch = 'SN-PHPUNIT-1';
		$this->assertGreaterThan(0, $productlot->create($user));

		unset($conf->cache['productlot']);
		$out = getBatchLotNomUrl($db, new Form($db), $langs, $product->id, 'SN-PHPUNIT-1', '', '');

		$this->assertStringContainsString('productlot_card.php?id='.$productlot->id, $out);
	}

	/**
	 * @return void
	 */
	public function testGetBatchLotNomUrlFallsBackToEscapedTooltipWhenLotIsNotRecorded(): void
	{
		global $conf;
		$db = $this->savdb;
		$langs = $this->savlangs;

		unset($conf->cache['productlot']);
		$out = getBatchLotNomUrl($db, new Form($db), $langs, 0, '<b>SN-UNKNOWN</b>', '', '');

		$this->assertStringNotContainsString('productlot_card.php', $out);
		$this->assertStringNotContainsString('<b>SN-UNKNOWN</b>', $out);
		$this->assertStringContainsString('SN-UNKNOWN', $out);
	}
}
