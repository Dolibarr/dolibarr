<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/unit/TicketTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql'); // This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/usergroup.class.php';
require_once dirname(__FILE__).'/../../htdocs/ticket/class/ticket.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/timespent.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
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
class TicketTest extends CommonClassTest
{
	/**
	 * testTicketCreate
	 *
	 * @return	int
	 */
	public function testTicketCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Try to create one with bad values
		$localobject = new Ticket($db);
		$localobject->initAsSpecimen();
		$localobject->ref = '';
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertEquals(-3, $result, $localobject->error.join(',', $localobject->errors));

		// Try to create one with correct values
		$localobject = new Ticket($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->error.join(',', $localobject->errors));

		return $result;
	}

	/**
	 * testTicketFetch
	 *
	 * @param	int		$id		Id of ticket
	 * @return	int
	 *
	 * @depends	testTicketCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Ticket($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);

		return $localobject;
	}

	/**
	 * testTicketmarkAsRead
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketmarkAsRead($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->markAsRead($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetProject
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetProject($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$project_id = 1;

		$result = $localobject->setProject($project_id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetContract
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetContract($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$contract_id = 1;

		$result = $localobject->setContract($contract_id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketsetProgression
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketsetProgression($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$percent = 80;

		$result = $localobject->setProgression($percent);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketassignUser
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketassignUser($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$user_id_to_assign = 1;

		$result = $localobject->assignUser($user, $user_id_to_assign);
		;
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketassignUserOther
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketassignUserOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$user_id_to_assign = 2;

		$result = $localobject->assignUser($user, $user_id_to_assign);
		;
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject;
	}

	/**
	 * testTicketclose
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	int
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketclose($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->close($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertGreaterThan(0, $result);
		return $localobject->id;
	}


	/**
	 * testTicketAddTimeSpent
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	Ticket
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketAddTimeSpent($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$timespent->note = 'PHPUnit ticket time spent';

		$result = $localobject->addTimeSpent($user, $timespent);
		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, (string) $localobject->error);

		// The record must be tagged for the ticket, not for a task
		$checked = new TimeSpent($db);
		$checked->fetch($result);
		$this->assertEquals('ticket', $checked->elementtype);
		$this->assertEquals($localobject->id, $checked->fk_element);
		$this->assertEquals(3600, (int) $checked->element_duration);

		// The hourly rate must be frozen from the contributor at recording time
		$sql = "SELECT thm FROM ".$db->prefix()."user WHERE rowid = ".((int) $user->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals((float) $obj->thm, (float) $checked->thm);

		return $localobject;
	}

	/**
	 * testTicketAddTimeSpentRefusesInvalidInput
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketAddTimeSpentRefusesInvalidInput($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$before = $localobject->countTimeSpent();

		// No duration
		$timespent = new TimeSpent($db);
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$this->assertLessThan(0, $localobject->addTimeSpent($user, $timespent));

		// No contributor
		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$this->assertLessThan(0, $localobject->addTimeSpent($user, $timespent));

		// No date
		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->fk_user = $user->id;
		$this->assertLessThan(0, $localobject->addTimeSpent($user, $timespent));

		print __METHOD__." nb before=".$before." after=".$localobject->countTimeSpent()."\n";
		$this->assertEquals($before, $localobject->countTimeSpent(), 'A refused input must not write any record');
	}

	/**
	 * A record of another elementtype is not writable through the ticket, even with the same fk_element
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentRejectsForeignLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// A task record sharing the very same fk_element as our ticket
		$foreign = new TimeSpent($db);
		$foreign->fk_element = $localobject->id;
		$foreign->elementtype = 'task';
		$foreign->element_date = dol_now();
		$foreign->element_datehour = dol_now();
		$foreign->element_duration = 7200;
		$foreign->fk_user = $user->id;
		$foreign->datec = dol_now();
		$resultcreate = $foreign->create($user);
		$this->assertGreaterThan(0, $resultcreate, (string) $foreign->error);

		$loaded = new TimeSpent($db);
		$loaded->fetch($resultcreate);
		$loaded->element_duration = 999;

		$resupdate = $localobject->updateTimeSpent($user, $loaded);
		print __METHOD__." update=".$resupdate."\n";
		$this->assertLessThan(0, $resupdate, 'Updating a task record through a ticket must be refused');

		$resdelete = $localobject->delTimeSpent($user, $loaded);
		print __METHOD__." delete=".$resdelete."\n";
		$this->assertLessThan(0, $resdelete, 'Deleting a task record through a ticket must be refused');

		// The record must still be there and untouched
		$stillthere = new TimeSpent($db);
		$this->assertGreaterThan(0, $stillthere->fetch($resultcreate));
		$this->assertEquals(7200, (int) $stillthere->element_duration);
	}

	/**
	 * Task::fetchTimeSpent() refuses a record of another elementtype
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTaskFetchTimeSpentRejectsTicketLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 1800;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$lineid = $localobject->addTimeSpent($user, $timespent);
		$this->assertGreaterThan(0, $lineid);

		$task = new Task($db);
		$task->id = 0;
		$result = $task->fetchTimeSpent($lineid);

		print __METHOD__." result=".$result." task id=".$task->id."\n";
		$this->assertEquals(0, $result, 'A ticket time record must not be loaded as a task one');
		$this->assertEmpty($task->timespent_id, 'No timespent property must be populated');
	}

	/**
	 * testTicketGetSummaryOfTimeSpent
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketGetSummaryOfTimeSpent($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Own ticket so the totals are not disturbed by the other tests of the chain
		$ticket = new Ticket($db);
		$ticket->initAsSpecimen();
		$suffix = dol_print_date(dol_now(), '%y%m%d%H%M%S').mt_rand(100, 999);
		$ticket->ref = 'PUSUM'.$suffix;
		$ticket->track_id = 'PUSUM'.$suffix;
		$this->assertGreaterThan(0, $ticket->create($user), (string) $ticket->error);

		$ids = array();
		foreach (array(3600, 7200) as $duration) {
			$timespent = new TimeSpent($db);
			$timespent->element_duration = $duration;
			$timespent->element_date = dol_now();
			$timespent->fk_user = $user->id;
			$id = $ticket->addTimeSpent($user, $timespent);
			$this->assertGreaterThan(0, $id);
			$ids[] = $id;
		}

		// Force two different hourly rates to check the amount computation
		$rates = array(10, 20);
		foreach ($ids as $key => $id) {
			$sql = "UPDATE ".$db->prefix()."element_time SET thm = ".((float) $rates[$key])." WHERE rowid = ".((int) $id);
			$this->assertNotFalse($db->query($sql));
		}

		$summary = $ticket->getSummaryOfTimeSpent();
		print __METHOD__." duration=".$summary['total_duration']." amount=".$summary['total_amount']."\n";

		$this->assertEquals(2, $summary['nblines']);
		$this->assertEquals(10800, (int) $summary['total_duration']);
		// 1h at 10 + 2h at 20 = 50
		$this->assertEquals(50.0, round((float) $summary['total_amount'], 2));
		$this->assertEquals(0, $summary['nblinesnull']);
	}

	/**
	 * Deleting a ticket purges its own time records only
	 *
	 * @return	void
	 */
	public function testTicketDeletePurgesTimeSpent()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$ticket = new Ticket($db);
		$ticket->initAsSpecimen();
		$suffix = dol_print_date(dol_now(), '%y%m%d%H%M%S').mt_rand(100, 999);
		$ticket->ref = 'PUPURGE'.$suffix;
		$ticket->track_id = 'PUPURGE'.$suffix;
		$this->assertGreaterThan(0, $ticket->create($user), (string) $ticket->error);

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$this->assertGreaterThan(0, $ticket->addTimeSpent($user, $timespent));

		// Decoy: a task record carrying the same fk_element as the ticket being deleted
		$decoy = new TimeSpent($db);
		$decoy->fk_element = $ticket->id;
		$decoy->elementtype = 'task';
		$decoy->element_date = dol_now();
		$decoy->element_datehour = dol_now();
		$decoy->element_duration = 3600;
		$decoy->fk_user = $user->id;
		$decoy->datec = dol_now();
		$decoyid = $decoy->create($user);
		$this->assertGreaterThan(0, $decoyid);

		$ticketid = $ticket->id;
		$result = $ticket->delete($user);
		print __METHOD__." delete=".$result."\n";
		$this->assertGreaterThan(0, $result, (string) $ticket->error);

		$counter = new TimeSpent($db);
		$this->assertEquals(0, $counter->countForElement('ticket', $ticketid), 'Ticket time records must be purged');
		$this->assertEquals(1, $counter->countForElement('task', $ticketid), 'Task time records must survive');

		$survivor = new TimeSpent($db);
		$this->assertGreaterThan(0, $survivor->fetch($decoyid), 'The task record must still be readable');
	}

	/**
	 * A selection is reduced to the records attached to the ticket
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentFilterAttachedIds($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$mine = new TimeSpent($db);
		$mine->element_duration = 3600;
		$mine->element_date = dol_now();
		$mine->fk_user = $user->id;
		$mineid = $localobject->addTimeSpent($user, $mine);
		$this->assertGreaterThan(0, $mineid);

		// Same fk_element, other elementtype: must not be seen as attached to the ticket
		$foreign = new TimeSpent($db);
		$foreign->fk_element = $localobject->id;
		$foreign->elementtype = 'task';
		$foreign->element_date = dol_now();
		$foreign->element_datehour = dol_now();
		$foreign->element_duration = 3600;
		$foreign->fk_user = $user->id;
		$foreign->datec = dol_now();
		$foreignid = $foreign->create($user);
		$this->assertGreaterThan(0, $foreignid, (string) $foreign->error);

		$filter = new TimeSpent($db);
		$kept = $filter->filterAttachedIds(array($mineid, $foreignid), 'ticket', (int) $localobject->id);

		print __METHOD__." kept=".join(',', $kept)."\n";
		$this->assertEquals(array($mineid), $kept, 'Only the records of the ticket may survive the filter');

		// An empty or bogus selection must not open anything
		$this->assertEquals(array(), $filter->filterAttachedIds(array(), 'ticket', (int) $localobject->id));
		$this->assertEquals(array(), $filter->filterAttachedIds(array($foreignid), 'ticket', (int) $localobject->id));
	}

	/**
	 * A negative product id is stored as 0
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentNormalizesProduct($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$timespent->fk_product = -1;
		$id = $localobject->addTimeSpent($user, $timespent);
		$this->assertGreaterThan(0, $id, (string) $localobject->error);

		$checked = new TimeSpent($db);
		$checked->fetch($id);
		print __METHOD__." fk_product stored=".var_export($checked->fk_product, true)."\n";
		$this->assertEquals(0, (int) $checked->fk_product, 'A negative product id must be stored as 0');

		// Same normalisation on update
		$checked->fk_product = -1;
		$this->assertGreaterThan(0, $localobject->updateTimeSpent($user, $checked));
		$reread = new TimeSpent($db);
		$reread->fetch($id);
		$this->assertEquals(0, (int) $reread->fk_product, 'An update must normalise it too');
	}

	/**
	 * A refusal of Task::fetchTimeSpent() leaves no property of the previous call
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTaskFetchTimeSpentClearsStaleProperties($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 1800;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$lineid = $localobject->addTimeSpent($user, $timespent);
		$this->assertGreaterThan(0, $lineid, (string) $localobject->error);

		$task = new Task($db);
		$task->timespent_id = 999999;
		$task->timespent_duration = 7200;
		$task->timespent_fk_user = $user->id;
		$task->timespent_thm = 42;

		$result = $task->fetchTimeSpent($lineid);

		print __METHOD__." result=".$result." duration=".var_export($task->timespent_duration, true)."\n";
		$this->assertEquals(0, $result, 'A ticket time record must not be loaded as a task one');
		$this->assertEmpty($task->timespent_duration, 'A refusal must not keep the duration of the previous iteration');
		$this->assertEmpty($task->timespent_fk_user, 'A refusal must not keep the contributor of the previous iteration');
		$this->assertEmpty($task->timespent_thm, 'A refusal must not keep the rate of the previous iteration');
	}

	/**
	 * testTicketTimeSpentRefusesUnknownUser
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentRefusesUnknownUser($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$before = $localobject->countTimeSpent();

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = 999999;
		$result = $localobject->addTimeSpent($user, $timespent);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan(0, $result, 'Time must not be recorded for a user that does not exist');
		$this->assertEquals($before, $localobject->countTimeSpent(), 'A refused input must not write any record');
	}

	/**
	 * testTicketTimeSpentFollowsRateOfNewAuthor
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentFollowsRateOfNewAuthor($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$other = new User($db);
		$other->lastname = 'TIMESPENTRATE';
		$other->firstname = 'Unit';
		$other->login = 'unittimespent'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		$otherid = $other->create($user);
		$this->assertGreaterThan(0, $otherid, (string) $other->error);
		$this->assertTrue((bool) $db->query("UPDATE ".$db->prefix()."user SET thm = 99 WHERE rowid = ".((int) $otherid)));

		// The first author needs a rate of its own, or the reload is caused by the missing rate
		$this->assertTrue((bool) $db->query("UPDATE ".$db->prefix()."user SET thm = 50 WHERE rowid = ".((int) $user->id)));

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 3600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$id = $localobject->addTimeSpent($user, $timespent);
		$this->assertGreaterThan(0, $id, (string) $localobject->error);

		$line = new TimeSpent($db);
		$line->fetch($id);
		$this->assertEquals(50, (float) $line->thm, 'The record must have been created with the rate of the first author');
		$line->fk_user = $otherid;
		$this->assertGreaterThan(0, $localobject->updateTimeSpent($user, $line), (string) $localobject->error);

		$reread = new TimeSpent($db);
		$reread->fetch($id);
		print __METHOD__." thm after author change=".var_export($reread->thm, true)."\n";
		$this->assertEquals(99, (float) $reread->thm, 'The rate must follow the new contributor, not stay on the previous one');
	}

	/**
	 * elementtype is the one passed by the caller, not $object->element ('project_task' for a Task)
	 *
	 * @return	void
	 */
	public function testTimeSpentForElementStoresGivenElementtype()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$task = new Task($db);
		$task->id = 999999001;

		$timespent = new TimeSpent($db);
		$timespent->element_duration = 600;
		$timespent->element_date = dol_now();
		$timespent->fk_user = $user->id;
		$id = $timespent->createForElement($task, 'task', $user);
		$this->assertGreaterThan(0, $id, (string) $timespent->error);

		$checked = new TimeSpent($db);
		$checked->fetch($id);
		print __METHOD__." elementtype=".$checked->elementtype."\n";
		$this->assertEquals('task', $checked->elementtype);

		$checked->element_duration = 1200;
		$this->assertGreaterThan(0, $checked->updateForElement($task, 'task', $user), (string) $checked->error);
		$this->assertLessThan(0, $checked->updateForElement($task, 'ticket', $user));
	}

	/**
	 * The TICKET_TIMESPENT_* triggers expose the record concerned as $ticket->timespent_id
	 *
	 * @param	Ticket		$localobject		Ticket
	 * @return	void
	 *
	 * @depends	testTicketFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketTimeSpentTriggerExposesRecord($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$first = new TimeSpent($db);
		$first->element_duration = 600;
		$first->element_date = dol_now();
		$first->fk_user = $user->id;
		$firstid = $localobject->addTimeSpent($user, $first);
		$this->assertGreaterThan(0, $firstid, (string) $localobject->error);
		$this->assertEquals($firstid, $localobject->timespent_id);

		$second = new TimeSpent($db);
		$second->element_duration = 600;
		$second->element_date = dol_now();
		$second->fk_user = $user->id;
		$secondid = $localobject->addTimeSpent($user, $second);
		$this->assertGreaterThan(0, $secondid, (string) $localobject->error);

		$reloaded = new TimeSpent($db);
		$reloaded->fetch($firstid);
		$this->assertGreaterThan(0, $localobject->updateTimeSpent($user, $reloaded), (string) $localobject->error);
		print __METHOD__." timespent_id after update=".$localobject->timespent_id."\n";
		$this->assertEquals($firstid, $localobject->timespent_id);
	}

	/**
	 * testTicketDelete
	 *
	 * @param	int		$id		Id of ticket
	 * @return	int
	 *
	 * @depends	testTicketclose
	 * The depends says test is run only if previous is ok
	 */
	public function testTicketDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Ticket($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		return $result;
	}
}
