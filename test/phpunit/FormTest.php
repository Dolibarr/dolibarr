<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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

	/**
	 * testSelectDolusersFilterKey
	 *
	 * select_dolusers() must restrict the returned users to those whose
	 * firstname, lastname or login matches the $filterkey search string
	 * (used by the user/ajax/users.php autocomplete endpoint).
	 *
	 * @return void
	 */
	public function testSelectDolusersFilterKey()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$db->begin();

		$uniq = 'zttestselusr'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		$tmpuser = new User($db);
		$tmpuser->lastname = 'Zorglub'.$uniq;
		$tmpuser->firstname = 'Filterkey';
		$tmpuser->login = $uniq;
		$tmpuser->email = $uniq.'@example.com';
		$resultcreate = $tmpuser->create($user);
		$this->assertGreaterThan(0, $resultcreate, 'Failed to create test user: '.$tmpuser->error);

		$form = new Form($db);

		// A matching key returns the user
		$match = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, $uniq);
		$this->assertIsArray($match);
		$this->assertArrayHasKey($tmpuser->id, $match, 'select_dolusers did not return the user matching the filterkey');

		// A non-matching key does not return the user
		$nomatch = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, 'nobodyxyz'.$uniq);
		$this->assertIsArray($nomatch);
		$this->assertArrayNotHasKey($tmpuser->id, $nomatch, 'select_dolusers ignored the filterkey and returned a non-matching user');

		$db->rollback();
	}

	/**
	 * testSelectDolusersLimit
	 *
	 * select_dolusers() must cap the number of returned users to the $limit
	 * argument (used by the user/ajax/users.php endpoint in "infinite list" mode).
	 *
	 * @return void
	 */
	public function testSelectDolusersLimit()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$db->begin();

		$uniq = 'zttestlimusr'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		for ($i = 1; $i <= 3; $i++) {
			$tmpuser = new User($db);
			$tmpuser->lastname = 'Limit'.$i.$uniq;
			$tmpuser->firstname = 'User';
			$tmpuser->login = $uniq.$i;
			$tmpuser->email = $uniq.$i.'@example.com';
			$this->assertGreaterThan(0, $tmpuser->create($user), 'Failed to create test user: '.$tmpuser->error);
		}

		$form = new Form($db);

		// Without limit: the 3 users match the filterkey
		$all = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, $uniq, 0);
		$this->assertIsArray($all);
		$this->assertGreaterThanOrEqual(3, count($all), 'Expected at least the 3 created users without a limit');

		// With limit=2: at most 2 rows are returned
		$limited = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, $uniq, 2);
		$this->assertIsArray($limited);
		$this->assertLessThanOrEqual(2, count($limited), 'select_dolusers did not honour the $limit argument');

		$db->rollback();
	}

	/**
	 * testSelectDolusersMultipleSearchToSelect
	 *
	 * When USER_USE_SEARCH_TO_SELECT is enabled, select_dolusers() in multiple
	 * mode must render an ajax select2 bound to user/ajax/users.php with only
	 * the preselected users as <option>, instead of loading the whole llx_user
	 * table. With the constant unset it must keep the full-list <select multiple>.
	 *
	 * @return void
	 */
	public function testSelectDolusersMultipleSearchToSelect()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->use_javascript_ajax = 1;

		$db->begin();

		$uniq = 'zttestmulusr'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		$user1 = new User($db);
		$user1->lastname = 'Selected'.$uniq;
		$user1->firstname = 'User';
		$user1->login = $uniq.'a';
		$user1->email = $uniq.'a@example.com';
		$this->assertGreaterThan(0, $user1->create($user), 'Failed to create test user: '.$user1->error);

		$user2 = new User($db);
		$user2->lastname = 'NotSelected'.$uniq;
		$user2->firstname = 'User';
		$user2->login = $uniq.'b';
		$user2->email = $uniq.'b@example.com';
		$this->assertGreaterThan(0, $user2->create($user), 'Failed to create test user: '.$user2->error);

		$form = new Form($db);

		// Ajax "search to select" mode ON
		$conf->global->USER_USE_SEARCH_TO_SELECT = 2;
		$out = $form->select_dolusers(array($user1->id), 'commercial', 0, null, 0, '', '', '0', 0, 0, '', 0, '', 'morecss', 1, 0, true);
		$this->assertIsString($out);
		$this->assertStringContainsString('name="commercial[]"', $out, 'multiple mode must add [] to the element name');
		$this->assertStringContainsString('multiple', $out, 'multiple attribute must be present');
		$this->assertStringContainsString('user/ajax/users.php', $out, 'multiple + search-to-select must bind select2 to the ajax endpoint');
		$this->assertStringContainsString('<option value="'.$user1->id.'"', $out, 'the preselected user must be rendered as an <option>');
		$this->assertStringNotContainsString('<option value="'.$user2->id.'"', $out, 'the full user list must not be rendered in ajax mode');

		// Ajax "search to select" mode OFF -> full list
		unset($conf->global->USER_USE_SEARCH_TO_SELECT);
		$outfull = $form->select_dolusers(array($user1->id), 'commercial', 0, null, 0, '', '', '0', 0, 0, '', 0, '', 'morecss', 1, 0, true);
		$this->assertIsString($outfull);
		$this->assertStringNotContainsString('user/ajax/users.php', $outfull, 'without the constant the ajax endpoint must not be used');
		$this->assertStringContainsString('<option value="'.$user2->id.'"', $outfull, 'without the constant the full user list must be rendered');

		$db->rollback();
	}

	/**
	 * testUserComboMorefilterAllowlist
	 *
	 * Only the known-safe expressions of Form::$user_combo_allowed_morefilters may be
	 * forwarded to the user/ajax/users.php endpoint; any other $morefilter is rejected.
	 *
	 * @return void
	 */
	public function testUserComboMorefilterAllowlist()
	{
		$this->assertTrue(Form::isUserComboMorefilterAllowed('u.statut:=:1'));
		$this->assertTrue(Form::isUserComboMorefilterAllowed('(statut:=:1)'));
		$this->assertTrue(Form::isUserComboMorefilterAllowed('employee:=:1'));
		$this->assertTrue(Form::isUserComboMorefilterAllowed('(admin:=:1) AND (statut:=:1)'));
		// surrounding whitespace is tolerated
		$this->assertTrue(Form::isUserComboMorefilterAllowed('  employee:=:1  '));

		$this->assertFalse(Form::isUserComboMorefilterAllowed(''));
		$this->assertFalse(Form::isUserComboMorefilterAllowed('(rowid:=:1) OR (1=1)'));
		$this->assertFalse(Form::isUserComboMorefilterAllowed('employee:=:1 OR 1=1'));
		$this->assertFalse(Form::isUserComboMorefilterAllowed('(pass_crypted:like:%)'));
	}

	/**
	 * testSelectDolusersAjaxMorefilterForwarded
	 *
	 * In "search to select" mode, an allow-listed $morefilter is forwarded to the ajax
	 * endpoint URL; a non allow-listed one is dropped (not exposed in the page).
	 *
	 * @return void
	 */
	public function testSelectDolusersAjaxMorefilterForwarded()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->use_javascript_ajax = 1;
		$conf->global->USER_USE_SEARCH_TO_SELECT = 2;

		$form = new Form($db);

		// Allow-listed morefilter -> present in the ajax URL
		$out = $form->select_dolusers('', 'userid', 0, null, 0, '', '', '0', 0, 0, 'employee:=:1', 0, '', '', 0, 0, false);
		$this->assertIsString($out);
		$this->assertStringContainsString('user/ajax/users.php', $out);
		$this->assertStringContainsString('morefilter='.urlencode('employee:=:1'), $out, 'an allow-listed morefilter must be forwarded to the ajax endpoint');

		// Arbitrary morefilter -> never forwarded
		$outbad = $form->select_dolusers('', 'userid', 0, null, 0, '', '', '0', 0, 0, '(rowid:=:1) OR (1=1)', 0, '', '', 0, 0, false);
		$this->assertIsString($outbad);
		$this->assertStringNotContainsString('morefilter=', $outbad, 'a non allow-listed morefilter must not be exposed in the page');

		unset($conf->global->USER_USE_SEARCH_TO_SELECT);
	}

	/**
	 * testSelectDolusersLimitOffset
	 *
	 * select_dolusers() must offset the result set by the $limitoffset argument, so the
	 * user/ajax/users.php endpoint can page through the list (select2 infinite scroll).
	 *
	 * @return void
	 */
	public function testSelectDolusersLimitOffset()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$db->begin();

		$uniq = 'zttestoffusr'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		for ($i = 1; $i <= 3; $i++) {
			$tmpuser = new User($db);
			$tmpuser->lastname = 'Offset'.$i.$uniq;
			$tmpuser->firstname = 'User';
			$tmpuser->login = $uniq.$i;
			$tmpuser->email = $uniq.$i.'@example.com';
			$this->assertGreaterThan(0, $tmpuser->create($user), 'Failed to create test user: '.$tmpuser->error);
		}

		$form = new Form($db);

		// Page 1: first 2 rows
		$page1 = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, $uniq, 2, 0);
		// Page 2: same limit, offset by 2
		$page2 = $form->select_dolusers(-1, 'userid', 0, null, 0, '', '', '', 0, 0, '', 0, '', '', 0, 2, false, 0, $uniq, 2, 2);

		$this->assertIsArray($page1);
		$this->assertIsArray($page2);
		$this->assertLessThanOrEqual(2, count($page1));
		$this->assertNotEmpty($page2, 'the offset page must still return the remaining user(s)');
		$this->assertEmpty(array_intersect(array_keys($page1), array_keys($page2)), 'offset page must not repeat rows from page 1');

		$db->rollback();
	}

	/**
	 * testSelectDolusersAjaxMultiplePagination
	 *
	 * The multiple "search to select" combo must let select2 page through the endpoint:
	 * the data callback sends the page number and processResults reports whether more
	 * rows are available.
	 *
	 * @return void
	 */
	public function testSelectDolusersAjaxMultiplePagination()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->use_javascript_ajax = 1;
		$conf->global->USER_USE_SEARCH_TO_SELECT = 'infinite';

		$form = new Form($db);
		$out = $form->select_dolusers(array(), 'commercial', 0, null, 0, '', '', '0', 0, 0, '', 0, '', 'morecss', 1, 0, true);

		$this->assertIsString($out);
		$this->assertStringContainsString('d.page = params.page', $out, 'the ajax data callback must forward the select2 page number');
		$this->assertStringContainsString('pagination: { more:', $out, 'processResults must tell select2 whether more rows are available');

		unset($conf->global->USER_USE_SEARCH_TO_SELECT);
	}
}
