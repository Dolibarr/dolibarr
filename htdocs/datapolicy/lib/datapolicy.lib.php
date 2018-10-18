<?php
/* Copyright (C) 2018 Nicolas ZABOURI   <info@inovea-conseil.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    datapolicy/lib/datapolicy.lib.php
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

	$langs->load("datapolicy@datapolicy");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/datapolicy/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Deletion");
	$head[$h][2] = 'settings';
	$h++;

	if (! empty($conf->global->DATAPOLICIES_ENABLE_EMAILS))
	{
		$head[$h][0] = dol_buildpath("/datapolicy/admin/setupmail.php", 1);
		$head[$h][1] = $langs->trans("DATAPOLICIESMail");
		$head[$h][2] = 'settings';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'datapolicy');

	return $head;
}
