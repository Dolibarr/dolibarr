<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

class Propal {
  var $id;
  var $db;
  var $socidp;
  var $contactid;
  var $projetidp;
  var $author;
  var $ref;
  var $datep;
  var $remise;
  var $products;
  var $products_qty;
  var $note;

  var $price;

  Function Propal($DB, $soc_idp="") {
    $this->db = $DB ;
    $this->socidp = $soc_idp;
    $this->products = array();
  }
  /*
   *
   *
   *
   */
  Function add_product($idproduct, $qty) {
    if ($idproduct > 0) {
      $i = sizeof($this->products);
      $this->products[$i] = $idproduct;
      if (!$qty) {
	$qty = 1 ;
      }
      $this->products_qty[$i] = $qty;
    }
  }
  /*
   *
   *
   *
   */
  Function create() {
    /*
     *  Insertion dans la base
     */
    $sql = "INSERT INTO llx_propal (fk_soc, fk_soc_contact, price, remise, tva, total, datep, datec, ref, fk_user_author, note) ";
    $sql .= " VALUES ($this->socidp, $this->contactid, 0, $this->remise, 0,0, $this->datep, now(), '$this->ref', $this->author, '$this->note')";
    $sqlok = 0;
      
    if ( $this->db->query($sql) ) {

      $this->id = $this->db->last_insert_id();

      $sql = "SELECT rowid FROM llx_propal WHERE ref='$this->ref';";
      if ( $this->db->query($sql) ) { 
	/*
	 *  Insertion du detail des produits dans la base
	 */
	if ( $this->db->num_rows() ) {
	  $propalid = $this->db->result( 0, 0);
	  $this->db->free();
	    
	  for ($i = 0 ; $i < sizeof($this->products) ; $i++) {
	    $prod = new Product($this->db, $this->products[$i]);
	    $prod->fetch($this->products[$i]);

	    $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price) VALUES ";
	    $sql .= " ($propalid,". $this->products[$i].",". $this->products_qty[$i].", $prod->price) ; ";

	    if (! $this->db->query($sql) ) {
	      print $sql . '<br>' . $this->db->error() .'<br>';
	    }
	  }
	  /*
	   *
	   */
	  $this->update_price($this->id);
	  /*
	   *  Affectation au projet
	   */
	  if ($this->projetidp) {
	    $sql = "UPDATE llx_propal SET fk_projet=$this->projetidp WHERE ref='$this->ref';";
	    $this->db->query($sql);
	  }
	}	  
      } else {
	print $this->db->error() . '<b><br>'.$sql;
      }
    } else {
      print $this->db->error() . '<b><br>'.$sql;
    }
    return $this->id;
  }
  /*
   *
   *
   */
  Function update_price($rowid) {
    /*
     *  Remise
     */
    $sql = "SELECT remise FROM llx_propal WHERE rowid = $rowid";
    if ( $this->db->query($sql) ) {
      $remise = $this->db->result(0, 0);
      $this->db->free();
    
      
      /*
       *  Total des produits a ajouter
       */
      $sql = "SELECT sum(price * qty) FROM llx_propaldet WHERE fk_propal = $rowid";
      if ( $this->db->query($sql) ) {
	$cprice = $this->db->result(0, 0);
	$this->db->free();
	
	/*
	 *  Calcul TVA, Remise
	 */
	$totalht = $cprice - $this->remise;
	$tva = tva($totalht);
	$total = $totalht + $tva;
	/*
	 *
	 */
	$sql = "UPDATE llx_propal set price=$cprice, tva=$tva, total=$total WHERE rowid = $rowid";
	if ( $this->db->query($sql) ) {
	  
	}
      }
    }

  }

  /*
   *
   *
   *
   */
  Function fetch($rowid) {

    $sql = "SELECT ref,price,remise,".$this->db->pdate(datep)."as dp FROM llx_propal WHERE rowid=$rowid;";

    if ($this->db->query($sql) ) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);

	$this->id = $rowid;
	$this->datep = $obj->dp;
	$this->ref = $obj->ref;
	$this->price = $obj->price;
	$this->remise = $obj->remise;
	
	$this->db->free();
      }
    } else {
      print $this->db->error();
    }    
  }
  /*
   *
   *
   *
   */
  Function valid($userid) {
    $sql = "UPDATE llx_propal SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
    $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
    
    if ($this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  Function cloture($userid, $statut, $note) {
    $sql = "UPDATE llx_propal SET fk_statut = $statut, note = '$note', date_cloture=now(), fk_user_cloture=$userid";

    $sql .= " WHERE rowid = $this->id;";
    
    if ($this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }








}    
?>
    
