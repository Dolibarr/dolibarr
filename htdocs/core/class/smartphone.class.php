<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 */

/**
 *  \file       htdocs/core/class/smartphone.class.php
 *  \ingroup    core
 *  \brief      Fichier de la classe de gestion des smartphones
 *  \version    $Id$
 */


/**
 *  \class      Smartphone
 *	\brief      Class to manage Smartphones
 */
class Smartphone {

    var $db;

    var $phone;
    var $theme;
    var $title;
    var $template_dir;

    /**
     *  Constructor for class
     *  @param	DB		Handler acces base de donnees
     */
    function Smartphone($DB,$phone)
    {
      	$this->db = $DB;

        $dirt='others';     // default

        if (preg_match('/android|blackberry|iphone|maemo/i',$phone))    // iWebKit template
        {
            $this->theme = 'default';
            $dirt='smartphone';
        }
        elseif (file_exists(DOL_DOCUMENT_ROOT."/theme/phones/".$phone)) // Special template
        {
            $this->theme = 'default';
            $dirt=$phone;
        }

        $this->phone=$phone;
        $this->template_dir=DOL_DOCUMENT_ROOT.'/theme/phones/'.$dirt.'/tpl/';
    }

	/**
     *  Show menu
     */
    function smartmenu()
    {
    	global $conf, $langs;

    	if (! $conf->smart_menu)  $conf->smart_menu ='smartphone_backoffice.php';
    	$smart_menu=$conf->smart_menu;
    	if (GETPOST('top_menu')) $smart_menu=GETPOST('top_menu');

    	// Load the smartphone menu manager
    	$result=@include_once(DOL_DOCUMENT_ROOT ."/includes/menus/smartphone/".$smart_menu);
    	if (! $result)	// If failed to include, we try with standard
    	{
    		$conf->smart_menu='smartphone_backoffice.php';
    		include_once(DOL_DOCUMENT_ROOT ."/includes/menus/smartphone/".$smart_menu);
    	}
    	$menusmart = new MenuSmart($this->db);
    	$menusmart->atarget=$target;

    	include_once($this->template_dir.'menu.tpl.php');
    }

}
