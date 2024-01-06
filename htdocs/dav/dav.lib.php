<?php
/* Copyright (C) 2018	Destailleur Laurent	<eldy@users.sourceforge.net>
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
 *      \file       htdocs/dav/dav.lib.php
 *      \ingroup    dav
 *      \brief      Server DAV
 */

// define CDAV_CONTACT_TAG if not
if (!defined('CDAV_CONTACT_TAG')) {
	if (getDolGlobalString('CDAV_CONTACT_TAG')) {
		define('CDAV_CONTACT_TAG', getDolGlobalString('CDAV_CONTACT_TAG'));
	} else {
		define('CDAV_CONTACT_TAG', '');
	}
}

// define CDAV_URI_KEY if not
if (!defined('CDAV_URI_KEY')) {
	if (getDolGlobalString('CDAV_URI_KEY')) {
		define('CDAV_URI_KEY', getDolGlobalString('CDAV_URI_KEY'));
	} else {
		define('CDAV_URI_KEY', substr(md5($_SERVER['HTTP_HOST']), 0, 8));
	}
}




/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function dav_admin_prepare_head()
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/dav.php';
	$head[$h][1] = $langs->trans("WebDAV");
	$head[$h][2] = 'webdav';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'admindav');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'admindav', 'remove');

	return $head;
}
