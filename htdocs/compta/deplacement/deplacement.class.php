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
 */

class Deplacement
{
  var $db;
  var $id;
  var $user;
  var $km;
  var $note;

  /*
   * Initialistation automatique de la classe
   */
  Function Deplacement($DB)
    {
      $this->db = $DB;
    
      return 1;
  }
  /*
   *
   */
  Function create($user)
    {
      $sql = "INSERT INTO llx_deplacement (datec, fk_user_author) VALUES (now(), $user->id)";

      $result = $this->db->query($sql);
      if ($result)
	{
	  $this->id = $this->db->last_insert_id();
	  if ( $this->update($user) ) 
	    {
	      return $this->id;
	    }
	}
    }
  /*
   *
   */
  Function update($user)
    {
      if (strlen($this->km)==0)
	$this->km = 0;

      $sql = "UPDATE llx_deplacement ";
      $sql .= " SET km = $this->km";
      $sql .= " , dated = '".$this->db->idate($this->date)."'";
      $sql .= " , fk_user = $this->userid";
      $sql .= " , fk_soc = $this->socid";
      $sql .= " WHERE rowid = ".$this->id;

      $result = $this->db->query($sql);
      if ($result)
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<br>".$sql;
	  return 0;
	}
    }
  /*
   *
   */
  Function fetch ($id)
    {    
      $sql = "SELECT rowid, fk_user, km, fk_soc,".$this->db->pdate("dated")." as dated";
      $sql .= " FROM llx_deplacement WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id       = $result["rowid"];
	  $this->date     = $result["dated"];
	  $this->userid   = $result["fk_user"];
	  $this->socid    = $result["fk_soc"];
	  $this->km       = $result["km"];
	  return 1;
	}
    }
  /*
   *
   */
  Function delete()
    {
      $sql = "DELETE FROM llx_deplacement WHERE rowid = $this->id)";

      $result = $this->db->query($sql);
      if ($result)
	{
	  return 1;
	}
      else
	{
	  return 0;
	}
    }
  /*
   *
   */

}

?>
