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

class Groupart {
  var $db ;

  var $id ;
  var $nom ;

  Function Groupart($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  
  /*
   *
   *
   *
   */
  Function create($user) {

    $sql = "INSERT INTO llx_groupart (fk_user_author) VALUES (".$user->id.")";
    
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
  Function update($id, $user)
 {

    $sql = "UPDATE llx_groupart ";
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
    
    $sql = "SELECT rowid, nom FROM llx_groupart WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result ) {
      $result = $this->db->fetch_array();

      $this->id          = $result["rowid"];
      $this->nom         = $result["nom"];

      $this->nom_url     = '<a href="'.DOL_URL_ROOT.'/product/groupart/fiche.php?id='.$result["rowid"].'">'.$result["nom"].'</a>';

    }
    $this->db->free();

    return $result;
  }
  /*
   *
   *
   */
  Function liste_albums ()
  {
    $ga = array();

    $sql = "SELECT a.rowid, a.title FROM llx_album as a, llx_album_to_groupart as l";
    $sql .= " WHERE a.rowid = l.fk_album AND l.fk_groupart = ".$this->id;
    $sql .= " ORDER BY a.title";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->rowid] = $obj->title;
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
   */
  Function updateosc()
  {
    $albs = array();
    $albs = $this->liste_albums();

    foreach($albs as $key => $value)
      {
	$alb = new Album($this->db);
	$alb->fetch($key);
	$alb->updateosc();
      }
  }
  /*
   *
   *
   */
  Function liste_array ()
  {
    $ga = array();

    $sql = "SELECT rowid, nom FROM llx_groupart ORDER BY nom";

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
   */
  Function delete($user) {

    $sql = "DELETE FROM ".DB_NAME_OSC.".products WHERE products_id = $idosc ";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_to_categories WHERE products_id = $idosc";

    $sql = "DELETE FROM ".DB_NAME_OSC.".products_description WHERE products_id = $idosc";
	      
    $sql = "DELETE FROM llx_album WHERE rowid = $id";
	    
    
  }


}
?>
