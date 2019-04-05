<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_changeselectedfields.inc.php
 *  \brief			Code for actions when we change list of fields on a list page
 */


// $action must be defined
// $db must be defined
// $conf must be defined
// $object must be defined (object is loaded in this file with fetch)

// Save selection
if (GETPOST('formfilteraction', 'none') == 'listafterchangingselectedfields')
{
    $tabparam=array();

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;

    if (GETPOST("selectedfields")) $tabparam["MAIN_SELECTEDFIELDS_".$varpage]=GETPOST("selectedfields");
    else $tabparam["MAIN_SELECTEDFIELDS_".$varpage]='';

    include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

    $result=dol_set_user_param($db, $conf, $user, $tabparam);

    //$action='list';
    //var_dump($tabparam);exit;
}
