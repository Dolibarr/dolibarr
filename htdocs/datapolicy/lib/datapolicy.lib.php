<?php
/* Copyright (C) 2018 Nicolas ZABOURI   <info@inovea-conseil.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 * @return array
 */
function datapolicyAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("datapolicy");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/setup.php";
	$head[$h][1] = $langs->trans("Deletion");
	$head[$h][2] = 'settings';
	$h++;

	if (getDolGlobalString('DATAPOLICY_ENABLE_EMAILS') || getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		$head[$h][0] = DOL_URL_ROOT."/datapolicy/admin/setupmail.php";
		$head[$h][1] = $langs->trans("DATAPOLICYMail");
		$head[$h][2] = 'emailing';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy', 'remove');

	return $head;
}
