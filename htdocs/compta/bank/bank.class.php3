<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Account {
  var $rowid;
  var $bank;
  var $label;
  var $name;

  Function Account($DB, $rowid=0) {
    global $config;

    $this->db = $DB;
    $this->rowid = $rowid;
    
    return 1;
  }

  Function fetch() {

    $sql = "SELECT s.idp, s.nom,".$this->db->pdate("s.datec");  

    $sql .= " AND s.idp = ".$this->id;


    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object($result , 0);

	$this->nom = $obj->nom;
      }
      $this->db->free();
    }
  }

  Function create() {
    $sql  = "INSERT INTO llx_bank_account (label, bank, name) ";
    $sql .= " VALUES ('$this->label', '$this->bank', '$this->name')";
    
    $this->db->query($sql);

}
/*
 * $Id$
 * $Source$
 */
?>
