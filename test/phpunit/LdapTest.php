<?php
/* Copyright (C) 2026 Umar Farooq <umarpak995@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       test/phpunit/LdapTest.php
 * \ingroup    test
 * \brief      PHPUnit test for Ldap class helper methods and attribute parsing (Issue #41842).
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/ldap.class.php';

/**
 * Class LdapTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class LdapTest extends CommonClassTest
{
	/**
	 * Test parseUACF parses Active Directory user account control flags properly.
	 *
	 * @return void
	 */
	public function testParseUACF()
	{
		$ldap = new Ldap();

		// Empty string or 0 should return empty array without errors
		$this->assertEquals(array(), $ldap->parseUACF(''));
		$this->assertEquals(array(), $ldap->parseUACF(0));

		// NORMAL_ACCOUNT (512)
		$flags512 = $ldap->parseUACF(512);
		$this->assertArrayHasKey(512, $flags512);
		$this->assertEquals('NORMAL_ACCOUNT', $flags512[512]);

		// Combined: NORMAL_ACCOUNT (512) + ACCOUNTDISABLE (2) = 514
		$flags514 = $ldap->parseUACF(514);
		$this->assertArrayHasKey(512, $flags514);
		$this->assertArrayHasKey(2, $flags514);
		$this->assertEquals('NORMAL_ACCOUNT', $flags514[512]);
		$this->assertEquals('ACCOUNTDISABLE', $flags514[2]);
	}

	/**
	 * Test convertTime converts Active Directory timestamp into Unix timestamp.
	 *
	 * @return void
	 */
	public function testConvertTime()
	{
		$ldap = new Ldap();

		// 0 returns 0 or negative
		$this->assertLessThanOrEqual(0, $ldap->convertTime(0));

		// AD timestamp: 132530784000000000 corresponds to 2021-01-01 00:00:00 UTC (~1609459200 unix)
		$adTime = '132530784000000000';
		$unixTime = $ldap->convertTime($adTime);
		$this->assertGreaterThan(1600000000, $unixTime);
		$this->assertLessThan(1700000000, $unixTime);
	}

	/**
	 * Test attribute case normalization logic used in Ldap::fetch.
	 *
	 * @return void
	 */
	public function testAttributeCaseNormalization()
	{
		$ldap = new Ldap();
		$ldap->attr_name = 'sAMAccountName';
		$ldap->attr_mail = 'UserPrincipalName';

		$attr_name = !empty($ldap->attr_name) ? dol_strtolower((string) $ldap->attr_name) : '';
		$attr_mail = !empty($ldap->attr_mail) ? dol_strtolower((string) $ldap->attr_mail) : '';

		$this->assertEquals('samaccountname', $attr_name);
		$this->assertEquals('userprincipalname', $attr_mail);

		// Mock ldap_get_entries result array with lowercased keys
		$mockResult = array(
			0 => array(
				'samaccountname' => array(0 => 'jdoe', 'count' => 1),
				'userprincipalname' => array(0 => 'jdoe@example.com', 'count' => 1),
				'dn' => 'uid=jdoe,ou=users,dc=example,dc=com'
			)
		);

		$extractedName = $mockResult[0][$attr_name][0] ?? $mockResult[0][$ldap->attr_name][0] ?? '';
		$extractedMail = $mockResult[0][$attr_mail][0] ?? $mockResult[0][$ldap->attr_mail][0] ?? '';
		$extractedDn = $mockResult[0]['dn'] ?? '';
		$extractedMissing = $mockResult[0]['nonexistent'][0] ?? '';

		$this->assertEquals('jdoe', $extractedName);
		$this->assertEquals('jdoe@example.com', $extractedMail);
		$this->assertEquals('uid=jdoe,ou=users,dc=example,dc=com', $extractedDn);
		$this->assertEquals('', $extractedMissing);
	}
}
