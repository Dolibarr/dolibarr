<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user, $mysoc;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/modSociete.class.php';

/**
 * Test import/export contract for contact use_thirdparty_address.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class SocieteImportExportAddressModeContractTest extends CommonClassTest
{
	/**
	 * @var Conf
	 */
	protected $savconf;

	/**
	 * @var DoliDB
	 */
	protected $savdb;

	/**
	 * @var Translate
	 */
	protected $savlangs;

	/**
	 * @var User
	 */
	protected $savuser;

	/**
	 * @var Societe
	 */
	private $savmysoc;

	/**
	 * Save globals.
	 *
	 * @param string $name Test name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		global $conf, $db, $langs, $user, $mysoc;
		$this->savconf = $conf;
		$this->savdb = $db;
		$this->savlangs = $langs;
		$this->savuser = $user;
		$this->savmysoc = $mysoc;
	}

	/**
	 * Restore globals.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $conf, $mysoc;
		$mysoc = $this->savmysoc;
		if (is_object($conf)) {
			$conf->loghandlers = array();
		}
	}

	/**
	 * Export and import descriptors must expose use_thirdparty_address for contacts.
	 *
	 * @return void
	 */
	public function testImportExportDescriptorsExposeUseThirdpartyAddress(): void
	{
		$module = new modSociete($this->savdb);

		$this->assertTrue(in_array('UseThirdpartyAddress', $module->export_fields_array[2], true));
		$this->assertSame('Numeric', $module->export_TypeFields_array[2]['c.use_thirdparty_address']);
		$this->assertSame('UseThirdpartyAddress', $module->import_fields_array[2]['s.use_thirdparty_address']);
		$this->assertSame('0 or 1', $module->import_examplevalues_array[2]['s.use_thirdparty_address']);
	}
}
