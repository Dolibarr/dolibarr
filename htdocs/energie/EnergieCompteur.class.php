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
   \file       htdocs/energie/EnergieCompteur.class.php
   \ingroup    energie
   \brief      Fichier des classes de compteur
   \version    $Revision$
*/


/**	
   \class      Compteur
   \brief      Classe de gestion des compteurs
*/

class EnergieCompteur
{
  var $db ;
  var $id ;
  var $user;

  /**  \brief  Constructeur
   */
  function EnergieCompteur($DB, $user)
  {
    $this->db = $DB;
    $this->user = $user;

    $this->energies[1] = "Electricité";
    $this->energies[2] = "Eau";
    $this->energies[3] = "Gaz naturel";

    $this->couleurs[1] = "gray";
    $this->couleurs[2] = "blue";
    $this->couleurs[3] = "yellow";
  }


  /** 
   * Lecture
   *
   */
  function fetch ($id)
  {
    $sql = "SELECT c.rowid, c.libelle, fk_energie";
    $sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur as c";
    $sql .= " WHERE c.rowid = ".$id;
    
    $resql = $this->db->query($sql) ;
    
    if ( $resql )
      {
	$obj = $this->db->fetch_object($resql);
	
	$this->id              = $obj->rowid;
	$this->libelle         = $obj->libelle;
	$this->energie         = $obj->fk_energie;
	$this->db->free();
	
	return 0;
      }
    else
      {
	dol_syslog("");
	return -1;
      }
  }

  /** 
   * Lecture
   *
   */
  function Create ($libelle, $energie)
  {
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."energie_compteur";
    $sql .= " (libelle, datec, fk_user_author, fk_energie, note)";
    $sql .= " VALUES (";
    $sql .= "'".trim($libelle)."'";
    $sql .= ",now()";
    $sql .= ",'".$this->user->id."'";
    $sql .= ",'".$energie."'";
    $sql .= ",'');";

    $resql = $this->db->query($sql) ;

    if ( $resql )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."energie_compteur");

	return 0;
      }
    else
      {
	dol_syslog("EnergieCompteur::Create Erreur 1");
	dol_syslog($this->db->error());
	return -1;
      }
  }

  /** 
   * Ajout d'une valeur relevée
   *
   */
  function AjoutReleve ($date_releve, $valeur)
  {
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."energie_compteur_releve";
    $sql .= " (fk_compteur, date_releve, valeur, datec, fk_user_author)";
    $sql .= " VALUES (";
    $sql .= "'".$this->id."'";
    $sql .= ",'".$this->db->idate($date_releve)."'";
    $sql .= ",'".$valeur."'";
    $sql .= ",now()";
    $sql .= ",'".$this->user->id."');";
   
    $resql = $this->db->query($sql) ;

    if ( $resql )
      {
	return 0;
      }
    else
      {
	dol_syslog("EnergieCompteur::AjoutReleve Erreur 1");
	dol_syslog($this->db->error());
	return -1;
      }
  }
  /** 
   * Suppression d'une valeur relevée
   *
   */
  function DeleteReleve ($rowid)
  {
    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."energie_compteur_releve";
    $sql .= " WHERE rowid = '".$rowid."';";
   
    $resql = $this->db->query($sql) ;

    if ( $resql )
      {
	return 0;
      }
    else
      {
	dol_syslog("EnergieCompteur::AjoutReleve Erreur 1");
	dol_syslog($this->db->error());
	return -1;
      }
  }
  /** 
   * Ajout d'une valeur relevée
   *
   */
  function AddGroup ($groupe)
  {
    $sql  = "INSERT INTO ".MAIN_DB_PREFIX."energie_compteur_groupe";
    $sql .= " (fk_energie_compteur, fk_energie_groupe)";
    $sql .= " VALUES ('".$this->id."','".$groupe."');";
   
    $resql = $this->db->query($sql);

    if ( $resql )
      {
	return 0;
      }
    else
      {
	dol_syslog("EnergieCompteur::AddGroup Erreur 1");
	dol_syslog($this->db->error());
	return -1;
      }
  }
  /** 
   * 
   *
   */
  function GroupsAvailable ()
  {
    $this->groups_available = array();

    $sql = "SELECT g.rowid, g.libelle";
    $sql .= " FROM ".MAIN_DB_PREFIX."energie_groupe as g";
    
    $resql = $this->db->query($sql) ;
    
    if ( $resql )
      {
	$num = $this->db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($resql);
	
	    $this->groups_available[$obj->rowid] = $obj->libelle;
	    $i++;
	  }
	$this->db->free();
	
	return 0;
      }
    else
      {
	dol_syslog("");
	return -1;
      }
  }
}
?>
