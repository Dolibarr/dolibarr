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

/*!	\file auteur.class.php
		\brief Classe permettant de gèrer des auteurs
		\author	Rodolphe Quideville
		\version $Revision$
*/

/*! \class Auteur
		\brief Classe permettant de gèrer des auteurs
*/

class Auteur {
  var $db ;

  var $id ;
  var $nom;

  Function Auteur($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;
  }

  /*
   *
   *
   *
   */

	Function create($user) {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."auteur (fk_user_author) VALUES (".$user->id.")";

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

	Function liste_array ()
  {
    $ga = array();

    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."auteur ORDER BY nom";

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

    $sql = "UPDATE ".MAIN_DB_PREFIX."auteur ";
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

	Function liste_livre($id_type='', $status=0)
  {
    $ga = array();
    if ($id_type == 'oscid')
      {
	$sql = "SELECT a.oscid, ";
      }
    else
      {
	$sql = "SELECT a.rowid, ";
      }
    $sql .= " a.title FROM ".MAIN_DB_PREFIX."livre as a, ".MAIN_DB_PREFIX."livre_to_auteur as l";
    $sql .= " WHERE a.rowid = l.fk_livre AND l.fk_auteur = ".$this->id;
    if ($status)
      {
	$sql .= " AND a.status = 1";
      }
    $sql .= " ORDER BY a.title";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();

	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$row = $this->db->fetch_row($i);

		$ga[$row[0]] = $row[1];
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

	Function fetch ($id) {

    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."auteur WHERE rowid = $id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id      = $result["rowid"];
	$this->nom     = stripslashes($result["nom"]);

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

	Function delete() {

    $livres = $this->liste_livre();

    if (sizeof($livres) == 0)
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."auteur WHERE rowid = $this->id ";
	$return = $this->db->query($sql) ;
      }


  }


}
?>
