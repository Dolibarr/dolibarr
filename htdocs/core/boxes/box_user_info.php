<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016 Frederic France      <frederic.france@free.fr>
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
 *	\file       htdocs/core/boxes/box_user_info.php
 *	\ingroup    user
 *	\brief      Module to show box of user info
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show user info
 */
class box_user_info extends ModeleBoxes
{
    var $boxcode="userinfo";
    var $boximg="object_user";
    var $boxlabel="BoxTitleLoginInformation";
    var $depends = array("user");

    var $db;
    var $param;
    var $enabled = 1;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param='')
    {
        global $conf, $user;

        $this->db = $db;

        // disable module for such cases

    }

    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param  int     $max        Maximum number of records to load
     *  @return void
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db, $conf;
        $langs->load("boxes");

        $this->max = $max;


        $this->info_box_head = array('text' => $langs->trans("BoxTitleLoginInformation"));

        $this->info_box_contents[0][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans("User"),
        );

        $this->info_box_contents[0][] = array(
                        'td' => 'align="left"',
                        'text' => $user->getNomUrl(0),
                        'asis' => 1,
                    );

        $this->info_box_contents[1][] = array(
                        'td' => 'align="left"',
                        'text' => $langs->trans("PreviousConnexion"),
        );

        if ($user->datepreviouslogin) $prevlogin = dol_print_date($user->datepreviouslogin,"dayhour",'tzuser');
        else $prevlogin = $langs->trans("Unknown");

        $this->info_box_contents[1][] = array(
                        'td' => 'align="left"',
                        'text' => $prevlogin,
                    );

    }

    /**
     *  Method to show box
     *
     *  @param  array   $head       Array with properties of box title
     *  @param  array   $contents   Array with properties of box lines
     *  @return void
     */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

