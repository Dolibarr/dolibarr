<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * 
 * $Id$
 * $Source$
 * Classe Company
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

class Societe {
  var $bs;
  var $db;

  var $id;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $tel;
  var $fax;
  var $url;
  var $siren;

  var $client;

  Function Societe($DB, $id=0) {
    global $config;

    $this->db = $DB;
    $this->id = $id;
    
    return 1;
  }

  Function create() {

    $sql = "INSERT INTO societe (nom, datec, datea, client) ";
    $sql .= " VALUES ('".trim($this->nom)."', now(), now(), $this->client)";

    if ($this->db->query($sql) ) {
      $id = $this->db->last_insert_id();

      $this->update($id);

      return $id;
    }
  }
  /*
   *
   *
   *
   */
  Function update($id) {

    $sql = "UPDATE societe ";
    $sql .= " SET nom = '" . trim($this->nom) ."'";
    $sql .= ",address = '" . trim($this->adresse) ."'";
    $sql .= ",cp = '" . trim($this->cp) ."'";
    $sql .= ",ville = '" . trim($this->ville) ."'";
    $sql .= ",tel = '" . trim($this->tel) ."'";
    $sql .= ",fax = '" . trim($this->fax) ."'";
    $sql .= ",url = '" . trim($this->url) ."'";
    $sql .= ",siren = '" . trim($this->siren) ."'";
    $sql .= " WHERE idp = " . $id;

    $this->db->query($sql);
  }
  /*
   *
   *
   *
   */
  Function fetch() {

    $sql = "SELECT s.idp, s.nom, s.address,".$this->db->pdate("s.datec")." as dc,";

    $sql .= " s.tel, s.fax, s.url,s.cp,s.ville, s.note, s.siren";

    $sql .= " FROM societe as s";
    $sql .= " WHERE s.idp = ".$this->id;

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);

	$this->nom = $obj->nom;

	$this->tel = $obj->tel;
	$this->fax = $obj->fax;

	$this->url = $obj->url;
	$this->adresse = $obj->address;
	$this->cp = $obj->cp;
	$this->ville = $obj->ville;

	$this->siren = $obj->siren;

      }
      $this->db->free();
    } else {
      print $this->db->error();
    }
  }
  /*
   *
   *
   *
   */

  Function attribute_prefix() {
    $sql = "SELECT nom FROM societe WHERE idp = $this->id";
    if ( $this->db->query( $sql) ) {
      if ( $this->db->num_rows() ) {
      $nom = $this->db->result(0,0);
      $this->db->free();
      
      $prefix = strtoupper(substr($nom, 0, 2));
      
      $sql = "SELECT count(*) FROM societe WHERE prefix_comm = '$prefix'";
      if ( $this->db->query( $sql) ) {
	if ( $this->db->result(0, 0) ) {
	  $this->db->free();
	} else {
	  $this->db->free();
	  $sql = "UPDATE societe set prefix_comm='$prefix' WHERE idp=$this->id";
	  
	  if ( $this->db->query( $sql) ) {
	    
	  } else {
	    print $this->db->error();
	  }
	}
      } else {
	print $this->db->error();
      }
      }
    } else {
      print $this->db->error();
    }
    return $prefix;
  }
  /*
   *
   *
   *
   */

  Function get_nom($id) {

    $sql = "SELECT nom FROM societe WHERE idp=$id;";

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->nom = $obj->nom;

      }
      $this->db->free();
    }
  }


}
/*
 * $Id$
 * $Source$
 */
?>
