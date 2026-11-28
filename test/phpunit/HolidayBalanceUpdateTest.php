<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/HolidayBalanceUpdateTest.php
 * \ingroup test
 * \brief Test automatic leave update routing without a database or installed instance.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__DIR__, 2).'/htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/DoliDB.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

/**
 * Verify that only the selected execution path reaches the monthly update.
 */
class HolidayBalanceUpdateTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Check all three modes, absent and invalid settings for both callers and two entities.
	 *
	 * @return void
	 */
	public function testOnlySelectedPathUpdatesBalances()
	{
		global $conf;
		$savedConf = $conf;
		$savedSession = isset($_SESSION) ? $_SESSION : null;
		try {
			foreach (array(1, 2) as $entity) {
				foreach (array(null, 0, 1, 2, 99) as $mode) {
					foreach (array(false, true) as $fromPage) {
						$_SESSION = array();
						$conf = new stdClass();
						$conf->entity = $entity;
						$conf->global = new stdClass();
						if ($mode !== null) {
							$conf->global->HOLIDAY_BALANCE_UPDATE_BY_CRON = $mode;
						}
						$shouldUpdate = $fromPage ? in_array($mode, array(null, 0, 2), true) : in_array($mode, array(1, 2), true);
						$database = $this->createMock(DoliDBMysqli::class);
						$database->expects($shouldUpdate ? $this->once() : $this->never())->method('begin')->willReturn(1);
						$database->expects($shouldUpdate ? $this->once() : $this->never())->method('commit')->willReturn(1);
						$database->expects($this->never())->method('rollback');
						$holiday = $this->getMockBuilder(Holiday::class)->setConstructorArgs(array($database))
							->onlyMethods(array('updateSoldeCP'))->getMock();
						$holiday->expects($shouldUpdate ? $this->once() : $this->never())->method('updateSoldeCP')->willReturn(1);
						$this->assertSame(0, $fromPage ? $holiday->updateBalance(true) : $holiday->updateBalance());
					}
				}
			}
		} finally {
			$conf = $savedConf;
			if ($savedSession === null) {
				unset($_SESSION);
			} else {
				$_SESSION = $savedSession;
			}
		}
	}

	/**
	 * Changing the setting immediately changes the selected path; errors still roll back.
	 *
	 * @return void
	 */
	public function testModeSwitchAndUpdateFailure()
	{
		global $conf;
		$savedConf = $conf;
		try {
			$conf = new stdClass();
			$conf->entity = 1;
			$conf->global = new stdClass();
			$database = $this->createMock(DoliDBMysqli::class);
			$database->expects($this->exactly(2))->method('begin')->willReturn(1);
			$database->expects($this->never())->method('commit');
			$database->expects($this->exactly(2))->method('rollback');
			$holiday = $this->getMockBuilder(Holiday::class)->setConstructorArgs(array($database))
				->onlyMethods(array('updateSoldeCP'))->getMock();
			$holiday->expects($this->exactly(2))->method('updateSoldeCP')->willReturn(-1);
			$conf->global->HOLIDAY_BALANCE_UPDATE_BY_CRON = 1;
			$this->assertSame(0, $holiday->updateBalance(true));
			$this->assertSame(-1, $holiday->updateBalance());
			$conf->global->HOLIDAY_BALANCE_UPDATE_BY_CRON = 0;
			$this->assertSame(0, $holiday->updateBalance());
			$this->assertSame(-1, $holiday->updateBalance(true));
		} finally {
			$conf = $savedConf;
		}
	}

	/**
	 * Business calls neither read nor change the caller's HTTP session state.
	 *
	 * @return void
	 */
	public function testBusinessUpdatesAreIndependentOfSessionState()
	{
		global $conf;
		$savedConf = $conf;
		$savedSession = isset($_SESSION) ? $_SESSION : null;
		try {
			$_SESSION = array('holiday_balance_update_month' => array(1 => dol_print_date(dol_now(), '%Y%m')));
			$sessionBefore = $_SESSION;
			$conf = new stdClass();
			$conf->global = new stdClass();
			$conf->entity = 1;
			$database = $this->createMock(DoliDBMysqli::class);
			$database->expects($this->exactly(2))->method('begin')->willReturn(1);
			$database->expects($this->exactly(2))->method('commit')->willReturn(1);
			$database->expects($this->never())->method('rollback');
			$holiday = $this->getMockBuilder(Holiday::class)->setConstructorArgs(array($database))
				->onlyMethods(array('updateSoldeCP'))->getMock();
			$holiday->expects($this->exactly(2))->method('updateSoldeCP')->willReturn(1);
			$this->assertSame(0, $holiday->updateBalance(true));
			$this->assertSame(0, $holiday->updateBalance(true));
			$this->assertSame($sessionBefore, $_SESSION);
		} finally {
			$conf = $savedConf;
			if ($savedSession === null) {
				unset($_SESSION);
			} else {
				$_SESSION = $savedSession;
			}
		}
	}

	/**
	 * A failed commit is reported and another business call can retry the update.
	 *
	 * @return void
	 */
	public function testFailedCommitAllowsAnotherPageAttempt()
	{
		global $conf;
		$savedConf = $conf;
		$savedSession = isset($_SESSION) ? $_SESSION : null;
		try {
			$_SESSION = array();
			$conf = new stdClass();
			$conf->global = new stdClass();
			$conf->entity = 1;
			$database = $this->createMock(DoliDBMysqli::class);
			$database->expects($this->exactly(2))->method('begin')->willReturn(1);
			$database->expects($this->exactly(2))->method('commit')->willReturnOnConsecutiveCalls(0, 1);
			$database->expects($this->once())->method('rollback');
			$database->method('lasterror')->willReturn('Simulated commit failure');
			$holiday = $this->getMockBuilder(Holiday::class)->setConstructorArgs(array($database))
				->onlyMethods(array('updateSoldeCP'))->getMock();
			$holiday->expects($this->exactly(2))->method('updateSoldeCP')->willReturn(1);
			$this->assertSame(-1, $holiday->updateBalance(true));
			$this->assertFalse(isset($_SESSION['holiday_balance_update_month'][1]));
			$this->assertSame(0, $holiday->updateBalance(true));
			$this->assertFalse(isset($_SESSION['holiday_balance_update_month'][1]));
		} finally {
			$conf = $savedConf;
			if ($savedSession === null) {
				unset($_SESSION);
			} else {
				$_SESSION = $savedSession;
			}
		}
	}

	/**
	 * A failed transaction start must prevent all accrual writes.
	 *
	 * @return void
	 */
	public function testFailedBeginDoesNotRunAccrual()
	{
		global $conf;
		$savedConf = $conf;
		try {
			$conf = new stdClass();
			$conf->global = new stdClass();
			$conf->global->HOLIDAY_BALANCE_UPDATE_BY_CRON = Holiday::BALANCE_UPDATE_BY_CRON;
			$conf->entity = 1;
			$database = $this->createMock(DoliDBMysqli::class);
			$database->expects($this->once())->method('begin')->willReturn(0);
			$database->expects($this->never())->method('commit');
			$database->expects($this->never())->method('rollback');
			$database->method('lasterror')->willReturn('Simulated transaction start failure');
			$holiday = $this->getMockBuilder(Holiday::class)->setConstructorArgs(array($database))
				->onlyMethods(array('updateSoldeCP'))->getMock();
			$holiday->expects($this->never())->method('updateSoldeCP');
			$this->assertSame(-1, $holiday->updateBalance());
		} finally {
			$conf = $savedConf;
		}
	}
}
