<?php
/* Copyright (C) 2026
 * This program is free software under the GNU General Public License,
 * version 3 or (at your option) any later version.
 */

/**
 * \file test/phpunit/CronjobTest.php
 * \ingroup test
 * \brief Regression tests for scheduled job alert email persistence.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	require_once __DIR__.'/../../htdocs/master.inc.php';
}
require_once DOL_DOCUMENT_ROOT.'/cron/class/cronjob.class.php';

/**
 * Scheduled jobs are only created and fetched, never executed.
 */
class CronjobTest extends PHPUnit\Framework\TestCase
{
	/** @var DoliDB */
	private $db;

	/** @var User */
	private $author;

	/** @return void */
	protected function setUp(): void
	{
		global $db, $user;
		$this->db = $db;
		$this->author = $user;
		if (empty($this->author->id)) {
			$this->assertGreaterThan(0, $this->author->fetch(1));
		}
		$this->db->begin();
	}

	/** @return void */
	protected function tearDown(): void
	{
		$this->db->rollback();
	}

	/**
	 * @param ?string $email Alert email
	 * @return Cronjob Freshly fetched job
	 */
	private function createAndFetch($email)
	{
		$job = new Cronjob($this->db);
		$job->label = 'Alert email persistence regression';
		$job->jobtype = 'method';
		$job->classesname = 'core/class/utils.class.php';
		$job->objectname = 'Utils';
		$job->methodename = 'dumpDatabase';
		$job->status = 0;
		$job->frequency = 1;
		$job->unitfrequency = '86400';
		$job->datenextrun = dol_now() + 86400;
		$job->email_alert = $email;
		$id = $job->create($this->author);
		$this->assertGreaterThan(0, $id, (string) $job->error);
		$stored = new Cronjob($this->db);
		$this->assertGreaterThan(0, $stored->fetch($id));
		$this->assertEquals(0, $stored->status);
		return $stored;
	}

	/** @return void */
	public function testCreatePersistsAlertEmail()
	{
		$this->assertSame('alerts@example.com', $this->createAndFetch('alerts@example.com')->email_alert);
	}

	/** @return void */
	public function testCreateEscapesAlertEmail()
	{
		$this->assertSame("o'connor@example.com", $this->createAndFetch("o'connor@example.com")->email_alert);
	}

	/** @return void */
	public function testCreateWithoutAlertEmail()
	{
		$this->assertNull($this->createAndFetch(null)->email_alert);
	}

	/** @return void */
	public function testCreateWithEmptyAlertEmail()
	{
		$this->assertSame('', $this->createAndFetch('')->email_alert);
	}

	/** @return void */
	public function testUpdateReplacesAndClearsAlertEmail()
	{
		$job = $this->createAndFetch('alerts@example.com');
		foreach (array('replacement@example.com', '') as $email) {
			$job->email_alert = $email;
			$this->assertGreaterThanOrEqual(0, $job->update($this->author), (string) $job->error);
			$stored = new Cronjob($this->db);
			$this->assertGreaterThan(0, $stored->fetch($job->id));
			$this->assertSame($email, $stored->email_alert);
		}
	}
}