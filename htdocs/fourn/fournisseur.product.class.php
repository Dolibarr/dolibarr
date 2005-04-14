<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/product.class.php
   \ingroup    produit
   \brief      Fichier de la classe des produits prédéfinis
   \version    $Revision$
*/


/**
   \class      Product
   \brief      Classe permettant la gestion des produits prédéfinis
*/

require_once DOL_DOCUMENT_ROOT."/product.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.product.class.php";

class ProductFournisseur extends Product
{
  var $db ;

  var $id ;
  var $fourn_ref;
  var $fourn;

  function ProductFournisseur($db)
  {
    $this->db = $db;

    $this->fourn = new Fournisseur($this->db);
  }
  
  function fetch ($id, $id_fourn)
  {    
    Product::Fetch($id);
    $this->fourn->fetch($id_fourn);


    $sql = "SELECT ref_fourn";
    $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
    $sql .="  WHERE fk_soc = ".$this->fourn->id;
    $sql .= " AND fk_product = ".$this->id;

    $resql = $this->db->query($sql) ;
    
    if ( $resql )
      {
	$result = $this->db->fetch_array($resql);

	$this->fourn_ref = $result["ref_fourn"];

	$this->db->free($resql);
      }
    else
      {
	print "Errir";
      }

    return 0;
  }  

  /*
   *
   *
   */

  function get_buyprice($qty) 
  {
    Product::get_buyprice($this->fourn->id, $qty);
  }

  /*
   *
   *
   */

  function update($ref, $qty, $buyprice, $user) 
  {
    $this->fourn_ref = $ref;

    /* Mise à jour du prix */

    Product::update_buyprice($this->fourn->id, $qty, $buyprice, $user);

    /* Mise à jour de la référence */

    $sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur ";
    $sql .= " SET ref_fourn = '" . $this->fourn_ref ."'";
    $sql .= " WHERE fk_product = " . $this->id;
    $sql .="  AND fk_soc = ".$this->fourn->id;    

    $resql = $this->db->query($sql) ;
  }

}
?>
