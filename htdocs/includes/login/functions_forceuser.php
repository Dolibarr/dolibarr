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
 *
 * $Id$
 */

/**
        \file       htdocs/includes/login/functions_forceuser.php
        \ingroup    core
        \brief      Authentication functions for forceuser
*/


/**
        \brief		Check user and password
        \param		usertotest		Login
        \param		passwordtotest	Password
        \return		string			Login if ok, '' if ko.
*/
function check_user_password_forceuser($usertotest,$passwordtotest)
{
	// Variable dolibarr_auto_user must be defined in conf.php file
	global $dolibarr_auto_user;
	
	dolibarr_syslog("functions_forceuser::check_user_password_forceuser");

	$login=$dolibarr_auto_user;
	if (empty($login)) $login='auto';
	
	if ($_SESSION["dol_loginmesg"]) $login='';
	
	return $login;
}


?>