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
	 * Build a lightweight user object with an explicit agenda read permission set, without
	 * touching the database: DeletionLog::getDeletionsSince() only reads
	 * $user->id/$user->socid/$user->rights->agenda->{myactions,allactions}->read.
	 *
	 * @param  int   $id          User id to fake
	 * @param  bool  $myactions   Grant agenda->myactions->read
	 * @param  bool  $allactions  Grant agenda->allactions->read
	 * @return User
	 */
	private function fakeUser($id, $myactions, $allactions)
	{
		$fakeuser = new User($this->savdb);
		$fakeuser->id = $id;
		$fakeuser->socid = 0;
		$fakeuser->rights = new stdClass();
		$fakeuser->rights->agenda = new stdClass();
		$fakeuser->rights->agenda->myactions = new stdClass();
		$fakeuser->rights->agenda->myactions->read = $myactions ? 1 : 0;
		$fakeuser->rights->agenda->allactions = new stdClass();
		$fakeuser->rights->agenda->allactions->read = $allactions ? 1 : 0;

		return $fakeuser;
	}

	/**
	 * Build a minimal ActionComm-like object (never inserted in DB) to feed DeletionLog::add(),
	 * the same way ActionComm::delete() feeds it from the real, just-deleted event.
	 *
	 * @param  int    $id               Fake actioncomm id
	 * @param  string $uid              Fake event uid
	 * @param  int    $fkuseraction     Owner user id
	 * @param  int[]  $assigneduserids  Assigned user ids
	 * @param  int    $entity           Entity
	 * @return ActionComm
	 */
	private function fakeEvent($id, $uid, $fkuseraction, array $assigneduserids, $entity)
	{
		$event = new ActionComm($this->savdb);
		$event->id = $id;
		$event->uid = $uid;
		$event->entity = $entity;
		$event->userownerid = $fkuseraction;
		$event->userassigned = array();
		foreach ($assigneduserids as $assigneduserid) {
			$event->userassigned[$assigneduserid] = array('id' => $assigneduserid);
		}

		return $event;
	}

	/**
	 * testAddCreatesRow
	 *
	 * @return void
	 */
	public function testAddCreatesRow()
	{
		global $user, $db;
		$db = $this->savdb;

		$uniq = 'zttestdladd'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		$event = $this->fakeEvent(999901, $uniq, 42, array(42, 43), $this->savconf->entity);

		$o = new DeletionLog($db);
		$id = $o->add($event, $user);
		$this->assertGreaterThan(0, $id, 'add() should return the new rowid');

		$sql = "SELECT fk_actioncomm, uid, fk_user_action, assigned_users, entity, fk_user FROM ".MAIN_DB_PREFIX."deletion_log WHERE rowid = ".((int) $id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$this->assertEquals(999901, $obj->fk_actioncomm);
		$this->assertSame($uniq, $obj->uid);
		$this->assertEquals(42, $obj->fk_user_action);
		$this->assertSame('42,43', $obj->assigned_users);
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

		$event = $this->fakeEvent(0, 'not-used', 0, array(), 1);

		$o = new DeletionLog($db);
		$this->assertLessThan(0, $o->add($event, $user), 'a non positive object id must be rejected');
	}

	/**
	 * testGetDeletionsSinceFiltersByOwnerOrAssignedOrAllactions
	 *
	 * @return void
	 */
	public function testGetDeletionsSinceFiltersByOwnerOrAssignedOrAllactions()
	{
		global $db;
		$db = $this->savdb;

		$entity = $this->savconf->entity;
		$now = dol_now();

		$owneruid = 'zttestdlowner'.dol_print_date($now, '%Y%m%d%H%M%S');
		$assigneduid = 'zttestdlassigned'.dol_print_date($now, '%Y%m%d%H%M%S');

		$o = new DeletionLog($db);
		// Owned by user 501, no one else assigned.
		$idowned = $o->add($this->fakeEvent(999902, $owneruid, 501, array(), $entity), null);
		// Owned by 601, but 502 is assigned.
		$idassigned = $o->add($this->fakeEvent(999903, $assigneduid, 601, array(502, 503), $entity), null);
		$this->assertGreaterThan(0, $idowned);
		$this->assertGreaterThan(0, $idassigned);

		$since = $now - 3600;

		// A user with allactions sees both.
		$admin = $this->fakeUser(999, false, true);
		$got = $o->getDeletionsSince($since, $admin, $entity);
		$this->assertContains($owneruid, $got);
		$this->assertContains($assigneduid, $got);

		// The owner of the first event (myactions only) sees only that one.
		$owner = $this->fakeUser(501, true, false);
		$got = $o->getDeletionsSince($since, $owner, $entity);
		$this->assertContains($owneruid, $got);
		$this->assertNotContains($assigneduid, $got);

		// A user assigned to the second event (myactions only) sees only that one.
		$assignee = $this->fakeUser(502, true, false);
		$got = $o->getDeletionsSince($since, $assignee, $entity);
		$this->assertContains($assigneduid, $got);
		$this->assertNotContains($owneruid, $got);

		// A stranger with only myactions sees neither.
		$stranger = $this->fakeUser(777, true, false);
		$got = $o->getDeletionsSince($since, $stranger, $entity);
		$this->assertNotContains($owneruid, $got);
		$this->assertNotContains($assigneduid, $got);

		// A user with no agenda read right at all sees nothing (and no error).
		$norights = $this->fakeUser(778, false, false);
		$this->assertSame(array(), $o->getDeletionsSince($since, $norights, $entity));
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

		$entity = $this->savconf->entity;
		$now = dol_now();

		$old = $this->insertRow($db, 999904, 'zttestdlold', $now - 40 * 24 * 3600, $entity);
		$fresh = $this->insertRow($db, 999905, 'zttestdlfresh', $now - 2 * 24 * 3600, $entity);

		$o = new DeletionLog($db);
		$nb = $o->purge(30);
		$this->assertGreaterThanOrEqual(1, $nb, 'purge() should report the number of removed rows');

		$this->assertFalse($this->rowExists($db, $old), 'row older than retention must be gone');
		$this->assertTrue($this->rowExists($db, $fresh), 'row inside retention must be kept');
	}

	/**
	 * testDeleteRecordsAgendaEventDeletion
	 *
	 * @return void
	 */
	public function testDeleteRecordsAgendaEventDeletion()
	{
		global $user, $db;
		$db = $this->savdb;

		$now = dol_now();
		$entity = $this->savconf->entity;

		// A second, real user to assign to the event.
		$uniq = 'zttestdlassignee'.dol_print_date($now, '%Y%m%d%H%M%S');
		$assigneduser = new User($db);
		$assigneduser->lastname = 'Assignee'.$uniq;
		$assigneduser->firstname = 'DeletionLog';
		$assigneduser->login = $uniq;
		$assigneduser->email = $uniq.'@example.com';
		$this->assertGreaterThan(0, $assigneduser->create($user), 'failed to create the assigned test user');

		$event = new ActionComm($db);
		$event->type_code = 'AC_OTH';
		$event->code = 'AC_PHPUNITTEST_DL';
		$event->label = 'Deletion log test event';
		$event->datep = $now;
		$event->datef = $now;
		$event->percentage = -1;
		$event->authorid = $user->id;
		$event->userownerid = $user->id;
		$event->userassigned = array(
			$user->id => array('id' => $user->id),
			$assigneduser->id => array('id' => $assigneduser->id),
		);
		$eventid = $event->create($user);
		$this->assertGreaterThan(0, $eventid, 'the test agenda event must be created');
		$this->assertNotEmpty($event->uid, 'create() must have generated a uid');
		$eventuid = $event->uid;

		// Re-fetch a fresh instance and delete it, the same way a real "delete" action does.
		$tofetch = new ActionComm($db);
		$this->assertGreaterThan(0, $tofetch->fetch($eventid));
		$this->assertGreaterThan(0, $tofetch->delete($user), 'the test agenda event must be deleted');

		$deletionlog = new DeletionLog($db);

		// The owner ($user, an admin with allactions) sees it.
		$got = $deletionlog->getDeletionsSince($now - 3600, $user, $entity);
		$this->assertContains($eventuid, $got, 'the owner must see the deletion');

		// The assigned user, restricted to myactions, also sees it.
		$assigneeasmyactions = $this->fakeUser($assigneduser->id, true, false);
		$got = $deletionlog->getDeletionsSince($now - 3600, $assigneeasmyactions, $entity);
		$this->assertContains($eventuid, $got, 'an assigned user with myactions must see the deletion');

		// An unrelated myactions-only user does not.
		$stranger = $this->fakeUser(999999, true, false);
		$got = $deletionlog->getDeletionsSince($now - 3600, $stranger, $entity);
		$this->assertNotContains($eventuid, $got, 'an unrelated user must not see the deletion');

		$assigneduser->delete($user);
	}

	/**
	 * testFetchBackfillsMissingUidWithoutTouchingTms
	 *
	 * @return void
	 */
	public function testFetchBackfillsMissingUidWithoutTouchingTms()
	{
		global $user, $db;
		$db = $this->savdb;

		$event = new ActionComm($db);
		$event->type_code = 'AC_OTH';
		$event->code = 'AC_PHPUNITTEST_DL';
		$event->label = 'Deletion log uid backfill test event';
		$event->datep = dol_now();
		$event->percentage = -1;
		$event->userownerid = $user->id;
		$eventid = $event->create($user);
		$this->assertGreaterThan(0, $eventid, 'the test agenda event must be created');

		// Simulate a pre-migration event: clear its uid directly, the way it is found for any
		// event created before the uid column existed. "tms = tms" here only re-arms the
		// baseline for the assertion below, it is not what is being tested.
		$db->query("UPDATE ".MAIN_DB_PREFIX."actioncomm SET uid = NULL, tms = tms WHERE id = ".((int) $eventid));
		$tmsbefore = $this->readTms($db, $eventid);
		$this->assertNotEmpty($tmsbefore);

		$fresh = new ActionComm($db);
		$this->assertGreaterThan(0, $fresh->fetch($eventid));
		$this->assertNotEmpty($fresh->uid, 'fetch() must backfill a missing uid');

		$dbuid = $this->readUid($db, $eventid);
		$this->assertSame($fresh->uid, $dbuid, 'the backfilled uid must be persisted to the row');

		$tmsafter = $this->readTms($db, $eventid);
		$this->assertSame($tmsbefore, $tmsafter, 'backfilling uid must not bump tms');

		$fresh->delete($user);
	}

	/**
	 * @param  DoliDB $db  Database handler
	 * @param  int    $id  Actioncomm id
	 * @return string      Current uid column value
	 */
	private function readUid($db, $id)
	{
		$resql = $db->query("SELECT uid FROM ".MAIN_DB_PREFIX."actioncomm WHERE id = ".((int) $id));
		$obj = $db->fetch_object($resql);
		return $obj->uid;
	}

	/**
	 * @param  DoliDB $db  Database handler
	 * @param  int    $id  Actioncomm id
	 * @return string      Current tms column value
	 */
	private function readTms($db, $id)
	{
		$resql = $db->query("SELECT tms FROM ".MAIN_DB_PREFIX."actioncomm WHERE id = ".((int) $id));
		$obj = $db->fetch_object($resql);
		return $obj->tms;
	}

	/**
	 * Insert a row with an explicit deletion date and return its rowid.
	 *
	 * @param  DoliDB $db             Database handler
	 * @param  int    $fk_actioncomm  Fake actioncomm id
	 * @param  string $uid            Fake uid
	 * @param  int    $date           Deletion date (unix timestamp)
	 * @param  int    $entity         Entity
	 * @return int                    Inserted rowid
	 */
	private function insertRow($db, $fk_actioncomm, $uid, $date, $entity)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deletion_log(entity, fk_actioncomm, uid, date_deletion)";
		$sql .= " VALUES (".((int) $entity).", ".((int) $fk_actioncomm).", '".$db->escape($uid)."', '".$db->idate($date)."')";
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
