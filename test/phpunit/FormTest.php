<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/FormTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Helper class for testing product option generation.
 */
class FormProductOptionTest extends Form
{
	/**
	 * Build a product option by exposing the protected helper for unit tests.
	 *
	 * @param stdClass $product Product row object
	 * @return array{0:string,1:array<string,mixed>}
	 */
	public function buildProductOption($product)
	{
		$option = '';
		$optionJson = array();

		$this->constructProductListOption($product, $option, $optionJson, 0, 0, 1, '', 1);

		return array($option, $optionJson);
	}
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FormTest extends CommonClassTest
{
	/**
	 * testSelectProduitsList
	 *
	 * @return int
	 */
	public function testSelectProduitsList()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Form($db);
		$result = $localobject->select_produits_list('', 'productid', '', 5, 0, '', 1, 2, 1);

		$this->assertEquals(count($result), 5);
		print __METHOD__." count result=".count($result)."\n";

		$conf->global->ENTREPOT_EXTRA_STATUS = 1;

		// Exclude stock in warehouseinternal
		$result = $localobject->select_produits_list('', 'productid', '', 5, 0, '', 1, 2, 1, 0, '1', 0, '', 0, 'warehouseclosed,warehouseopen');
		$this->assertEquals(count($result), 5);
		print __METHOD__." count result=".count($result)."\n";

		return $result;
	}

	/**
	 * testProductOptionUsesFullEscapedTextForSearch
	 *
	 * @return void
	 */
	public function testProductOptionUsesFullEscapedTextForSearch()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->PRODUCT_MAX_LENGTH_COMBO = 48;
		$conf->global->PRODUCT_SHOW_ORIGIN_IN_COMBO = 0;
		$conf->global->PRODUCT_SHOW_DIMENSIONS_IN_COMBO = 0;
		$conf->global->PRODUCT_USE_UNITS = 0;

		$form = new FormProductOptionTest($db);
		$product = (object) array(
			'rowid' => 123,
			'ref' => 'P00489',
			'label' => 'PVC Pipe 1/2" Standard Quality Professional Grade Heavy Duty Construction Tool for Industrial Use - KEYWORD',
			'label_translated' => '',
			'description' => '',
			'description_translated' => '',
			'barcode' => '',
			'fk_country' => 0,
			'fk_product_type' => Product::TYPE_PRODUCT,
			'duration' => '',
			'price_by_qty_rowid' => '',
		);

		list($option) = $form->buildProductOption($product);

		$htmlMatches = array();
		$textMatches = array();
		$this->assertSame(1, preg_match('/ data-html="([^"]+)"/', $option, $htmlMatches));
		$this->assertSame(1, preg_match('/>(.*)<\/option>/s', $option, $textMatches));

