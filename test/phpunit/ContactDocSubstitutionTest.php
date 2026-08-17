<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/commondocgenerator.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

/**
 * Test doc substitutions for effective contact address.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @phan-file-suppress PhanTypeMismatchPropertyProbablyReal
 */
class ContactDocSubstitutionTest extends CommonClassTest
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
	 * Contact substitutions must use effective postal fields.
	 *
	 * @return void
	 */
	public function testSubstitutionArrayUsesEffectiveAddress(): void
	{
		$generator = new class($this->savdb) extends CommonDocGenerator {
		};

		$effective = new Societe($this->savdb);
		$effective->address = 'Thirdparty avenue';
		$effective->zip = '75001';
		$effective->town = 'Paris';
		$effective->country_id = 1;
		$effective->country_code = 'FR';
		$effective->country = 'France';

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

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = 'Contact street';
		$contact->country_code = 'FR';
		$contact->country = 'France';

		$substitutions = $generator->get_substitutionarray_contact($contact, $this->savlangs, 'contact');

		$this->assertSame('Thirdparty avenue', $substitutions['contact_address']);
		$this->assertSame('75001', $substitutions['contact_zip']);
		$this->assertSame('Paris', $substitutions['contact_town']);
		$this->assertSame('France', $substitutions['contact_country']);
		$this->assertSame('Jane', $substitutions['contact_firstname']);
	}

	/**
	 * Empty effective state/country ids must not be forced to zero in substitutions.
	 *
	 * @return void
	 */
	public function testSubstitutionArrayKeepsEmptyEffectiveIdsEmpty(): void
	{
		$generator = new class($this->savdb) extends CommonDocGenerator {
		};

		$effective = new Societe($this->savdb);
		$effective->address = 'Thirdparty avenue';
		/** @phpstan-ignore-next-line */
		$effective->state_id = null;
		/** @phpstan-ignore-next-line */
		$effective->country_id = null;

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

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';

		$substitutions = $generator->get_substitutionarray_contact($contact, $this->savlangs, 'contact');

		$this->assertNull($substitutions['contact_state_id']);
		$this->assertNull($substitutions['contact_country_id']);
	}
}
