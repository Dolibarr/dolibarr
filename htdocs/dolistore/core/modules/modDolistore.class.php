<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
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
 * 
 *
 * 	Classe de description et activation du module DOLISTORE
 */


include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modDolistore extends DolibarrModules
{

    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        global $conf, $langs;

        $this->db     = $db;
        $this->numero = 66666;


        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = /* $langs->trans( */preg_replace('/^mod/i', '', get_class($this))/* ) */;

        $this->boxes = array();


        $this->description = $langs->trans("DOLISTOREdescription");

		$this->descriptionlong = $langs->trans("DOLISTOREdescriptionLong");

        $this->family = "interface";

        $this->module_position = 1;
        $this->version         = 'dolibarr';
        $this->picto           = 'dolistore@dolistore';
        $this->special=1;
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Dependances
        $this->depends    = array();
        $this->requiredby = array();
        $this->langfiles  = array('dolistore@dolistore');

        // Config pages
        //$this->config_page_url = array("index.php?page=config@dolistore");



        $this->module_parts = array(
            'triggers' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 0,
        );





        // Main menu entries
        $r = 0;

        $this->menu[$r] = array(
            //'fk_menu' => -1, // Put 0 if this is a top menu
            'fk_menu' => 'fk_mainmenu=home,fk_leftmenu=setup',
            'type' => 'left', // This is a left menu entry
            'titre' => 'DOLISTOREMENU',
            'mainmenu' => 'home',
            'leftmenu' => 'setup',
            'url' => '/dolistore/index.php?mainmenu=home&leftmenu=setup',
            'langs' => 'dolistore@dolistore', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position' => 1,
            'enabled' => '$leftmenu=="setup" && $conf->dolistore->enabled', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            //'perms' => '$user->rights->edi->message->view', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'perms' => '',
            'target' => '',
            'user' => 0);                    // 0=Menu for internal users, 1=external users, 2=both

        $r++;

        // Constantes
        $this->const = array();


        $r = 0;
        $this->const[$r][0] = 'MAIN_MODULE_'.strtoupper($this->name).'_API_SRV';
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "https://www.dolistore.com";
        $this->const[$r][3] = "Server URL";
        $this->const[$r][4] = 0;
        $this->const[$r][5] = 1; // supprime la constante Ã  la dÃ©sactivation du module
        $r++;

        $this->const[$r][0] = 'MAIN_MODULE_'.strtoupper($this->name).'_API_KEY';
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "dolistorecatalogpublickey1234567";
        $this->const[$r][3] = "API key to authenticate";
        $this->const[$r][4] = 0;
        $this->const[$r][5] = 1; // supprime la constante Ã  la dÃ©sactivation du module
        $r++;

    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function init($options = '')
    {
        global $conf;

        $this->remove($options);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * 		Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     * 		Data directories are not deleted
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}