<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   caisse     Module caisse
        \brief      Module pour gérer la tenue d'une ou plusieurs caisses liquides
*/

/*!
        \file       htdocs/includes/modules/modCaisse.class.php
        \ingroup    caisse
        \brief      Fichier de description et activation du module Caisse
*/

include_once "DolibarrModules.class.php";

/*! \class modCaisse
        \brief      Classe de description et activation du module Caisse
*/

class modCaisse extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
   */

  function modCaisse($DB)
  {
    $this->db = $DB ;
    $this->numero = 84 ;

    $this->family = "financial";
    $this->name = "Caisse";
    $this->description = "Gestion des comptes fincanciers de type Caisses liquides (pas encore opérationnel)";
    $this->const_name = "MAIN_MODULE_CAISSE";
    $this->const_config = MAIN_MODULE_CAISSE;

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
		 "insert into ".MAIN_DB_PREFIX."rights_def values (130,'Tous les droits sur les caisses','caisse','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (131,'Lire les caisses liquide','caisse','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (132,'Créer, supprimer transactions','caisse','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (133,'Configurer les caisses (créer, gérer catégories)','caisse','w',0);",
		 );
    
    return $this->_init($sql);
  }
  /** suppression du module
   *
   *
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'caisse';");

    return $this->_remove($sql);
  }
}
?>
