<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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

/*!     \defgroup   banque     Module banque
        \brief      Module pour gérer la tenue d'un compte bancaire et rapprochements
*/

/*!
        \file       htdocs/includes/modules/modBanque.class.php
        \ingroup    banque
        \brief      Fichier de description et activation du module Banque
*/

include_once "DolibarrModules.class.php";

/*! \class modBanque
		\brief      Classe de description et activation du module Banque
*/

class modBanque extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modBanque($DB)
  {
    $this->db = $DB ;
    $this->numero = 85 ;

    $this->family = "financial";
    $this->name = "Banque";
    $this->description = "Gestion des comptes financiers de type Comptes bancaires ou postaux";
    $this->const_name = "MAIN_MODULE_BANQUE";
    $this->const_config = MAIN_MODULE_BANQUE;

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'banque';

  }


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $this->rights[0][0] = 110; // id de la permission
    $this->rights[0][1] = 'Tous les droits sur les comptes bancaires'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[0][0] = 111; // id de la permission
    $this->rights[0][1] = 'Lire les comptes bancaires'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[0][0] = 112; // id de la permission
    $this->rights[0][1] = 'Créer modifier rapprocher transactions'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[0][0] = 113; // id de la permission
    $this->rights[0][1] = 'Configurer les comptes bancaires (créer, gérer catégories)'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $sql = array();
    
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'banque';");

    return $this->_remove($sql);
  }
}
?>
