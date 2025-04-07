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
	$head[$h][1] = $langs->trans("DataAnonymization");
	$head[$h][2] = 'settings';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'datapolicy', 'remove');

	return $head;
}
