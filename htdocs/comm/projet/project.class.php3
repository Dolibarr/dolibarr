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


class Project {
  var $id;
  var $ref;
  var $title;
  var $socidp;

  Function Project() {

  }
  /*
   *
   *
   *
   */

  Function create($db, $creatorid) {

    $sql = "INSERT INTO llx_projet (ref, title, fk_soc, fk_user_creat) ";
    $sql .= " VALUES ('$this->ref', '$this->title', $this->socidp, $creatorid) ;";
    
    if (!$db->query($sql) ) 
      {
	print '<b>'.$sql.'</b><br>'.$db->error();
	
	}
    
  }
  /*
   *
   *
   *
   */

  Function fetch($db, $rowid) {

    $sql = "SELECT title, ref FROM llx_projet WHERE rowid=$rowid;";

    if ($db->query($sql) ) {
      if ($db->num_rows()) {
	$obj = $db->fetch_object(0);

	$this->id = $rowid;
	$this->ref = $obj->ref;
	$this->title = $obj->title;

	$db->free();
      }
    } else {
      print $db->error();
    }
  }
  /*
   *
   *
   *
   */
  Function get_propal_list($db) {
    $propales = array();
    $sql = "SELECT rowid FROM llx_propal WHERE fk_projet=$this->id;";

    if ($db->query($sql) ) {
      $nump = $db->num_rows();
      if ($nump) {
	$i = 0;
	while ($i < $nump) {
	  $obj = $db->fetch_object($i);

	  $propales[$i] = $obj->rowid;

	  $i++;
	}
	$db->free();
	/*
	 *  Retourne un tableau contenant la liste des propales associees
	 */
	return $propales;
      }
    } else {
      print $db->error() . '<br>' .$sql;
    }


  }
}
?>
