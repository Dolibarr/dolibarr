<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 * \file       htdocs/core/login/functions_forceuser.php
 * \ingroup    core
 * \brief      Authentication functions for forceuser
 */


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_forceuser($usertotest, $passwordtotest, $entitytotest)
{
	// Variable dolibarr_auto_user must be defined in conf.php file
	global $dolibarr_auto_user;

	dol_syslog("functions_forceuser::check_user_password_forceuser");

	$login = $dolibarr_auto_user;
	if (empty($login)) {
		$login = 'auto';
	}

	if ($_SESSION["dol_loginmesg"]) {
		$login = '';
	}

	dol_syslog("functions_forceuser::check_user_password_forceuser ok. forced user = ".$login);
	return $login;
}
