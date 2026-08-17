<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/api/class/api.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/api_contacts.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/includes/restler/framework/Luracast/Restler/Scope.php';
require_once dirname(__FILE__).'/../../htdocs/includes/restler/framework/Luracast/Restler/RestException.php';

use Luracast\Restler\RestException;
use Luracast\Restler\Scope;

/**
 * Test API validation for use_thirdparty_address.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ApiContactsUseThirdpartyAddressTest extends CommonClassTest
{
	/**
	 * @var Conf
	 */
	protected $savconf;

	/**
	 * @var Contacts
	 */
	private $api;

	/**
	 * @var DoliDB
	 */
	protected $savdb;

	/**
	 * Build API fixture.
	 *
	 * @param string $name Test name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		global $conf, $db;
		$this->savconf = $conf;
		$this->savdb = $db;
		$this->api = new Contacts();
		Scope::set('Restler', new class {
			/**
			 * @return array<int,string>
			 */
			public function getEvents(): array
			{
				return array('setup');
			}
		});
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
	 * API validator must accept explicit values and null.
	 *
	 * @return void
	 */
	public function testValidateUseThirdpartyAddressValue(): void
	{
		$method = new ReflectionMethod(Contacts::class, 'validateUseThirdpartyAddressValue');
		$method->setAccessible(true);

		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, $method->invoke($this->api, '0'));
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, $method->invoke($this->api, '1'));
		$this->assertNull($method->invoke($this->api, null));
	}

	/**
	 * API validator must reject unexpected values.
	 *
	 * @return void
	 */
	public function testValidateUseThirdpartyAddressValueRejectsInvalidValue(): void
	{
		$method = new ReflectionMethod(Contacts::class, 'validateUseThirdpartyAddressValue');
		$method->setAccessible(true);

		$this->expectException(RestException::class);
		$method->invoke($this->api, '2');
	}

	/**
	 * API validator must reject inconsistent flag without thirdparty.
	 *
	 * @return void
	 */
	public function testAssertUseThirdpartyAddressIsConsistentRejectsInvalidCombination(): void
	{
		$method = new ReflectionMethod(Contacts::class, 'assertUseThirdpartyAddressIsConsistent');
		$method->setAccessible(true);

		$contact = new Contact($this->savdb);
		$contact->socid = 0;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;

		$this->expectException(RestException::class);
		$method->invoke($this->api, $contact);
	}

	/**
	 * API consistency validator must accept contact mode without thirdparty.
	 *
	 * @return void
	 */
	public function testAssertUseThirdpartyAddressIsConsistentAcceptsContactModeWithoutThirdparty(): void
	{
		$method = new ReflectionMethod(Contacts::class, 'assertUseThirdpartyAddressIsConsistent');
		$method->setAccessible(true);

		$contact = new Contact($this->savdb);
		$contact->socid = 0;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_NO;

		$this->assertNull($method->invoke($this->api, $contact));
	}
}
