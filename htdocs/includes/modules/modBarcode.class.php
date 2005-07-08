<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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

/**
        \defgroup   produit     Module code barre
        \brief      Module pour gérer les codes barres des produits
*/

/**
        \file       htdocs/includes/modules/modBarcode.class.php
        \ingroup    produit
        \brief      Fichier de description et activation du module Barcode
*/

include_once "DolibarrModules.class.php";

/**
        \class      modBarcode
		\brief      Classe de description et activation du module Barcode
*/

class modBarcode extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modBarcode($DB)
  {
    $this->db = $DB ;
    $this->numero = 55 ;
    
    $this->family = "products";
    $this->name = "Codes barres";
    $this->description = "Gestion des codes barres des produits";

    //$this->revision = explode(" ","$Revision$");
    $this->version = 'experimental';    // 'experimental' or 'dolibarr' or version
    //$this->version = $this->revision[1];

    $this->const_name = "MAIN_MODULE_BARCODE";
    $this->const_config = MAIN_MODULE_BARCODE;
    $this->special = 0;
    $this->picto='product';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array("modProduit");
	$this->requiredby = array();
	
    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();
    // $this->boxes[0][0] = "Derniers produits/services enregistrés";
    // $this->boxes[0][1] = "box_produits.php";
    // $this->boxes[1][0] = "Derniers produits/services vendus";
    // $this->boxes[1][1] = "box_services_vendus.php";

    // Permissions
    $this->rights = array();
    $this->rights_class = 'barcode';
    
    $this->rights[1][0] = 300; // id de la permission
    $this->rights[1][1] = 'Lire les codes barres'; // libelle de la permission
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 301; // id de la permission
    $this->rights[2][1] = 'Créer/modifier les codes barres'; // libelle de la permission
    $this->rights[2][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[2][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[2][4] = 'creer';

    $this->rights[4][0] = 302; // id de la permission
    $this->rights[4][1] = 'Supprimer les codes barres'; // libelle de la permission
    $this->rights[4][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[4][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[4][4] = 'supprimer';

  }


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $sql = array();

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
