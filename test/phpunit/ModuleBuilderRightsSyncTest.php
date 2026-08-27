<?php
/* Copyright (C) 2026 ATM Consulting <support@atm-consulting.fr>
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
 */

/**
 *      \file       test/phpunit/ModuleBuilderRightsSyncTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ModuleBuilder permissions sync service
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/modulebuilder/class/SyncReport.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 * @phan-file-suppress PhanUndeclaredClass
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class ModuleBuilderRightsSyncTest extends CommonClassTest
{
	/**
	 * A report with conflicts reports no write and maps to the legacy -1 return code.
	 *
	 * @return void
	 */
	public function testSyncReportConflictSemantics()
	{
		$clean = new SyncReport(12, 0, array(), array());
		$this->assertFalse($clean->hasConflicts());
		$this->assertFalse($clean->hasWarnings());
		$this->assertFalse($clean->isNoop());
		$this->assertSame(1, $clean->toLegacyReturnCode());

		$blocked = new SyncReport(0, 0, array('dynamic code detected'), array());
		$this->assertTrue($blocked->hasConflicts());
		$this->assertTrue($blocked->isNoop());
		$this->assertSame(-1, $blocked->toLegacyReturnCode());

		// A warning is non-blocking: the write happened, the legacy code stays 1
		$warned = new SyncReport(12, 0, array(), array('right #0 carries unsupported index 2'));
		$this->assertTrue($warned->hasWarnings());
		$this->assertFalse($warned->hasConflicts());
		$this->assertSame(1, $warned->toLegacyReturnCode());

		$skippedReport = new SyncReport(0, 1, array(), array());
		$this->assertSame(1, $skippedReport->skipped);
		$this->assertTrue($skippedReport->isNoop());
	}
}
