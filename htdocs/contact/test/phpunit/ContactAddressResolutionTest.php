<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/../../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * PHPUnit test for contact effective address resolution.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ContactAddressResolutionTest extends PHPUnit\Framework\TestCase
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
	 * Save globals.
	 *
	 * @param string $name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		global $conf, $db, $langs, $user;
		$this->savconf = $conf;
		$this->savdb = $db;
		$this->savlangs = $langs;
		$this->savuser = $user;
	}

	/**
	 * Restore globals.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf, $db, $langs, $user;
		$conf = $this->savconf;
		$db = $this->savdb;
		$langs = $this->savlangs;
		$user = $this->savuser;
		if (is_object($conf)) {
			$conf->loghandlers = array();
		}
	}

	/**
	 * Build a contact fixture.
	 *
	 * @return Contact
	 */
	private function buildContact(): Contact
	{
		$contact = new Contact($this->savdb);
		$contact->lastname = 'Fixture';
		$contact->firstname = 'Contact';
		$contact->socid = 1;

		return $contact;
	}

	/**
	 * Contact with no thirdparty must never use thirdparty address.
	 *
	 * @return void
	 */
	public function testMustUseThirdpartyAddressWithoutThirdparty(): void
	{
		$contact = $this->buildContact();
		$contact->socid = 0;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;

		$this->assertFalse($contact->mustUseThirdpartyAddress());
	}

	/**
	 * Legacy empty postal fields must resolve to thirdparty.
	 *
	 * @return void
	 */
	public function testLegacyEmptyAddressUsesThirdparty(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = null;
		$contact->address = '';
		$contact->zip = '';
		$contact->town = '';
		$contact->state_id = 0;
		$contact->country_id = 0;

		$this->assertTrue($contact->mustUseThirdpartyAddress());
		$this->assertSame('legacy_empty', $contact->getAddressResolutionMode());
	}

	/**
	 * Legacy filled postal fields must resolve to contact.
	 *
	 * @return void
	 */
	public function testLegacyFilledAddressUsesContact(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = null;
		$contact->address = 'Specific street';

		$this->assertFalse($contact->mustUseThirdpartyAddress());
		$this->assertSame('legacy_filled', $contact->getAddressResolutionMode());
	}

	/**
	 * Explicit flag must win over legacy heuristics.
	 *
	 * @return void
	 */
	public function testExplicitFlagWins(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Specific street';

		$this->assertTrue($contact->mustUseThirdpartyAddress());
		$this->assertSame('explicit_thirdparty', $contact->getAddressResolutionMode());
	}

	/**
	 * Legacy address resolution must switch to contact mode as soon as one postal field is filled.
	 *
	 * @return void
	 */
	public function testLegacyPartialPostalFieldsUseContact(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = null;
		$contact->address = '';
		$contact->zip = '';
		$contact->town = '';
		$contact->state_id = 12;
		$contact->country_id = 0;

		$this->assertFalse($contact->mustUseThirdpartyAddress());
		$this->assertSame('legacy_filled', $contact->getAddressResolutionMode());
	}

	/**
	 * Preloaded thirdparty must be reused as effective address source.
	 *
	 * @return void
	 */
	public function testGetEffectiveAddressObjectUsesProvidedThirdparty(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$thirdparty = new Societe($this->savdb);
		$thirdparty->id = 1;
		$thirdparty->address = 'Thirdparty street';

		$this->assertSame($thirdparty, $contact->getEffectiveAddressObject($thirdparty));
	}

	/**
	 * Invalid explicit thirdparty mode must fall back to the contact when linked thirdparty is missing.
	 *
	 * @return void
	 */
	public function testGetEffectiveAddressObjectFallsBackToContactWhenThirdpartyMissing(): void
	{
		$contact = $this->buildContact();
		$contact->id = 987654;
		$contact->socid = 987654;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Fallback street';

		$this->assertSame($contact, $contact->getEffectiveAddressObject());
	}

	/**
	 * Full address must route to the effective address object when thirdparty mode is enabled.
	 *
	 * @return void
	 */
	public function testGetFullAddressUsesEffectiveAddressObject(): void
	{
		$effective = new Societe($this->savdb);
		$effective->id = 1;
		$effective->address = 'Thirdparty street';
		$effective->zip = '75001';
		$effective->town = 'Paris';

		$contact = new class($this->savdb, $effective) extends Contact {
			/**
			 * @var Societe
			 */
			private $effective;

			/**
			 * @param DoliDB  $db
			 * @param Societe $effective
			 */
			public function __construct($db, Societe $effective)
			{
				parent::__construct($db);
				$this->effective = $effective;
			}

			/**
			 * @param   Societe|null $thirdparty
			 * @return  CommonObject
			 */
			public function getEffectiveAddressObject(?Societe $thirdparty = null): CommonObject
			{
				return $this->effective;
			}
		};

		$contact->socid = 1;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Old copied street';

		$fulladdress = $contact->getFullAddress();

		$this->assertStringContainsString('Thirdparty street', $fulladdress);
		$this->assertStringNotContainsString('Old copied street', $fulladdress);
	}
}
