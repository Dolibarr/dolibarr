<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/includes/login/functions_ldap.php
 *       \ingroup    core
 *       \brief      Authentication functions for LDAP
 */


/**
 *       @brief		Check user and password
 *       @param		usertotest		Login
 *       @param		passwordtotest	Password
 *       @return	string			Login if ok, '' if ko.
 * 	     @remarks	If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 */
function check_user_password_ldap($usertotest,$passwordtotest)
{
	global $_POST,$db,$conf,$langs;
	global $dolibarr_main_auth_ldap_host,$dolibarr_main_auth_ldap_port;
	global $dolibarr_main_auth_ldap_version,$dolibarr_main_auth_ldap_servertype;
	global $dolibarr_main_auth_ldap_login_attribute,$dolibarr_main_auth_ldap_dn;
	global $dolibarr_main_auth_ldap_admin_login,$dolibarr_main_auth_ldap_admin_pass;
	global $dolibarr_main_auth_ldap_debug;

	if (! function_exists("ldap_connect"))
	{
		dol_syslog("functions_ldap::check_user_password_ldap Authentification ko failed to connect to LDAP. LDAP functions are disabled on this PHP");
		sleep(1);
		$langs->load('main');
		$langs->load('other');
		$_SESSION["dol_loginmesg"]=$langs->trans("ErrorLDAPFunctionsAreDisabledOnThisPHP").' '.$langs->trans("TryAnotherConnectionMode");
		return;
	}

	$login='';
	$resultFetchUser='';

	if (! empty($_POST["username"]))
	{
		// If test username/password asked, we define $test=false and $login var if ok, set $_SESSION["dol_loginmesg"] if ko
		$ldaphost=$dolibarr_main_auth_ldap_host;
		$ldapport=$dolibarr_main_auth_ldap_port;
		$ldapversion=$dolibarr_main_auth_ldap_version;
		$ldapservertype=(empty($dolibarr_main_auth_ldap_servertype) ? 'openldap' : $dolibarr_main_auth_ldap_servertype);

		$ldapuserattr=$dolibarr_main_auth_ldap_login_attribute;
		$ldapdn=$dolibarr_main_auth_ldap_dn;
		$ldapadminlogin=$dolibarr_main_auth_ldap_admin_login;
		$ldapadminpass=$dolibarr_main_auth_ldap_admin_pass;
		$ldapdebug=(empty($dolibarr_main_auth_ldap_debug) || $dolibarr_main_auth_ldap_debug=="false" ? false : true);

		if ($ldapdebug) print "DEBUG: Logging LDAP steps<br>\n";

		require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
		$ldap=new Ldap();
		$ldap->server=array($ldaphost);
		$ldap->serverPort=$ldapport;
		$ldap->ldapProtocolVersion=$ldapversion;
		$ldap->serverType=$ldapservertype;
		$ldap->searchUser=$ldapadminlogin;
		$ldap->searchPassword=$ldapadminpass;

		dol_syslog("functions_ldap::check_user_password_ldap usertotest=".$usertotest);
		if ($ldapdebug)
		{
			dol_syslog("functions_ldap::check_user_password_ldap Server:".join(',',$ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType);
			dol_syslog("functions_ldap::check_user_password_ldap uid/samacountname=".$ldapuserattr.", dn=".$ladpdn.", Admin:".$ldap->searchUser.", Pass:".$ldap->searchPassword);
			print "DEBUG: Server:".join(',',$ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType."\n";
			print "DEBUG: uid/samacountname=".$ldapuserattr.", dn=".$ladpdn.", Admin:".$ldap->searchUser.", Pass:".$ldap->searchPassword."\n";
		}

		$resultCheckUserDN=false;

		// Define $userSearchFilter
        $userSearchFilter = "";
        if (empty($dolibarr_main_auth_ldap_filter)) {
            $userSearchFilter = "(" . $ldapuserattr . "=" . $usertotest . ")";
        } else {
            $userSearchFilter = str_replace('%1%', $usertotest, $dolibarr_main_auth_ldap_filter);
        }

		// If admin login provided
		// Code to get user in LDAP from an admin connection (may differ from user connection, done later)
		if ($ldapadminlogin)
		{
			$result=$ldap->connect_bind();
			if ($result)
			{
				$resultFetchLdapUser = $ldap->fetch($usertotest,$userSearchFilter);
				// On stop si le mot de passe ldap doit etre modifie sur le domaine
				if ($resultFetchLdapUser == 1 && $ldap->pwdlastset == 0)
				{
					dol_syslog('functions_ldap::check_user_password_ldap '.$usertotest.' must change password next logon');
					if ($ldapdebug) print "DEBUG: User ".$usertotest." must change password<br>\n";
					$ldap->close();
					sleep(1);
					$langs->load('ldap');
					$_SESSION["dol_loginmesg"]=$langs->trans("YouMustChangePassNextLogon",$usertotest,$ldap->domainFQDN);
					return '';
				}
				else
				{
					$resultCheckUserDN = $ldap->checkPass($usertotest,$passwordtotest); // $ldap->ldapUserDN is defined if ok
				}
			}
			$ldap->close();
		}

		// Forge LDAP user and password to test with them
		$ldap->searchUser=$ldapuserattr."=".$usertotest.",".$ldapdn;
		if ($resultCheckUserDN) $ldap->searchUser = $ldap->ldapUserDN;
        $ldap->searchPassword=$passwordtotest;

		// Test with this->seachUser and this->searchPassword
		$result=$ldap->connect_bind();
		if ($result > 0)
		{
			if ($result == 2)
			{
				dol_syslog("functions_ldap::check_user_password_ldap Authentification ok");
				$login=$usertotest;

				// ldap2dolibarr synchronisation
				if ($login && $conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
				{
					// On charge les attributs du user ldap
					if ($ldapdebug) print "DEBUG: login ldap = ".$login."<br>\n";
					$ldap->fetch($login,$userSearchFilter);

					if ($ldapdebug) print "DEBUG: UACF = ".join(',',$ldap->uacf)."<br>\n";
					if ($ldapdebug) print "DEBUG: pwdLastSet = ".dol_print_date($ldap->pwdlastset,'day')."<br>\n";
					if ($ldapdebug) print "DEBUG: badPasswordTime = ".dol_print_date($ldap->badpwdtime,'day')."<br>\n";

					// On recherche le user dolibarr en fonction de son SID ldap
					$sid = $ldap->getObjectSid($login);
					if ($ldapdebug) print "DEBUG: sid = ".$sid."<br>\n";

					$user=new User($db);
					$resultFetchUser=$user->fetch('',$login,$sid);
					if ($resultFetchUser > 0)
					{
						// On verifie si le login a change et on met a jour les attributs dolibarr
						if ($user->login != $ldap->login && $ldap->login)
						{
							$user->login = $ldap->login;
							$user->update($user);
							// TODO Que faire si update echoue car on update avec un login deja existant.
						}
						//$resultUpdate = $user->update_ldap2dolibarr();
					}
				}
			}
			if ($result == 1)
			{
				dol_syslog("functions_ldap::check_user_password_ldap Authentification ko bad user/password for '".$usertotest."'");
				sleep(1);
				$langs->load('main');
				$langs->load('other');
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
			}
		}
		else
		{
			dol_syslog("functions_ldap::check_user_password_ldap Authentification ko failed to connect to LDAP for '".$usertotest."'");
			sleep(1);
			$langs->load('main');
			$langs->load('other');
			$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
		}

		$ldap->close();
	}

	return $login;
}


?>