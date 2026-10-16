<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       test/phpunit/ExpeditionLineFormTest.php
 * \ingroup    test
 * \brief      Standalone shipment line form regression tests.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', sys_get_temp_dir());
}
if (!defined('DOL_URL_ROOT')) {
	define('DOL_URL_ROOT', '');
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/html.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/DoliDB.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Exercise the real templates and warehouse selector without a live database.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ExpeditionLineFormTest extends \PHPUnit\Framework\TestCase
{
	/** @var array<string,mixed> Suite globals to restore */
	private $savedGlobals = array();

	/**
	 * Isolate the request and the Dolibarr environment.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		foreach (array('conf', 'langs', 'user', 'object', 'form', 'extrafields', 'hookmanager', 'forceall', 'forcetoshowtitlelines', 'filtertype', 'disableedit', 'disablemove', 'disableremove', '_POST', '_GET', '_SESSION', '_SERVER') as $name) {
			$this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
		}
		global $conf, $langs, $user, $object, $form, $extrafields, $hookmanager, $forcetoshowtitlelines;
		$conf = new Conf();
		$conf->entity = 1;
		$conf->modules = array('product' => 1, 'stock' => 1);
		$conf->use_javascript_ajax = 0;
		$conf->browser->layout = 'classic';
		$conf->file->dol_document_root = array(DOL_DOCUMENT_ROOT);
		$conf->file->strict_mode = 1;
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('en_US');
		$db = $this->createMock(DoliDBMysqli::class);
		$db->method('prefix')->willReturn('llx_');
		$db->method('query')->willReturn(true);
		$db->method('num_rows')->willReturn(1);
		$db->method('fetch_object')->willReturn((object) array('rowid' => 3, 'label' => 'Main warehouse', 'description' => '', 'fk_parent' => 0, 'stock' => 0));
		$object = new Expedition($db);
		$object->id = 12;
		$object->status = Expedition::STATUS_DRAFT;
		$form = $this->createMock(Form::class);
		$user = new User($db);
		$extrafields = new ExtraFields($db);
		$extrafields->attributes['expeditiondet'] = array('loaded' => 1, 'label' => array());
		$hookmanager = new HookManager($db);
		$forcetoshowtitlelines = true;
		$_POST = array();
		$_GET = array();
		$_SESSION = array();
		$_SERVER['PHP_SELF'] = '/expedition/card.php';
	}

	/**
	 * Restore globals also after a failed assertion.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		foreach ($this->savedGlobals as $name => $value) {
			$GLOBALS[$name] = $value;
		}
		parent::tearDown();
	}

	/**
	 * Render through CommonObject so template resolution is exercised too.
	 *
	 * @return string HTML
	 */
	private function renderCreateForm()
	{
		global $object;
		ob_start();
		try {
			$object->formAddObjectLine(1, new Societe($object->db), null, '/expedition/tpl');
			return ob_get_contents();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * Products, quantity and warehouse remain selected after a failed request.
	 * The add table keeps its own header even when shipment lines already exist.
	 *
	 * @return void
	 */
	public function testCreateFormKeepsSubmittedValues()
	{
		global $form, $object;
		$_POST = array('idprod' => '7', 'qty' => '1,5', 'entrepot_id' => '3');
		$object->lines = array(new ExpeditionLigne($object->db));
		$form->expects($this->once())->method('select_produits')->with(7, 'idprod', 0);
		$html = $this->renderCreateForm();
		$this->assertStringContainsString('AddNewLine', $html);
		$this->assertStringContainsString('value="1,5"', $html);
		$this->assertStringContainsString('name="prod_entry_mode" value="predef"', $html);
		$this->assertStringContainsString('name="entrepot_id"', $html);
		$this->assertStringContainsString('<option value="3" selected', $html);
		$this->assertStringNotContainsString('name="price_ht"', $html);
	}

	/**
	 * Stock-disabled installations show neither a warehouse field nor its header.
	 * Services are selectable only with shipment service support enabled.
	 *
	 * @return void
	 */
	public function testCreateFormWithoutStockAndWithServices()
	{
		global $conf, $form;
		$conf->modules = array('product' => 1, 'service' => 1);
		$conf->global->PRODUCT_USE_UNITS = 1;
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = 1;
		$form->expects($this->once())->method('select_produits')->with(0, 'idprod', '');
		$html = $this->renderCreateForm();
		$this->assertStringNotContainsString('linecolwarehouse', $html);
		$this->assertStringNotContainsString('name="entrepot_id"', $html);
		$this->assertStringContainsString('linecoluseunit', $html);
	}

	/**
	 * Service-only installations use the service selector; without service
	 * shipment support they must not expose a product selector.
	 *
	 * @return void
	 */
	public function testServiceOnlySelectorRequiresShipmentSupport()
	{
		global $conf, $form;
		$conf->modules = array('service' => 1);
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = 1;
		$form->expects($this->once())->method('select_produits')->with(0, 'idprod', 1);
		$this->assertStringContainsString('name="prod_entry_mode"', $this->renderCreateForm());
		$conf->global->SHIPMENT_SUPPORTS_SERVICES = 0;
		$this->assertStringNotContainsString('name="prod_entry_mode"', $this->renderCreateForm());
	}

	/**
	 * Editing preserves decimal input and the submitted warehouse on error.
	 *
	 * @return void
	 */
	public function testEditFormKeepsSubmittedValues()
	{
		global $object;
		$_POST = array('qty' => '2,75', 'entrepot_id' => '3');
		$line = new ExpeditionLigne($object->db);
		$line->id = 42;
		$line->fk_product = 0;
		$line->qty = 1;
		$line->entrepot_id = 0;
		ob_start();
		try {
			$object->printObjectLine('editline', $line, false, 1, 0, 0, new Societe($object->db), null, 42, null, '/expedition/tpl');
			$html = ob_get_contents();
		} finally {
			ob_end_clean();
		}
		$this->assertStringContainsString('name="lineid" value="42"', $html);
		$this->assertStringContainsString('value="2,75"', $html);
		$this->assertStringContainsString('<option value="3" selected', $html);
	}

	/**
	 * Shipment creation permission enables draft editing, while read-only users
	 * and validated shipments have no edit or delete controls.
	 *
	 * @return void
	 */
	public function testLineActionsUseShipmentPermissionAndStatus()
	{
		global $object, $user, $conf;
		$conf->modules = array('product' => 1);
		$line = new ExpeditionLigne($object->db);
		$line->id = 42;
		$line->fk_product = 0;
		$line->qty = 0;
		foreach (array(array(0, true), array(0, false), array(1, true)) as $case) {
			$object->status = $case[0];
			$user = $this->createMock(User::class);
			$user->method('hasRight')->willReturnCallback(function ($module, $permission) use ($case) {
				return $module === 'expedition' && $permission === 'creer' && $case[1];
			});
			ob_start();
			try {
				$object->printObjectLine('view', $line, false, 1, 0, 0, new Societe($object->db), null, 0, null, '/expedition/tpl');
				$html = ob_get_contents();
			} finally {
				ob_end_clean();
			}
			$this->assertSame($case[0] === 0 && $case[1], strpos($html, 'action=editline') !== false);
			$this->assertSame($case[0] === 0 && $case[1], strpos($html, 'action=deleteline') !== false);
		}
	}
}
