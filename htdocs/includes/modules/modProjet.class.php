<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \defgroup   projet     Module projet
 *	\brief      Module pour inclure le detail par projets dans les autres modules
 * 	\version	$Id$
 */

/**
 *  \file       htdocs/includes/modules/modProjet.class.php
 *	\ingroup    projet
 *	\brief      Fichier de description et activation du module Projet
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modProjet
 *	\brief      Classe de description et activation du module Projet
 */
class modProjet extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modProjet($DB)
	{
		$this->db = $DB ;
		$this->numero = 400 ;

		$this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des projets";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->config_page_url = array("project.php");
		$this->picto='project';

		// Data directories to create when module is enabled
		$this->dirs = array("/project/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "PROJECT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "baleine";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des projets en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROJECT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_project_simple";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des projets';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'projet';
		$r=0;

		$r++;
		$this->rights[$r][0] = 41; // id de la permission
		$this->rights[$r][1] = "Lire les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 42; // id de la permission
		$this->rights[$r][1] = "Creer/modifier les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 44; // id de la permission
		$this->rights[$r][1] = "Supprimer les projets et taches (partagés ou dont je suis contact)"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 141; // id de la permission
		$this->rights[$r][1] = "Lire tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 142; // id de la permission
		$this->rights[$r][1] = "Creer/modifier tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 144; // id de la permission
		$this->rights[$r][1] = "Supprimer tous les projets et taches (y compris prives qui ne me sont pas affectes)"; // libelle de la permission
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
		// Permissions
		$this->remove();

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
}
?>
