<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/../../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * Test user creation bootstrap from contact effective address.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class UserCreateFromContactAddressTest extends PHPUnit\Framework\TestCase
{
	/**
	 * @var Conf
	 */
	private $savconf;

	/**
	 * @var DoliDB
	 */
	private $savdb;

	/**
	 * @var Translate
	 */
	private $savlangs;

	/**
	 * @var User
	 */
	private $savuser;

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
	 * create_from_contact() must hydrate user postal fields from the effective address object.
	 *
	 * @return void
	 */
	public function testCreateFromContactUsesEffectiveAddressObject(): void
	{
		$effective = new Societe($this->savdb);
		$effective->address = 'Thirdparty avenue';
		$effective->zip = '75001';
		$effective->town = 'Paris';
		$effective->state_id = 12;
		$effective->country_id = 1;

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

		$contact->id = 123;
		$contact->socid = 10;
		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = 'Copied street';
		$contact->zip = '33000';
		$contact->town = 'Bordeaux';

		$userfixture = new class($this->savdb) extends User {
			/**
			 * @param User   $user
			 * @param int    $notrigger
			 * @return int
			 */
			public function create($user, $notrigger = 0)
			{
				$this->id = 999;
				return 1;
			}

			/**
			 * @param string $triggerName
			 * @param User   $user
			 * @return int
			 */
			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};

		$dbstub = new class {
			/**
			 * @return void
			 */
			public function begin()
			{
			}

			/**
			 * @return string
			 */
			public function prefix()
			{
				return 'llx_';
			}

			/**
			 * @param string $value
			 * @return string
			 */
			public function escape($value)
			{
				return addslashes((string) $value);
			}

			/**
			 * @param string $sql
			 * @return bool
			 */
			public function query($sql)
			{
				return true;
			}

			/**
			 * @return void
			 */
			public function commit()
			{
			}

			/**
			 * @return void
			 */
			public function rollback()
			{
			}
		};

		$userfixture->db = $dbstub;

		$result = $userfixture->create_from_contact($contact, 'jdoe');

		$this->assertSame(999, $result);
		$this->assertSame('Thirdparty avenue', $userfixture->address);
		$this->assertSame('75001', $userfixture->zip);
		$this->assertSame('Paris', $userfixture->town);
		$this->assertSame(12, $userfixture->state_id);
		$this->assertSame(1, $userfixture->country_id);
		$this->assertNotSame('Copied street', $userfixture->address);
	}
}
