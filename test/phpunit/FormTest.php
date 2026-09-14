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

global $conf,$user,$langs,$db;
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
}
