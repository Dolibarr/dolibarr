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
 *      \file       htdocs/core/login/functions_openid.php
 *      \ingroup    core
 *      \brief      Authentication functions for OpenId mode
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/openid.class.php';


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_openid($usertotest, $passwordtotest, $entitytotest)
{
	global $db, $conf, $langs;

	dol_syslog("functions_openid::check_user_password_openid usertotest=".$usertotest);

	$login = '';

	// Get identity from user and redirect browser to OpenID Server
	if (GETPOSISSET('username'))
	{
		$openid = new SimpleOpenID();
		$openid->SetIdentity($_POST['username']);
		$protocol = ($conf->file->main_force_https ? 'https://' : 'http://');
		$openid->SetTrustRoot($protocol.$_SERVER["HTTP_HOST"]);
		$openid->SetRequiredFields(array('email', 'fullname'));
		$_SESSION['dol_entity'] = $_POST["entity"];
		//$openid->SetOptionalFields(array('dob','gender','postcode','country','language','timezone'));
		if ($openid->sendDiscoveryRequestToGetXRDS())
		{
			$openid->SetApprovedURL($protocol.$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]); // Send Response from OpenID server to this script
			$openid->Redirect(); // This will redirect user to OpenID Server
		} else {
			$_SESSION["dol_loginmesg"] = $openid->GetError();
			return false;
		}
		return false;
	}
	// Perform HTTP Request to OpenID server to validate key
	elseif ($_GET['openid_mode'] == 'id_res')
	{
		$openid = new SimpleOpenID();
		$openid->SetIdentity($_GET['openid_identity']);
		$openid_validation_result = $openid->ValidateWithServer();
		if ($openid_validation_result === true)
		{
			// OK HERE KEY IS VALID

			$sql = "SELECT login, entity, datestartvalidity, dateendvalidity";
			$sql .= " FROM ".MAIN_DB_PREFIX."user";
			$sql .= " WHERE openid = '".$db->escape($_GET['openid_identity'])."'";
			$sql .= " AND entity IN (0,".($_SESSION["dol_entity"] ? $_SESSION["dol_entity"] : 1).")";

			dol_syslog("functions_openid::check_user_password_openid", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$now = dol_now();
					if ($obj->datestartvalidity && $db->jdate($obj->datestartvalidity) > $now) {
						// Load translation files required by the page
						$langs->loadLangs(array('main', 'errors'));
						$_SESSION["dol_loginmesg"] = $langs->trans("ErrorLoginDateValidity");
						return '--bad-login-validity--';
					}
					if ($obj->dateendvalidity && $db->jdate($obj->dateendvalidity) < dol_get_first_hour($now)) {
						// Load translation files required by the page
						$langs->loadLangs(array('main', 'errors'));
						$_SESSION["dol_loginmesg"] = $langs->trans("ErrorLoginDateValidity");
						return '--bad-login-validity--';
					}

					$login = $obj->login;
				}
			}
		} elseif ($openid->IsError() === true)
		{
			// ON THE WAY, WE GOT SOME ERROR
			$_SESSION["dol_loginmesg"] = $openid->GetError();
			return false;
		} else {
			// Signature Verification Failed
			//echo "INVALID AUTHORIZATION";
			return false;
		}
	} elseif ($_GET['openid_mode'] == 'cancel')
	{
		// User Canceled your Request
		//echo "USER CANCELED REQUEST";
		return false;
	}

	return $login;
}
