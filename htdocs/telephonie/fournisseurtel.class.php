<?PHP
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

class FournisseurTelephonie {
  var $db;

  var $id;

  function FournisseurTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;
    return 1;
  }
  /**
   *
   *
   */
  function create()
  {
    $res = 0;
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " (nom, email_commande, commande_active)";
    $sql .= " VALUES ('".$this->nom."','".$this->email_commande."',1)";

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   */
  function fetch()
    {
      $sql = "SELECT f.rowid, f.nom, f.email_commande, f.commande_active";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
      $sql .= " WHERE f.rowid = ".$this->id;
	  
      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);


	      $this->email_commande = $obj->email_commande;
	      $this->commande_enable = $obj->commande_active;

	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("FournisseurTelephonie::Fetch Erreur id=".$this->id);
	      return -1;
	    }
	}
      else
	{
	  dolibarr_syslog("FournisseurTelephonie::Fetch Erreur SQL id=".$this->id);
	  return -2;
	}
    }
  /**
   *
   *
   */
  function active()
  {
    $res = 0;
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " SET  commande_active = 1";
    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
  /**
   *
   *
   */
  function desactive()
  {
    $res = 0;
    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_fournisseur";
    $sql .= " SET  commande_active = 0";
    $sql .= " WHERE rowid = ".$this->id;

    if (! $this->db->query($sql) )
      {
	$res = -1;
      }
    return $res;
  }
}

?>
