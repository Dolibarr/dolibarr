<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Concert {
  var $db ;

  var $id ;
  var $date ;
  var $groupartid;
  var $lieuid;
  var $description;

  function Concert($DB, $id=0) {
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
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."concert (fk_user_author) VALUES (".$user->id.")";
	    
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

    $sql = "UPDATE ".MAIN_DB_PREFIX."concert ";
    $sql .= " SET date_concert = '" . $this->date ."'";
    $sql .= ", fk_groupart = '" . $this->groupartid ."'";
    $sql .= ", fk_lieu_concert = '" . $this->lieuid ."'";
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
    
    $sql = "SELECT rowid,".$this->db->pdate("date_concert")." as dc, description, fk_groupart, fk_lieu_concert";
    $sql .= " FROM ".MAIN_DB_PREFIX."concert WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id            = $result["rowid"];
      $this->date          = $result["dc"];
      $this->titre         = $result["title"];
      $this->description   = $result["description"];
      $this->groupartid    = $result["fk_groupart"];
      $this->lieuid        = $result["fk_lieu_concert"];
    }
    $this->db->free();

    return $result;
  }


  /*
   *
   *
   */


  /*
   *
   *
   */
  function liste_groupart()
  {
    $ga = array();

    $sql = "SELECT g.rowid, g.nom FROM ".MAIN_DB_PREFIX."groupart as g, ".MAIN_DB_PREFIX."album_to_groupart as l";
    $sql .= " WHERE g.rowid = l.fk_groupart AND l.fk_album = ".$this->id;
    $sql .= " ORDER BY g.nom";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->rowid] = $obj->nom;
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
