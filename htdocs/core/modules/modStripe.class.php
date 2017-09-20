<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
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
 * 	\defgroup   stripe     Module stripe
 * 	\brief      Add integration with Stripe online payment system.
 *  \file       htdocs/core/modules/modStripe.class.php
 *  \ingroup    stripe
 *  \brief      Description and activation file for module Stripe
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 	Description and activation class for module Paybox
 */
class modStripe extends DolibarrModules
{
    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 50300;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'stripe';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "other";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module to offer an online payment page by credit card with Stripe";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=other)
        $this->special = 1;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory, use this->picto=DOL_URL_ROOT.'/module/img/file.png'
        $this->picto='stripe';

        // Data directories to create when module is enabled.
        $this->dirs = array();

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array("stripe.php@stripe");

        // Dependencies
        $this->depends = array();		// List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();	// List of modules id to disable if this one is disabled
        $this->phpmin = array(5,3);					// Minimum version of PHP required by module
        $this->need_dolibarr_version = array(5,0);	// Minimum version of Dolibarr required by module
        $this->langfiles = array("stripe");

        // Constants
        $this->const = array();			// List of particular constants to add when module is enabled

        // New pages on tabs
        $this->tabs = array();

        // Boxes
        $this->boxes = array();			// List of boxes
        $r=0;

        // Permissions
        $this->rights = array();		// Permission array used by this module
        $r=0;

        // Main menu entries
        $this->menus = array();			// List of menus to add
        $r=0;

        // Exports
        $r=1;
    }
}

