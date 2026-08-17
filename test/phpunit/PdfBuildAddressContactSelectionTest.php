<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

/**
 * Test PDF address selection for contacts.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class PdfBuildAddressContactSelectionTest extends CommonClassTest
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
	 * Save globals.
	 *
	 * @param string $name Test name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		global $conf, $db, $langs;
		$this->savconf = $conf;
		$this->savdb = $db;
		$this->savlangs = $langs;
	}

	/**
	 * Restore globals.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		global $conf;
		if (is_object($conf)) {
			$conf->loghandlers = array();
		}
	}

	/**
	 * PDF address must use effective thirdparty postal fields while keeping contact name.
	 *
	 * @return void
	 */
	public function testPdfBuildAddressUsesEffectiveAddressObject(): void
	{
		$sourcecompany = new Societe($this->savdb);
		$sourcecompany->country_code = 'FR';

		$targetcompany = new Societe($this->savdb);
		$targetcompany->id = 10;
		$targetcompany->address = 'Wrong company street';
		$targetcompany->zip = '33000';
		$targetcompany->town = 'Bordeaux';
		$targetcompany->country_code = 'FR';

		$effective = new Societe($this->savdb);
		$effective->id = 10;
		$effective->address = 'Effective avenue';
		$effective->zip = '75001';
		$effective->town = 'Paris';
		$effective->country_code = 'FR';

		$contact = new class($this->savdb, $effective) extends Contact {
			/**
			 * @var Societe
			 */
			private $effective;

			/**
			 * @param DoliDB  $db Database handler
			 * @param Societe $effective Effective address object
			 */
			public function __construct($db, Societe $effective)
			{
				parent::__construct($db);
				$this->effective = $effective;
			}

			/**
			 * @param   Societe|null $thirdparty Unused preloaded thirdparty
			 * @return  CommonObject
			 */
			public function getEffectiveAddressObject(?Societe $thirdparty = null): CommonObject
			{
				return $this->effective;
			}
		};

		$contact->firstname = 'Jane';
		$contact->lastname = 'Doe';
		$contact->socid = 10;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Old copied street';

		$output = pdf_build_address($this->savlangs, $sourcecompany, $targetcompany, $contact, 1, 'target', null);

		$this->assertStringContainsString('Jane Doe', $output);
		$this->assertStringContainsString('Effective avenue', $output);
		$this->assertStringNotContainsString('Old copied street', $output);
	}
}
