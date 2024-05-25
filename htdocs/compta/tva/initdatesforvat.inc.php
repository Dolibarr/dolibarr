<?php
/* Copyright (C) 2021 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file           htdocs/compta/tva/initdatesforvat.inc.php
 *  \brief          Set value for date_start and date_end
 */

$now = dol_now();
$current_date = dol_getdate($now);
if (!getDolGlobalInt('SOCIETE_FISCAL_MONTH_START')) {
	$conf->global->SOCIETE_FISCAL_MONTH_START = 1;
}

// Date range
$year = GETPOSTINT("year");
if (empty($year)) {
	$year_current = $current_date['year'];
	$year_start = $year_current;
} else {
	$year_current = $year;
	$year_start = $year;
}
$date_start = dol_mktime(0, 0, 0, GETPOST("date_startmonth"), GETPOST("date_startday"), GETPOST("date_startyear"), 'tzserver');
$date_end = dol_mktime(23, 59, 59, GETPOST("date_endmonth"), GETPOST("date_endday"), GETPOST("date_endyear"), 'tzserver');
// Set default period if not defined
if (empty($date_start) || empty($date_end)) { // We define date_start and date_end
	$q = GETPOSTINT("q");
	if (empty($q)) {
		if (GETPOSTINT("month")) {
			$date_start = dol_get_first_day($year_start, GETPOSTINT("month"), 'tzserver');
			$date_end = dol_get_last_day($year_start, GETPOSTINT("month"), 'tzserver');
		} else {
			if (!getDolGlobalString('MAIN_INFO_VAT_RETURN') || getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 2) { // quaterly vat, we take last past complete quarter
				$date_start = dol_time_plus_duree(dol_get_first_day($year_start, $current_date['mon'], false), -3 - (($current_date['mon'] - $conf->global->SOCIETE_FISCAL_MONTH_START) % 3), 'm');
				$date_end = dol_time_plus_duree($date_start, 3, 'm') - 1;
			} elseif (getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 3) { // yearly vat
				if ($current_date['mon'] < $conf->global->SOCIETE_FISCAL_MONTH_START) {
					if (($conf->global->SOCIETE_FISCAL_MONTH_START - $current_date['mon']) > 6) {	// If period started from less than 6 years, we show past year
						$year_start--;
					}
				} else {
					if (($current_date['mon'] - $conf->global->SOCIETE_FISCAL_MONTH_START) < 6) {	// If perdio started from less than 6 years, we show past year
						$year_start--;
					}
				}
				$date_start = dol_get_first_day($year_start, $conf->global->SOCIETE_FISCAL_MONTH_START, 'tzserver');
				$date_end = dol_time_plus_duree($date_start, 1, 'y') - 1;
			} elseif (getDolGlobalInt('MAIN_INFO_VAT_RETURN') == 1) {	// monthly vat, we take last past complete month
				$date_start = dol_time_plus_duree(dol_get_first_day($year_start, $current_date['mon'], false), -1, 'm');
				$date_end = dol_time_plus_duree($date_start, 1, 'm') - 1;
			}
		}
	} else {
		if ($q == 1) {
			$date_start = dol_get_first_day($year_start, 1, 'tzserver');
			$date_end = dol_get_last_day($year_start, 3, 'tzserver');
		}
		if ($q == 2) {
			$date_start = dol_get_first_day($year_start, 4, 'tzserver');
			$date_end = dol_get_last_day($year_start, 6, 'tzserver');
		}
		if ($q == 3) {
			$date_start = dol_get_first_day($year_start, 7, 'tzserver');
			$date_end = dol_get_last_day($year_start, 9, 'tzserver');
		}
		if ($q == 4) {
			$date_start = dol_get_first_day($year_start, 10, 'tzserver');
			$date_end = dol_get_last_day($year_start, 12, 'tzserver');
		}
	}
}

//print dol_print_date($date_start, 'day').' '.dol_print_date($date_end, 'day');

$tmp = dol_getdate($date_start);
$date_start_day = $tmp['mday'];
$date_start_month = $tmp['mon'];
$date_start_year = $tmp['year'];
$tmp = dol_getdate($date_end);
$date_end_day = $tmp['mday'];
$date_end_month = $tmp['mon'];
$date_end_year = $tmp['year'];
