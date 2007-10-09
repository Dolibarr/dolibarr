<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \defgroup   deplacement     Module deplacement et notes de frais
        \brief      Module pour gérer les déplacements et notes de frais
*/

/**
        \file       htdocs/includes/modules/modDeplacement.class.php
        \ingroup    deplacement
        \brief      Fichier de description et activation du module Deplacement et notes de frais
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modDeplacement
		\brief      Classe de description et activation du module Deplacement
*/

class modDeplacement extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modDeplacement($DB)
  {
    $this->db = $DB ;
    $this->id = 'deplacement';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 75 ;

    $this->family = "financial";
    $this->name = "Déplacement et frais";                        			// Si traduction Module75Name non trouvée
    $this->description = "Gestion des notes de frais et déplacements";		// Si traduction Module75Desc non trouvée

    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_DEPLACEMENT';
    $this->special = 0;
    $this->picto = "generic";
    
    // Dir
    $this->dirs = array();

    // Config pages
    $this->config_page_url = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();
    
    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'deplacement';

    $this->rights[1][0] = 170;
    $this->rights[1][1] = 'Lire les déplacements';
    $this->rights[1][2] = 'r';
    $this->rights[1][3] = 1;
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 171;
    $this->rights[2][1] = 'Créer/modifier les déplacements';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'creer';

    $this->rights[3][0] = 172;
    $this->rights[3][1] = 'Supprimer les déplacements';
    $this->rights[3][2] = 'd';
    $this->rights[3][3] = 0;
    $this->rights[3][4] = 'supprimer';
  
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
