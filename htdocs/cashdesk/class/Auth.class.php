<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2011 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
 * Class ot manage authentication for pos module (cashdesk)
 */
class Auth
{
	protected $db;

	private $login;
	private $passwd;

	private $reponse;

	public $sqlQuery;

	/**
	 * Enter description here ...
	 *
	 * @param	DoliDB	$db			Database handler
	 * @return	void
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->reponse(null);
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string	$aLogin		Login
	 * @return	void
	 */
	public function login($aLogin)
	{
		$this->login = $aLogin;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string	$aPasswd	Password
	 * @return	void
	 */
	public function passwd($aPasswd)
	{
		$this->passwd = $aPasswd;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string 	$aReponse	Response
	 * @return	void
	 */
	public function reponse($aReponse)
	{
		$this->reponse = $aReponse;
	}

	/**
	 * Validate login/pass
	 *
	 * @param	string	$aLogin		Login
	 * @param	string	$aPasswd	Password
	 * @return	int					0 or 1
	 */
	public function verif($aLogin, $aPasswd)
	{
		global $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_auto_user;

		$ret = -1;

		$login = '';

		$test = true;

        // Authentication mode
        if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication = 'http,dolibarr';
        // Authentication mode: forceuser
        if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user = 'auto';
        // Set authmode
        $authmode = explode(',', $dolibarr_main_authentication);

        // No authentication mode
        if (!count($authmode))
        {
            $langs->load('main');
            dol_print_error('', $langs->trans("ErrorConfigParameterNotDefined", 'dolibarr_main_authentication'));
            exit;
        }

		$usertotest = $aLogin;
		$passwordtotest = $aPasswd;
		$entitytotest = $conf->entity;

        // Validation tests user / password
        // If ok, the variable will be initialized login
        // If error, we will put error message in session under the name dol_loginmesg
        $goontestloop = false;
        if (isset($_SERVER["REMOTE_USER"]) && in_array('http', $authmode)) $goontestloop = true;
        if (isset($aLogin) || GETPOST('openid_mode', 'alpha', 1)) $goontestloop = true;

        if ($test && $goontestloop)
        {
            include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$login = checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode);
            if ($login)
            {
                $this->login($aLogin);
                $this->passwd($aPasswd);
                $ret = 0;
            }
            else
            {
                $ret = -1;
            }
        }

		return $ret;
	}
}
