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

class pnArticle {
  var $db ;

  var $id ;

  var $titre;
  var $body;

  Function pnArticle($DB, $id=0) {
    $this->db = $DB;
    $this->id   = $id ;
  }  

  /*
   *
   *
   */
  Function update($id, $user)
  {

    if (strlen($this->annee)==0)
      {
	$this->annee = 0;
      }

    $sql = "UPDATE ". PN_DB_NAME . "." . PN_TABLE_STORIES_NAME;
    $sql .= " SET pn_hometext = '" . nl2br(trim($this->body)) ."'";
    //$sql .= ", pn_title = '" . trim($this->titre) ."'";


    $sql .= " WHERE pn_sid = " . $id;

    if ( $this->db->query($sql) )
      {
	return 1;
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
  Function fetch ($id) {
    
    $sql = "SELECT p.pn_sid, p.pn_title, p.pn_hometext FROM " . PN_DB_NAME . "." . PN_TABLE_STORIES_NAME . " as p";

    if ($id)
      {
	$sql .= " WHERE p.pn_sid = $id";
      }

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id         = $result["pn_id"];
	$this->titre      = $result["pn_title"];
	$this->body       = $result["pn_hometext"];

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


}
?>
