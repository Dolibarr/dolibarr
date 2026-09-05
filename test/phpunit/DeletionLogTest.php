<?php
/* Copyright (C) 2026       Frédéric France             <frederic.france@free.fr>
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
 *      \file       test/phpunit/DeletionLogTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/deletionlog.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/action/class/actioncomm.class.php';
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
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 * @remarks                backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DeletionLogTest extends CommonClassTest
{
	/**
	 * testAddCreatesRow
	 *
	 * @return void
	 */
	public function testAddCreatesRow()
	{
		global $user, $db;
		$db = $this->savdb;

		$id = DeletionLog::add($db, 'unittest_dl_add', 4242, $user, $this->savconf->entity);
		$this->assertGreaterThan(0, $id, 'add() should return the new rowid');

		$sql = "SELECT element_type, fk_object, entity, fk_user FROM ".MAIN_DB_PREFIX."deletion_log WHERE rowid = ".((int) $id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$this->assertSame('unittest_dl_add', $obj->element_type);
		$this->assertEquals(4242, $obj->fk_object);
		$this->assertEquals($this->savconf->entity, $obj->entity);
		$this->assertEquals($user->id, $obj->fk_user);
	}

	/**
	 * testAddRejectsBadInput
	 *
	 * @return void
	 */
	public function testAddRejectsBadInput()
	{
		global $user, $db;
		$db = $this->savdb;

		$this->assertLessThan(0, DeletionLog::add($db, '', 5, $user, 1), 'empty element type must be rejected');
		$this->assertLessThan(0, DeletionLog::add($db, 'unittest_dl_bad', 0, $user, 1), 'non positive id must be rejected');
	}

	/**
	 * testGetDeletionsSinceReturnsRecentIdsOnly
	 *
	 * @return void
	 */
	public function testGetDeletionsSinceReturnsRecentIdsOnly()
	{
		global $user, $db;
		$db = $this->savdb;

		$element = 'unittest_dl_since';
		$entity = $this->savconf->entity;
		$now = dol_now();

		// One deletion 10 days ago, two "just now".
		$this->insertRow($db, $element, 100, $now - 10 * 24 * 3600, $entity);
		DeletionLog::add($db, $element, 101, $user, $entity);
		DeletionLog::add($db, $element, 102, $user, $entity);
		// A different element type must be ignored.
		DeletionLog::add($db, 'unittest_dl_other', 103, $user, $entity);

		$ids = DeletionLog::getDeletionsSince($db, $element, $now - 24 * 3600, $entity);

		sort($ids);
		$this->assertSame(array(101, 102), $ids);
	}

	/**
	 * testPurgeDropsExpiredRowsOnly
	 *
	 * @return void
	 */
	public function testPurgeDropsExpiredRowsOnly()
	{
		global $db;
		$db = $this->savdb;

		$element = 'unittest_dl_purge';
		$entity = $this->savconf->entity;
		$now = dol_now();

		$old = $this->insertRow($db, $element, 200, $now - 40 * 24 * 3600, $entity);
		$fresh = $this->insertRow($db, $element, 201, $now - 2 * 24 * 3600, $entity);

		$nb = DeletionLog::purge($db, 30);
		$this->assertGreaterThanOrEqual(1, $nb, 'purge() should report the number of removed rows');

		$this->assertFalse($this->rowExists($db, $old), 'row older than retention must be gone');
		$this->assertTrue($this->rowExists($db, $fresh), 'row inside retention must be kept');
	}

	/**
	 * testCronMethodReturnsSuccess
	 *
	 * @return void
	 */
	public function testCronMethodReturnsSuccess()
	{
		global $db;
		$db = $this->savdb;

		$now = dol_now();
		$this->insertRow($db, 'unittest_dl_cron', 300, $now - 400 * 24 * 3600, $this->savconf->entity);

		$o = new DeletionLog($db);
		$res = $o->purgeDeletionLog();

		$this->assertSame(0, $res, 'cron method must return 0 on success');
		$this->assertNotEmpty($o->output, 'cron method must fill $this->output');
	}

	/**
	 * testTriggerRecordsAgendaEventDeletion
	 *
	 * @return void
	 */
	public function testTriggerRecordsAgendaEventDeletion()
	{
		global $user, $db;
		$db = $this->savdb;

		$now = dol_now();

		$event = new ActionComm($db);
		$event->type_code = 'AC_OTH';
		$event->code = 'AC_PHPUNITTEST_DL';
		$event->label = 'Deletion log trigger test';
		$event->datep = $now;
		$event->datef = $now;
		$event->percentage = -1;
		$event->authorid = $user->id;
		$event->userownerid = $user->id;
		$eventid = $event->create($user);
		$this->assertGreaterThan(0, $eventid, 'the test agenda event must be created');

		$res = $event->delete($user);
		$this->assertGreaterThan(0, $res, 'the test agenda event must be deleted');

		$ids = DeletionLog::getDeletionsSince($db, 'action', $now - 3600, $this->savconf->entity);
		$this->assertContains((int) $eventid, $ids, 'the ACTION_DELETE trigger must record a tombstone row');
	}

	/**
	 * Insert a row with an explicit deletion date and return its rowid.
	 *
	 * @param  DoliDB $db         Database handler
	 * @param  string $element    Element type
	 * @param  int    $fk_object  Deleted object id
	 * @param  int    $date       Deletion date (unix timestamp)
	 * @param  int    $entity     Entity
	 * @return int                Inserted rowid
	 */
	private function insertRow($db, $element, $fk_object, $date, $entity)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deletion_log(entity, element_type, fk_object, date_deletion, fk_user)";
		$sql .= " VALUES (".((int) $entity).", '".$db->escape($element)."', ".((int) $fk_object).", '".$db->idate($date)."', null)";
		$db->query($sql);
		return (int) $db->last_insert_id(MAIN_DB_PREFIX."deletion_log");
	}

	/**
	 * @param  DoliDB $db     Database handler
	 * @param  int    $rowid  Row id
	 * @return bool           True if the row still exists
	 */
	private function rowExists($db, $rowid)
	{
		$resql = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."deletion_log WHERE rowid = ".((int) $rowid));
		return (bool) $db->num_rows($resql);
	}
}
