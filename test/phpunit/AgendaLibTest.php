<?php
/* Copyright (C) 2026	Frédéric France	<frederic.france@free.fr>
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
 *      \file       test/phpunit/AgendaLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for core/lib/agenda.lib.php
 */

global $conf, $user, $langs, $db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/agenda.lib.php';
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
 */
class AgendaLibTest extends CommonClassTest
{
	/**
	 * Build an event with the given calendar start/end. The real dates (datep/datef) are the calendar
	 * dates unless given, as set by agenda_build_eventarray() for an event that is not clipped.
	 *
	 * @param	int|string		$start			Calendar start timestamp
	 * @param	int|string		$end			Calendar end timestamp, or '' for none
	 * @param	int				$fulldayevent	1 for a full-day event
	 * @param	int|string|null	$datep			Real start timestamp, null to use $start
	 * @param	int|string|null	$datef			Real end timestamp, null to use $end
	 * @return	ActionComm
	 */
	private function makeEvent($start, $end, $fulldayevent = 0, $datep = null, $datef = null)
	{
		global $db;
		$event = new ActionComm($db);
		$event->fulldayevent = $fulldayevent;
		$event->datep = ($datep === null ? $start : $datep);
		$event->datef = ($datef === null ? $end : $datef);
		$event->date_start_in_calendar = $start;
		$event->date_end_in_calendar = $end;
		return $event;
	}

	/**
	 * testAgendaEventDayMinutes
	 *
	 * @return	void
	 */
	public function testAgendaEventDayMinutes()
	{
		// Timed event 09:30-10:45
		$event = $this->makeEvent(dol_mktime(9, 30, 0, 10, 5, 2026, 'tzuserrel'), dol_mktime(10, 45, 0, 10, 5, 2026, 'tzuserrel'));
		$this->assertEquals([570, 645], agenda_event_day_minutes($event));

		// No end date: hydration sets the calendar end to the start
		$start = dol_mktime(14, 0, 0, 10, 5, 2026, 'tzuserrel');
		$this->assertEquals([840, 840], agenda_event_day_minutes($this->makeEvent($start, $start)));

		// Empty end
		$this->assertEquals([840, 840], agenda_event_day_minutes($this->makeEvent($start, '')));

		// End before start is clamped to start
		$this->assertEquals([840, 840], agenda_event_day_minutes($this->makeEvent($start, $start - 3600)));

		// Full-day event goes to the all-day row
		$this->assertNull(agenda_event_day_minutes($this->makeEvent($start, $start + 3600, 1)));

		// Event over two days goes to the all-day row
		$this->assertNull(agenda_event_day_minutes($this->makeEvent(dol_mktime(9, 0, 0, 10, 5, 2026, 'gmt'), dol_mktime(10, 0, 0, 10, 6, 2026, 'gmt'))));

		// Multi-day event seen on its first day: the calendar end is clipped to the end of the displayed
		// window, but the real dates span three days, so it goes to the all-day row
		$event = $this->makeEvent(
			dol_mktime(9, 0, 0, 10, 5, 2026, 'tzuserrel'),
			dol_mktime(23, 59, 59, 10, 5, 2026, 'tzuserrel'),
			0,
			dol_mktime(9, 0, 0, 10, 5, 2026, 'tzuserrel'),
			dol_mktime(17, 0, 0, 10, 7, 2026, 'tzuserrel')
		);
		$this->assertNull(agenda_event_day_minutes($event));

		// Event ending exactly at midnight stays a timed event of its start day, ending at 24:00
		$event = $this->makeEvent(dol_mktime(23, 0, 0, 10, 5, 2026, 'tzuserrel'), dol_mktime(0, 0, 0, 10, 6, 2026, 'tzuserrel'));
		$this->assertEquals([1380, 1440], agenda_event_day_minutes($event));
	}

	/**
	 * testAgendaEventDayMinutesUserTimezone
	 *
	 * The day is decided in the user timezone, not the server one.
	 *
	 * @return	void
	 */
	public function testAgendaEventDayMinutesUserTimezone()
	{
		$hadtz = isset($_SESSION['dol_tz_string']);
		$oldtz = ($hadtz ? $_SESSION['dol_tz_string'] : null);
		$_SESSION['dol_tz_string'] = 'Europe/Paris';
		try {
			// Sun 23:00 -> Mon 01:00 Paris crosses midnight in Paris (not in UTC): all-day row
			$event = $this->makeEvent(dol_mktime(23, 0, 0, 10, 4, 2026, 'tzuserrel'), dol_mktime(1, 0, 0, 10, 5, 2026, 'tzuserrel'));
			$this->assertNull(agenda_event_day_minutes($event));

			// Mon 00:30 -> 01:30 Paris is on the previous day in UTC, but a timed event in Paris
			$event = $this->makeEvent(dol_mktime(0, 30, 0, 10, 5, 2026, 'tzuserrel'), dol_mktime(1, 30, 0, 10, 5, 2026, 'tzuserrel'));
			$this->assertEquals([30, 90], agenda_event_day_minutes($event));
		} finally {
			if ($hadtz) {
				$_SESSION['dol_tz_string'] = $oldtz;
			} else {
				unset($_SESSION['dol_tz_string']);
			}
		}
	}
}
