<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


class ProjetDon {
  var $id;
  var $db;
  var $ref;
  var $title;
  var $socidp;

  function ProjetDon($DB) {
    $this->db = $DB;
  }

  /*
   *
   *
   *
   */
	 
	 function liste_array($id_societe='')
    {
      $projets = array();

      $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."don_projet";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();

	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
	      
		  $projets[$obj->rowid] = $obj->libelle;
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
