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

// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName

/**
 * Test persistence contract for use_thirdparty_address.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @phan-file-suppress PhanUndeclaredMethod
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanTypeMismatchArgument
 * @phan-file-suppress PhanTypeMismatchArgumentProbablyReal
 */
class ContactPersistenceAddressModeTest extends PHPUnit\Framework\TestCase  // @phan-suppress-current-line PhanUndeclaredExtendedClass
{
	/**
	 * Create() must default to thirdparty address when a linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testCreateDefaultsToThirdpartyAddressWhenSocidExists(): void
	{
		// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName
		$dbstub = new class {
			public $queries = array();

			public function begin()
			{
			}

			public function commit()
			{
			}

			public function rollback()
			{
			}

			public function idate($value)
			{
				return '2026-05-04 00:00:00';
			}

			public function escape($value)
			{
				return addslashes((string) $value);
			}

			public function query($sql)
			{
				$this->queries[] = $sql;
				return true;
			}

			public function last_insert_id($table)
			{
				return 123;
			}

			public function lasterror()
			{
				return '';
			}
		};

		$contact = new class($dbstub) extends Contact {
			public function update($id, $user = null, $notrigger = 0, $action = 'update', $nosyncuser = 0)
			{
				return 1;
			}

			public function update_perso($id, $user = null, $notrigger = 0)
			{
				return 1;
			}

			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};
		// phpcs:enable

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->socid = 10;
		$contact->use_thirdparty_address = null;

		$result = $contact->create((object) array('id' => 1), 1);

		$this->assertSame(123, $result);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, $contact->use_thirdparty_address);
		$this->assertStringContainsString('use_thirdparty_address', $dbstub->queries[0]);
		$this->assertStringContainsString(', 10, 1,', preg_replace('/\s+/', ' ', $dbstub->queries[0]));
	}

	/**
	 * Create() must default to contact address when no linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testCreateDefaultsToContactAddressWithoutSocid(): void
	{
		// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName
		$dbstub = new class {
			public $queries = array();

			public function begin()
			{
			}

			public function commit()
			{
			}

			public function rollback()
			{
			}

			public function idate($value)
			{
				return '2026-05-04 00:00:00';
			}

			public function escape($value)
			{
				return addslashes((string) $value);
			}

			public function query($sql)
			{
				$this->queries[] = $sql;
				return true;
			}

			public function last_insert_id($table)
			{
				return 124;
			}

			public function lasterror()
			{
				return '';
			}
		};

		$contact = new class($dbstub) extends Contact {
			public function update($id, $user = null, $notrigger = 0, $action = 'update', $nosyncuser = 0)
			{
				return 1;
			}

			public function update_perso($id, $user = null, $notrigger = 0)
			{
				return 1;
			}

			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};
		// phpcs:enable

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->socid = 0;
		$contact->use_thirdparty_address = null;

		$result = $contact->create((object) array('id' => 1), 1);

		$this->assertSame(124, $result);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, $contact->use_thirdparty_address);
		$this->assertStringContainsString('null, 0,', preg_replace('/\s+/', ' ', $dbstub->queries[0]));
	}

	/**
	 * Create() must preserve legacy explicit contact addresses when a linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testCreateKeepsContactAddressWhenOwnPostalFieldsAreProvided(): void
	{
		// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName
		$dbstub = new class {
			public $queries = array();

			public function begin()
			{
			}

			public function commit()
			{
			}

			public function rollback()
			{
			}

			public function idate($value)
			{
				return '2026-05-04 00:00:00';
			}

			public function escape($value)
			{
				return addslashes((string) $value);
			}

			public function query($sql)
			{
				$this->queries[] = $sql;
				return true;
			}

			public function last_insert_id($table)
			{
				return 125;
			}

			public function lasterror()
			{
				return '';
			}
		};

		$contact = new class($dbstub) extends Contact {
			public function update($id, $user = null, $notrigger = 0, $action = 'update', $nosyncuser = 0)
			{
				return 1;
			}

			public function update_perso($id, $user = null, $notrigger = 0)
			{
				return 1;
			}

			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};
		// phpcs:enable

		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->socid = 10;
		$contact->address = '21 Jump Street';
		$contact->zip = '33000';
		$contact->town = 'Bordeaux';
		$contact->use_thirdparty_address = null;

		$result = $contact->create((object) array('id' => 1), 1);

		$this->assertSame(125, $result);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, $contact->use_thirdparty_address);
		$this->assertStringContainsString(', 10, 0,', preg_replace('/\s+/', ' ', $dbstub->queries[0]));
	}

	/**
	 * Update() must normalize legacy null to explicit thirdparty mode when own postal fields are empty.
	 *
	 * @return void
	 */
	public function testUpdateNormalizesLegacyNullToThirdpartyMode(): void
	{
		// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName
		$dbstub = new class {
			public $queries = array();

			public function begin()
			{
			}

			public function commit()
			{
			}

			public function rollback()
			{
			}

			public function escape($value)
			{
				return addslashes((string) $value);
			}

			public function idate($value)
			{
				return '2026-05-04 00:00:00';
			}

			public function query($sql)
			{
				$this->queries[] = $sql;
				return true;
			}

			public function lasterror()
			{
				return '';
			}
		};

		$contact = new class($dbstub) extends Contact {
			public function insertExtraFields($trigger = '', $userused = null)
			{
				return 1;
			}

			public function updateRoles()
			{
				return 1;
			}

			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};
		// phpcs:enable

		$contact->id = 50;
		$contact->socid = 10;
		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = '';
		$contact->zip = '';
		$contact->town = '';
		$contact->state_id = 0;
		$contact->country_id = 0;
		$contact->use_thirdparty_address = null;
		$contact->socialnetworks = array();
		$contact->phone_pro = '';
		$contact->phone_perso = '';
		$contact->phone_mobile = '';
		$contact->fax = '';
		$contact->email = '';
		$contact->photo = '';

		$result = $contact->update(50, (object) array('id' => 1), 1);

		$this->assertSame(1, $result);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, $contact->use_thirdparty_address);
		$this->assertStringContainsString('use_thirdparty_address=1', preg_replace('/\s+/', ' ', $dbstub->queries[0]));
	}

	/**
	 * Update() must force explicit contact mode when no linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testUpdateForcesContactModeWithoutSocid(): void
	{
		// phpcs:disable Squiz.Commenting.FunctionComment,PEAR.Commenting.FunctionComment,PEAR.NamingConventions.ValidFunctionName
		$dbstub = new class {
			public $queries = array();

			public function begin()
			{
			}

			public function commit()
			{
			}

			public function rollback()
			{
			}

			public function escape($value)
			{
				return addslashes((string) $value);
			}

			public function idate($value)
			{
				return '2026-05-04 00:00:00';
			}

			public function query($sql)
			{
				$this->queries[] = $sql;
				return true;
			}

			public function lasterror()
			{
				return '';
			}
		};

		$contact = new class($dbstub) extends Contact {
			public function insertExtraFields($trigger = '', $userused = null)
			{
				return 1;
			}

			public function updateRoles()
			{
				return 1;
			}

			public function call_trigger($triggerName, $user)
			{
				return 1;
			}
		};
		// phpcs:enable

		$contact->id = 51;
		$contact->socid = 0;
		$contact->lastname = 'Doe';
		$contact->firstname = 'Jane';
		$contact->address = '';
		$contact->zip = '';
		$contact->town = '';
		$contact->state_id = 0;
		$contact->country_id = 0;
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$contact->socialnetworks = array();
		$contact->phone_pro = '';
		$contact->phone_perso = '';
		$contact->phone_mobile = '';
		$contact->fax = '';
		$contact->email = '';
		$contact->photo = '';

		$result = $contact->update(51, (object) array('id' => 1), 1);

		$this->assertSame(1, $result);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, $contact->use_thirdparty_address);
		$this->assertStringContainsString('use_thirdparty_address=0', preg_replace('/\s+/', ' ', $dbstub->queries[0]));
	}
}
// phpcs:enable
