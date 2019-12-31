<?php
/* Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Frederic France      <frederic.france@free.fr>
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

/** \defgroup   printing     Module printing
 *  \brief      Module for activation of printing icon to make direct printing
 */

/**
 *  \file       htdocs/core/modules/modPrinting.class.php
 *  \ingroup    printing
 *  \brief      File of class to describe and activate module Direct Printing
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';



/**
 *  Class to describe and activate module Direct Printing
 */
class modPrinting extends DolibarrModules
{

    /**
     *  Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db ;
        $this->numero = 64000;
        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "interface";
        $this->module_position = '52';
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Enable Direct Printing System.";
        $this->version = 'dolibarr';    // 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'printer';

        // Data directories to create when module is enabled.
        $this->dirs = array();

        // Config pages
        $this->config_page_url = array("printing.php@printing");

        // Dependencies
        $this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,7,-2);   // Minimum version of Dolibarr required by module
        $this->conflictwith = array();
        $this->langfiles = array("printing");

        // Constants
        $this->const = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'printing';

        $r=0;
        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $r++;
        $this->rights[$r][0] = 64001;
        $this->rights[$r][1] = 'DirectPrint';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';

        // Main menu entries
        $this->menus = array();         // List of menus to add
        $r=0;

        // This is to declare the Top Menu entry:
        $this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',               // Put 0 if this is a top menu
                                'type'=>'left',                 // This is a Top menu entry
                                'titre'=>'MenuDirectPrinting',
                                'url'=>'/printing/index.php?mainmenu=home&leftmenu=admintools',
                                'langs'=>'printing',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>300,
                                'enabled'=>'$conf->printing->enabled && preg_match(\'/^(admintools|all)/\', $leftmenu)',
                                'perms'=>'$user->rights->printing->read',    // Use 'perms'=>'1' if you want your menu with no permission rules
                                'target'=>'',
                                'user'=>0);                     // 0=Menu for internal users, 1=external users, 2=both

        $r++;
    }
}
