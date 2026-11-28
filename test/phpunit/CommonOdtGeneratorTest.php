<?php
/* Copyright (C) 2026		Jonathan Lescaut			<jonathan.lescaut@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/CommonOdtGeneratorTest.php
 * \ingroup core
 * \brief   PHPUnit test for the CommonOdtGenerator trait, through the order ODT generator.
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/commande/doc/doc_generic_order_odt.modules.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CommonOdtGeneratorTest extends CommonClassTest
{
	/**
	 * @var string	Output and temporary directory of the generated documents
	 */
	private $workDir;

	/**
	 * @var array<string,mixed>	Order module directories and constants changed by a test, restored in tearDown()
	 */
	private $saved = [];

	/**
	 * Point the order module directories to a private work directory.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf;

		parent::setUp();

		$this->workDir = sys_get_temp_dir().'/dolibarr-phpunit-odt-'.getmypid();
		dol_mkdir($this->workDir.'/temp');

		$this->saved = [
			'multidir_output' => $conf->commande->multidir_output ?? null,
			'multidir_temp' => $conf->commande->multidir_temp ?? null,
			'dir_output' => $conf->commande->dir_output ?? null,
			'dir_temp' => $conf->commande->dir_temp ?? null,
			'ORDER_FREE_TEXT' => $conf->global->ORDER_FREE_TEXT ?? null,
			'MAIN_DOC_USE_TIMING' => $conf->global->MAIN_DOC_USE_TIMING ?? null,
			'COMMANDE_ADDON_PDF_ODT_PATH' => $conf->global->COMMANDE_ADDON_PDF_ODT_PATH ?? null,
		];
		$conf->commande->multidir_output = [$conf->entity => $this->workDir];
		$conf->commande->multidir_temp = [$conf->entity => $this->workDir.'/temp'];
		$conf->commande->dir_output = $this->workDir;
		$conf->commande->dir_temp = $this->workDir.'/temp';
	}

	/**
	 * Restore the order module directories and constants, remove the work directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		$conf->commande->multidir_output = $this->saved['multidir_output'];
		$conf->commande->multidir_temp = $this->saved['multidir_temp'];
		$conf->commande->dir_output = $this->saved['dir_output'];
		$conf->commande->dir_temp = $this->saved['dir_temp'];
		foreach (['ORDER_FREE_TEXT', 'MAIN_DOC_USE_TIMING', 'COMMANDE_ADDON_PDF_ODT_PATH'] as $constant) {
			if ($this->saved[$constant] === null) {
				unset($conf->global->$constant);
			} else {
				$conf->global->$constant = $this->saved[$constant];
			}
		}

		dol_delete_dir_recursive($this->workDir);

		parent::tearDown();
	}

	/**
	 * Call a protected method of the generator.
	 *
	 * @param	doc_generic_order_odt	$generator	Generator
	 * @param	string					$method		Method name
	 * @param	mixed					...$args	Arguments
	 * @return	mixed							Returned value
	 */
	private function callProtected(doc_generic_order_odt $generator, string $method, ...$args)
	{
		$reflection = new ReflectionMethod($generator, $method);
		if (PHP_VERSION_ID < 80100) {
			$reflection->setAccessible(true);
		}

		return $reflection->invoke($generator, ...$args);
	}

	/**
	 * Return a specimen order, which needs no record in database.
	 *
	 * @return Commande
	 */
	private function getSpecimenOrder(): Commande
	{
		global $db;

		$order = new Commande($db);
		$order->initAsSpecimen();

		return $order;
	}

	/**
	 * @return void
	 */
	public function testBuildOdtFileNameRemovesTemplatePrefixAndKeepsExtension()
	{
		global $db;

		$generator = new doc_generic_order_odt($db);

		$this->assertSame('CO01_order.odt', $this->callProtected($generator, 'buildOdtFileName', 'CO01', 'template_order.odt'));
		$this->assertSame('CO01_order.ods', $this->callProtected($generator, 'buildOdtFileName', 'CO01', 'modele_order.ods'));
	}

	/**
	 * @return void
	 */
	public function testBuildOdtFileNameAddsDateWhenTimingIsEnabled()
	{
		global $db, $conf;

		$conf->global->MAIN_DOC_USE_TIMING = '%Y';
		$generator = new doc_generic_order_odt($db);

		$yearBefore = dol_print_date(dol_now(), '%Y');
		$fileName = $this->callProtected($generator, 'buildOdtFileName', 'CO01', 'template_order.odt');
		$yearAfter = dol_print_date(dol_now(), '%Y');

		$this->assertContains($fileName, ['CO01_order-'.$yearBefore.'.odt', 'CO01_order-'.$yearAfter.'.odt']);
	}

	/**
	 * @return void
	 */
	public function testGetOdtTemplateDirsResolvesDataRootAndSkipsEmptyEntries()
	{
		global $db;

		$generator = new doc_generic_order_odt($db);

		$dirs = $this->callProtected($generator, 'getOdtTemplateDirs', "DOL_DATA_ROOT/doctemplates/orders, \n /tmp/models ,,");

		$this->assertSame([DOL_DATA_ROOT.'/doctemplates/orders', '/tmp/models'], $dirs);
	}

	/**
	 * @return void
	 */
	public function testSetupKeepsOneTemplateDirectoryPerLine()
	{
		global $db, $conf, $langs;

		$conf->global->COMMANDE_ADDON_PDF_ODT_PATH = "/tmp/models-a\n/tmp/models-b&c";
		$generator = new doc_generic_order_odt($db);

		$this->assertStringContainsString('name="value1">/tmp/models-a'."\n".'/tmp/models-b&amp;c</textarea>', $generator->info($langs));
	}

	/**
	 * @return void
	 */
	public function testGetOdtObjectDirAddsRefSubdirectory()
	{
		global $db;

		$generator = new doc_generic_order_odt($db);
		$order = $this->getSpecimenOrder();

		$this->assertSame($this->workDir.'/CO01', $this->callProtected($generator, 'getOdtObjectDir', $order, 'CO01'));
	}

	/**
	 * @return void
	 */
	public function testGetOdtObjectDirHasNoRefSubdirectoryForSpecimen()
	{
		global $db;

		$generator = new doc_generic_order_odt($db);
		$order = $this->getSpecimenOrder();

		$this->assertSame($this->workDir, $this->callProtected($generator, 'getOdtObjectDir', $order, 'SPECIMEN'));
	}

	/**
	 * @return void
	 */
	public function testPreSubstitutionHookRunsOnlyWhenAFreeTextConstantIsDeclared()
	{
		global $db;

		$generator = new doc_generic_order_odt($db);
		$this->assertTrue($this->callProtected($generator, 'hasOdtPreSubstitutionHook'));

		$property = new ReflectionProperty($generator, 'odtFreeTextConst');
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}
		$property->setValue($generator, '');
		$this->assertFalse($this->callProtected($generator, 'hasOdtPreSubstitutionHook'));
	}

	/**
	 * @return void
	 */
	public function testWriteFileFailsWithoutTemplate()
	{
		global $db, $langs;

		$generator = new doc_generic_order_odt($db);

		$this->assertSame(-1, $generator->write_file($this->getSpecimenOrder(), $langs, ''));
	}

	/**
	 * @return void
	 */
	public function testWriteFileFailsForUnknownId()
	{
		global $db, $langs;

		$generator = new doc_generic_order_odt($db);

		// An unknown id must not produce an empty document reported as a success.
		$this->assertSame(-1, $generator->write_file(PHP_INT_MAX, $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt'));
		$this->assertSame($langs->transnoentities('ErrorRecordNotFound'), $generator->error);
	}

	/**
	 * @return void
	 */
	public function testWriteFileFailsWithErrorWhenModuleHasNoOutputDir()
	{
		global $db, $langs, $conf;

		$conf->commande->multidir_output = [];
		$generator = new doc_generic_order_odt($db);

		$this->assertSame(-1, $generator->write_file($this->getSpecimenOrder(), $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt'));
		$this->assertNotEmpty($generator->error);
	}

	/**
	 * @return void
	 */
	public function testWriteFileFailsWhenTempDirIsNotWritable()
	{
		global $db, $langs, $conf;

		if ($this->fakeAssertIfNotUnix(__METHOD__." uses /proc, which only exists on *nix")) {
			return;
		}

		$conf->commande->multidir_temp = [$conf->entity => '/proc/dolibarr-phpunit-not-writable'];
		$generator = new doc_generic_order_odt($db);

		$this->assertSame(-1, $generator->write_file($this->getSpecimenOrder(), $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt'));
		$this->assertNotEmpty($generator->error);
		$this->assertFileNotExistsCompat($this->workDir.'/SPECIMEN_order.odt');
	}

	/**
	 * @return void
	 */
	public function testWriteFileGeneratesOdtWithSubstitutedFreeText()
	{
		global $db, $langs, $conf, $mysoc;

		$conf->global->ORDER_FREE_TEXT = 'PHPUNIT-FREE __FROM_NAME__';
		$generator = new doc_generic_order_odt($db);

		$result = $generator->write_file($this->getSpecimenOrder(), $langs, DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt');

		$this->assertSame(1, $result, $generator->error);
		$this->assertSame($this->workDir.'/SPECIMEN_order.odt', $generator->result['fullpath']);
		$this->assertFileExists($generator->result['fullpath']);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($generator->result['fullpath']));
		$content = $zip->getFromName('content.xml');
		$zip->close();

		$this->assertStringContainsString('PHPUNIT-FREE '.$mysoc->name, html_entity_decode((string) $content, ENT_QUOTES | ENT_XML1, 'UTF-8'));
		$this->assertStringNotContainsString('{object_ref}', (string) $content);
	}
}
