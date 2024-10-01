<?php
/* Copyright (C) 2006-2009  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/lib/import.lib.php
 *  \brief      Ensemble de functions de base pour le module import
 *  \ingroup    import
 */

/**
 * Function to return list of tabs for import pages
 *
 * @param	string		$param		Params to add on url links
 * @param	int			$maxstep	Limit steps to maxstep or no limit if 0
 * @return	array					Array of tabs
 */
function import_prepare_head($param, $maxstep = 0)
{
	global $langs;

	if (empty($maxstep)) {
		$maxstep = 6;
	}

	$h = 0;
	$head = array();
	$i = 1;
	while ($i <= $maxstep) {
		if ($i < 6) {
			$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step='.$i.$param;
		} else {
			$head[$h][0] = DOL_URL_ROOT.'/imports/import.php?step=5'.$param; // For step6, link is to step 5
		}
		$head[$h][1] = $langs->trans("Step")." ".$i;
		$head[$h][2] = 'step'.$i;
		$h++;
		$i++;
	}

	return $head;
}