		$visibleText = html_entity_decode($htmlMatches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$searchText = html_entity_decode($textMatches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

		$this->assertStringContainsString('PVC Pipe 1/2" Standard Quality', $visibleText);
		$this->assertStringNotContainsString('KEYWORD', $visibleText);
		$this->assertStringContainsString('PVC Pipe 1/2" Standard Quality', $searchText);
		$this->assertStringContainsString('KEYWORD', $searchText);
	}

	/**
	 * Check filtering, option keys and selection with shared, sales and purchase VAT rates.
	 *
	 * @param int|string|null $typevat Requested VAT type, null to omit the argument
	 * @param int $mode Option key mode
	 * @param bool $optionsonly Return only options
	 * @param int[] $expectedids Expected VAT row IDs
	 * @return void
	 * @dataProvider loadTvaProvider
	 */
	public function testLoadTvaTypes($typevat, $mode, $optionsonly, $expectedids)
	{
		global $mysoc;

		$form = $this->createVatForm();
		$cache = $form->cache_vatrates;
		$seller = clone $mysoc;
		$seller->country_code = 'CH';
		$seller->tva_assuj = 1;
		$selectedcode = ($typevat == 2 ? 'IPMat' : 'TVADueOPT');
		if ($typevat === null) {
			$html = $form->load_tva('tva_tx', '8.1 ('.$selectedcode.')', $seller, null, 0, 0, 1, $optionsonly, $mode);
		} else {
			$html = $form->load_tva('tva_tx', '8.1 ('.$selectedcode.')', $seller, null, 0, 0, 1, $optionsonly, $mode, $typevat);
		}

		$document = new DOMDocument();
		$document->loadHTML($html);
		$actualkeys = array();
		$selectedkeys = array();
		foreach ($document->getElementsByTagName('option') as $option) {
			$actualkeys[] = $option->getAttribute('value');
			if ($option->hasAttribute('selected')) {
				$selectedkeys[] = $option->getAttribute('value');
			}
		}
		$expectedkeys = array();
		$expectedselected = '';
		foreach ($expectedids as $id) {
			$rate = $cache[$id];
			$key = ($mode < 0 ? (string) $id : $rate['txtva'].($rate['nprtva'] ? '*' : '').($mode > 0 && $rate['code'] ? ' ('.$rate['code'].')' : ''));
			$expectedkeys[] = $key;
			if ($rate['code'] == $selectedcode) {
				$expectedselected = $key;
			}
		}
		$this->assertSame($expectedkeys, $actualkeys);
		$this->assertSame(array($expectedselected), $selectedkeys);
		$this->assertSame(count($expectedids), $form->num);
		$this->assertSame($optionsonly ? 0 : 1, $document->getElementsByTagName('select')->length);
		$this->assertSame($cache, $form->cache_vatrates);
	}

	/**
	 * Provide all supported VAT types and rendering modes, including an omitted type.
	 *
	 * @return array<string,array{0:int|string|null,1:int,2:bool,3:int[]}>
	 */
	public function loadTvaProvider()
	{
		$cases = array();
		foreach (array('default' => null, 'all' => 0, 'string-zero' => '0', 'sales' => 1, 'purchases' => 2) as $name => $typevat) {
			$ids = ($typevat == 1 ? array(1, 2, 3, 4) : ($typevat == 2 ? array(1, 2, 5, 6) : array(1, 2, 3, 4, 5, 6)));
			foreach (array(0, 1, -1) as $mode) {
				foreach (array(false, true) as $optionsonly) {
					$cases[$name.'-'.$mode.'-'.(int) $optionsonly] = array($typevat, $mode, $optionsonly, $ids);
				}
			}
		}
		return $cases;
	}

	/**
	 * Switching VAT types on the same Form must not discard cached rates.
	 *
	 * @return void
	 */
	public function testLoadTvaReusesAllCachedTypes()
	{
		$form = $this->createVatForm();
		$cache = $form->cache_vatrates;
		foreach (array(1 => 4, 2 => 4, 0 => 6) as $typevat => $count) {
			$html = $form->load_tva('tva_tx', '0', null, null, 0, 0, '', true, 1, $typevat);
			$this->assertSame($count, substr_count($html, '<option '));
			$this->assertSame($count, $form->num);
			$this->assertSame($cache, $form->cache_vatrates);
		}
	}

	/**
	 * A dictionary containing only shared rates must behave identically for every type.
	 *
	 * @return void
	 */
	public function testLoadTvaSharedRates()
	{
		$form = $this->createVatForm();
		$form->cache_vatrates = array_slice($form->cache_vatrates, 0, 2, true);
		$expected = $form->load_tva('tva_tx', '0', null, null, 0, 0, '', true, 1);
		foreach (array(0, 1, 2) as $typevat) {
			$this->assertSame($expected, $form->load_tva('tva_tx', '0', null, null, 0, 0, '', true, 1, $typevat));
		}
	}

	/**
	 * Shared non-recoverable VAT must remain selectable by its numeric rate and NPR flag.
	 *
	 * @return void
	 */
	public function testLoadTvaSharedNonRecoverableSelection()
	{
		$form = $this->createVatForm();
		foreach (array(0, 1, 2) as $typevat) {
			$html = $form->load_tva('tva_tx', '2.6', null, null, 0, 1, '', true, 1, $typevat);
			$document = new DOMDocument();
			$document->loadHTML($html);
			$xpath = new DOMXPath($document);
			$selected = $xpath->query('//option[@selected]');
			$this->assertSame(1, $selected->length);
			$this->assertSame('2.6*', $selected->item(0)->getAttribute('value'));
		}
	}

	/**
	 * Sellers not subject to VAT must still be restricted to a disabled zero-rate select.
	 *
	 * @return void
	 */
	public function testLoadTvaSellerNotSubjectToVat()
	{
		global $conf, $mysoc;

		$savedconf = $conf;
		$conf = clone $conf;
		$conf->global = clone $conf->global;
		$conf->global->EXPENSEREPORT_OVERRIDE_VAT = 0;
		$seller = clone $mysoc;
		$seller->country_code = 'CH';
		$seller->tva_assuj = 0;
		$form = $this->createVatForm();
		try {
			foreach (array(0, 1, 2) as $typevat) {
				$html = $form->load_tva('tva_tx', '0', $seller, null, 0, 0, '', false, 1, $typevat);
				$document = new DOMDocument();
				$document->loadHTML($html);
				$this->assertTrue($document->getElementsByTagName('select')->item(0)->hasAttribute('disabled'));
				$this->assertSame(1, $document->getElementsByTagName('option')->length);
				$this->assertSame('0', $document->getElementsByTagName('option')->item(0)->getAttribute('value'));
			}
		} finally {
			$conf = $savedconf;
		}
	}

	/**
	 * Build a populated cache without changing the VAT dictionary in the database.
	 *
	 * @return Form
	 */
	private function createVatForm()
	{
		$form = new Form($this->savdb);
		foreach (array(array('0', '0', '', 0), array('0', '2.6', '', 1), array('1', '8.1', 'TVADue', 0), array('1', '8.1', 'TVADueOPT', 0), array('2', '8.1', 'IPInv', 0), array('2', '8.1', 'IPMat', 0)) as $index => $rate) {
			$id = $index + 1;
			$label = $rate[1].'%'.($rate[2] ? ' ('.$rate[2].')' : '');
			$form->cache_vatrates[$id] = array('rowid' => $id, 'type_vat' => $rate[0], 'txtva' => $rate[1], 'code' => $rate[2], 'nprtva' => $rate[3], 'label' => $label, 'labelpositiverates' => $label);
		}
		return $form;
	}
}
