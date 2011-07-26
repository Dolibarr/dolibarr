<?php
/* Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
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
 *       \defgroup   member     Module team
 *       \brief      Module to manage members of a company
 */

/**
 *		\file       htdocs/includes/modules/modTeam.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Member
 *		\version	$Id: modTeam.class.php,v 1.76 2010/10/01 23:37:37 Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *       \class      modAdherent
 *       \brief      Classe de description et activation du module Adherent
 */
class modTeam extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      Database handler
     */
    function modTeam($DB)
    {
        $this->db = $DB;
        $this->numero = 430 ;

        $this->family = "hr";
	// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
	$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Gestion d'équipes et suivi d'objectifs";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='user';

        // Data directories to create when module is enabled
        $this->dirs = array("/team/temp");

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
        // Menu
		$this->menu = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu' => 0,			// Put 0 if this is a top menu
			'type'=>'top',			// This is a Top menu entry
			'titre'=>'Team',
			'mainmenu'=>'team',
			'leftmenu'=>'1',
			'url'=>'/team/index.php',
			'langs'=>'comm',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>5,
			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->team->read',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=0',
			'type'=>'left',	
			'titre'=>'Leads',
			'mainmenu'=>'team',
			'url'=>'/lead/index.php?mode=mine',
			'langs'=>'lead@lead',
			'position'=>1,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->lire',
			'target'=>'',
			'user'=>0);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'r=1',
			'type'=>'left',	
			'titre'=>'AddLead',
			'mainmenu'=>'team',
			'url'=>'/lead/fiche.php?action=create',
			'langs'=>'lead@lead',
			'position'=>2,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->creer',
			'target'=>'',
			'user'=>0);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'r=1',
			'type'=>'left',	
			'titre'=>'List',
			'mainmenu'=>'team',
			'url'=>'/lead/liste.php',
			'langs'=>'lead@lead',
			'position'=>5,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->all',
			'target'=>'',
			'user'=>0);
		$r++;


        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'team';
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
		return $this->_load_tables('/team/sql/');
	}

}
?>
