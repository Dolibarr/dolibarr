<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

class CActioncomm {
  var $id;
  var $libelle;

  function CActioncomm($DB=0)
    {
      $this->db = $DB;
    }
  /*
   * Récupération des données
   *
   */

  function fetch($db, $id)
    {

      $sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE id=$id;";
      
      if ($db->query($sql) )
	{
	  if ($db->num_rows())
	    {
	      $obj = $db->fetch_object();
	      
	      $this->id = $id;
	      $this->libelle = $obj->libelle;
	      
	      $db->free();

	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
      else
	{
	  print $db->error();
	  return -1;
	}    
    }
  /*
   * \brief     Renvoi la liste des type d'actions existant
   * \param     active  1 ou 0 pour un filtre sur l'etat actif ou non ('' par defaut)
   *
   */
  function liste_array($active='')
  {
    $ga = array();

    $sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_actioncomm";
    if ($active != '') {
        $sql.=" WHERE active=$active";
    }
    $sql .= " ORDER BY id";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object();
		
		$ga[$obj->id] = $obj->libelle;
		$i++;
	      }
	  }
	return $ga;
      }
    else
      {
    dolibarr_print_error($this->db);
      }    
  }

  
  /*
   * Renvoie le nom d'une action a partir d'un id
   *
   */
  function get_nom($id)
    {

      $sql = "SELECT libelle nom FROM ".MAIN_DB_PREFIX."c_actioncomm WHERE id='$id';";
      
      $result = $this->db->query($sql);
      
    if ($result)
      {
    	if ($this->db->num_rows())
    	  {
    	    $obj = $this->db->fetch_object($result);
    	    return $obj->nom;
    	  }
    	$this->db->free();
       }
     else {
        dolibarr_print_error($db);   
       }    
       
    }
   
  
}    
?>
