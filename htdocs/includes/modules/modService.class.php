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

/*!     \defgroup   service     Module service
        \brief      Module pour gérer le suivi de services prédéfinis
*/

/*!
        \file       htdocs/includes/modules/modService.class.php
        \ingroup    service
        \brief      Fichier de description et activation du module Service
*/

include_once "DolibarrModules.class.php";

/*! \class modService
        \brief      Classe de description et activation du module Service
*/

class modService extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modService($DB)
  {
    $this->db = $DB ;
    $this->numero = 53 ;
    
    $this->family = "products";
    $this->name = "Service";
    $this->description = "Gestion des services";
    $this->const_name = "MAIN_MODULE_SERVICE";
    $this->const_config = MAIN_MODULE_SERVICE;

    // Dépendances
    $this->depends = array("modProduit");
    $this->requiredby = array("modContrat");

    $this->const = array();
    $this->boxes = array();

    $this->boxes[0][0] = "Derniers produits/services contractés";
    $this->boxes[0][1] = "box_services_vendus.php";
  }
  /*
   *
   *
   *
   */

  function init()
  {
    /*
     *  Activation du module
     */
    /*
     * Permissions
     */

    $sql = array(
		 "insert into ".MAIN_DB_PREFIX."rights_def values (30,'Tous les droits sur les produits/services','produit','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (31,'Lire les produits/services','produit','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (32,'Créer modifier les produits/services','produit','w',0);",
		 "insert INTO ".MAIN_DB_PREFIX."rights_def values (34,'Supprimer les produits/services','produit','d',0);"
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
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'produit';",
		 "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_services_vendus.php';",
		 "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = 'box_produits.php';"
		 );

    return $this->_remove($sql);
  }
}
?>
