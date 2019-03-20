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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
    global $_POST,$db,$conf,$langs;

    dol_syslog("functions_openid::check_user_password_openid usertotest=".$usertotest);

    $login='';

    // Get identity from user and redirect browser to OpenID Server
    if (isset($_POST['username']))
    {
        $openid = new SimpleOpenID();
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
        return false;
    }
    // Perform HTTP Request to OpenID server to validate key
    elseif($_GET['openid_mode'] == 'id_res')
    {
        $openid = new SimpleOpenID();
        $openid->SetIdentity($_GET['openid_identity']);
        $openid_validation_result = $openid->ValidateWithServer();
        if ($openid_validation_result === true)
        {
            // OK HERE KEY IS VALID

            $sql ="SELECT login";
            $sql.=" FROM ".MAIN_DB_PREFIX."user";
            $sql.=" WHERE openid = '".$db->escape($_GET['openid_identity'])."'";
            $sql.=" AND entity IN (0," . ($_SESSION["dol_entity"] ? $_SESSION["dol_entity"] : 1) . ")";

            dol_syslog("functions_openid::check_user_password_openid", LOG_DEBUG);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj=$db->fetch_object($resql);
                if ($obj)
                {
                    $login=$obj->login;
                }
            }
        }
        elseif($openid->IsError() === true)
        {
            // ON THE WAY, WE GOT SOME ERROR
            $error = $openid->GetError();
            return false;
        }
        else
        {
            // Signature Verification Failed
            //echo "INVALID AUTHORIZATION";
            return false;
        }
    }
    elseif ($_GET['openid_mode'] == 'cancel')
    {
        // User Canceled your Request
        //echo "USER CANCELED REQUEST";
        return false;
    }

    return $login;
}
