<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
  \file       htdocs/project.class.php
  \ingroup    projet
  \brief      Fichier de la classe de gestion des projets
  \version    $Revision$
*/

/*!
  \class      Project
  \brief      Classe permettant la gestion des projets
*/

class Project {
  var $id;
  var $db;
  var $ref;
  var $title;
  var $socidp;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   */
  function Project($DB)
  {
    $this->db = $DB;
  }
  
  /*
   *    \brief      Crée un projet en base
   *    \param      user id utilisateur qui crée
   */

  function create($user)
  {
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet (ref, title, fk_soc, fk_user_creat) ";
    $sql .= " VALUES ('$this->ref', '$this->title', $this->socidp, ".$user->id.") ;";
    
    if (!$this->db->query($sql) ) 
      {
	print '<b>'.$sql.'</b><br>'.$this->db->error();	
      }    
  }
	
  /*
   *    \brief      Charge objet projet depuis la base
   *    \param      rowid       id du projet à charger
   */

  function fetch($rowid)
  {
    
    $sql = "SELECT title, ref FROM ".MAIN_DB_PREFIX."projet";
    $sql .= " WHERE rowid=".$rowid;

    $resql = $this->db->query($sql);
    if ($resql)
      {
	if ($this->db->num_rows($resql))
	  {
	    $obj = $this->db->fetch_object($resql);
	    
	    $this->id = $rowid;
	    $this->ref = $obj->ref;
	    $this->title = $obj->title;
	    
	    $this->db->free($resql);
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
		  $obj = $this->db->fetch_object();
	      
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
		  $obj = $this->db->fetch_object();
	      
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
