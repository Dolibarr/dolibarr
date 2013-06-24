<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   printipp     Module printipp
 *      \brief      Module pour imprimer via CUPS
 */

/**
 *  \file       htdocs/core/modules/modPrintIPP.class.php
 *  \ingroup    printipp
 *  \brief      Fichier de description et activation du module OSCommerce2
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");



/**
 *  \class      modPrintIPP
 *  \brief      Classe de description et activation du module PrintIPP
 */
class modPrintIPP extends DolibarrModules
{

	
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    function  __construct($db)
    {
        $this->db = $db ;
        $this->numero = 54000;
        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "other";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Print via Cups IPP Printer.";
        $this->version = 'experimental';    // 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 1;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'technic';

        // Data directories to create when module is enabled.
        $this->dirs = array();

        // Config pages
        $this->config_page_url = array("printipp.php@printipp");

        // Dependances
        $this->depends = array();
        $this->requiredby = array();
        $this->phpmin = array(5,1);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,2);  // Minimum version of Dolibarr required by module
        $this->conflictwith = array();
        $this->langfiles = array("printipp");

        // Constantes
        $this->const = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'printipp';

        $r=0;
        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $r++;
        $this->rights[$r][0] = 54001;
        $this->rights[$r][1] = 'Printer';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';

        // Main menu entries
        $this->menus = array();         // List of menus to add
        $r=0;

        // This is to declare the Top Menu entry:
        $this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=modulesadmintools',               // Put 0 if this is a top menu
                                'type'=>'left',              // This is a Top menu entry
                                'titre'=>'Printer',
                                'mainmenu'=>'printer',
                                'url'=>'/printipp/index.php',
                                'langs'=>'printipp',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                'position'=>300,
                                'enabled'=>'$conf->printipp->enabled && $leftmenu==\'modulesadmintools\'',
                                'perms'=>'$user->rights->printipp->read',    // Use 'perms'=>'1' if you want your menu with no permission rules
                                'target'=>'',
                                'user'=>0);                 // 0=Menu for internal users, 1=external users, 2=both

        $r++;


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
        $sql = array();

        return $this->_init($sql, $options);
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

        return $this->_remove($sql, $options);
    }

}
?>
