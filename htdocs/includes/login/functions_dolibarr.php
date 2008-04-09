<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/includes/login/functions_dolibarr.php
        \ingroup    core
        \brief      Authentication functions for Dolibarr mode
		\version	$Id$
*/


/**
        \brief		Check user and password
        \param		usertotest		Login
        \param		passwordtotest	Password
        \return		string			Login if ok, '' if ko.
*/
function check_user_password_dolibarr($usertotest,$passwordtotest)
{
	global $_POST,$db,$conf,$langs;
	
	dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr usertotest=".$usertotest);

	$login='';
	
	if (! empty($_POST["username"])) 
	{
		// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
		$table = MAIN_DB_PREFIX."user";
		$usernamecol = 'login';
		
		$sql ='SELECT pass, pass_crypted';
		$sql.=' from '.$table;
		$sql.=' where '.$usernamecol." = '".addslashes($_POST["username"])."'";

		dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr sql=".$sql);
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);
			if ($obj)
			{
				$passclear=$obj->pass;
				$passcrypted=$obj->pass_crypted;
				$passtyped=$_POST["password"];

				$passok=false;
				
				// Check crypted password
				$cryptType='';
				if ($conf->global->DATABASE_PWD_ENCRYPTED) $cryptType='md5';
				if ($cryptType == 'md5') 
				{
					if (md5($passtyped) == $passcrypted) $passok=true;
				}

				// For compatibility with old versions
				if (! $passok)
				{
					if ((! $passcrypted || $passtyped) 
						&& ($passtyped == $passclear)) $passok=true;
				}
				
				// Password ok ?
				if ($passok)
				{
					dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ok");
					$login=$_POST["username"];
				}
				else
				{
					dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko bad password pour '".$_POST["username"]."'");
					sleep(1);
					$langs->load('main');
					$langs->load('other');
					$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
				}
			}
			else
			{
				dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko user not found pour '".$_POST["username"]."'");
				sleep(1);
				$langs->load('main');
				$langs->load('other');
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
			}
		}
		else
		{
			dolibarr_syslog("functions_dolibarr::check_user_password_dolibarr Authentification ko db error pour '".$_POST["username"]."' error=".$db->lasterror());
			sleep(1);
			$_SESSION["dol_loginmesg"]=$db->lasterror();
		}
	}

	return $login;
}


?>