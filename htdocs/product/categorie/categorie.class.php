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

class Categorie {
  var $db ;

  var $id ;
  var $oscid ;
  var $ref;
  var $titre;
  var $description;
  var $price ;
  var $status ;

  Function Categorie($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  Function create($user) {

    $sql = "INSERT INTO llx_album (osc_id, fk_user_author) VALUES ($idosc, ".$user->id.")";
    
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
  Function linkga($id, $gaid)
  {

    $sql = "INSERT INTO llx_album_to_groupart (fk_album, fk_groupart) values ($id, $gaid)";

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   */
  Function liste_array()
  {
    $cl = array();

    $sql = "SELECT c.categories_id, cd.categories_name ";
    $sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
    $sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
    $sql .= " AND c.parent_id = 0";
    
    if ( $this->db->query($sql) )
      {
	$num = $this->db->num_rows();
	$i = 0;
	
	while ($i < $num)
	  {
	    $objp = $this->db->fetch_object( $i);
	    $var=!$var;
	    $pc = array();
	    $pc = $this->printc($objp->categories_id, 0);
	    foreach($pc as $key => $value)
	      {
		$cl[$key] = $value;
	      } 
	    $i++;
	  }
      }
    return $cl;
  }
  /*
   */
  Function printc($id, $level)
  {
    $cr = array();
    $cat = new Categorie($this->db);
    $cat->fetch($id);

    for ($i = 0 ; $i < $level ; $i++)
      {
	$string = "&nbsp;&nbsp;|--";
      }
    
    $string .= $cat->name;

    
    
    $childs = array();
    $childs = $cat->liste_childs_array();
    
    if (sizeof($childs))
      {
	foreach($childs as $key => $value)
	  {
	    $cr[$key] = $value;
	    $this->printc($key, $level+1);
	  }
      }
    
    return $cr;
  }
  /*
   *
   *
   */
  Function liste_childs_array()
  {
    $ga = array();

    $sql = "SELECT c.categories_id, cd.categories_name";
    $sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
    $sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
    $sql .= " AND c.parent_id = " . $this->id;

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->categories_id] = $obj->categories_name;
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
  /*
   *
   *
   *
   */
  Function update($id, $user)
 {

    $sql = "UPDATE llx_album ";
    $sql .= " SET title = '" . trim($this->titre) ."'";
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
  Function fetch ($id) {
    
    $sql = "SELECT c.categories_id, cd.categories_name";
    $sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
    $sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
    $sql .= " AND c.categories_id = $id";
    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id          = $result["categories_id"];
      $this->name        = $result["categories_name"];
      $this->titre       = $result["title"];
      $this->description = $result["description"];
      $this->oscid       = $result["osc_id"];
    }
    $this->db->free();

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
	      
    $sql = "DELETE FROM llx_album WHERE rowid = $id";
	    
    
  }


}
?>
