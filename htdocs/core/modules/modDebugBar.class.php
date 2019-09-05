<?php
/* Copyright (C) 2019-2020 AXeL-dev <contact.axel.dev@gmail.com>
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
 * 	\defgroup   debugbar     Module Debug bar
 *  \brief      debugbar module descriptor.
 *
 *  \file       htdocs/core/modules/modDebugBar.class.php
 *  \ingroup    debugbar
 *  \brief      Description and activation file for module debugbar
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module
 */
class modDebugBar extends DolibarrModules
{

    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->numero = 43;

        $this->rights_class = 'debugbar';

        $this->family = "base";
        $this->module_position = '75';

        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "A tool for developper adding a debug bar in your browser.";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto='technic';

        $this->module_parts = array('moduleforexternal' => 0);

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Dependencies
        $this->depends = array();        // May be used for product or service or third party module
        $this->requiredby = array();

        // Config pages
        $this->config_page_url = array("debugbar.php");

        // Constants
        // Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
        //							  1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
        $this->const = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();

        $this->rights[1][0] = 430; // id de la permission
        $this->rights[1][1] = 'Use Debug Bar'; // libelle de la permission
        $this->rights[1][2] = 'u'; // type de la permission (deprecie a ce jour)
        $this->rights[1][3] = 1; // La permission est-elle une permission par defaut
        $this->rights[1][4] = 'read';
    }


    /**
     *      Function called when module is enabled.
     *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *      It also creates data directories.
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
     *      @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        // Permissions
        $this->remove($options);

        $sql = array(
        );

        return $this->_init($sql, $options);
    }
}
