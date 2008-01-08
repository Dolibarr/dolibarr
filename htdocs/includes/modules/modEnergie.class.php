<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    \defgroup   energie     Module energie
    \brief      Module pour le suivi de la consommation d'energie
*/

/**
   \file       htdocs/includes/modules/modEnergie.class.php
   \ingroup    energie
   \brief      Fichier de description et activation du module Energie
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
   \class modEnergie
   \brief      Classe de description et activation du module Energie
*/

class modEnergie extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modEnergie($DB)
  {
    $this->db = $DB ;
    $this->id = 'energie';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 23 ;
    
    $this->family = "other";
    $this->name = "Energie";
    $this->description = "Suivi de la consommation des energies";

    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_ENERGIE';
    $this->special = 2;
    $this->picto='energie';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();

    // Config pages
    $this->config_page_url = array("energie.php");

    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();
	$r=0;
    $this->boxes[$r][1] = "box_energie_releve.php";
	$r++;
	$this->boxes[$r][1] = "box_energie_graph.php";
    $r++;

    // Permissions
    $this->rights = array();

  }


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {       
    global $conf;
    // Permissions et valeurs par défaut
    $this->remove();

    $sql = array();

    $this->dirs[0] = DOL_DATA_ROOT."/energie";
    $this->dirs[1] = DOL_DATA_ROOT."/energie/graph";
    
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
