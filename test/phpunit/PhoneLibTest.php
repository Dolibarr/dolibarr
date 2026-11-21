<?php
/* Copyright (C) 2026	Open-Dsi	<support@open-dsi.fr>
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
 *      \file       test/phpunit/PhoneLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/phone.lib.php';
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
class PhoneLibTest extends CommonClassTest
{
	/**
	 * Search contacts with dol_natural_search_phone() and return the found rowids
	 *
	 * @param	string		$search		Value entered by the user
	 * @param	int[]		$ids		Restrict the search to these contact ids
	 * @return	int[]					Found contact ids
	 */
	private function searchContacts($search, $ids)
	{
		$db = $this->savdb;

		$sql = "SELECT p.rowid FROM ".$db->prefix()."socpeople as p";
		$sql .= " WHERE p.rowid IN (".$db->sanitize(implode(',', $ids)).")";
		$sql .= dol_natural_search_phone($db, array('p.phone', 'p.phone_mobile'), $search);
		$sql .= " ORDER BY p.rowid";

		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'SQL error for search "'.$search.'": '.$db->lasterror());

		$found = array();
		while ($obj = $db->fetch_object($resql)) {
			$found[] = (int) $obj->rowid;
		}
		$db->free($resql);

		return $found;
	}

	/**
	 * Insert a contact with a phone number
	 *
	 * @param	string	$phone	Stored phone value
	 * @return	int				Contact id
	 */
	private function insertContact($phone)
	{
		$db = $this->savdb;

		$sql = "INSERT INTO ".$db->prefix()."socpeople (lastname, phone, entity, datec, fk_user_creat)";
		$sql .= " VALUES ('PhoneLibTest', '".$db->escape($phone)."', 1, '".$db->idate(dol_now())."', NULL)";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, (string) $db->lasterror());

		return (int) $db->last_insert_id($db->prefix()."socpeople");
	}

	/**
	 * testDolNaturalSearchPhoneEmpty
	 *
	 * @return	void
	 */
	public function testDolNaturalSearchPhoneEmpty()
	{
		$db = $this->savdb;

		$this->assertSame('', dol_natural_search_phone($db, 'p.phone', ''));
		$this->assertSame('', dol_natural_search_phone($db, 'p.phone', '   '));
	}

	/**
	 * testDolNaturalSearchPhoneSql
	 *
	 * @return	void
	 */
	public function testDolNaturalSearchPhoneSql()
	{
		$db = $this->savdb;

		$s = dol_natural_search_phone($db, 'p.phone', '+33 6 12');
		print __METHOD__." s=".$s."\n";
		$this->assertStringStartsWith(' AND (', $s);
		$this->assertStringContainsString("REPLACE(COALESCE(p.phone, ''), ' ', '')", $s);
		$this->assertStringContainsString("LIKE '%+33612%'", $s);
		$this->assertStringContainsString("LIKE '%0612%'", $s);

		$s = dol_natural_search_phone($db, 'p.phone', '0612', 1);
		$this->assertStringStartsWith('(', $s);
	}

	/**
	 * testDolNaturalSearchPhoneFindsStoredNumbers
	 *
	 * @return	void
	 */
	public function testDolNaturalSearchPhoneFindsStoredNumbers()
	{
		$idintl = $this->insertContact('+33 612345678');
		$idnational = $this->insertContact('06 12 34 56 78');
		// Witness without "1" nor "2": "06" is also a trunk prefix (Hungary), so "06 12" searches "12" too
		$idother = $this->insertContact('+33 987654309');
		$ids = array($idintl, $idnational, $idother);

		$searches = array('0612345678', '06 12 34 56 78', '+33612345678', '+33 6 12 34 56 78', '612345678', '+33 (0)6 12 34 56 78', '+33 06 12 34 56 78');
		foreach ($searches as $search) {
			$found = $this->searchContacts($search, $ids);
			$this->assertContains($idintl, $found, 'Search "'.$search.'" must find "+33 612345678"');
			// A code glued to the number ("+33612...") is not recognized by dol_parse_phone(), so its national format is not searched
			if ($search !== '+33612345678') {
				$this->assertContains($idnational, $found, 'Search "'.$search.'" must find "06 12 34 56 78"');
			}
			$this->assertNotContains($idother, $found, 'Search "'.$search.'" must not find "+33 987654309"');
		}

		$found = $this->searchContacts('06 12', $ids);
		$this->assertContains($idnational, $found, 'Search "06 12" must find "06 12 34 56 78"');
		$this->assertContains($idintl, $found, 'Search "06 12" must find "+33 612345678"');
		$this->assertNotContains($idother, $found);
	}

	/**
	 * testDolNaturalSearchPhoneOperators
	 *
	 * @return	void
	 */
	public function testDolNaturalSearchPhoneOperators()
	{
		$idintl = $this->insertContact('+33 612345678');
		$idother = $this->insertContact('+33 987654309');
		$idempty = $this->insertContact('');
		$idnational = $this->insertContact('06 12 34 56 78');
		$ids = array($idintl, $idother, $idempty, $idnational);

		// "|" is a OR between values
		$this->assertSame(array($idintl, $idother, $idnational), $this->searchContacts('06 12 34|09 87 65', $ids));
		// natural_search() operators are kept
		$this->assertSame(array($idempty), $this->searchContacts('^$', $ids));
		// Exclusion of an international value does not add its national format
		$this->assertSame(array($idother, $idempty, $idnational), $this->searchContacts('!+33 6', $ids));
		// A code without trunk prefix (Spain) must not search the number alone
		$this->assertSame(array(), $this->searchContacts('+34 6', $ids));
		$this->assertSame(array($idintl, $idnational), $this->searchContacts('+33 6*78', $ids));
		$this->assertSame(array($idintl, $idnational), $this->searchContacts('06 12*78', $ids));
		$this->assertSame(array($idnational), $this->searchContacts('^06 12', $ids));
		$this->assertSame(array($idintl, $idnational), $this->searchContacts('^+33 6', $ids));
		$this->assertSame(array($idintl, $idnational), $this->searchContacts('56 78$', $ids));
		// Exclusion does not strip the trunk prefix: "!06" must not exclude every number containing "6"
		$this->assertSame(array($idintl, $idother, $idempty), $this->searchContacts('!06', $ids));
		// A code followed by the trunk prefix only must not search "0"
		$this->assertSame(array(), $this->searchContacts('+33 0', $ids));
	}

	/**
	 * testDolNaturalSearchPhoneEscaping
	 *
	 * @return	void
	 */
	public function testDolNaturalSearchPhoneEscaping()
	{
		$id = $this->insertContact('+33 612345678');

		// A quote must not break the SQL string, % and _ must not act as wildcards
		foreach (array("06' OR '1'='1", '06%78', '06_2', "06\\'") as $search) {
			$found = $this->searchContacts($search, array($id));
			$this->assertNotContains($id, $found, 'Search "'.$search.'" must not find "+33 612345678"');
		}
	}
}
