<?php
/**
 * Copyright (C) 2015	Charlie BENKE       <charlie@patas-monkey.com>
 * Copyright (C) 2019	Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023       Frédéric France         <frederic.france@netlogic.fr>
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
 * Function for return differents onglet of socialnetworks admin page
 * @param   int  $id    id of dictionary
 * @return  array   Tabs for the admin section
 */
function socialnetwork_prepare_head($id)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/dict.php?id='.$id;
	$head[$h][1] = $langs->trans("Dictionary");
	$head[$h][2] = 'dict';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/admin/faitdivers.php';
	$head[$h][1] = $langs->trans("FaitDivers");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $id, $head, $h, 'socialnetwork', 'add', 'external');
	complete_head_from_modules($conf, $langs, $id, $head, $h, 'socialnetwork', 'remove');
	return $head;
}
