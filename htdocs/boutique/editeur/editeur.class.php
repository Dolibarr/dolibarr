<?PHP
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

class Editeur {
  var $db ;

  var $id ;
  var $nom;

  Function Editeur($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;
  }  
  /*
   *
   *
   *
   */
  Function create($user) {

    $sql = "INSERT INTO llx_editeur (fk_user_author) VALUES (".$user->id.")";
    
    if ($this->db->query($sql) )
      {
	$id = $this->db->last_insert_id();
	
	if ( $this->update($id, $user) )
	  {
	    return $id;
	  }
      }
    
  }

  /*
   *
   *
   */
  Function liste_groupart()
  {
    $ga = array();

    $sql = "SELECT g.rowid, g.nom FROM llx_groupart as g, llx_livre_to_groupart as l";
    $sql .= " WHERE g.rowid = l.fk_groupart AND l.fk_livre = ".$this->id;
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
  /*
   *
   *
   *
   */
  Function update($id, $user)
  {

    $sql = "UPDATE llx_editeur ";
    $sql .= " SET nom = '" . trim($this->nom) ."'";

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
  Function fetch ($id) {
    
    $sql = "SELECT rowid, nom FROM llx_editeur WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id      = $result["rowid"];
	$this->nom     = $result["nom"];
	
	$this->db->free();
      }
    else
      {
	print $this->db->error();
      }
    
    return $result;
  }


  /*
   *
   *
   */
  Function delete($user) {

    $sql = "DELETE FROM ".DB_NAME_OSC.".products WHERE products_id = $idosc ";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_to_categories WHERE products_id = $idosc";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_description WHERE products_id = $idosc";
	      
    $sql = "DELETE FROM llx_livre WHERE rowid = $id";
	    
    
  }


}
?>
