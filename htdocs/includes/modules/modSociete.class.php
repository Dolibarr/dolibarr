<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
 * $Source$
 *
 */

/** 
    \defgroup   societe     Module societe
    \brief      Module pour gérer les societes et contacts clients
*/

/**
   \file       htdocs/includes/modules/modSociete.class.php
   \ingroup    societe
   \brief      Fichier de description et activation du module Societe
*/

include_once "DolibarrModules.class.php";

/** 
    \class      modSociete
    \brief      Classe de description et activation du module Societe
*/

class modSociete extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modSociete($DB)
  {
    $this->db = $DB ;
    $this->numero = 1 ;

    $this->family = "crm";
    $this->name = "Module societe";
    $this->description = "Gestion des sociétés et contacts";

    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_SOCIETE';
    $this->special = 0;
    $this->config_page_url = "societe.php";
    $this->picto='company';
    
    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modCommercial","modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");

    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'societe';
  
    $this->rights[1][0] = 121; // id de la permission
    $this->rights[1][1] = 'Lire les societes'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 122; // id de la permission
    $this->rights[2][1] = 'Créer modifier les societes'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[2][4] = 'creer';

    $this->rights[3][0] = 129; // id de la permission
    $this->rights[3][1] = 'Supprimer les sociétés'; // libelle de la permission
    $this->rights[3][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[3][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[3][4] = 'supprimer';
    
    $this->rights[4][0] = 261; // id de la permission
    $this->rights[4][1] = 'Créer modifier les contacts'; // libelle de la permission
    $this->rights[4][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[4][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[4][4] = 'contact';
    $this->rights[4][5] = 'creer';
    
    $this->rights[5][0] = 262; // id de la permission
    $this->rights[5][1] = 'Supprimer les contacts'; // libelle de la permission
    $this->rights[5][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[5][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[5][4] = 'contact';
    $this->rights[5][5] = 'supprimer';

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
    $this->dirs[0] = $conf->societe->dir_output;
    
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
