<?php
/* Copyright (c) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**	  
	 \file       htdocs/usergroup.class.php
	 \brief      Fichier de la classe des groupes d'utilisateur
	 \author     Rodolphe Qiedeville
	 \version    $Revision$
*/

/**    
       \class      User
       \brief      Classe permettant la gestion des groupes d'utilisateur
*/

class UserGroup
{
  var $db;
	
  var $id;
  var $label;

  /**
   *    \brief Constructeur de la classe
   *    \param  $DB         handler accès base de données
   */
	 
  function UserGroup($DB)
  {
    $this->db = $DB;
    
    return 0;
  }

  /**
   *    \brief      Ajoute un droit a l'utilisateur
   *    \param      rid        id du droit à ajouter
   */
	 

  /**
   *    \brief      Charge un objet user avec toutes ces caractéristiques depuis un login
   *    \param      login   login a charger
   */
	 
  function fetch($id)
  {      
    $this->id = $id;

    $sql = "SELECT g.rowid, g.nom, g.note FROM ".MAIN_DB_PREFIX."usergroup as g";
    $sql .= " WHERE g.rowid = ".$this->id;
    
      
    $result = $this->db->query($sql);

    if ($result) 
      {
	if ($this->db->num_rows($result)) 
	  {
	    $obj = $this->db->fetch_object($result);

	    $this->id = $obj->rowid;
	    $this->nom  = $obj->nom;
	    $this->note = $obj->note;
	    
	  }
	$this->db->free($result);
	
      }
    else
      {
	dolibarr_syslog("UserGroup::Fetch Erreur");
      }
  }

  /**
   *    \brief  Efface un groupe de la base
   */
	 
  function delete()
  {

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup";
    $sql .= " WHERE rowid = ".$this->id;

    if ($this->db->query($sql)) 
      {
	
      }

  }

  /**
   *        \brief      Crée un groupe en base
   *        \return     si erreur <0, si ok renvoie id groupe créé
   */
	 
  function create()
  {

    $sql = "INSERT into ".MAIN_DB_PREFIX."usergroup (datec,nom)";
    $sql .= " VALUES(now(),'$this->nom')";

    $result=$this->db->query($sql);
    if ($result)
    {
        $table =  "".MAIN_DB_PREFIX."usergroup";
        $this->id = $this->db->last_insert_id($table);

        if ($this->update() < 0) return -2;

        return $this->id;
    }
    else
    {
        dolibarr_syslog("UserGroup::Create");
        return -1;
    }
  }


  /**
   *    \brief      Mise à jour en base d'un utilisateur
   *    \return     <0 si echec, >=0 si ok
   */
  function update()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."usergroup SET ";
        $sql .= " note = '$this->note'";
        $sql .= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->affected_rows())
            {
                return 1;
            }
            return 0;
        }
        else
        {
            dolibarr_print_error($this->db);
            return -2;
        }

   }
   
}

?>
