<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/ws.lib.php
 *  \ingroup	webservices
 *  \brief		Set of function for manipulating web services
 */


/**
 *  Check authentication array and set error, errorcode, errorlabel
 *
 *  @param	array	$authentication     Array with authentication informations ('login'=>,'password'=>,'entity'=>,'dolibarrkey'=>)
 *  @param 	int		&$error				Number of errors
 *  @param  string	&$errorcode			Error string code
 *  @param  string	&$errorlabel		Error string label
 *  @return User						Return user object identified by login/pass/entity into authentication array
 */
function check_authentication($authentication,&$error,&$errorcode,&$errorlabel)
{
    global $db,$conf,$langs;
    global $dolibarr_main_authentication,$dolibarr_auto_user;

    $fuser=new User($db);

    if (! $error && ($authentication['dolibarrkey'] != $conf->global->WEBSERVICES_KEY))
    {
        $error++;
        $errorcode='BAD_VALUE_FOR_SECURITY_KEY'; $errorlabel='Value provided into dolibarrkey entry field does not match security key defined in Webservice module setup';
    }

    if (! $error && ! empty($authentication['entity']) && ! is_numeric($authentication['entity']))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="The entity parameter must be empty (or filled with numeric id of instance if multicompany module is used).";
    }

    if (! $error)
    {
        $result=$fuser->fetch('',$authentication['login'],'',0);
        if ($result < 0)
        {
            $error++;
            $errorcode='ERROR_FETCH_USER'; $errorlabel='A technical error occurred during fetch of user';
        }
        else if ($result == 0)
        {
            $error++;
            $errorcode='BAD_CREDENTIALS'; $errorlabel='Bad value for login or password';
        }

		if (! $error && $fuser->statut == 0)
		{
			$error++;
			$errorcode='ERROR_USER_DISABLED'; $errorlabel='This user has been locked or disabled';
		}

    	// Validation of login
		if (! $error)
		{
			$fuser->getrights();	// Load permission of user

        	// Authentication mode
        	if (empty($dolibarr_main_authentication)) $dolibarr_main_authentication='http,dolibarr';
        	// Authentication mode: forceuser
        	if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) $dolibarr_auto_user='auto';
        	// Set authmode
        	$authmode=explode(',',$dolibarr_main_authentication);

            include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        	$login = checkLoginPassEntity($authentication['login'],$authentication['password'],$authentication['entity'],$authmode);
			if (empty($login))
			{
			    $error++;
                $errorcode='BAD_CREDENTIALS'; $errorlabel='Bad value for login or password';
			}
		}
    }

    return $fuser;
}

?>
