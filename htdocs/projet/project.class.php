<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*! \file htdocs/projet/project.class.php
        \ingroup    projet
		\brief      Fichier de la classe de gestion des projets
		\version    $Revision$
*/


/*! \class Project
        \brief      Classe de gestion des projets
*/

class Project {
  var $id;
  var $db;
  var $ref;
  var $title;
  var $socidp;
  var $societe;

  function Project($DB)
    {
      $this->db = $DB;
      $this->societe = new Societe($DB);
    }
  /*
   *
   *
   *
   */

  function create($creatorid) 
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet (ref, title, fk_soc, fk_user_creat, dateo) ";
      $sql .= " VALUES ('$this->ref', '$this->title', $this->socidp, $creatorid, now()) ;";
    
      if ($this->db->query($sql) ) 
	{
	  return $this->db->last_insert_id();
	}
      else
	{
	  print '<b>'.$sql.'</b><br>'.$this->db->error();	  
	}    
    }
  /*
   *
   *
   *
   */
  function update() 
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."projet ";
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
  function delete() 
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."projet WHERE rowid = $this->id";

      if ($this->db->query($sql) ) 
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."propal SET fk_projet = 0 WHERE fk_projet = $this->id";
	  if ($this->db->query($sql) ) 
	    {
	      $sql = "UPDATE ".MAIN_DB_PREFIX."facture SET fk_projet = 0 WHERE fk_projet = $this->id";

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
  function fetch($rowid)
    {
      
      $sql = "SELECT fk_soc, title, ref FROM ".MAIN_DB_PREFIX."projet WHERE rowid=$rowid;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id = $rowid;
	      $this->ref = $obj->ref;
	      $this->title = $obj->title;
	      $this->titre = $obj->title;
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
  function get_facture_list()
    {
      $factures = array();
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE fk_projet=$this->id;";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();
	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $factures[$i] = $obj->rowid;
		  
		  $i++;
		}
	      $this->db->free();
	      /*
	       *  Retourne un tableau contenant la liste des factures associees
	       */
	      return $factures;
	    }
	}
      else
	{
	  print $this->db->error() . '<br>' .$sql;
	}
    }
  /**
   * Renvoie la liste des commande associées au projet
   *
   *
   */
  function get_commande_list()
    {
      $commandes = array();
      $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande WHERE fk_projet=$this->id;";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();
	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $commandes[$i] = $obj->rowid;
		  
		  $i++;
		}
	      $this->db->free();
	      /*
	       *  Retourne un tableau contenant la liste des commandes associees
	       */
	      return $commandes;
	    }
	}
      else
	{
	  print $this->db->error() . '<br>' .$sql;
	}
    }

}
?>
