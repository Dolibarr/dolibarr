<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Project {
  var $id;
  var $db;
  var $ref;
  var $title;
  var $socidp;
  var $societe;

  Function Project($DB)
    {
      $this->db = $DB;
      $this->societe = new Societe($DB);
    }
  /*
   *
   *
   *
   */

  Function create($creatorid) 
    {
      $sql = "INSERT INTO llx_projet (ref, title, fk_soc, fk_user_creat, dateo) ";
      $sql .= " VALUES ('$this->ref', '$this->title', $this->socidp, $creatorid, now()) ;";
    
      if (!$this->db->query($sql) ) 
	{
	  print '<b>'.$sql.'</b><br>'.$this->db->error();	  
	}    
    }
  /*
   *
   *
   *
   */
  Function update() 
    {
      $sql = "UPDATE llx_projet ";
      $sql .= " SET ref = '$this->ref', title = '$this->title'";
      $sql .= " WHERE rowid = $this->id";

      if (!$this->db->query($sql) ) 
	{
	  print '<b>'.$sql.'</b><br>'.$this->db->error();	  
	}    
    }
  /*
   *
   *
   */
  Function delete() 
    {
      $sql = "DELETE FROM llx_projet WHERE rowid = $this->id";

      if ($this->db->query($sql) ) 
	{
	  $sql = "UPDATE llx_propal SET fk_projet = 0 ";
	  if ($this->db->query($sql) ) 
	    {
	      $sql = "UPDATE llx_facture SET fk_projet = 0 ";

	      if ($this->db->query($sql) ) 
		{
	      
		}    
	    }    
	}
    }
  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {
      
      $sql = "SELECT fk_soc, title, ref FROM llx_projet WHERE rowid=$rowid;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id = $rowid;
	      $this->ref = $obj->ref;
	      $this->title = $obj->title;
	      $this->societe->id = $obj->fk_soc;
	      $this->db->free();
	    }
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
  Function get_propal_list()
    {
      $propales = array();
      $sql = "SELECT rowid FROM llx_propal WHERE fk_projet=$this->id;";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();
	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $propales[$i] = $obj->rowid;
		  
		  $i++;
		}
	      $this->db->free();
	      /*
	       *  Retourne un tableau contenant la liste des propales associees
	       */
	      return $propales;
	    }
	}
      else
	{
	  print $this->db->error() . '<br>' .$sql;
	}
    }
}
?>
