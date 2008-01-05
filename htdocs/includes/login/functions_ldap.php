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
        \file       htdocs/includes/login/functions_ldap.php
        \ingroup    core
        \brief      Authentication functions for LDAP
*/


/**
        \brief		Check user and password
        \param		usertotest		Login
        \param		passwordtotest	Password
        \return		string			Login if ok, '' if ko.
		\remarks	If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
*/
function check_user_password_ldap($usertotest,$passwordtotest)
{
	global $_POST,$db,$conf,$langs;
	global $dolibarr_main_auth_ldap_host,$dolibarr_main_auth_ldap_port;
	global $dolibarr_main_auth_ldap_version,$dolibarr_main_auth_ldap_servertype;
	global $dolibarr_main_auth_ldap_login_attribute,$dolibarr_main_auth_ldap_dn;
	global $dolibarr_main_auth_ldap_admin_login,$dolibarr_main_auth_ldap_admin_pass;
	global $dolibarr_main_auth_ldap_debug;
	
	dolibarr_syslog("functions_ldap::check_user_password_ldap usertotest=".$usertotest);

	if (! function_exists("ldap_connect"))
	{
		dolibarr_syslog("functions_ldap::check_user_password_ldap Authentification ko failed to connect to LDAP. LDAP functions are disabled on this PHP");
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

		// Debut code pour compatibilite (prend info depuis config en base)
		// Ne plus utiliser. La config LDAP de connexion doit etre dans le fichier conf.php
		if (! $ldapuserattr && $conf->ldap->enabled)
		{
			if ($conf->global->LDAP_SERVER_TYPE == "activedirectory")
			  {
				$ldapuserattr = $conf->global->LDAP_FIELD_LOGIN_SAMBA;
			  }
			  else
			  {
				$ldapuserattr = $conf->global->LDAP_FIELD_LOGIN;
			  }
		}
		if (! $ldaphost)       $ldaphost=$conf->global->LDAP_SERVER_HOST;
		if (! $ldapport)       $ldapport=$conf->global->LDAP_SERVER_PORT;
		if (! $ldapservertype) $ldapservertype=$conf->global->LDAP_SERVER_TYPE;
		if (! $ldapversion)    $ldapversion=$conf->global->LDAP_SERVER_PROTOCOLVERSION;
		if (! $ldapdn)         $ldapdn=$conf->global->LDAP_SERVER_DN;
		if (! $ldapadminlogin) $ldapadminlogin=$conf->global->LDAP_ADMIN_DN;
		if (! $ldapadminpass)  $ldapadminpass=$conf->global->LDAP_ADMIN_PASS;
		// Fin code pour compatiblite
		
		require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
		$ldap=new Ldap();
		$ldap->server=array($ldaphost);
		$ldap->serverPort=$ldapport;
		$ldap->ldapProtocolVersion=$ldapversion;
		$ldap->serverType=$ldapservertype;
		$ldap->searchUser=$ldapadminlogin;
		$ldap->searchPassword=$ldapadminpass;
		
		if ($ldapdebug) dolibarr_syslog("Authentification LDAP --> Server:".join(',',$ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType.", Admin:".$ldap->searchUser.", Pass:".$ldap->searchPassword);
		
		// Code to get user in LDAP (may differ from Dolibarr user)
		$result=$ldap->connect_bind();
		if ($result)
		{
			$resultFetchLdapUser = $ldap->fetch($_POST["username"]);
			// On stop si le mot de passe ldap doit etre modifie sur le domaine
			if ($resultFetchLdapUser == 1 && $ldap->pwdlastset == 0)
			{
				dolibarr_syslog('functions_ldap::User '.$login.' must change password next logon');
				if ($ldapdebug) print "DEBUG: User ".$login." must change password<br>\n";
				$ldap->close();
				sleep(1);
				$langs->load('ldap');
				$_SESSION["dol_loginmesg"]=$langs->trans("YouMustChangePassNextLogon",$ldap->domainFQDN);
				return $login;
			}
			else
			{
				$resultCheckUserDN = $ldap->checkPass($usertotest,$passwordtotest);
			}
		}
		$ldap->close();
		
		$ldap->searchUser=$usertotest;
		if ($resultCheckUserDN) $ldap->searchUser = $ldap->ldapUserDN;
		$ldap->searchPassword=$passwordtotest;

		$result=$ldap->connect_bind();	// Test with this->seachUser and this->searchPassword
		if ($result > 0)
		{
			if ($result == 2)
			{
				dolibarr_syslog("functions_ldap::check_user_password_ldap Authentification ok");
				$login=$_POST["username"];

				// ldap2dolibarr synchronisation
				if ($login && $conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
				{
					// On charge les attributs du user ldap
					if ($ldapdebug) print "DEBUG: login ldap = ".$login."<br>\n";
					$ldap->fetch($login);
					
					if ($ldapdebug) print "DEBUG: UACF = ".join(',',$ldap->uacf)."<br>\n";
					if ($ldapdebug) print "DEBUG: pwdLastSet = ".dolibarr_print_date($ldap->pwdlastset,'day')."<br>\n";
					if ($ldapdebug) print "DEBUG: badPasswordTime = ".dolibarr_print_date($ldap->badpwdtime,'day')."<br>\n";
					
					// On recherche le user dolibarr en fonction de son SID ldap
					$sid = $ldap->getObjectSid($login);
					if ($ldapdebug) print "DEBUG: sid = ".$sid."<br>\n";

					$user=new User($db);
					$resultFetchUser=$user->fetch($login,$sid);
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
				dolibarr_syslog("functions_ldap::check_user_password_ldap Authentification ko bad user/password pour '".$_POST["username"]."'");
				sleep(1);
				$langs->load('main');
				$langs->load('other');
				$_SESSION["dol_loginmesg"]=$langs->trans("ErrorBadLoginPassword");
			}
		}
		else
		{
			dolibarr_syslog("functions_ldap::check_user_password_ldap Authentification ko failed to connect to LDAP pour '".$_POST["username"]."'");
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