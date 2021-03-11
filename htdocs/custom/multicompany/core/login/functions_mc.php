<?php
/* Copyright (C) 2014-2019	Regis Houssin	<regis.houssin@inodbox.com>
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
 *      \file       multicompany/core/login/functions_mc.php
 *      \ingroup    multicompany
 *      \brief      Authentication functions for Multicompany mode when combobox in login page is disabled
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
function check_user_password_mc($usertotest,$passwordtotest,$entitytotest=1)
{
	global $db,$conf,$langs;
	global $mc;

	dol_syslog("functions_mc::check_user_password_mc usertotest=".$usertotest);

	$login='';

	if (!empty($conf->multicompany->enabled))
	{
		$langs->loadLangs(array('main','errors','multicompany@multicompany'));

		if (! empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX))
		{
			$entity=$entitytotest;

			if (!empty($usertotest))
			{
				// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
				$table = MAIN_DB_PREFIX."user";
				$usernamecol = 'login';
				$entitycol = 'entity';

				$sql ='SELECT rowid, entity, pass, pass_crypted';
				$sql.=' FROM '.$table;
				$sql.=' WHERE '.$usernamecol." = '".$db->escape($usertotest)."'";
				$sql.=' AND statut = 1';

				dol_syslog("functions_mc::check_user_password_mc sql=" . $sql);
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
							if (dol_verifyHash($passtyped, $passcrypted))
							{
								$passok=true;
								dol_syslog("functions_mc::check_user_password_mc Authentification ok - " . $cryptType . " of pass is ok");
							}
						}

						// For compatibility with old versions
						if (! $passok)
						{
							if ((! $passcrypted || $passtyped)
								&& ($passclear && ($passtyped == $passclear)))
							{
								$passok=true;
								dol_syslog("functions_mc::check_user_password_mc Authentification ok - found pass in database");
							}
						}

						if ($passok && !empty($obj->entity))
						{
							global $entitytotest;

							$entitytotest = $obj->entity;

							if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
							{
								$sql = "SELECT uu.entity";
								$sql.= " FROM " . MAIN_DB_PREFIX . "usergroup_user as uu";
								$sql.= ", " . MAIN_DB_PREFIX . "entity as e";
								$sql.= " WHERE uu.entity = e.rowid AND e.visible < 2"; // Remove template of entity
								$sql.= " AND fk_user = " . $obj->rowid;

								dol_syslog("functions_mc::check_user_password_mc sql=" . $sql, LOG_DEBUG);
								$result = $db->query($sql);
								if ($result)
								{
									while($array = $db->fetch_array($result)) // user allowed if at least in one group
									{
										$entitytotest = $array['entity'];
										break; // stop in first entity
									}
								}
							}

							$ret=$mc->switchEntity($entitytotest, $obj->rowid);

							if ($ret < 0) $passok=false;
						}

						// Password ok ?
						if ($passok)
						{
							$login=$usertotest;
						}
						else
						{
							dol_syslog("functions_mc::check_user_password_mc Authentification ko bad password pour '".$usertotest."'", LOG_ERR);
							$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
						}
					}
					else
					{
						dol_syslog("functions_mc::check_user_password_mc Authentification ko user not found for '".$usertotest."'", LOG_ERR);
						$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
					}
				}
				else
				{
					dol_syslog("functions_mc::check_user_password_mc Authentification ko db error for '".$usertotest."' error=".$db->lasterror(), LOG_ERR);
					$_SESSION["dol_loginmesg"]=$db->lasterror();
				}
			}
		}
		else
		{
			dol_syslog("functions_mc::check_user_password_mc Authentification ko, the drop-down list of entities on the login page must be hidden", LOG_ERR);
			$_SESSION["dol_loginmesg"]=$langs->trans("ErrorDropDownListOfEntitiesMustBeHidden");
		}
	}

	return $login;
}
