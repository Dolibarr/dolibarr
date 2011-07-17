<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *  \defgroup   lead     Module leads
 *	\brief      Module to create lead/tasks. Leads can them be affected to tasks.
 * 	\version	$Id: modLeads.class.php,v 0.1 2011/03/12 13:27:26 eldy Exp $
 */

/**
 *  \file       htdocs/includes/modules/modLead.class.php
 *	\ingroup    lead
 *	\brief      Fichier de description et activation du module Lead
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");
if (!defined('DOL_CLASS_PATH')) define('DOL_CLASS_PATH', null);

/**
 *	\class      modLeads
 *	\brief      Classe de description et activation du module Leads
 */
class modLead extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modLead($DB)
	{
		$this->db = $DB ;
		$this->numero = 420 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des affaires";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->config_page_url = array("lead.php@lead");
		$this->picto='lead';

		// Data directories to create when module is enabled
		$this->dirs = array("/lead/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();

		$r=0;

		$this->const[$r][0] = "LEAD_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "potentiel";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des leads en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LEAD_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_lead_simple";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des leads';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Menu
		$this->menu = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(	'fk_menu' => 0,			// Put 0 if this is a top menu
			'type'=>'top',			// This is a Top menu entry
			'titre'=>'Commercial',
			'mainmenu'=>'commercial',
			'leftmenu'=>'1',
			'url'=>'/comm/index.php',
			'langs'=>'comm',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>5,
			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->lead->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=0',
			'type'=>'left',	
			'titre'=>'Leads',
			'mainmenu'=>'commercial',
			'url'=>'/lead/index.php?mode=mine',
			'langs'=>'lead',
			'position'=>1,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->lire',
			'target'=>'',
			'user'=>0);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'r=1',
			'type'=>'left',	
			'titre'=>'AddLead',
			'mainmenu'=>'commercial',
			'url'=>'/lead/fiche.php?action=create',
			'langs'=>'lead',
			'position'=>2,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->creer',
			'target'=>'',
			'user'=>0);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'r=1',
			'type'=>'left',	
			'titre'=>'List',
			'mainmenu'=>'commercial',
			'url'=>'/lead/liste.php',
			'langs'=>'lead',
			'position'=>5,
			'enabled'=>'1',
			'perms'=>'$user->rights->lead->all',
			'target'=>'',
			'user'=>0);
		$r++;


		// Permissions
		$this->rights = array();
		$this->rights_class = 'lead';
		$r=0;

		$this->rights[$r][0] = 421; // id de la permission
		$this->rights[$r][1] = "Lire les affaires et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 422; // id de la permission
		$this->rights[$r][1] = "Creer/modifier les affaires et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 423; // id de la permission
		$this->rights[$r][1] = "Supprimer les affaires et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 424; // id de la permission
		$this->rights[$r][1] = "Lire tous les affaires et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 425; // id de la permission
		$this->rights[$r][1] = "Creer/modifier tous les affaires et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 426; // id de la permission
		$this->rights[$r][1] = "Supprimer tous les affaires et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'supprimer';
	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','lead',".$conf->entity.")",
		);

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
		return $this->_load_tables('/lead/sql/');
	}
}
?>
