<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!     \defgroup   societe     Module societe
        \brief      Module pour gérer les societes et contacts clients
*/

/*!
        \file       htdocs/includes/modules/modSociete.class.php
        \ingroup    societe
        \brief      Fichier de description et activation du module Societe
*/

include_once "DolibarrModules.class.php";

/*!     \class      modSociete
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
    $this->const_name = "MAIN_MODULE_SOCIETE";
    $this->const_config = MAIN_MODULE_SOCIETE;
    $this->special = 0;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");

    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'societe';
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $this->rights[0][0] = 120; // id de la permission
    $this->rights[0][1] = 'Tous les droits sur les sociétés'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[1][0] = 121; // id de la permission
    $this->rights[1][1] = 'Lire les societes'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut

    $this->rights[2][0] = 122; // id de la permission
    $this->rights[2][1] = 'Créer modifier les societes'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[3][0] = 129; // id de la permission
    $this->rights[3][1] = 'Supprimer les sociétés'; // libelle de la permission
    $this->rights[3][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[3][3] = 0; // La permission est-elle une permission par défaut

    $sql = array();
    
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'societe';",
		 );

    return $this->_remove($sql);
  }
}
?>
