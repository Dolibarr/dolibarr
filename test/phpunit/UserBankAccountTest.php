<?php
/* Copyright (C) 2026       Maximilien Valenzano    <slordef.vikamet@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/UserBankAccountTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/userbankaccount.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class UserBankAccountTest extends CommonClassTest
{
	const IBAN_1 = 'FR7630001007941234567890185';
	const IBAN_2 = 'FR7630001007941234567890282';
	const IBAN_3 = 'FR7630001007941234567890379';

	/**
	 * The first bank account of a user in a currency is its default one in this currency, and setting another one
	 * as default keeps only one default per currency.
	 *
	 * @return void
	 */
	public function testOneDefaultPerCurrency()
	{
		global $user, $db;

		$foreigncurrency = $this->getForeignCurrency();
		$userid = $this->createUser();
		$first = $this->createBankAccount($userid, self::IBAN_1, '');
		$second = $this->createBankAccount($userid, self::IBAN_2, getDolCurrency());
		$foreign = $this->createBankAccount($userid, self::IBAN_3, $foreigncurrency);

		$this->assertSame(array($first->id => 1, $second->id => 0, $foreign->id => 1), $this->getDefaults($userid));

		$this->assertGreaterThan(0, $second->setAsDefault(), (string) $second->error);
		$this->assertSame(array($first->id => 0, $second->id => 1, $foreign->id => 1), $this->getDefaults($userid));

		$second->currency_code = $foreigncurrency;
		$this->assertGreaterThan(0, $second->update($user), (string) $second->error);
		$this->assertSame(array($first->id => 1, $second->id => 0, $foreign->id => 1), $this->getDefaults($userid), 'An account moved to another currency must not take the default of this currency');

		$foreign->fetch($foreign->id);
		$this->assertGreaterThan(0, $foreign->delete($user));
		$this->assertSame(array($first->id => 1, $second->id => 1), $this->getDefaults($userid), 'Deleting the default account of a currency must make another one of this currency the default');

		$this->assertCount(2, $first->fetchAllOfUser($userid));
	}

	/**
	 * A bank account is loaded by user and currency as the default one in this currency, an empty currency being the company currency.
	 *
	 * @return void
	 */
	public function testFetchDefaultOfCurrency()
	{
		global $db;

		$foreigncurrency = $this->getForeignCurrency();
		$userid = $this->createUser();
		$this->createBankAccount($userid, self::IBAN_1, $foreigncurrency);
		$companycurrencyaccount = $this->createBankAccount($userid, self::IBAN_2, '');
		$other = $this->createBankAccount($userid, self::IBAN_3, getDolCurrency());
		$this->assertGreaterThan(0, $other->setAsDefault());

		$account = new UserBankAccount($db);
		$account->fetch(0, '', $userid);
		$this->assertSame(self::IBAN_3, $account->iban);
		$this->assertSame(1, $account->default_rib);

		$account = new UserBankAccount($db);
		$account->fetch(0, '', $userid, $foreigncurrency);
		$this->assertSame(self::IBAN_1, $account->iban);

		$account = new UserBankAccount($db);
		$account->fetch(0, '', $userid, 'JPY');
		$this->assertEmpty($account->id);

		$this->assertGreaterThan(0, $companycurrencyaccount->setAsDefault());
		$account = new UserBankAccount($db);
		$account->fetch(0, '', $userid, getDolCurrency());
		$this->assertSame(self::IBAN_2, $account->iban);
	}

	/**
	 * The migration gives each user one default bank account per currency, the oldest one, and an empty currency becomes the company currency.
	 *
	 * @return void
	 */
	public function testMigrationSetsDefault()
	{
		global $db, $conf;

		$foreigncurrency = $this->getForeignCurrency();
		$userid = $this->createUser();
		$ids = array();
		foreach (array(null, getDolCurrency(), $foreigncurrency) as $currency) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rib (fk_user, entity, iban_prefix, currency_code, default_rib)";
			$sql .= " VALUES (".((int) $userid).", ".((int) $conf->entity).", '".self::IBAN_1."', ".($currency === null ? "NULL" : "'".$db->escape($currency)."'").", 0)";
			$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror());
			$ids[] = (int) $db->last_insert_id(MAIN_DB_PREFIX."user_rib");
		}
		$otheruserid = $this->createUser();
		$alreadydefault = $this->createBankAccount($otheruserid, self::IBAN_1, '');
		$this->createBankAccount($otheruserid, self::IBAN_2, '');

		$lines = preg_grep('/^UPDATE llx_user_rib /', file(DOL_DOCUMENT_ROOT.'/install/mysql/migration/'.$this->getMigrationFile()));
		$this->assertCount(2, $lines);
		$sqlfile = $conf->admin->dir_temp.'/user_rib_migration.sql';
		dol_mkdir($conf->admin->dir_temp);
		file_put_contents($sqlfile, implode('', $lines));
		$this->assertEquals(1, run_sql($sqlfile, 1, 0, 1));

		$this->assertSame(array($ids[0] => 1, $ids[1] => 0, $ids[2] => 1), $this->getDefaults($userid));
		$resql = $db->query("SELECT currency_code FROM ".MAIN_DB_PREFIX."user_rib WHERE rowid = ".((int) $ids[0]));
		$this->assertSame(getDolCurrency(), $db->fetch_object($resql)->currency_code);
		$this->assertSame(1, array_sum($this->getDefaults($otheruserid)));
		$this->assertSame(1, $this->getDefaults($otheruserid)[$alreadydefault->id]);
	}

	/**
	 * Return the migration file of the current version
	 *
	 * @return string
	 */
	private function getMigrationFile(): string
	{
		$versions = explode('.', DOL_VERSION);
		return ((int) $versions[0] - 1).'.0.0-'.((int) $versions[0]).'.0.0.sql';
	}

	/**
	 * Return a currency that is not the company currency
	 *
	 * @return string
	 */
	private function getForeignCurrency(): string
	{
		return (getDolCurrency() == 'CHF' ? 'EUR' : 'CHF');
	}

	/**
	 * Return the default flag of each bank account of a user
	 *
	 * @param int $userid Row ID of the user
	 * @return array<int,int> Default flag per bank account id
	 */
	private function getDefaults(int $userid): array
	{
		global $db;

		$defaults = array();
		$resql = $db->query("SELECT rowid, default_rib FROM ".MAIN_DB_PREFIX."user_rib WHERE fk_user = ".((int) $userid)." ORDER BY rowid");
		while ($obj = $db->fetch_object($resql)) {
			$defaults[(int) $obj->rowid] = (int) $obj->default_rib;
		}

		return $defaults;
	}

	/**
	 * Creates a user
	 *
	 * @return int Row ID of the user
	 */
	private function createUser(): int
	{
		global $user, $db;

		$employee = new User($db);
		$employee->login = 'userbankaccounttest'.uniqid();
		$employee->lastname = 'Employee';
		$employeeid = (int) $employee->create($user);
		$this->assertGreaterThan(0, $employeeid, 'User::create() failed: '.$employee->error);

		return $employeeid;
	}

	/**
	 * Creates a bank account for a user
	 *
	 * @param int    $userid   Row ID of the user
	 * @param string $iban     IBAN
	 * @param string $currency Currency code, empty for the company currency
	 * @return UserBankAccount
	 */
	private function createBankAccount(int $userid, string $iban, string $currency): UserBankAccount
	{
		global $user, $db;

		$account = new UserBankAccount($db);
		$account->userid = $userid;
		$account->iban = $iban;
		$account->currency_code = $currency;
		$this->assertGreaterThan(0, $account->create($user), 'UserBankAccount::create() failed: '.$account->error);
		$account->fetch($account->id);

		return $account;
	}
}
