<?php
/* Copyright (C) 2013   Alexandre Spangaro  <alexandre.spangaro@gmail.com>
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
 * 	\defgroup   Skype   Module Skype
 *  \brief      Add a skype button.
 *  \file       htdocs/core/modules/modSkype.class.php
 *  \ingroup    Skype
 *  \brief      Description and activation file for module Skype
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a Cron module
 */
class modSkype extends DolibarrModules
{

    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
    	global $langs,$conf;

        $this->db = $db;
        $this->numero = 3100;

		    // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		    // It is used to group modules in module setup page
        $this->family = "crm";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Enable Skype button into contact";
        $this->version = 'experimental';                        // 'experimental' or 'dolibarr' or version
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of image file used for this module.
        $this->picto='skype';

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array();

        // Dependancies
        //-------------
	    $this->hidden = ! empty($conf->global->SKYPE_MODULE_DISABLED);	// A condition to disable module
	    $this->depends = array('modSociete');		// List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();	// List of modules id to disable if this one is disabled
	    $this->conflictwith = array();	// List of modules id this module is in conflict with
        $this->langfiles = array();

        // Constantes
        //-----------


        // New pages on tabs
        // -----------------
        $this->tabs = array();

        // Boxes
        //------
        $this->boxes = array();

    		// Permissions
        //------------
    		$this->rights = array();		// Permission array used by this module
    		$this->rights_class = 'skype';
    		$r=0;
    
    		$this->rights[$r][0] = 3101;
    		$this->rights[$r][1] = 'View skype link';
    		$this->rights[$r][3] = 1;
    		$this->rights[$r][4] = 'view';
    		$r++;

        // Main menu entries
        //------------------
        $this->menu = array();
    }


    /**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function init($options='')
    {
        // Prevent pb of modules not correctly disabled
        //$this->remove($options);

        return $this->_init($sql,$options);
    }

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
