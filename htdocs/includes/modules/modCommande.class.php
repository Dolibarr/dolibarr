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

/*!     \defgroup   commande     Module commande
        \brief      Module pour gérer le suivi des commandes
*/

/*!
        \file       htdocs/includes/modules/modCommande.class.php
        \ingroup    commande
        \brief      Fichier de description et activation du module Commande
*/

include_once "DolibarrModules.class.php";

/*! \class modCommande
        \brief      Classe de description et activation du module Commande
*/

class modCommande extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modCommande($DB)
  {
    $this->db = $DB ;
    $this->numero = 25 ;

    $this->family = "crm";
    $this->name = "Commande";
    $this->description = "Gestion des commandes";
    $this->const_name = "MAIN_MODULE_COMMANDE";
    $this->const_config = MAIN_MODULE_COMMANDE;

    // Config pages
    $this->config_page_url = "commande.php";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
    $this->boxes[0][0] = "Commandes";
    $this->boxes[0][1] = "box_commandes.php";
  }
  /*
   *
   *
   *
   */

  function init()
  {
    /*
     * Permissions
     */
		 
		$this->remove();
		 
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (80,'Tous les droits sur les commandes','commande','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (81,'Lire les commandes','commande','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (82,'Créer modifier les commandes','commande','w',0);",
		 //"INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (83,'Modifier les commandes d\'autrui','commande','m',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (84,'Valider les commandes','commande','d',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (89,'Supprimer les commandes','commande','d',0);",
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);

  }
}
?>
