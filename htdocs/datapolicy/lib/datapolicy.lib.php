<?php
/* Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/datapolicy/lib/datapolicy.lib.php
 * \ingroup datapolicy
 * \brief   Library files with common functions for datapolicy
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function datapolicyAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("datapolicy");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/setup.php";

	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		$head[$h][1] = $langs->trans("DataAnonymizationOrDeletion");
	} else {
		$head[$h][1] = $langs->trans("DataAnonymization");
	}
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/retention.php";
	$head[$h][1] = $langs->trans("DocumentRetention");
	$head[$h][2] = 'retention';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy', 'remove');

	return $head;
}

/**
 * Date until which a file should be kept, when the module Data privacy is enabled and a number of years to keep
 * business documents is set (DATAPOLICY_DOCUMENT_RETENTION_YEARS): the end of the fiscal year of the date of the
 * file, plus this number of years. Counting from the end of the fiscal year gives a date that is never earlier than
 * the usual legal rules (for example 5 years after the record was made, or 10 years after the end of the fiscal year).
 * It is a hint only: nothing is deleted or blocked.
 *
 * @param	int		$filedate	Date of the file (timestamp)
 * @return	int					Date until which the file should be kept (timestamp), or 0 if not used
 */
function datapolicyGetFileKeepUntilDate($filedate)
{
	$years = getDolGlobalInt('DATAPOLICY_DOCUMENT_RETENTION_YEARS');
	if (!isModEnabled('datapolicy') || $years <= 0 || empty($filedate)) {
		return 0;
	}
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$startmonth = max(1, min(12, getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)));
	$endmonth = ($startmonth == 1 ? 12 : $startmonth - 1);
	$tmparray = dol_getdate((int) $filedate);
	$endyear = ((int) $tmparray['mon'] > $endmonth ? (int) $tmparray['year'] + 1 : (int) $tmparray['year']);

	return (int) dol_get_last_day($endyear + $years, $endmonth);
}
