<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class LolixSociete {
  var $db;

  var $id;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $tel;
  var $fax;
  var $url;
  var $siren;
  var $client;
  var $note;
  var $fournisseur;
 

  Function LolixSociete($DB, $id=0)
  {
    global $config;

    $this->db = $DB;
    $this->id = $id;

    return 1;
  }
  /*
   *
   *
   *
   */
  Function update($id)
  {
    $sql = "SELECT short_desc";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_details as sd";
    $sql .= " WHERE sd.fk_soc = ".$this->id;
    
    if ($this->db->query($sql))
      {
	if ($this->db->num_rows() == 0)
	  {
	    $this->db->free();
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_details (fk_soc) VALUES ($id)";
	    $result = $this->db->query($sql);
	  }
      }

    $sql = "UPDATE ".MAIN_DB_PREFIX."societe_details ";
    $sql .= " SET short_desc = '" . trim($this->short_desc) ."'";
    $sql .= ", long_desc = '" . trim($this->long_desc) ."'";
    $sql .= ", contact_nom = '" . trim($this->contact_nom) ."'";
    $sql .= ", contact_email = '" . trim($this->contact_email) ."'";
    $sql .= ", date_creation = ',".$this->db->idate($this->date_creation). "'";
    $sql .= " WHERE fk_soc = " . $id .";";
    
    if ($this->db->query($sql)) 
      {

      }
    else
      {
	print $this->db->error();
      }
  }

  /*
   *
   *
   */
  Function fetch($socid)
    {
      $this->id = $socid;

      $sql = "SELECT s.nom,s.active,s.siren,s.tel,s.url,s.fax,";
      $sql .= $this->db->pdate("s.datec")." as dc";
      $sql .= " FROM lolixfr.societe as s";
      $sql .= " WHERE s.idp = ".$this->id;

      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object();

	      $this->nom = $obj->nom;
	      $this->active = $obj->active;
	      $this->tel = $obj->tel;
	      $this->fax = $obj->fax;
	      $this->url = $obj->url;

	      $this->date_creation = $obj->dc;

	      $this->siren = $obj->siren;


	      return 1;
	      
	    }
	  else
	    {
	      print "Error";
	    }
	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	}
  }
  /*
   *
   *
   */
  
}

?>
