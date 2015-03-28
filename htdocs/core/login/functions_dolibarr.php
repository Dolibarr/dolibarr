<?php
/* Copyright (C) 2007-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent		<jmenent@2byte.es>
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
 *      \brief      Authentication functions for Dolibarr mode (check user on login or email and check pass)
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

	// Force master entity in transversal mode
	$entity=$entitytotest;
	if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)) $entity=1;

	$login='';

	if (! empty($usertotest))
	{
		dol_syslog("functions_dolibarr::check_user_password_dolibarr usertotest=".$usertotest." passwordtotest=".preg_replace('/./','*',$passwordtotest)." entitytotest=".$entitytotest);

		// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
		$table = MAIN_DB_PREFIX."user";
		$usernamecol1 = 'login';
		$usernamecol2 = 'email';
		$entitycol = 'entity';

		$sql ='SELECT rowid, login, entity, pass, pass_crypted';
		$sql.=' FROM '.$table;
		$sql.=' WHERE ('.$usernamecol1." = '".$db->escape($usertotest)."'";
		if (preg_match('/@/',$usertotest)) $sql.=' OR '.$usernamecol2." = '".$db->escape($usertotest)."'";
		$sql.=') AND '.$entitycol." IN (0," . ($entity ? $entity : 1) . ")";

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
				$cryptType='';
				if (! empty($conf->global->DATABASE_PWD_ENCRYPTED)) $cryptType=$conf->global->DATABASE_PWD_ENCRYPTED;
				// By default, we used MD5
				if (! in_array($cryptType,array('md5'))) $cryptType='md5';
				// Check crypted password according to crypt algorithm
				if ($cryptType == 'md5')
				{
					if (dol_hash($passtyped) == $passcrypted)
					{
						$passok=true;
						dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok - ".$cryptType." of pass is ok");
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

				// Password ok ?
				if ($passok)
				{
					$login=$obj->login;
				}
				else
				{
					dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko bad password for '".$usertotest."'");
					sleep(1);
					$langs->load('main');
					$langs->load('errors');
					$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
				}

				// We must check entity
				if ($passok)
				{
					global $mc;

					if (!isset($mc)) {
						//Global not available, disable $conf->multicompany->enabled for safety
						$conf->multicompany->enabled = false;
					}

					if (! empty($conf->multicompany->enabled)) {
						$ret = $mc->checkRight($obj->rowid, $entitytotest);
						if ($ret < 0) {
							dol_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko entity '" . $entitytotest . "' not allowed for user '" . $obj->rowid . "'");
							$login = ''; // force authentication failure
						}
					}
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


