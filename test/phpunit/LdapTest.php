<?php
/* Copyright (C) 2026 Maximilien Valenzano
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
 *      \file       test/phpunit/LdapTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/ldap.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LdapTest extends CommonClassTest
{
	/**
	 * testRestrictToUserFilterWrapsSearchWhenFilterIsSet
	 *
	 * @return	void
	 */
	public function testRestrictToUserFilterWrapsSearchWhenFilterIsSet()
	{
		$ldap = new Ldap();
		$ldap->filter = '&(objectClass=inetOrgPerson)';

		$this->assertSame('(&(&(objectClass=inetOrgPerson))(uid=jdoe))', $ldap->restrictToUserFilter('(uid=jdoe)'));
	}

	/**
	 * testRestrictToUserFilterKeepsSearchWhenNoFilterIsSet
	 *
	 * @return	void
	 */
	public function testRestrictToUserFilterKeepsSearchWhenNoFilterIsSet()
	{
		$ldap = new Ldap();
		$ldap->filter = '';

		$this->assertSame('(uid=jdoe)', $ldap->restrictToUserFilter('(uid=jdoe)'));
	}
}
