<?php
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

  function Project($DB) {
    $this->db = $DB;
  }
	
  /*
   *
   *
   *
   */

  function create($creatorid) {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet (ref, title, fk_soc, fk_user_creat) ";
    $sql .= " VALUES ('$this->ref', '$this->title', $this->socidp, $creatorid) ;";
    
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

  function fetch($rowid) {

    $sql = "SELECT title, ref FROM ".MAIN_DB_PREFIX."projet WHERE rowid=$rowid;";

    if ($this->db->query($sql) ) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);

	$this->id = $rowid;
	$this->ref = $obj->ref;
	$this->title = $obj->title;

	$this->db->free();
      }
    } else {
      print $this->db->error();
    }
  }
	
  /*
   *
   *
   *
   */
	 
  function get_propal_list()
    {
      $propales = array();
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."propal WHERE fk_projet=$this->id;";
      
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
		
  /*
   *
   *
   *
   */
	 
  function liste_array($id_societe='')
    {
      $projets = array();

      $sql = "SELECT rowid, title FROM ".MAIN_DB_PREFIX."projet";

      if (isset($id_societe))
	{
	  $sql .= " WHERE fk_soc = $id_societe";
	}
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
	      
		  $projets[$obj->rowid] = $obj->title;
		  $i++;
		}
	    }
	  return $projets;
	}
      else
	{
	  print $this->db->error();
	}
      
    }
}
?>
