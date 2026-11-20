<?php
/* Copyright (C) 2022 Alice Adminson <aadminson@example.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/bookcal/lib/bookcal.lib.php
 * \ingroup bookcal
 * \brief   Library files with common functions for BookCal
 */

/**
 * Prepare admin pages header
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function bookcalAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("agenda");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT . '/bookcal/admin/setup.php';
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/bookcal/admin/myobject_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@bookcal:/bookcal/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@bookcal:/bookcal/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'bookcal@bookcal');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'bookcal@bookcal', 'remove');

	return $head;
}

/**
 * Return a valid timezone identifier from the string sent by the browser of a visitor
 *
 * @param	string	$tzstring	Timezone identifier sent by the browser (Example: 'Europe/Paris')
 * @return	string				Valid timezone identifier. The server timezone if $tzstring is empty or invalid.
 */
function bookcalGetVisitorTimezone($tzstring)
{
	$tzstring = trim((string) $tzstring);

	if ($tzstring !== '' && preg_match('/^[a-zA-Z0-9_\/+\-]+$/', $tzstring)) {
		try {
			$tz = new DateTimeZone($tzstring);
			return $tz->getName();
		} catch (Exception $e) {
			dol_syslog("bookcalGetVisitorTimezone Invalid timezone received, we use the server timezone", LOG_WARNING);
		}
	}

	return @date_default_timezone_get();
}

/**
 * Return the offset in seconds, at a given date, between GMT and a timezone
 *
 * @param	int		$timestamp	Timestamp
 * @param	string	$tzstring	Valid timezone identifier (Example: 'Europe/Paris')
 * @return	int					Offset in seconds (Example: 3600 for 'Europe/Paris' in winter)
 */
function bookcalGetTimezoneOffset($timestamp, $tzstring)
{
	$tz = new DateTimeZone($tzstring);
	$dt = new DateTime('@'.((int) $timestamp));

	return $tz->getOffset($dt);
}

/**
 * Return a date formatted into a given timezone
 *
 * @param	int		$timestamp	Timestamp
 * @param	string	$format		Output format (See dol_print_date)
 * @param	string	$tzstring	Valid timezone identifier (Example: 'Europe/Paris')
 * @return	string				Formatted date
 */
function bookcalPrintDate($timestamp, $format, $tzstring)
{
	return dol_print_date((int) $timestamp + bookcalGetTimezoneOffset($timestamp, $tzstring), $format, 'gmt');
}

/**
 * Return a label of a timezone with its current GMT offset
 *
 * @param	string	$tzstring	Valid timezone identifier (Example: 'Europe/Paris')
 * @return	string				Label (Example: 'Europe/Paris (UTC+01:00)')
 */
function bookcalGetTimezoneLabel($tzstring)
{
	$offset = bookcalGetTimezoneOffset(dol_now(), $tzstring);
	$absoffset = abs($offset);

	return $tzstring.' (UTC'.($offset < 0 ? '-' : '+').sprintf("%02d", (int) floor($absoffset / 3600)).':'.sprintf("%02d", (int) floor(($absoffset % 3600) / 60)).')';
}

/**
 * Return the booking slots of a calendar that start inside a period.
 * Availabilities are declared in the server timezone. Returned slots are timestamps so they can be shown in any timezone.
 *
 * @param	DoliDB	$db				Database handler
 * @param	int		$calendarid		Id of calendar
 * @param	int		$datestart		Start of period (timestamp, included)
 * @param	int		$dateend		End of period (timestamp, excluded)
 * @return	array<int,int>|int		Array with timestamp of start of slot => duration in minutes (negative if slot is already booked), or -1 if error
 */
function bookcalGetSlots($db, $calendarid, $datestart, $dateend)
{
	$slots = array();
	$servertz = new DateTimeZone(@date_default_timezone_get());

	// Get all availability ranges of the calendar
	$sql = "SELECT ba.rowid as id, ba.duration, ba.startHour, ba.endHour, ba.date_start, ba.date_end";
	$sql .= " FROM ".MAIN_DB_PREFIX."bookcal_availabilities as ba";
	$sql .= " WHERE ba.fk_bookcal_calendar = ".((int) $calendarid);
	$sql .= " AND ba.status = 1";
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("bookcalGetSlots ".$db->lasterror(), LOG_ERR);
		return -1;
	}

	// First server day that can contain a slot of the period
	$firstday = new DateTime('@'.((int) $datestart));
	$firstday->setTimezone($servertz);
	$firstday->setTime(0, 0, 0);

	while ($obj = $db->fetch_object($resql)) {
		$duration = (int) $obj->duration;
		if ($duration <= 0) {
			continue;
		}
		$starthour = max(0, (int) $obj->startHour);
		$endhour = min(24, (int) $obj->endHour);

		$day = new DateTime('@'.((int) $db->jdate($obj->date_start)));
		$day->setTimezone($servertz);
		$day->setTime(0, 0, 0);
		if ($day < $firstday) {
			$day = clone $firstday;
		}
		$lastday = new DateTime('@'.((int) $db->jdate($obj->date_end)));
		$lastday->setTimezone($servertz);
		$lastday->setTime(0, 0, 0);

		while ($day <= $lastday && $day->getTimestamp() < $dateend) {
			for ($min = $starthour * 60; $min + $duration <= $endhour * 60; $min += $duration) {
				$slot = clone $day;
				$slot->setTime((int) floor($min / 60), $min % 60, 0);
				$slottime = $slot->getTimestamp();
				if ($slottime >= $datestart && $slottime < $dateend) {
					$slots[$slottime] = $duration;
				}
			}
			$day->modify('+1 day');
		}
	}
	$db->free($resql);

	// Flag the slots already booked
	$sql = "SELECT b.datep, b.id";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as b";
	$sql .= " WHERE b.datep >= '".$db->idate($datestart)."'";
	$sql .= " AND b.datep < '".$db->idate($dateend)."'";
	$sql .= " AND b.code = 'AC_RDV'";
	$sql .= " AND b.status = 0";
	$sql .= " AND b.fk_bookcal_calendar = ".((int) $calendarid);
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("bookcalGetSlots ".$db->lasterror(), LOG_ERR);
		return -1;
	}
	while ($obj = $db->fetch_object($resql)) {
		$slottime = (int) $db->jdate($obj->datep);
		if (isset($slots[$slottime]) && $slots[$slottime] > 0) {
			$slots[$slottime] *= -1;
		}
	}
	$db->free($resql);

	ksort($slots);

	return $slots;
}
