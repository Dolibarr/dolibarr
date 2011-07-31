<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/includes/login/functions_http.php
        \ingroup    core
        \brief      Authentication functions for HTTP Basic
		\version	$Id$
*/


/**
        \brief		Check user and password
        \param		usertotest		Login
        \param		passwordtotest	Password
        \return		string			Login if ok, '' if ko.
*/
function check_user_password_http($usertotest,$passwordtotest)
{
	dol_syslog("functions_http::check_user_password_http _SERVER[REMOTE_USER]=".$_SERVER["REMOTE_USER"]);

	$login='';
	if (! empty($_SERVER["REMOTE_USER"]))
	{
		$login=$_SERVER["REMOTE_USER"];
	}
		
	return $login;
}


?>