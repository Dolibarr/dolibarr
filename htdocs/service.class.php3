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

class Service {
  var $db;

  var $id;
  var $libelle;
  var $price;
  var $tms;
  var $debut;
  var $fin;

  var $debut_epoch;
  var $fin_epoch;


  Function Service($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id;
    
    return 1;
  }
  /*
   *
   *
   *
   */
  Function create($user) {

    $sql = "INSERT INTO llx_service (datec, fk_user_author) VALUES (now(), ".$user->id.")";

    if ($this->db->query($sql) ) {
      $id = $this->db->last_insert_id();

      if ( $this->update($id, $user) ) {
	return $id;
      }
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  Function update($id, $user) {

    $sql = "UPDATE llx_service ";
    $sql .= " SET label = '" . trim($this->libelle) ."'";
    $sql .= ",ref = '" . trim($this->ref) ."'";
    $sql .= ",price = " . $this->price ;
    $sql .= ",description = '" . trim($this->description) ."'";
    $sql .= ",fk_user_modif = " . $user->id ;

    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
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
  Function start_comm($id, $user, $datedeb=0) {

    $sql = "UPDATE llx_service ";
    if ($datedeb) {
      $sql .= " SET debut_comm = '$datedeb'";
    } else {
      $sql .= " SET debut_comm = now()";
    }
    $sql .= ",fk_user_modif = " . $user->id ;

    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
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
  Function stop_comm($id, $user, $datefin=0) {

    $sql = "UPDATE llx_service ";
    if ($datefin) {
      $sql .= " SET fin_comm = '$datefin'";
    } else {
      $sql .= " SET fin_comm = now()";
    }
    $sql .= ",fk_user_modif = " . $user->id ;

    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
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
  Function fetch($id) {

    $sql = "SELECT s.ref,s.label,s.price,s.tms,s.debut_comm,s.fin_comm,s.description,";
    $sql .= $this->db->pdate("s.debut_comm") . ' as debut_epoch,';
    $sql .= $this->db->pdate("s.fin_comm") . ' as fin_epoch';
    $sql .= " FROM llx_service as s";
    $sql .= " WHERE s.rowid = $id";
  
    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->id = $obj->rowid;
	$this->ref = $obj->ref;
	$this->libelle = $obj->label;
	$this->price = $obj->price;
	$this->description = $obj->description;

	$this->tms = $obj->tms;

	$this->debut = $obj->debut_comm;
	$this->fin = $obj->fin_comm;

	$this->debut_epoch = $obj->debut_epoch;
	$this->fin_epoch = $obj->fin_epoch;

      }
      $this->db->free();

    } else {
      print $this->db->error();
    }
  }


}
/*
 * $Id$
 * $Source$
 */
?>
