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

/*!     \defgroup   banque     Module banque
        \brief      Module pour gérer la tenue d'un compte bancaire et rapprochements
*/

/*!
        \file       htdocs/includes/modules/modBanque.class.php
        \brief      Fichier de description et activation du module Banque
*/

include_once "DolibarrModules.class.php";

class modBanque extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
   */

  function modBanque($DB)
  {
    $this->db = $DB ;
    $this->numero = 85 ;

    $this->family = "financial";
    $this->name = "Banque";
    $this->description = "Gestion des comptes fincanciers de type Comptes bancaires ou postaux";
    $this->const_name = "MAIN_MODULE_BANQUE";
    $this->const_config = MAIN_MODULE_BANQUE;

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
  }
  /** initialisation du module
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
		 "insert into ".MAIN_DB_PREFIX."rights_def values (110,'Tous les droits sur les comptes bancaires','banque','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (111,'Lire les comptes','banque','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (112,'Créer modifier rapprocher transactions','banque','w',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (113,'Configurer les comptes (créer, gérer catégories)','banque','w',0);",
		 );
    
    return $this->_init($sql);
  }
  /** suppression du module
   *
   *
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'banque';");

    return $this->_remove($sql);
  }
}
?>
