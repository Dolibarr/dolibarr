<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/includes/login/functions_empty.php
 *      \ingroup    core
 *      \brief      Empty authentication functions for test
 *		\version	$Id: functions_empty.php,v 1.2 2011/07/31 23:29:10 eldy Exp $
 */


/**
 *      \brief		Check user and password
 *      \param		usertotest		Login
 *      \param		passwordtotest	Password
 *      \return		string			Login if ok, '' if ko.
 */
function check_user_password_empty($usertotest,$passwordtotest)
{
	dol_syslog("functions_empty::check_user_password_empty usertotest=".$usertotest);

	$login='';

	return $login;
}

?>