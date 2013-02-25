<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 *       \file       htdocs/core/login/functions_ldap.php
 *       \ingroup    core
 *       \brief      Authentication functions for LDAP
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
function check_user_password_ldap($usertotest,$passwordtotest,$entitytotest)
{
	global $_POST,$db,$conf,$langs;
	global $dolibarr_main_auth_ldap_host,$dolibarr_main_auth_ldap_port;
	global $dolibarr_main_auth_ldap_version,$dolibarr_main_auth_ldap_servertype;
	global $dolibarr_main_auth_ldap_login_attribute,$dolibarr_main_auth_ldap_dn;
	global $dolibarr_main_auth_ldap_admin_login,$dolibarr_main_auth_ldap_admin_pass;
	global $dolibarr_main_auth_ldap_filter;
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

		require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
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
			dol_syslog("functions_ldap::check_user_password_ldap uid/samacountname=".$ldapuserattr.", dn=".$ldapdn.", Admin:".$ldap->searchUser.", Pass:".$ldap->searchPassword);
			print "DEBUG: Server:".join(',',$ldap->server).", Port:".$ldap->serverPort.", Protocol:".$ldap->ldapProtocolVersion.", Type:".$ldap->serverType."<br>\n";
			print "DEBUG: uid/samacountname=".$ldapuserattr.", dn=".$ldapdn.", Admin:".$ldap->searchUser.", Pass:".$ldap->searchPassword."<br>\n";
		}

		$resultFetchLdapUser=0;

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
			if ($result > 0)
			{
				$resultFetchLdapUser = $ldap->fetch($usertotest,$userSearchFilter);
				//dol_syslog('functions_ldap::check_user_password_ldap resultFetchLdapUser='.$resultFetchLdapUser);
				if ($resultFetchLdapUser > 0 && $ldap->pwdlastset == 0) // If ok but password need to be reset
				{
					dol_syslog('functions_ldap::check_user_password_ldap '.$usertotest.' must change password next logon');
					if ($ldapdebug) print "DEBUG: User ".$usertotest." must change password<br>\n";
					$ldap->close();
					sleep(1);
					$langs->load('ldap');
					$_SESSION["dol_loginmesg"]=$langs->trans("YouMustChangePassNextLogon",$usertotest,$ldap->domainFQDN);
					return '';
				}
			}
			else
			{
			     if ($ldapdebug) print "DEBUG: ".$ldap->error."<br>\n";
			}
			$ldap->close();
		}

		// Forge LDAP user and password to test with them
		// If LDAP need a dn with login like "uid=jbloggs,ou=People,dc=foo,dc=com", default dn may work even if previous code with
		// admin login no exectued.
        $ldap->searchUser=$ldapuserattr."=".$usertotest.",".$ldapdn;  // Default dn (will work if LDAP accept a dn with login value inside)
		// But if LDAP need a dn with name like "cn=Jhon Bloggs,ou=People,dc=foo,dc=com", previous part must have been executed to have
		// dn detected into ldapUserDN.
		if ($resultFetchLdapUser) $ldap->searchUser = $ldap->ldapUserDN;
        $ldap->searchPassword=$passwordtotest;

		// Test with this->seachUser and this->searchPassword
        //print $resultFetchLdapUser."-".$ldap->ldapUserDN."-".$ldap->searchUser.'-'.$ldap->searchPassword;exit;
        $result=$ldap->connect_bind();
		if ($result > 0)
		{
			if ($result == 2)
			{
				dol_syslog("functions_ldap::check_user_password_ldap Authentification ok");
				$login=$usertotest;

				// ldap2dolibarr synchronisation
				if ($login && ! empty($conf->ldap->enabled) && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
				{
					dol_syslog("functions_ldap::check_user_password_ldap Sync ldap2dolibarr");

					// On charge les attributs du user ldap
					if ($ldapdebug) print "DEBUG: login ldap = ".$login."<br>\n";
					$resultFetchLdapUser = $ldap->fetch($login,$userSearchFilter);

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
						dol_syslog("functions_ldap::check_user_password_ldap Sync user found id=".$user->id);
						// On verifie si le login a change et on met a jour les attributs dolibarr
						if ($user->login != $ldap->login && $ldap->login)
						{
							$user->login = $ldap->login;
							$user->update($user);
							// TODO Que faire si update echoue car on update avec un login deja existant.
						}
						
						//$resultUpdate = $user->update_ldap2dolibarr($ldap);
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
            /* Login failed. Return false, together with the error code and text from
             ** the LDAP server. The common error codes and reasons are listed below :
             ** (for iPlanet, other servers may differ)
             ** 19 - Account locked out (too many invalid login attempts)
             ** 32 - User does not exist
             ** 49 - Wrong password
             ** 53 - Account inactive (manually locked out by administrator)
             */
            dol_syslog("functions_ldap::check_user_password_ldap Authentification ko failed to connect to LDAP for '".$usertotest."'");
            if (is_resource($ldap->connection))    // If connection ok but bind ko
		    {
                $ldap->ldapErrorCode = ldap_errno($ldap->connection);
                $ldap->ldapErrorText = ldap_error($ldap->connection);
                dol_syslog("functions_ldap::check_user_password_ldap ".$ldap->ldapErrorText);
		    }
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