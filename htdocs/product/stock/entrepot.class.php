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

class Entrepot
{
  var $db ;

  var $id ;
  var $libelle;
  var $description;
  var $statut;

  Function Entrepot($DB)
    {
      $this->db = $DB;

      $this->statuts[0] = "fermé";
      $this->statuts[1] = "ouvert";
    }
  /*
   *
   *
   */
  Function create($user) 
    {

      if ($this->db->query("BEGIN") )
	{

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (datec, fk_user_author)";
	  $sql .= " VALUES (now(),".$user->id.")";

	  if ($this->db->query($sql) )
	    {
	      $id = $this->db->last_insert_id();	      
	      if ($id > 0)
		{
		  $this->id = $id;

		  if ( $this->update($id, $user) )
		    {
		      $this->db->query("COMMIT") ;
		      return $id;
		    }
		  else
		    {
		      $this->db->query("ROLLBACK") ;
		    }


		}
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	    }
	}
    }
  /*
   *
   */
  Function update($id, $user)
    {
      if (strlen(trim($this->libelle)))
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
	  $sql .= " SET label = '" . trim($this->libelle) ."'";
	  $sql .= ",description = '" . trim($this->description) ."'";
	  $sql .= ",statut = " . $this->statut ;
	  
	  $sql .= " WHERE rowid = " . $id;
	  
	  if ( $this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	    }
	}
      else
	{
	  $this->mesg_error = "Vous devez indiquer une référence";
	  return 0;
	}
    }
  /*
   *
   *
   *
   */
  Function fetch ($id)
    {    
      $sql = "SELECT rowid, label, description, statut";
      $sql .= " FROM ".MAIN_DB_PREFIX."entrepot WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id             = $result["rowid"];
	  $this->ref            = $result["ref"];
	  $this->libelle        = stripslashes($result["label"]);
	  $this->description    = stripslashes($result["description"]);
	  $this->statut         = $result["statut"];

	  $this->db->free();
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  return -1;
	}
  }
  /**
   * Renvoie la liste des entrepôts ouverts
   *
   */
  Function list_array()
  {
    $liste = array();

    $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."entrepot WHERE statut = 1";

      $result = $this->db->query($sql) ;
      $i = 0;
      $num = $this->db->num_rows();

      if ( $result )
	{
	  while ($i < $num)
	    {
	      $row = $this->db->fetch_row($i);
	      $liste[$row[0]] = $row[1];
	      $i++;
	    }
	  $this->db->free();
	}
      return $liste;
  }
}
?>
