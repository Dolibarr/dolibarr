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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/lib/ws.lib.php
 *  \brief		Set of function for manipulating web services
 * 	\version	$Id$
 */


/**
 *  Check authentication array and set error, errorcode, errorlabel
 *  @param      authentication      Array
 *  @param      error
 *  @param      errorcode
 *  @param      errorlabel
 */
function check_authentication($authentication,&$error,&$errorcode,&$errorlabel)
{
    global $db,$conf,$langs;

    $fuser=new User($db);

    if (! $error && ($authentication['dolibarrkey'] != $conf->global->WEBSERVICES_KEY))
    {
        $error++;
        $errorcode='BAD_VALUE_FOR_SECURITY_KEY'; $errorlabel='Value provided into dolibarrkey entry field does not match security key defined in Webservice module setup';
    }
    if (! $error)
    {
        $result=$fuser->fetch('',$authentication['login'],'',0);
        if ($result <= 0) $error++;

        // TODO Check password

        if ($error)
        {
            $errorcode='BAD_CREDENTIALS'; $errorlabel='Bad value for login or password';
        }
    }
    if (! $error && ! empty($authentication['entity']) && ! is_numeric($authentication['entity']))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter entity must be empty (or a numeric with id of instance if multicompany module is used).";
    }

    return $fuser;
}

?>
