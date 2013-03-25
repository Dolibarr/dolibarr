<?php
/* Copyright (C) 2012 Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013 Florian Henry <florian.henry@opn-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       cron/lib/cron.lib.php
 *	\brief      Function for module cron
 *	\ingroup    cron
 */

/**
 * Return array of tabs to used on pages to setup cron module.
 *
 * @return 	array				Array of tabs
 */
function cronadmin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/cron/admin/cron.php', 1);
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'setup';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'cronadmin');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'cronadmin', 'remove');


    return $head;
}

/**
 * Return array of tabs to used on a cron job
 *
 * @param 	Object	$object		Object cron
 * @return 	array				Array of tabs
 */
function cron_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/cron/card.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("CronTask");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath('/cron/info.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'cron');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'cron', 'remove');

	return $head;
}
