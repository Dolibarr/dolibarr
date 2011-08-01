<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \defgroup   webservices     Module webservices
 *      \brief      Module to enable the Dolibarr server of web services
 *		\brief		$Id: modWebServices.class.php,v 1.7 2011/07/31 23:28:10 eldy Exp $
*/

/**
 *       \file       htdocs/includes/modules/modWebServices.class.php
 *       \ingroup    webservices
 *       \brief      File to describe webservices module
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *       \class      modWebServices
 *       \brief      Class to describe a WebServices module
 */

class modWebServices extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modWebServices($DB)
    {
        $this->db = $DB;
        $this->numero = 2600;

        $this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Enable the Dolibarr web services server";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 1;
		// Name of image file used for this module.
		$this->picto='technic';

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array("webservices.php@webservices");

        // Dependancies
        //-------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("other");

        // Constantes
        //-----------
        $this->const = array();

		// New pages on tabs
		// -----------------
        $this->tabs = array();

		// Boxes
        //------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'webservices';
        $r=0;
    }


    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
	 *	\param		options		Options when enabling module
     */
    function init($options='')
    {
		// Prevent pb of modules not correctly disabled
		//$this->remove($options);

		$sql = array();

        return $this->_init($sql,$options);
    }

    /**
     *	\brief      Fonction appelee lors de la desactivation d'un module.
     *              Supprime de la base les constantes, boites et permissions du module.
	 *	\param		options		Options when disabling module
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
