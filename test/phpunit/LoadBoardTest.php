<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *      \file       test/phpunit/LoadBoardTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test of the load_board() methods (the lines of the home dashboard)
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/workboardresponse.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/../../htdocs/contrat/class/contrat.class.php';
require_once dirname(__FILE__).'/../../htdocs/ticket/class/ticket.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/task.class.php';
require_once dirname(__FILE__).'/../../htdocs/mrp/class/mo.class.php';
require_once dirname(__FILE__).'/../../htdocs/expensereport/class/expensereport.class.php';
require_once dirname(__FILE__).'/../../htdocs/holiday/class/holiday.class.php';
require_once dirname(__FILE__).'/../../htdocs/adherents/class/adherent.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/paiement/cheque/class/remisecheque.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/action/class/actioncomm.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of the load_board() methods.
 *
 * Each board counts its objects and the late ones with one aggregate query. For every board, rows are inserted in a
 * transaction that is rolled back (a late one, a recent one, one without date, one that must not be counted...) and the
 * counts returned before and after the insertion are compared, so the existing data does not matter. The warning delays
 * are forced to 7 days so that "late" means "older than 9 days" and "recent" means "one hour ago".
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LoadBoardTest extends CommonClassTest
{
	/**
	 * @var int Warning delay forced for every board, in seconds
	 */
	private $delay = 7 * 86400;

	/**
	 * Insert a row, fail the test with the database error if it fails.
	 *
	 * @param	DoliDB	$db		Database handler
	 * @param	string	$sql	INSERT statement
	 * @param	string	$table	Table name without prefix, when the id of the new row is needed: last_insert_id() needs it to
	 *							find the sequence of the auto increment column on pgsql (rowid for every table needing it here)
	 * @return	int				Id of the new row, 0 when $table is empty
	 */
	private function insert($db, $sql, $table = '')
	{
		$this->assertTrue((bool) $db->query($sql), $db->lasterror().' - '.$sql);
		return $table === '' ? 0 : (int) $db->last_insert_id($db->prefix().$table);
	}

	/**
	 * Check the counts of a board grew by the expected numbers.
	 *
	 * @param	WorkboardResponse|int	$before		Board before the insertion
	 * @param	WorkboardResponse|int	$after		Board after the insertion
	 * @param	int						$todo		Expected number of objects added
	 * @param	int						$late		Expected number of late objects added
	 * @param	string					$label		Board, for the messages
	 * @return	void
	 */
	private function assertBoard($before, $after, $todo, $late, $label)
	{
		$this->assertInstanceOf('WorkboardResponse', $before, $label);
		$this->assertInstanceOf('WorkboardResponse', $after, $label);
		print __METHOD__." ".$label.": nbtodo ".$before->nbtodo." -> ".$after->nbtodo.", nbtodolate ".$before->nbtodolate." -> ".$after->nbtodolate."\n";
		$this->assertSame($todo, $after->nbtodo - $before->nbtodo, $label.' objects counted');
		$this->assertSame($late, $after->nbtodolate - $before->nbtodolate, $label.' late objects counted');
		$this->assertEqualsWithDelta($this->delay / 86400, $after->warning_delay, 0.0001, $label.' warning delay of the response, in days');
	}

	/**
	 * Make sure a $conf sub object exists and set its warning_delay
	 *
	 * @param	Conf		$conf	Conf object
	 * @param	string[]	$path	Property path, like ['propal', 'cloture']
	 * @return	void
	 */
	private function setDelay($conf, $path)
	{
		$node = $conf;
		foreach ($path as $property) {
			if (empty($node->$property) || !is_object($node->$property)) {
				$node->$property = new stdClass();
			}
			$node = $node->$property;
		}
		$node->warning_delay = $this->delay;
	}

	/**
	 * testLoadBoards
	 *
	 * @return void
	 */
	public function testLoadBoards()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$now = dol_now();
		$old = $db->idate($now - $this->delay - 2 * 86400);	// Late
		$recent = $db->idate($now - 3600);					// Not late
		$entity = (int) $conf->entity;
		$userid = (int) $user->id;
		$suffix = dol_print_date($now, 'dayhourlog');

		$this->assertTrue($user->admin > 0, 'The test user must be an admin (no restriction on sales representatives)');
		// Project::load_board() and Task::load_board() only see every project when the user has this specific right: being admin
		// does not grant it on its own (loadRights() only auto-forces a handful of user-self rights for admins), and whether the
		// CI test admin has it or not depends on the seed data, not on this test. Force it in memory so the synthetic projects
		// inserted below are counted regardless of environment.
		if (empty($user->rights->projet)) {
			$user->rights->projet = new stdClass();
		}
		if (empty($user->rights->projet->all)) {
			$user->rights->projet->all = new stdClass();
		}
		$user->rights->projet->all->lire = 1;

		$db->begin();

		// Proposals: open ones are late when the validity end date is before now - delay, or when there is none; signed ones are never late
		if (isModEnabled('propal')) {
			$this->setDelay($conf, ['propal', 'cloture']);
			$this->setDelay($conf, ['propal', 'facturation']);
			$object = new Propal($db);
			$before = $object->load_board($user, 'opened');
			$beforesigned = $object->load_board($user, 'signed');
			foreach ([[1, "'".$old."'", 10], [1, "'".$recent."'", 20], [1, 'NULL', 40], [2, "'".$old."'", 80], [2, 'NULL', 160]] as $k => [$status, $datefin, $total]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."propal (ref, entity, fk_soc, fk_statut, datec, fin_validite, total_ht) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", 1, ".$status.", '".$db->idate($now)."', ".$datefin.", ".$total.")");
			}
			$after = $object->load_board($user, 'opened');
			$this->assertBoard($before, $after, 3, 2, 'propal opened');
			$this->assertEqualsWithDelta(70, $after->total - $before->total, 0.001, 'propal opened total');
			$this->assertBoard($beforesigned, $object->load_board($user, 'signed'), 2, 0, 'propal signed');
		}

		// Supplier proposals: same rule on the closing date
		if (isModEnabled('supplier_proposal')) {
			$this->setDelay($conf, ['supplier_proposal', 'cloture']);
			$this->setDelay($conf, ['supplier_proposal', 'facturation']);
			$object = new SupplierProposal($db);
			$before = $object->load_board($user, 'opened');
			$beforesigned = $object->load_board($user, 'signed');
			foreach ([[1, "'".$old."'"], [1, "'".$recent."'"], [1, 'NULL'], [2, "'".$old."'"]] as $k => [$status, $datefin]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."supplier_proposal (ref, entity, fk_soc, fk_statut, datec, date_cloture) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", 1, ".$status.", '".$db->idate($now)."', ".$datefin.")");
			}
			$this->assertBoard($before, $object->load_board($user, 'opened'), 3, 2, 'supplier proposal opened');
			$this->assertBoard($beforesigned, $object->load_board($user, 'signed'), 1, 0, 'supplier proposal signed');
		}

		// Contract services: late when the date (planned start for inactive, end of validity for the others) is set and old
		if (isModEnabled('contract')) {
			$this->setDelay($conf, ['contract', 'services', 'inactifs']);
			$this->setDelay($conf, ['contract', 'services', 'expires']);
			$object = new Contrat($db);
			$before = [];
			foreach (['inactive', 'expired', 'active'] as $mode) {
				$before[$mode] = $object->load_board($user, $mode);
			}
			$contratid = $this->insert($db, "INSERT INTO ".$db->prefix()."contrat (ref, entity, fk_soc, statut, datec, fk_user_author) VALUES ('TESTBRD".$suffix."', ".$entity.", 1, 1, '".$db->idate($now)."', ".$userid.")", 'contrat');
			// [status, planned start, end of validity]
			foreach ([[0, "'".$old."'", 'NULL'], [0, "'".$recent."'", 'NULL'], [0, 'NULL', 'NULL'], [4, 'NULL', "'".$old."'"], [4, 'NULL', "'".$recent."'"], [4, 'NULL', 'NULL']] as [$status, $datestart, $dateend]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."contratdet (fk_contrat, statut, qty, date_ouverture_prevue, date_fin_validite) VALUES (".$contratid.", ".$status.", 1, ".$datestart.", ".$dateend.")");
			}
			$this->assertBoard($before['inactive'], $object->load_board($user, 'inactive'), 3, 1, 'contract inactive services');
			$this->assertBoard($before['expired'], $object->load_board($user, 'expired'), 2, 1, 'contract expired services');	// Only the 2 with an end of validity before now
			$this->assertBoard($before['active'], $object->load_board($user, 'active'), 3, 1, 'contract active services');
		}

		// Tickets: counted, never late
		if (isModEnabled('ticket')) {
			$object = new Ticket($db);
			$before = $object->load_board($user, 'opened');
			foreach ([Ticket::STATUS_NOT_READ, Ticket::STATUS_READ, Ticket::STATUS_CLOSED] as $k => $status) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."ticket (ref, track_id, entity, fk_statut, datec) VALUES ('TESTBRD".$suffix."-".$k."', 'TESTBRD".$suffix."-".$k."', ".$entity.", ".$status.", '".$old."')");
			}
			$after = $object->load_board($user, 'opened');
			$this->assertInstanceOf('WorkboardResponse', $after);
			$this->assertSame(2, $after->nbtodo - $before->nbtodo, 'tickets counted (not the closed one)');
			$this->assertSame(0, $after->nbtodolate - $before->nbtodolate, 'no ticket is late');
		}

		// Projects and tasks: late when the end date is set and old; a task done (progress 100) is not counted
		if (isModEnabled('project')) {
			$this->setDelay($conf, ['project']);
			$this->setDelay($conf, ['project', 'task']);
			$project = new Project($db);
			$task = new Task($db);
			$beforeproject = $project->load_board($user);
			$beforetask = $task->load_board($user);
			$projectids = [];
			foreach (["'".$old."'", "'".$recent."'", 'NULL'] as $k => $dateend) {
				$projectids[] = $this->insert($db, "INSERT INTO ".$db->prefix()."projet (ref, title, entity, fk_soc, fk_statut, fk_user_creat, datec, datee) VALUES ('TESTBRD".$suffix."-".$k."', 'Board test', ".$entity.", 1, 1, ".$userid.", '".$db->idate($now)."', ".$dateend.")", 'projet');
			}
			$this->assertBoard($beforeproject, $project->load_board($user), 3, 1, 'projects');
			// [end date, progress]
			foreach ([["'".$old."'", 50], ["'".$old."'", 100], ["'".$recent."'", 0], ['NULL', 0], ["'".$old."'", 'NULL']] as $k => [$dateend, $progress]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."projet_task (ref, label, entity, fk_projet, fk_user_creat, datec, datee, progress) VALUES ('TESTBRD".$suffix."-".$k."', 'Board test', ".$entity.", ".$projectids[0].", ".$userid.", '".$db->idate($now)."', ".$dateend.", ".$progress.")");
			}
			$this->assertBoard($beforetask, $task->load_board($user), 4, 2, 'tasks');
		}

		// MO: late when the planned end date is set and old; a done MO is not counted
		if (isModEnabled('mrp')) {
			$this->setDelay($conf, ['mrp', 'progress']);
			$object = new Mo($db);
			$before = $object->load_board($user);
			foreach ([[Mo::STATUS_VALIDATED, "'".$old."'"], [Mo::STATUS_INPROGRESS, "'".$recent."'"], [Mo::STATUS_VALIDATED, 'NULL'], [Mo::STATUS_PRODUCED, "'".$old."'"]] as $k => [$status, $dateend]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."mrp_mo (ref, entity, label, qty, fk_product, date_creation, fk_user_creat, status, date_end_planned) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", 'Board test', 1, 1, '".$db->idate($now)."', ".$userid.", ".$status.", ".$dateend.")");
			}
			$this->assertBoard($before, $object->load_board($user), 3, 1, 'MO');
		}

		// Expense reports: late when the validation date is old or missing
		if (isModEnabled('expensereport')) {
			$this->setDelay($conf, ['expensereport', 'approve']);
			$this->setDelay($conf, ['expensereport', 'payment']);
			$object = new ExpenseReport($db);
			$beforeapprove = $object->load_board($user, 'toapprove');
			$beforepay = $object->load_board($user, 'topay');
			foreach ([[ExpenseReport::STATUS_VALIDATED, "'".$old."'"], [ExpenseReport::STATUS_VALIDATED, "'".$recent."'"], [ExpenseReport::STATUS_VALIDATED, 'NULL'], [ExpenseReport::STATUS_APPROVED, "'".$old."'"], [ExpenseReport::STATUS_APPROVED, "'".$recent."'"]] as $k => [$status, $datevalid]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."expensereport (ref, entity, date_debut, date_fin, date_create, fk_user_author, fk_statut, date_valid) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", '".$db->idate($now)."', '".$db->idate($now)."', '".$db->idate($now)."', ".$userid.", ".$status.", ".$datevalid.")");
			}
			$this->assertBoard($beforeapprove, $object->load_board($user, 'toapprove'), 3, 2, 'expense reports to approve');
			$this->assertBoard($beforepay, $object->load_board($user, 'topay'), 2, 1, 'expense reports to pay');
		}

		// Holidays: late when the start date is old
		if (isModEnabled('holiday')) {
			$this->setDelay($conf, ['holiday', 'approve']);
			$object = new Holiday($db);
			$before = $object->load_board($user);
			foreach ([[2, $old], [2, $recent], [3, $old]] as $k => [$status, $datestart]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."holiday (ref, entity, fk_user, fk_type, date_create, description, date_debut, date_fin, fk_validator, statut) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", ".$userid.", 1, '".$db->idate($now)."', 'Board test', '".$datestart."', '".$datestart."', ".$userid.", ".$status.")");
			}
			$this->assertBoard($before, $object->load_board($user), 2, 1, 'holidays to approve');
		}

		// Members: a validated member with an expired subscription is late when the end date is set and old; drafts are never late
		if (isModEnabled('member')) {
			$this->setDelay($conf, ['adherent', 'subscription']);
			$this->setDelay($conf, ['member', 'subscription']);
			$typeid = $this->insert($db, "INSERT INTO ".$db->prefix()."adherent_type (libelle, morphy, entity, subscription, statut) VALUES ('Board test', 'phy', ".$entity.", 1, 1)", 'adherent_type');
			$object = new Adherent($db);
			$beforeexpired = $object->load_board($user, 'expired');
			$beforeshift = $object->load_board($user, 'shift');
			foreach ([[Adherent::STATUS_VALIDATED, "'".$old."'"], [Adherent::STATUS_VALIDATED, "'".$recent."'"], [Adherent::STATUS_VALIDATED, 'NULL'], [Adherent::STATUS_DRAFT, 'NULL']] as $k => [$status, $datefin]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."adherent (ref, entity, fk_adherent_type, morphy, statut, datec, datefin) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", ".$typeid.", 'phy', ".$status.", '".$db->idate($now)."', ".$datefin.")");
			}
			$this->assertBoard($beforeexpired, $object->load_board($user, 'expired'), 3, 1, 'members with expired subscription');
			$this->assertBoard($beforeshift, $object->load_board($user, 'shift'), 1, 0, 'members to validate');
		}

		// Bank: cheques to deposit and transactions to conciliate are late when the value date is old or missing
		if (isModEnabled('bank')) {
			$this->setDelay($conf, ['bank', 'cheque']);
			$this->setDelay($conf, ['bank', 'rappro']);
			$accountid = $this->insert($db, "INSERT INTO ".$db->prefix()."bank_account (ref, label, entity, fk_pays, currency_code, rappro, courant, clos) VALUES ('TESTBRD', 'Board test', ".$entity.", 1, 'EUR', 1, ".Account::TYPE_CURRENT.", 0)", 'bank_account');
			$cheque = new RemiseCheque($db);
			$account = new Account($db);
			$beforecheque = $cheque->load_board($user);
			$beforeaccount = $account->load_board($user);
			// Cheques to deposit: reconciled so they are not in the other board. [value date, amount, deposited]
			foreach ([["'".$old."'", 10, 0], ["'".$recent."'", 10, 0], ['NULL', 10, 0], ["'".$old."'", -10, 0], ["'".$old."'", 10, 5]] as [$datev, $amount, $bordereau]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."bank (datec, dateo, datev, amount, label, fk_account, fk_user_author, fk_type, rappro, fk_bordereau) VALUES ('".$db->idate($now)."', '".$db->idate($now)."', ".$datev.", ".$amount.", 'Board test', ".$accountid.", ".$userid.", 'CHQ', 1, ".$bordereau.")");
			}
			$this->assertBoard($beforecheque, $cheque->load_board($user), 3, 2, 'cheques to deposit');
			// Transactions to conciliate
			foreach (["'".$old."'", "'".$recent."'", 'NULL'] as $datev) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."bank (datec, dateo, datev, amount, label, fk_account, fk_user_author, fk_type, rappro, fk_bordereau) VALUES ('".$db->idate($now)."', '".$db->idate($now)."', ".$datev.", 10, 'Board test', ".$accountid.", ".$userid.", 'VIR', 0, 0)");
			}
			$this->assertBoard($beforeaccount, $account->load_board($user), 3, 2, 'transactions to conciliate');
			$this->assertBoard($beforeaccount, $account->load_board($user, $accountid), 3 - $beforeaccount->nbtodo, 2 - $beforeaccount->nbtodolate, 'transactions to conciliate on the test account only');
		}

		// Agenda: an event to do is late when its date is set and old; a done event is not counted
		if (isModEnabled('agenda')) {
			$this->setDelay($conf, ['agenda']);
			$object = new ActionComm($db);
			$before = $object->load_board($user);
			foreach ([["'".$old."'", 0], ["'".$recent."'", 0], ['NULL', 0], ["'".$old."'", 100]] as $k => [$datep, $percent]) {
				$this->insert($db, "INSERT INTO ".$db->prefix()."actioncomm (ref, entity, label, datep, datec, fk_user_author, percent) VALUES ('TESTBRD".$suffix."-".$k."', ".$entity.", 'Board test', ".$datep.", '".$db->idate($now)."', ".$userid.", ".$percent.")");
			}
			$this->assertBoard($before, $object->load_board($user), 3, 1, 'agenda events to do');
		}

		$db->rollback();
	}
}
