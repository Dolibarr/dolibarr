<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!   \defgroup   telephonie  Module telephonie
      \brief      Module pour gérer la téléphonie
*/

/*!
      \file       htdocs/includes/modules/modTelephonie.class.php
      \ingroup    telephonie
      \brief      Fichier de description et activation du module de Téléphonie
*/

include_once "DolibarrModules.class.php";

/*! \class modTelephonie
    \brief Classe de description et activation du module Telephonie
*/

class modTelephonie extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modTelephonie($DB)
  {
    $this->db = $DB ;
    $this->numero = 56 ;

    $this->family = "technic";
    $this->name = "Telephonie";
    $this->description = "Gestion de la Telephonie (experimental)";
    $this->const_name = "MAIN_MODULE_TELEPHONIE";
    $this->const_config = MAIN_MODULE_TELEPHONIE;
    $this->special = 1;

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'telephonie';
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

    $this->rights[0][0] = 140; // id de la permission
    $this->rights[0][1] = 'Tous les droits sur la telephonie'; // libelle de la permission
    $this->rights[0][2] = 'a'; // type de la permission (déprécié à ce jour)
    $this->rights[0][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[1][0] = 141; // id de la permission
    $this->rights[1][1] = 'Consulter la telephonie'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[2][0] = 142; // id de la permission
    $this->rights[2][1] = 'Commander les lignes'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[3][0] = 143; // id de la permission
    $this->rights[3][1] = 'Activer une ligne'; // libelle de la permission
    $this->rights[3][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[3][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[4][0] = 144; // id de la permission
    $this->rights[4][1] = 'Configurer la telephonie'; // libelle de la permission
    $this->rights[4][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[4][3] = 0; // La permission est-elle une permission par défaut

    $this->rights[5][0] = 145; // id de la permission
    $this->rights[5][1] = 'Configurer les fournisseurs'; // libelle de la permission
    $this->rights[5][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[5][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[5][4] = 'fournisseur'; // La permission est-elle une permission par défaut
    $this->rights[5][5] = 'config'; // La permission est-elle une permission par défaut

    $this->rights[6][0] = 192;
    $this->rights[6][1] = 'Creer des lignes';
    $this->rights[6][2] = 'w';
    $this->rights[6][3] = 0;
    $this->rights[6][4] = 'ligne';
    $this->rights[6][5] = 'creer';

    $this->rights[7][0] = 202;
    $this->rights[7][1] = 'Créer des liaisons ADSL';
    $this->rights[7][2] = 'w';
    $this->rights[7][3] = 0;
    $this->rights[7][4] = 'adsl';
    $this->rights[7][5] = 'creer';

    $this->rights[8][0] = 203;
    $this->rights[8][1] = "Demander la commande des liaisons";
    $this->rights[8][2] = 'w';
    $this->rights[8][3] = 0;
    $this->rights[8][4] = 'adsl';
    $this->rights[8][5] = 'requete';

    $this->rights[9][0] = 204;
    $this->rights[9][1] = 'Commander les liaisons';
    $this->rights[9][2] = 'w';
    $this->rights[9][3] = 0;
    $this->rights[9][4] = 'adsl';
    $this->rights[9][5] = 'commander';

    $this->rights[10][0] = 205;
    $this->rights[10][1] = 'Gérer les liaisons';
    $this->rights[10][2] = 'w';
    $this->rights[10][3] = 0;
    $this->rights[10][4] = 'adsl';
    $this->rights[10][5] = 'gerer';

    // Dir
    $this->dirs[0] = $conf->telephonie->dir_output;
    $this->dirs[1] = $conf->telephonie->dir_output."/ligne";
    $this->dirs[2] = $conf->telephonie->dir_output."/ligne/commande" ;	 
    $this->dirs[3] = $conf->telephonie->dir_output."/logs" ;
    $this->dirs[4] = $conf->telephonie->dir_output."/client" ;
    $this->dirs[5] = $conf->telephonie->dir_output."/rapports" ;
    
    return $this->_init($sql);



  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'telephonie';");

    return $this->_remove($sql);
  }
}
?>
