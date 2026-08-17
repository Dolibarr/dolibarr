<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

/**
 * PHPUnit test for contact effective address resolution.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ContactAddressResolutionTest extends CommonClassTest
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
	 * @param string $name Test name
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
		parent::setUp();

		global $conf;
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
	 * Build a contact fixture whose effective address source is controlled by the test.
	 *
	 * @param Societe $effective Effective address object
	 * @return Contact
	 */
	private function buildContactResolvingTo(Societe $effective): Contact
	{
		return new class($this->savdb, $effective) extends Contact {
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
	 * Contact without linked thirdparty must always resolve as explicit contact mode.
	 *
	 * @return void
	 */
	public function testContactWithoutThirdpartyHasExplicitContactResolutionMode(): void
	{
		$contact = $this->buildContact();
		$contact->socid = 0;
		$contact->use_thirdparty_address = null;
		$contact->address = '';

		$this->assertSame('explicit_contact', $contact->getAddressResolutionMode());
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
	 * Effective address resolution must load the linked thirdparty when no preloaded object is provided.
	 *
	 * @return void
	 */
	public function testGetEffectiveAddressObjectLoadsPersistedThirdparty(): void
	{
		$thirdparty = new Societe($this->savdb);
		$thirdparty->initAsSpecimen();
		$thirdparty->name = 'Effective address thirdparty '.dol_print_date(dol_now(), 'dayhourlog').'-'.mt_rand();
		$thirdparty->client = 1;
		$thirdparty->address = 'Persisted thirdparty street';
		$thirdparty->zip = '75001';
		$thirdparty->town = 'Paris';

		$result = $thirdparty->create($this->savuser);
		$this->assertGreaterThan(0, $result, 'Thirdparty creation failed: '.$thirdparty->error);

		$contact = $this->buildContact();
		$contact->socid = (int) $thirdparty->id;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Copied contact street';

		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$refetched = new Contact($this->savdb);
		$result = $refetched->fetch((int) $contactid);
		$this->assertGreaterThan(0, $result, 'Contact fetch failed: '.$refetched->error);

		$effectiveaddressobject = $refetched->getEffectiveAddressObject();
		$effectiveaddressfields = $refetched->getEffectiveAddressFields();

		$this->assertInstanceOf(Societe::class, $effectiveaddressobject);
		$this->assertSame('Persisted thirdparty street', $effectiveaddressfields['address']);
		$this->assertSame('75001', $effectiveaddressfields['zip']);
		$this->assertSame('Paris', $effectiveaddressfields['town']);
	}

	/**
	 * Effective address cache must follow manual rehydration of static Contact instances.
	 *
	 * @return void
	 */
	public function testEffectiveAddressCacheFollowsRehydratedStaticContact(): void
	{
		$contact = $this->buildContact();
		$contact->id = 10;
		$contact->socid = 1;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;

		$firstthirdparty = new Societe($this->savdb);
		$firstthirdparty->id = 1;
		$firstthirdparty->address = 'First thirdparty street';

		$this->assertSame($firstthirdparty, $contact->getEffectiveAddressObject($firstthirdparty));

		$contact->id = 11;
		$contact->socid = 2;
		$contact->address = 'Second contact copied street';

		$secondthirdparty = new Societe($this->savdb);
		$secondthirdparty->id = 2;
		$secondthirdparty->address = 'Second thirdparty street';

		$expected = $secondthirdparty;
		$actual = $contact->getEffectiveAddressObject($secondthirdparty);
		$this->assertSame($expected, $actual);

		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_NO;

		$expected = $contact;
		$actual = $contact->getEffectiveAddressObject($secondthirdparty);
		$this->assertSame($expected, $actual);
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

		$contact = $this->buildContactResolvingTo($effective);

		$contact->socid = 1;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->address = 'Old copied street';

		$fulladdress = $contact->getFullAddress();

		$this->assertStringContainsString('Thirdparty street', $fulladdress);
		$this->assertStringNotContainsString('Old copied street', $fulladdress);
	}

	/**
	 * LDAP export must also use the effective postal address object.
	 *
	 * @return void
	 */
	public function testLoadLdapInfoUsesEffectiveAddressObject(): void
	{
		global $conf;

		$conf->global->LDAP_CONTACT_OBJECT_CLASS = 'top,person,organizationalPerson,inetOrgPerson';
		$conf->global->LDAP_CONTACT_FIELD_FULLNAME = 'cn';
		$conf->global->LDAP_CONTACT_FIELD_NAME = 'sn';
		$conf->global->LDAP_CONTACT_FIELD_FIRSTNAME = 'givenName';
		$conf->global->LDAP_CONTACT_FIELD_ADDRESS = 'postalAddress';
		$conf->global->LDAP_CONTACT_FIELD_ZIP = 'postalCode';
		$conf->global->LDAP_CONTACT_FIELD_TOWN = 'l';
		$conf->global->LDAP_CONTACT_FIELD_COUNTRY = 'c';
		$conf->global->LDAP_SERVER_TYPE = '';

		$effective = new Societe($this->savdb);
		$effective->address = 'Thirdparty avenue';
		$effective->zip = '75001';
		$effective->town = 'Paris';
		$effective->country_code = 'FR';

		$contact = $this->buildContactResolvingTo($effective);

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = 'Copied street';
		$contact->zip = '33000';
		$contact->town = 'Bordeaux';

		$info = $contact->_load_ldap_info();

		$this->assertSame('Thirdparty avenue', $info['postalAddress']);
		$this->assertSame('75001', $info['postalCode']);
		$this->assertSame('Paris', $info['l']);
		$this->assertSame('FR', $info['c']);
	}

	/**
	 * Contact tooltip must render the effective postal address instead of the raw copied one.
	 *
	 * @return void
	 */
	public function testTooltipUsesEffectiveAddressObject(): void
	{
		$effective = new Societe($this->savdb);
		$effective->address = 'Thirdparty avenue';
		$effective->zip = '75001';
		$effective->town = 'Paris';
		$effective->country_code = 'FR';
		$effective->country = 'France';

		$contact = $this->buildContactResolvingTo($effective);

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = 'Copied street';
		$contact->zip = '33000';
		$contact->town = 'Bordeaux';
		$contact->email = 'jane@example.test';

		$tooltip = implode($contact->getTooltipContentArray(array()));

		$this->assertStringContainsString('Thirdparty avenue', $tooltip);
		$this->assertStringContainsString('75001', $tooltip);
		$this->assertStringContainsString('Paris', $tooltip);
		$this->assertStringNotContainsString('Copied street', $tooltip);
	}

	/**
	 * Optimized text-browser tooltip mode must not resolve or fetch the effective address.
	 *
	 * @return void
	 */
	public function testOptimizedTooltipDoesNotResolveEffectiveAddress(): void
	{
		global $conf;

		$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = 1;

		$contact = new class($this->savdb) extends Contact {
			/**
			 * @param   Societe|null $thirdparty Unused preloaded thirdparty
			 * @return  CommonObject
			 */
			public function getEffectiveAddressObject(?Societe $thirdparty = null): CommonObject
			{
				throw new Exception('Effective address should not be resolved in optimized tooltip mode.');
			}
		};

		$tooltip = $contact->getTooltipContentArray(array());

		$this->assertSame(array('optimize' => $this->savlangs->trans('ShowContact')), $tooltip);
		unset($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
	}

	/**
	 * Legacy persisted value written on first save must resolve to thirdparty when own postal fields are empty.
	 *
	 * @return void
	 */
	public function testLegacyResolvedPersistedValueUsesThirdpartyForEmptyAddress(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = null;
		$contact->address = '';
		$contact->zip = '';
		$contact->town = '';
		$contact->state_id = 0;
		$contact->country_id = 0;

		$method = new ReflectionMethod(Contact::class, 'getLegacyResolvedUseThirdpartyAddressValue');
		$method->setAccessible(true);

		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, $method->invoke($contact));
	}

	/**
	 * Legacy persisted value written on first save must resolve to contact when a postal field is filled.
	 *
	 * @return void
	 */
	public function testLegacyResolvedPersistedValueUsesContactForFilledAddress(): void
	{
		$contact = $this->buildContact();
		$contact->use_thirdparty_address = null;
		$contact->address = '';
		$contact->zip = '75001';
		$contact->town = '';
		$contact->state_id = 0;
		$contact->country_id = 0;

		$method = new ReflectionMethod(Contact::class, 'getLegacyResolvedUseThirdpartyAddressValue');
		$method->setAccessible(true);

		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, $method->invoke($contact));
	}
}
