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
if (!defined('ROWS_3')) {
	define('ROWS_3', 3);
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/html.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
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
		foreach (array('conf', 'db', 'langs', 'user', 'object', 'form', 'extrafields', 'hookmanager', 'forceall', 'forcetoshowtitlelines', 'filtertype', 'disableedit', 'disablemove', 'disableremove', '_POST', '_GET', '_SESSION', '_SERVER') as $name) {
			$this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
		}
		global $conf, $db, $langs, $user, $object, $form, $extrafields, $hookmanager, $forcetoshowtitlelines;
		$conf = new Conf();
		$conf->entity = 1;
		$conf->global->MAIN_DISABLE_FREE_LINES = 1;
		$conf->modules = array('product' => 1, 'stock' => 1);
		$conf->use_javascript_ajax = 0;
		$conf->browser->layout = 'classic';
		$conf->file->dol_document_root = array(DOL_DOCUMENT_ROOT);
		$conf->file->strict_mode = 1;
		$langs = new Translate('', $conf);
		$langs->setDefaultLang('en_US');
		$db = $this->createMock(DoliDBMysqli::class);
		$db->method('prefix')->willReturn('llx_');
		$fixtureRow = 0;
		$db->method('query')->willReturnCallback(function () use (&$fixtureRow) {
			$fixtureRow = 0;
			return true;
		});
		$db->method('num_rows')->willReturn(1);
		$db->method('fetch_object')->willReturnCallback(function () use (&$fixtureRow) {
			return $fixtureRow++ ? false : (object) array('rowid' => 3, 'label' => 'Main warehouse', 'code' => 'P', 'sortorder' => 1, 'short_label' => 'p.', 'unit_type' => 'unit', 'active' => 1, 'description' => '', 'fk_parent' => 0, 'stock' => 0);
		});
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
	 * Free entry requires shipment opt-in and respects the global prohibition.
	 *
	 * @return void
	 */
	public function testCreateFormRespectsFreeLineSetting()
	{
		global $conf, $form;
		$_POST = array('prod_entry_mode' => 'free', 'type' => '0', 'dp_desc' => 'Packing & handling');
		$form->expects($this->once())->method('select_type_of_lines')->with('0', 'type');
		foreach (array(array(null, 0, false), array(0, 0, false), array(1, 0, true), array(1, 1, false)) as $case) {
			unset($conf->global->SHIPMENTS_ALLOW_FREE_LINES);
			if ($case[0] !== null) {
				$conf->global->SHIPMENTS_ALLOW_FREE_LINES = $case[0];
			}
			$conf->global->MAIN_DISABLE_FREE_LINES = $case[1];
			$html = $this->renderCreateForm();
			$this->assertSame($case[2], strpos($html, 'id="prod_entry_mode_free"') !== false);
			$this->assertSame($case[2], strpos($html, 'name="dp_desc"') !== false);
			if ($case[2]) {
				$this->assertStringContainsString('id="prod_entry_mode_free" value="free" checked', $html);
				$this->assertStringContainsString('Packing &amp; handling', $html);
			} else {
				$this->assertStringContainsString('name="prod_entry_mode" value="predef"', $html);
			}
		}
	}

	/**
	 * Free shipment lines expose a unit selector and retain the submitted unit.
	 *
	 * @return void
	 */
	public function testCreateFormKeepsSubmittedUnit()
	{
		global $conf, $form;
		$conf->global->PRODUCT_USE_UNITS = 1;
		$conf->global->MAIN_DISABLE_FREE_LINES = 0;
		$conf->global->SHIPMENTS_ALLOW_FREE_LINES = 1;
		$_POST = array('prod_entry_mode' => 'free', 'type' => '0', 'dp_desc' => 'Packing', 'units' => '6');
		$form->expects($this->once())->method('selectUnits')->with(6, 'units', 1)->willReturn('<select name="units"><option value="6" selected>Piece</option></select>');
		$html = $this->renderCreateForm();
		$dom = new DOMDocument();
		$dom->loadHTML('<table>'.$html.'</table>');
		$xpath = new DOMXPath($dom);
		$this->assertSame(1, $xpath->query('//td[contains(@class, "linecoluseunit")]/select[@name="units"]/option[@selected and @value="6"]')->length);
	}

	/**
	 * Shipment headers and inputs share the same columns with optional features.
	 * Commercial fields and service periods must never enter this form.
	 *
	 * @return void
	 */
	public function testCreateFormColumnsMatchShipmentHeaders()
	{
		global $conf;
		foreach (array(0, 1) as $stock) {
			foreach (array(0, 1) as $units) {
				foreach (array(0, 1) as $numbering) {
					$conf->modules = array('product' => 1, 'stock' => $stock);
					$conf->global->PRODUCT_USE_UNITS = $units;
					$conf->global->MAIN_VIEW_LINE_NUMBER = $numbering;
					$conf->global->MAIN_DISABLE_FREE_LINES = 0;
					$conf->global->SHIPMENTS_ALLOW_FREE_LINES = 1;
					$html = $this->renderCreateForm();
					$dom = new DOMDocument();
					$dom->loadHTML('<table>'.$html.'</table>');
					$xpath = new DOMXPath($dom);
					$rows = $xpath->query('//table/tr');
					$this->assertSame(2, $rows->length);
					$expected = 5 + $stock + $units + $numbering;
					foreach ($rows as $row) {
						$columns = 0;
						foreach ($xpath->query('./td|./th', $row) as $cell) {
							$columns += max(1, (int) $cell->getAttribute('colspan'));
						}
						$this->assertSame($expected, $columns);
					}
					foreach (array('linecoldescription', 'linecolqty', 'linecoluseunit', 'linecolwarehouse') as $column) {
						$positions = array();
						foreach ($rows as $row) {
							$cell = $xpath->query('./td[contains(@class, "'.$column.'")]|./th[contains(@class, "'.$column.'")]', $row)->item(0);
							$positions[] = $cell ? $xpath->query('preceding-sibling::*', $cell)->length : -1;
						}
						$this->assertSame($positions[0], $positions[1]);
					}
					$this->assertSame(0, $xpath->query('//*[@name="tva_tx" or @name="price_ht" or @name="price_ttc" or @name="remise_percent" or @name="date_start" or @name="date_end"]')->length);
				}
			}
		}
	}

	/**
	 * Viewing and editing keep the same column count as the shipment header.
	 *
	 * @return void
	 */
	public function testViewAndEditColumnsMatchShipmentHeaders()
	{
		global $conf, $object;
		$line = new ExpeditionLigne($object->db);
		$line->id = 42;
		$line->fk_product = 0;
		$line->qty = 1;
		$line->description = 'Packing';
		$object->lines = array($line);
		foreach (array(0, 1) as $stock) {
			foreach (array(0, 1) as $units) {
				foreach (array(0, 1) as $numbering) {
					$conf->modules = array('product' => 1, 'stock' => $stock);
					$conf->global->PRODUCT_USE_UNITS = $units;
					$conf->global->MAIN_VIEW_LINE_NUMBER = $numbering;
					foreach (array('view', 'editline') as $action) {
						ob_start();
						try {
							$object->printObjectLines($action, new Societe($object->db), null, 42, 0, '/expedition/tpl');
							$html = ob_get_contents();
						} finally {
							ob_end_clean();
						}
						$dom = new DOMDocument();
						$dom->loadHTML('<table>'.$html.'</table>');
						$xpath = new DOMXPath($dom);
						$rows = $xpath->query('//table/thead/tr|//table/tr');
						$this->assertSame(2, $rows->length);
						foreach ($rows as $row) {
							$columns = 0;
							foreach ($xpath->query('./td|./th', $row) as $cell) {
								$columns += max(1, (int) $cell->getAttribute('colspan'));
							}
							$this->assertSame(5 + $stock + $units + $numbering, $columns);
						}
					}
				}
			}
		}
	}

	/**
	 * A single warehouse must not override an intentional empty selection.
	 *
	 * @return void
	 */
	public function testCreateFormLeavesWarehouseEmptyByDefault()
	{
		global $conf, $user;
		foreach (array('none', 'global', 'user') as $default) {
			$conf->global->MAIN_DEFAULT_WAREHOUSE = $default === 'global' ? 3 : 0;
			$conf->global->MAIN_DEFAULT_WAREHOUSE_USER = $default === 'user' ? 1 : 0;
			$user->fk_warehouse = $default === 'user' ? 3 : 0;
			$html = $this->renderCreateForm();
			$this->assertStringContainsString('<option value="-1">', $html);
			$this->assertStringContainsString('<option value="3"', $html);
			$this->assertStringNotContainsString('<option value="3" selected', $html);
		}
	}

	/**
	 * An empty warehouse submitted on error must remain empty.
	 *
	 * @return void
	 */
	public function testCreateFormKeepsSubmittedEmptyWarehouse()
	{
		global $conf;
		$conf->global->MAIN_DEFAULT_WAREHOUSE = 3;
		foreach (array('-1', '0') as $warehouse) {
			$_POST = array('entrepot_id' => $warehouse);
			$html = $this->renderCreateForm();
			$this->assertStringContainsString('<option value="-1">', $html);
			$this->assertStringNotContainsString('<option value="3" selected', $html);
		}
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
