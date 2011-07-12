<?php
/* Copyright (C) 20011 Herve Prot <herve.prot@symeos.com>
 * 
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
 *       \defgroup   member     Module Map
 *       \brief      Module to create map
 */

/**
 *		\file       htdocs/includes/modules/modMap.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Map
 *		\version	$Id: modMap.class.php,v 1.76 2010/10/01 23:37:37 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *       \class      modAdherent
 *       \brief      Classe de description et activation du module Adherent
 */
class modMap extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      Database handler
     */
    function modMap($DB)
    {
        $this->db = $DB;
        $this->numero = 450 ;

        $this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Carthographie";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='globe';

        // Data directories to create when module is enabled
        $this->dirs = array("/map/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("map.php@map");

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("map","companies");

        // Constantes
        //-----------
        $this->const = array();
        

        // Boites
        //-------
        $this->boxes = array();


        // Menu
        //------------
	// None

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'map';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $this->rights[$r][0] = 451;
        $this->rights[$r][1] = 'See map';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';

    }


    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {

        $sql = array();

        $result=$this->load_tables();
        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
		$sql = array();

		return $this->_remove($sql);
    }
    
    /**
	*		\brief		Create tables and keys required by module
	* 					Files mymodule.sql and mymodule.key.sql with create table and create keys
	* 					commands must be stored in directory /mymodule/sql/
	*					This function is called by this->init.
	* 		\return		int		<=0 if KO, >0 if OK
	*/
	function load_tables()
	{
		return $this->_load_tables('/map/sql/');
	}

}
?>
