<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
   \file       htdocs/task.class.php
   \ingroup    projet
   \brief      Fichier de la classe de gestion des taches
   \version    $Revision$
*/

/**
  \class      Task
  \brief      Classe permettant la gestion des taches
*/

class Task {
  var $id;
  var $db;
  
  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   */
  function Task($DB)
  {
    $this->db = $DB;
  }
  
  /*
   *    \brief      Charge objet projet depuis la base
   *    \param      rowid       id du projet à charger
   */

  function fetch($rowid)
  {
    
    $sql = "SELECT title, fk_projet, duration_effective, statut";
    $sql .= " FROM ".MAIN_DB_PREFIX."projet_task";
    $sql .= " WHERE rowid=".$rowid;
    
    $resql = $this->db->query($sql);
    if ($resql)
      {
	if ($this->db->num_rows($resql))
	  {
	    $obj = $this->db->fetch_object($resql);
	    
	    $this->id = $rowid;
	    $this->title = $obj->title;
	    $this->statut = $obj->statut;
	    $this->projet_id = $obj->fk_projet;
	    
	    $this->db->free($resql);

	    return 0;
	  }
	else
	  {
	    return -1;
	  }
      }
    else
      {
	print $this->db->error();
	return -2;
      }
  }
}
?>
