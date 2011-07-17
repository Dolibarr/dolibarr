<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *       \defgroup   member     Module foundation
 *       \brief      Module to manage members of a foundation
 */

/**
 *		\file       htdocs/includes/modules/modAdherent.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Member
 *		\version	$Id: modHr.class.php,v 1.76 2010/10/01 23:37:37 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *       \class      modAdherent
 *       \brief      Classe de description et activation du module Adherent
 */
class modHr extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      Database handler
     */
    function modHr($DB)
    {
        $this->db = $DB;
        $this->numero = 430 ;

        $this->family = "hr";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Gestion prévisionnelle des emplois et des compétences";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/hr/temp");

        // Config pages
        //-------------
        //$this->config_page_url = array("hr.php@hr");

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("members","companies");

        // Boites
        //-------
        $this->boxes = array();
        
        // Constants
	$this->const = array();


        // Menu
        //------------
	// voir menu dans le module lead (Problème avec l'ajout de menu dans les menus de gauche : attente de correction de bug pour remodifier

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'hr';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $this->rights[$r][0] = 431;
        $this->rights[$r][1] = 'Read user objectif mensual';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';

        $r++;
        $this->rights[$r][0] = 432;
        $this->rights[$r][1] = 'Add objectif mensual ';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'add';

        $r++;
        $this->rights[$r][0] = 433;
        $this->rights[$r][1] = 'Delete objectif mensual ';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'modify';

        $r++;
        $this->rights[$r][0] = 434;
        $this->rights[$r][1] = 'Modify All objectifs mensual';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'all';

        $r++;
        $this->rights[$r][0] = 435;
        $this->rights[$r][1] = 'Read objectifs annual';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'annual';
        $this->rights[$r][5] = 'read';

        $r++;
        $this->rights[$r][0] = 436;
        $this->rights[$r][1] = 'Add objectifs annual';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'annual';
        $this->rights[$r][5] = 'add';

        $r++;
        $this->rights[$r][0] = 437;
        $this->rights[$r][1] = 'Remove objectifs annual';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'annual';
        $this->rights[$r][5] = 'modify';

        $r++;
        $this->rights[$r][0] = 438;
        $this->rights[$r][1] = 'Modify objectifs annual';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'annual';
        $this->rights[$r][5] = 'all';


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
		return $this->_load_tables('/hr/sql/');
	}

}
?>
