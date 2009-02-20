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

class EnergieGroupe
{
  var $db ;
  var $user ;

  /**  \brief  Constructeur
   */
  function EnergieGroupe($DB, $user)
  {
    $this->db = $DB;
    $this->user = $user;
  }

  /** 
   * Lecture
   *
   */
  function fetch ($id)
  {
    $sql = "SELECT c.rowid, c.libelle";
    $sql .= " FROM ".MAIN_DB_PREFIX."energie_groupe as c";
    $sql .= " WHERE c.rowid = ".$id;
    
    $resql = $this->db->query($sql) ;
    
    if ( $resql )
      {
	$obj = $this->db->fetch_object($resql);
	
	$this->id              = $obj->rowid;
	$this->libelle         = $obj->libelle;
	
	$this->db->free();	
	return 0;
      }
    else
      {
	dol_syslog("EnergieGroupe::fetch Erreur");
	return -1;
      }
  }

  /** 
   * Creation
   *
   */
  function Create ($libelle)
  {
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."energie_groupe";
    $sql .= " (libelle, datec, fk_user_author, note)";
    $sql .= " VALUES (";
    $sql .= "'".trim($libelle)."'";
    $sql .= ",now()";
    $sql .= ",'".$this->user->id."'";
    $sql .= ",'".$libelle."');";

    $resql = $this->db->query($sql) ;

    if ( $resql )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."energie_groupe");

	return 0;
      }
    else
      {
	dol_syslog("EnergieGroupe::Create Erreur 1");
	dol_syslog($this->db->error());
	return -1;
      }
  }
}
?>
