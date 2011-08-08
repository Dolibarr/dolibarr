<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2011 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Class ot manage authentication for pos module (cashdesk)
 */
class Auth {

	var $db;

	var $login;
	var $passwd;

	var $reponse;

	var $sqlQuery;


	function Auth ($DB) {

		$this->db = $DB;
		$this->reponse (null);

	}

	function login ($aLogin) {

		$this->login = $aLogin;

	}

	function passwd ($aPasswd) {

		$this->passwd = $aPasswd;


	}

	function reponse ($aReponse) {

		$this->reponse = $aReponse;

	}

	function verif ($aLogin, $aPasswd)
	{
		global $conf,$dolibarr_main_authentication,$langs;

		$ret=-1;

		$login='';

        // Authentication mode
        if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
        // Authentication mode: forceuser
        if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';

        // Set authmode
        $authmode=explode(',',$dolibarr_main_authentication);

        // No authentication mode
        if (! sizeof($authmode) && empty($conf->login_method_modules))
        {
            $langs->load('main');
            dol_print_error('',$langs->trans("ErrorConfigParameterNotDefined",'dolibarr_main_authentication'));
            exit;
        }


        $test=true;

        // Validation of third party module login method
        if (is_array($conf->login_method_modules) && !empty($conf->login_method_modules))
        {
            include_once(DOL_DOCUMENT_ROOT . "/lib/security.lib.php");
            $login = getLoginMethod();
            if ($login) $test=false;
        }

        // Validation tests user / password
        // If ok, the variable will be initialized login
        // If error, we will put error message in session under the name dol_loginmesg
        $goontestloop=false;
        if (isset($_SERVER["REMOTE_USER"]) && in_array('http',$authmode)) $goontestloop=true;
        if (isset($aLogin) || GETPOST('openid_mode','alpha',1)) $goontestloop=true;

        if ($test && $goontestloop)
        {
            foreach($authmode as $mode)
            {
                if ($test && $mode && ! $login)
                {
                    $authfile=DOL_DOCUMENT_ROOT.'/includes/login/functions_'.$mode.'.php';
                    $result=include_once($authfile);
                    if ($result)
                    {
                        $this->login ($aLogin);
                        $this->passwd ($aPasswd);
                        $entitytotest=$conf->entity;

                        $function='check_user_password_'.$mode;
                        $login=$function($aLogin,$aPasswd,$entitytotest);
                        if ($login) // Login is successfull
                        {
                            $test=false;
                            $dol_authmode=$mode;    // This properties is defined only when logged to say what mode was successfully used
                            $ret=0;
                        }
                    }
                    else
                    {
                        dol_syslog("Authentification ko - failed to load file '".$authfile."'",LOG_ERR);
                        sleep(1);
                        $ret=-1;
                    }
                }
            }
        }

		return $ret;
	}

}

?>
