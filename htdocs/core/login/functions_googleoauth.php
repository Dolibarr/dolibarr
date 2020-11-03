<?php
/* Copyright (C) 2007-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 */

/**
 *      \file       htdocs/core/login/functions_googleoauth.php
 *      \ingroup    core
 *      \brief      Authentication functions for Google OAuth mode using "Server flow"
 *                  Another method could be to use the "Implicit flow" using Google-Signin library.
 */





//include_once DOL_DOCUMENT_ROOT.'/core/class/openid.class.php';


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_googleoauth($usertotest, $passwordtotest, $entitytotest)
{
	global $_POST, $db, $conf, $langs;

	dol_syslog("functions_googleoauth::check_user_password_googleoauth usertotest=".$usertotest);

	$login = '';

	// Get identity from user and redirect browser to Google OAuth Server
	if (GETPOSTISSET('username'))
	{
		/*$openid = new SimpleOpenID();
        $openid->SetIdentity($_POST['username']);
        $protocol = ($conf->file->main_force_https ? 'https://' : 'http://');
        $openid->SetTrustRoot($protocol . $_SERVER["HTTP_HOST"]);
        $openid->SetRequiredFields(array('email','fullname'));
        $_SESSION['dol_entity'] = $_POST["entity"];
        //$openid->SetOptionalFields(array('dob','gender','postcode','country','language','timezone'));
        if ($openid->sendDiscoveryRequestToGetXRDS())
        {
            $openid->SetApprovedURL($protocol . $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]);      // Send Response from OpenID server to this script
            $openid->Redirect();     // This will redirect user to OpenID Server
        }
        else
        {
            $error = $openid->GetError();
            return false;
        }
        return false;*/
	}


	return $login;
}
