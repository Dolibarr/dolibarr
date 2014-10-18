<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014	   Teddy Andreotti		<125155@supinfo.com>
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
 *      \file       htdocs/core/login/functions_dolibarr.php
 *      \ingroup    core
 *      \brief      Authentication functions for Dolibarr mode
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
function check_user_password_dolibarr($usertotest,$passwordtotest,$entitytotest=1)
{
	global $db,$conf,$langs;
	global $mc;

	dol_syslog("functions_dolibarr::check_user_password_dolibarr usertotest=".$usertotest);

	// Force master entity in transversal mode
	$entity=$entitytotest;
	if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)) $entity=1;

	$login='';

	if (! empty($usertotest))
	{
		// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
		$table = MAIN_DB_PREFIX."user";
		$usernamecol = 'login';
		$entitycol = 'entity';

		$sql ='SELECT rowid, entity, pass, pass_crypted';
		$sql.=' FROM '.$table;
		$sql.=' WHERE '.$usernamecol." = '".$db->escape($usertotest)."'";
		$sql.=' AND '.$entitycol." IN (0," . ($entity ? $entity : 1) . ")";

		dol_syslog("functions_dolibarr::check_user_password_dolibarr", LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);
			if ($obj)
			{
				$passclear=$obj->pass;
				$passcrypted=$obj->pass_crypted;
				$passtyped=$passwordtotest;

				$passok=false;

				// Check crypted password
				//can update password security and use md5
				if (version_compare(PHP_VERSION, '5.5.0') >= 0 && strlen($passcrypted) == 32) {
					if ($passcrypted == dol_hash($passtyped)) {
						$newpasscrypted = password_hash($passtyped, PASSWORD_DEFAULT);

						$edituser = new User($db);
						$result   = $edituser->fetch('', GETPOST('username'));
						if ($result < 0) {
							dol_syslog("functions_dolibarr::check_user_password_dolibarr Update password on missing user");
						} else {
							$edituser->setPassword($edituser, $newpasscrypted);

							dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok - Old md5 is update");
							$passok = true;
						}
					}
				// use password_hash better security
				} else if (version_compare(PHP_VERSION, '5.5.0') >= 0 && strlen($passcrypted) > 32) {
					if (password_verify($passtyped,$passcrypted)) {
						$passok = true;
						dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok - " . password_get_info($passcrypted));
					}
				//php version didn't support password_hash()
				} else if (version_compare(PHP_VERSION, '5.5.0') < 0 && strlen($passcrypted) == 32) {
					if ($passcrypted == dol_hash($passtyped)) {
						$passok = true;
						dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok - md5");
					}
				}

				// For compatibility with old versions
				if (! $passok)
				{
					if ((! $passcrypted || $passtyped)
						&& ($passtyped == $passclear))
					{
						$passok=true;
						dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok - found pass in database");
					}
				}

				if ($passok && ! empty($obj->entity) && (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
				{
					$ret=$mc->checkRight($obj->rowid, $entitytotest);
					if ($ret < 0) $passok=false;
				}

				// Password ok ?
				if ($passok)
				{
					$login=$usertotest;
				}
				else
				{
					dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko bad password pour '".$usertotest."'");
					sleep(1);
					$langs->load('main');
					$langs->load('errors');
					$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
				}
			}
			else
			{
				dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko user not found for '".$usertotest."'");
				sleep(1);
				$langs->load('main');
				$langs->load('errors');
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
			}
		}
		else
		{
			dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko db error for '".$usertotest."' error=".$db->lasterror());
			sleep(1);
			$_SESSION["dol_loginmesg"]=$db->lasterror();
		}
	}

	return $login;
}


