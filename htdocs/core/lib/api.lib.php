<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/core/lib/api.lib.php
 * \brief      Ensemble de functions de base pour le module propal
 * \ingroup    propal
 */

/**
 *  Return array head with list of tabs to view object information.
 *
 *  @return	array<array{0:string,1:string,2:string}>	head array with tabs
 */
function api_admin_prepare_head()
{
	global $langs;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/api/admin/index.php';
	$head[$h][1] = $langs->trans("Parameter");
	$head[$h][2] = 'parameter';
	$h++;

	if (getDolGlobalString('API_IN_TOKEN_TABLE')) {
		$head[$h][0] = DOL_URL_ROOT.'/api/admin/token_list.php';
		$head[$h][1] = $langs->trans("ListOfTokens");
		$head[$h][2] = 'token_list';
		$h++;
	}

	return $head;
}
