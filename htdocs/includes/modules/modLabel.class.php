<?php
/* Copyright (C) 2007  Regis Houssin   <regis@dolibarr.fr>
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
        \defgroup   label         Module étiquettes
        \brief      Module pour gérer l'impression des étiquettes
*/

/**
        \file       htdocs/includes/modules/modLabel.class.php
        \ingroup    other
        \brief      Fichier de description et activation du module Label
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modLabel
		\brief      Classe de description et activation du module Label
*/

class modLabel extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modLabel($DB)
  {
    $this->db = $DB ;
    $this->id = 'label';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 60 ;
    
    $this->family = "other";
    $this->name = "Etiquettes";
    $this->description = "Gestion des étiquettes";
    $this->version = 'development';		// 'development' or 'experimental' or 'dolibarr' or version
    $this->const_name = 'MAIN_MODULE_LABEL';
    $this->special = 2;
    $this->picto='label';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
	  $this->requiredby = array();
	
	  // Config pages
    $this->config_page_url = array("label.php");
	
    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'label';
    
    $this->rights[1][0] = 601; // id de la permission
    $this->rights[1][1] = 'Lire les étiquettes'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 602; // id de la permission
    $this->rights[2][1] = 'Créer/modifier les étiquettes'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[2][4] = 'creer';

    $this->rights[4][0] = 609; // id de la permission
    $this->rights[4][1] = 'Supprimer les étiquettes'; // libelle de la permission
    $this->rights[4][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[4][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[4][4] = 'supprimer';

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
