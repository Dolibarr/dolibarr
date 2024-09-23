<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2021 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2019      Eric Seigne          <eric.seigne@cap-rel.fr>
 * Copyright (C) 2021-2024 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 */

/**
 * 	\file		htdocs/core/lib/public.lib.php
 * 	\ingroup	public
 * 	\brief		Library of public page functions
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Check if the object exceeded the number of posts for a specific ip
 * @param  object    $object         Object to check
 * @param  int       $nb_post_max    Number max of posts
 *
 * @return int       return <0 if error, >0 if OK
 */
function checkNbPostsForASpeceificIp($object, $nb_post_max)
{
	global $db, $langs;

	$nb_post_ip = 0;
	$now = dol_now();
	$minmonthpost = dol_time_plus_duree($now, -1, "m");

	if (empty($object->ip)) {
		$object->ip = getUserRemoteIP();
	}

	if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
		$sql = "SELECT COUNT(".(!empty($object->table_rowid) ? $object->table_rowid : 'rowid').") as nb_posts";
		$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element;
		$sql .= " WHERE ip = '".$db->escape($object->ip)."'";
		$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$i++;
				$obj = $db->fetch_object($resql);
				$nb_post_ip = $obj->nb_posts;
			}
		} else {
			array_push($object->errors, $db->lasterror());
			return -1;
		}
	}
	if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
		array_push($object->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		return -1;
	}
	return 1;
}
