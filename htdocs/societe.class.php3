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

  var $tel;
  var $fax;
  var $url;

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

    $sql = "SELECT s.idp, s.nom,".$this->db->pdate("s.datec")." as dc,".$this->db->pdate("s.datem")." as dm,".$this->db->pdate("s.datea")." as da, s.intern, s.cjn, ";

    $sql .= " s.c_nom, s.c_prenom, s.c_tel, s.c_mail, s.tel, s.fax, s.fplus, s.cjn, s.viewed, st.libelle as stcomm, s.fk_stcomm, s.url,s.cp,s.ville, s.note";

    $sql .= " FROM societe as s, c_stcomm as st ";
    $sql .= " WHERE s.fk_stcomm = st.id";
  
    $sql .= " AND s.idp = ".$this->id;

    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->nom = $obj->nom;

	$this->tel = $obj->tel;
	$this->fax = $obj->fax;

	$this->url = $obj->url;
	$this->cp = $obj->cp;
	$this->ville = $obj->ville;


	$this->cjn = $obj->cjn;

	$this->viewed = $obj->viewed;

	$this->stcomm = $obj->stcomm;

	$this->c_nom = $obj->c_nom;
	$this->c_prenom = $obj->c_prenom;
	$this->c_tel = $obj->c_tel;
	$this->c_fax = $obj->c_fax;

      }
      $this->db->free();
    }
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
