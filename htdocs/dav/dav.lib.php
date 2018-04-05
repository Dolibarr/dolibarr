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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/dav/dav.lib.php
 *      \ingroup    dav
 *      \brief      Server DAV
 */

// define CDAV_CONTACT_TAG if not
if(!defined('CDAV_CONTACT_TAG'))
{
	if(isset($conf->global->CDAV_CONTACT_TAG))
		define('CDAV_CONTACT_TAG', $conf->global->CDAV_CONTACT_TAG);
		else
			define('CDAV_CONTACT_TAG', '');
}

// define CDAV_URI_KEY if not
if(!defined('CDAV_URI_KEY'))
{
	if(isset($conf->global->CDAV_URI_KEY))
		define('CDAV_URI_KEY', $conf->global->CDAV_URI_KEY);
		else
			define('CDAV_URI_KEY', substr(md5($_SERVER['HTTP_HOST']),0,8));
}
