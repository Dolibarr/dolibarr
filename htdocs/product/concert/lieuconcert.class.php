<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

class LieuConcert {
  var $db ;
  var $id ;
  var $nom;
  var $ville;
  var $description;

  function LieuConcert($DB, $id=0)
  {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  function create($user) 
  {
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."lieu_concert (fk_user_author) VALUES (".$user->id.")";
	    
    if ($this->db->query($sql) )
      {
	$id = $this->db->last_insert_id();
	
	if ( $this->update($id, $user) )
	  {
	    return $id;
	  }
      }
    else
      {
	print $this->db->error() . ' in ' . $sql;
      }
    
  }

  /*
   *
   *
   *
   */
  function update($id, $user)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."lieu_concert ";
    $sql .= " SET nom = '" . trim($this->nom) ."'";
    $sql .= ",ville = '" . trim($this->ville) ."'";
    $sql .= ",description = '" . trim($this->description) ."'";

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
  function fetch ($id) {
    
    $sql = "SELECT rowid, nom, ville, description FROM ".MAIN_DB_PREFIX."lieu_concert WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id          = $result["rowid"];
      $this->nom         = $result["nom"];
      $this->ville       = $result["ville"];
      $this->description = $result["description"];

      $this->nom_url     = '<a href="'.DOL_URL_ROOT.'/product/concert/fichelieu.php?id='.$result["rowid"].'">'.$result["nom"].'</a>';
    }
    $this->db->free();

    return $result;
  }


  /*
   *
   *
   */
  function delete($user) {

	    
    
  }


  /*
   *
   *
   */
  function liste()
  {
    $ga = array();

    $sql = "SELECT rowid, nom, ville, description FROM ".MAIN_DB_PREFIX."lieu_concert";
    $sql .= " ORDER BY ville, nom"; 
    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->rowid] = $obj->ville . " - " .$obj->nom;
		$i++;
	      }
	  }
	return $ga;
      }
    else
      {
	print $this->db->error();
      }    
  }


}
?>
