<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \defgroup   prelevement     Module prelevement
   \brief      Module de gestion des prélèvements bancaires
*/

/**
		\file       htdocs/includes/modules/modPrelevement.class.php
		\ingroup    prelevement
		\brief      Fichier de description et activation du module Prelevement
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
		\class 		modPrelevement
		\brief      Classe de description et activation du module Prelevement
*/
class modPrelevement extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
	function modPrelevement($DB)
	{
		global $conf;
	
		$this->db = $DB ;
		$this->id = 'prelevement';   // Same value xxx than in file modXxx.class.php file
		$this->numero = 57 ;
	
		$this->family = "financial";
		$this->name = "Prelevement";
		$this->description = "Gestion des Prélèvements";
	
		$this->revision = explode(' ','$Revision$');
		$this->version = $this->revision[1];
	
		$this->const_name = 'MAIN_MODULE_PRELEVEMENT';
		$this->special = 0;
	
		// Dir
		$this->dirs = array();
		$this->data_directory = $conf->prelevement->dir_output . "/bon";
	
		// Dépendances
		$this->depends = array("modFacture");
		$this->requiredby = array();
	
		// Constantes
		$this->const = array();
	
		// Boites
		$this->boxes = array();
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'prelevement';
	
		$this->rights[1][0] = 151;
		$this->rights[1][1] = 'Consulter les prélèvements';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'bons';
		$this->rights[1][5] = 'lire';
	
		$this->rights[2][0] = 152;
		$this->rights[2][1] = 'Configurer les prélèvements';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'bons';
		$this->rights[2][5] = 'configurer';
	
		$this->rights[3][0] = 153;
		$this->rights[3][1] = 'Consulter les bons de prélèvements';
		$this->rights[3][2] = 'r';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'bons';
		$this->rights[3][5] = 'lire';
	
		$this->rights[4][0] = 154;
		$this->rights[4][1] = 'Créer un bon de prélèvement';
		$this->rights[4][2] = 'w';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'bons';
		$this->rights[4][5] = 'creer';
	}


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
	function init()
	{
		global $conf;
	
		// Permissions
		$this->remove();
	
		// Dir
		$this->dirs[0] = $conf->prelevement->dir_output;
		$this->dirs[1] = $conf->prelevement->dir_output."/bon" ;
	
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
