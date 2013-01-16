<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/emailing.lib.php
 *		\brief      Library file with function for emailing module
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function emailing_prepare_head($object)
{
	global $user, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("MailCard");
	$head[$h][2] = 'card';
	$h++;
	
	if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->recipient) {
		return $head;
	}

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$object->id;
	$head[$h][1] = $langs->trans("MailRecipients");
	$head[$h][2] = 'targets';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/info.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>