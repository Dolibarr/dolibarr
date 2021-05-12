<?php
/* Copyright (C) 2012      Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2005-2017 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2015 Frederic France        <frederic.france@free.fr>
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
 *  \file       htdocs/core/boxes/box_lastlogin.php
 *  \ingroup    core
 *  \brief      Module to show box of bills, orders & propal of the current year
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box of last login
 */
class box_lastlogin extends ModeleBoxes
{
<<<<<<< HEAD
    var $boxcode="lastlogin";
    var $boximg="object_user";
    var $boxlabel='BoxLoginInformation';
    var $depends = array("user");

    var $db;
    var $param;
    var $enabled = 1;

    var $info_box_head = array();
    var $info_box_contents = array();
=======
    public $boxcode="lastlogin";
    public $boximg="object_user";
    public $boxlabel='BoxLoginInformation';
    public $depends = array("user");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $enabled = 1;

    public $info_box_head = array();
    public $info_box_contents = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
<<<<<<< HEAD
    function __construct($db,$param)
=======
    public function __construct($db, $param)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf;

        $this->db=$db;
    }

    /**
     *  Charge les donnees en memoire pour affichage ulterieur
     *
     *  @param  int     $max        Maximum number of records to load
     *  @return void
     */
<<<<<<< HEAD
    function loadBox($max=5)
=======
    public function loadBox($max = 5)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf, $user, $langs, $db;

        $textHead = $langs->trans("BoxLoginInformation");
        $this->info_box_head = array(
            'text' => $textHead,
            'limit'=> dol_strlen($textHead),
        );
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $line=0;
        $this->info_box_contents[$line][0] = array(
            'td' => '',
            'text' => $langs->trans("User"),
        );
        $this->info_box_contents[$line][1] = array(
            'td' => '',
            'text' => $user->getNomUrl(-1),
            'asis' => 1
        );
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $line=1;
        $this->info_box_contents[$line][0] = array(
            'td' => '',
            'text' => $langs->trans("PreviousConnexion"),
        );
<<<<<<< HEAD
        if ($user->datepreviouslogin) $tmp= dol_print_date($user->datepreviouslogin,"dayhour",'tzuser');
=======
        if ($user->datepreviouslogin) $tmp= dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        else $tmp= $langs->trans("Unknown");
        $this->info_box_contents[$line][1] = array(
            'td' => '',
            'text' => $tmp,
        );
<<<<<<< HEAD
        
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


	/**
<<<<<<< HEAD
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
=======
	 *  Method to show box
	 *
	 *  @param	array	$head       Array with properties of box title
	 *  @param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *  @return	void
	 */
    public function showBox($head = null, $contents = null, $nooutput = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
