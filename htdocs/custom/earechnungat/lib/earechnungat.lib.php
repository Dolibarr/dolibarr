<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \file       lib/earechnungat.lib.php
 * \ingroup    earechnungat
 * \brief      Library files with common functions for EARechnungAT
 */

/**
 * Prepare admin pages header
 *
 * @return array Array of tabs
 */
function earechnungatAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("earechnungat@earechnungat");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/earechnungat/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("EARechnungATSetup");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/earechnungat/admin/about.php", 1);
	$head[$h][1] = $langs->trans("EARechnungATAbout");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'earechnungat@earechnungat');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'earechnungat@earechnungat', 'remove');

	return $head;
}

/**
 * Get date range for a given year/period
 *
 * @param int         $year   Year
 * @param string|null $period Period: null=full year, 'Q1'-'Q4', '01'-'12'
 * @return array{start:int,end:int} Array with start and end timestamps
 */
function earechnungatGetDateRange($year, $period = null)
{
	if ($period && preg_match('/^Q([1-4])$/', $period, $matches)) {
		$quarter = (int) $matches[1];
		$startMonth = (($quarter - 1) * 3) + 1;
		$endMonth = $quarter * 3;
		$start = dol_mktime(0, 0, 0, $startMonth, 1, $year);
		$end = dol_mktime(23, 59, 59, $endMonth, (int) date('t', mktime(0, 0, 0, $endMonth, 1, $year)), $year);
	} elseif ($period && is_numeric($period) && $period >= 1 && $period <= 12) {
		$month = (int) $period;
		$start = dol_mktime(0, 0, 0, $month, 1, $year);
		$end = dol_mktime(23, 59, 59, $month, (int) date('t', mktime(0, 0, 0, $month, 1, $year)), $year);
	} else {
		$start = dol_mktime(0, 0, 0, 1, 1, $year);
		$end = dol_mktime(23, 59, 59, 12, 31, $year);
	}

	return array('start' => $start, 'end' => $end);
}
