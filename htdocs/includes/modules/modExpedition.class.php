<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   expedition     Module expedition
        \brief      Module pour gérer les expeditions de produits
*/

/*!
        \file       htdocs/includes/modules/modExpeditione.class.php
        \brief      Fichier de description et activation du module Expedition
*/

include_once "DolibarrModules.class.php";

class modExpedition extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modExpedition($DB)
  {
    $this->db = $DB ;
    $this->numero = 80 ;

    $this->family = "crm";
    $this->name = "Expedition";
    $this->description = "Gestion des expéditions";
    $this->const_name = "MAIN_MODULE_EXPEDITION";
    $this->const_config = MAIN_MODULE_EXPEDITION;

    // Config pages
    $this->config_page_url = "expedition.php";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
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
    $sql = array(
		 "insert into ".MAIN_DB_PREFIX."rights_def values (100,'Tous les droits sur les expeditions','expedition','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (101,'Lire les expeditions','expedition','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (102,'Créer modifier les expeditions','expedition','w',0);",
		 //"insert into ".MAIN_DB_PREFIX."rights_def values (83,'Modifier les expeditions d\'autrui','expedition','m',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (104,'Valider les expeditions','expedition','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (109,'Supprimer les expeditions','expedition','d',0);",
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  function remove()
  {
    $sql = array(

		 );

    return $this->_remove($sql);

  }
}
?>
