<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!     \defgroup   produit     Module produit
        \brief      Module pour gérer le suivi de produits prédéfinis
*/

/*!
        \file       htdocs/includes/modules/modProduit.class.php
        \ingroup    produit
        \brief      Fichier de description et activation du module Produit
*/

include_once "DolibarrModules.class.php";

/*! \class modProduit
		\brief      Classe de description et activation du module Produit
*/

class modProduit extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modProduit($DB)
  {
    $this->db = $DB ;
    $this->numero = 50 ;
    
    $this->family = "products";
    $this->name = "Produit";
    $this->description = "Gestion des produits";
    $this->const_name = "MAIN_MODULE_PRODUIT";
    $this->const_config = MAIN_MODULE_PRODUIT;

    // Dépendances
    $this->depends = array();
	$this->requiredby = array("modStock","modService");
	
    $this->const = array();
    $this->boxes = array();

    $this->boxes[0][0] = "Derniers produits/services enregistrés";
    $this->boxes[0][1] = "box_produits.php";
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
		$this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (30,'Tous les droits sur les produits/services','produit','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (31,'Lire les produits/services','produit','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (32,'Créer modifier les produits/services','produit','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (34,'Supprimer les produits/services','produit','d',0);"
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
