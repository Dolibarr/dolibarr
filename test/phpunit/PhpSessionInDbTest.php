<?php
/* Copyright (C) 2026  Frédéric France     <frederic.france@free.fr>
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
 *      \file       test/phpunit/PhpSessionInDbTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the database session handler helpers
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/phpsessionindb.lib.php';
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
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PhpSessionInDbTest extends CommonClassTest
{
	/** @var int */
	const FKUSER1 = 990001;
	/** @var int */
	const FKUSER2 = 990002;

	/**
	 * Insert a fake row into llx_session.
	 *
	 * @param	string	$sessionid		Session id to use
	 * @param	int		$fk_user		Owner user id
	 * @param	int		$secondsago		Age of last_accessed, in seconds before now
	 * @return	void
	 */
	private function insertSession($sessionid, $fk_user, $secondsago)
	{
		global $db;

		$now = dol_now();
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."session";
		$sql .= "(session_id, session_variable, date_creation, last_accessed, fk_user, remote_ip, user_agent)";
		$sql .= " VALUES ('".$db->escape($sessionid)."', '', '".$db->idate($now - $secondsago)."', '".$db->idate($now - $secondsago)."', ".((int) $fk_user).", '127.0.0.1', 'phpunit')";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Failed to insert fake session '.$sessionid.': '.$db->lasterror());
	}

	/**
	 * Return the list of session ids currently stored for a given user.
	 *
	 * @param	int		$fk_user	Owner user id
	 * @return	string[]			Session ids, ordered by session_id
	 */
	private function sessionIdsForUser($fk_user)
	{
		global $db;

		$ids = array();
		$sql = "SELECT session_id FROM ".MAIN_DB_PREFIX."session WHERE fk_user = ".((int) $fk_user)." ORDER BY session_id";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Failed to read sessions: '.$db->lasterror());
		while ($obj = $db->fetch_object($resql)) {
			$ids[] = $obj->session_id;
		}
		return $ids;
	}

	/**
	 * testDolSessionsLimitForUser
	 *
	 * @return	void
	 */
	public function testDolSessionsLimitForUser()
	{
		global $db;

		// The helper works on the global $dbsession connection, like the rest of phpsessionindb.lib.php.
		$GLOBALS['dbsession'] = $db;

		// --- Case 1: keepcount = 1 evicts every other session of that user, and never touches other users.
		$this->insertSession('ut-sess-A', self::FKUSER1, 300);
		$this->insertSession('ut-sess-B', self::FKUSER1, 200);
		$this->insertSession('ut-sess-C', self::FKUSER1, 100);
		$this->insertSession('ut-sess-X', self::FKUSER2, 100);

		$nb = dolSessionsLimitForUser(self::FKUSER1, 1, 'ut-sess-CUR');
		$this->assertEquals(3, $nb, 'keepcount=1 should evict the 3 existing sessions');
		$this->assertEquals(array(), $this->sessionIdsForUser(self::FKUSER1), 'no session should remain for user 1');
		$this->assertEquals(array('ut-sess-X'), $this->sessionIdsForUser(self::FKUSER2), 'sessions of other users must be left untouched');

		// --- Case 2: keepcount = 2 keeps the single most recent session and evicts the rest.
		$this->insertSession('ut-sess-A', self::FKUSER1, 300);
		$this->insertSession('ut-sess-B', self::FKUSER1, 200);
		$this->insertSession('ut-sess-C', self::FKUSER1, 100);

		$nb = dolSessionsLimitForUser(self::FKUSER1, 2, 'ut-sess-CUR');
		$this->assertEquals(2, $nb, 'keepcount=2 should evict the 2 oldest of the 3 existing sessions');
		$this->assertEquals(array('ut-sess-C'), $this->sessionIdsForUser(self::FKUSER1), 'only the most recent session must survive');

		// --- Case 3: the session being established is never evicted, even when it is the oldest row.
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."session WHERE fk_user = ".self::FKUSER1);
		$this->insertSession('ut-sess-CUR', self::FKUSER1, 500);
		$this->insertSession('ut-sess-D', self::FKUSER1, 100);

		$nb = dolSessionsLimitForUser(self::FKUSER1, 1, 'ut-sess-CUR');
		$this->assertEquals(1, $nb, 'only the other session must be evicted');
		$this->assertEquals(array('ut-sess-CUR'), $this->sessionIdsForUser(self::FKUSER1), 'the current session must survive');

		// --- Case 4: a non-positive keepcount is a no-op (feature disabled).
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."session WHERE fk_user = ".self::FKUSER1);
		$this->insertSession('ut-sess-E', self::FKUSER1, 100);

		$nb = dolSessionsLimitForUser(self::FKUSER1, 0, 'ut-sess-CUR');
		$this->assertEquals(0, $nb, 'keepcount=0 must not evict anything');
		$this->assertEquals(array('ut-sess-E'), $this->sessionIdsForUser(self::FKUSER1), 'keepcount=0 must leave every session in place');
	}
}
