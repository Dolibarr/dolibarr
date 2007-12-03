<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \defgroup   category       Module categorie
        \brief      Module pour gérer les catégories
*/

/**
        \file       htdocs/includes/modules/modCategorie.class.php
        \ingroup    category
        \brief      Fichier de description et activation du module Categorie
*/
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modCategorie
        \brief      Classe de description et activation du module Categorie
*/
class modCategorie extends DolibarrModules
{
	/**
	 *		\brief	Constructeur. définit les noms, constantes et boîtes
	 * 		\param	DB	handler d'accès base
	 */
	function modCategorie ($DB)
	{
		$this->db = $DB;
		$this->id = 'categorie';   // Same value xxx than in file modXxx.class.php file
		$this->numero = 1780;
	
		$this->family = "technic";
		$this->name = "Catégories";
		$this->description = "Gestion des catégories (produits, clients, fournisseurs...)";
	
		$this->revision = explode(' ','$Revision$');
		$this->version = $this->revision[1];
		//$this->version = 'experimental';    // 'development' or 'experimental' or 'dolibarr' or version
	
		$this->const_name = 'MAIN_MODULE_CATEGORIE';
		$this->special = 0;
		$this->picto = '';
	
		// Dir
		$this->dirs = array();
	
		// Dépendances
		$this->depends = array("modProduit");
	
		// Constantes
		$this->const = array();
	
		// Boxes
		$this->boxes = array();
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'categorie';
	
		$r=0;
	
		$this->rights[$r][0] = 241; // id de la permission
		$this->rights[$r][1] = 'Lire les catégories'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
		$this->rights[$r][4] = 'lire';
		$r++;
	
		$this->rights[$r][0] = 242; // id de la permission
		$this->rights[$r][1] = 'Créer/modifier les catégories'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
		$this->rights[$r][4] = 'creer';
		$r++;
	
		$this->rights[$r][0] = 243; // id de la permission
		$this->rights[$r][1] = 'Supprimer les catégories'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (déprécié à ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
		$this->rights[$r][4] = 'supprimer';
		$r++;
	
		$this->rights[$r][0] = 244; // id de la permission
		$this->rights[$r][1] = 'Voir le contenu des catégories cachées'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
		$this->rights[$r][4] = 'voir';
		$r++;
	}


	/**
	 *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
	 *               Définit également les répertoires de données à créer pour ce module.
	 */
	function init()
	{
		// Permissions
		$this->remove();
	
		$sql = array();
	
		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelée lors de la désactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();
	
		return $this->_remove($sql);
	}

}
?>
