<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!     \defgroup   societe     Module societe
        \brief      Module pour gérer les societes et contacts clients
*/

/*!
        \file       htdocs/includes/modules/modSociete.class.php
        \ingroup    societe
        \brief      Fichier de description et activation du module Societe
*/

include_once "DolibarrModules.class.php";

/*! \class modSociete
        \brief      Classe de description et activation du module Societe
*/

class modSociete extends DolibarrModules
{

  /*
   * Initialisation
   *
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

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");

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
		 "insert into ".MAIN_DB_PREFIX."rights_def values (120,'Tous les droits sur les sociétés','societe','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (121,'Lire les societes','societe','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (122,'Créer modifier les societes','societe','w',0);",
		 "insert INTO ".MAIN_DB_PREFIX."rights_def values (129,'Supprimer les sociétés','societe','d',0);"
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
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'societe';",
		 );

    return $this->_remove($sql);
  }
}
?>
