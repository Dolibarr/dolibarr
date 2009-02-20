<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class FactureTel {
  var $db;
  var $id;

  function FactureTel($DB, $id=0)
  {
    $this->db = $DB;

    return 1;
  }
  /*
   *
   *
   */
  function fetch($id)
    {
      $sql = "SELECT rowid, ligne, date, cout_vente, cout_vente_remise, fk_facture";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
      $sql .= " WHERE tf.rowid = ".$id;

      $resql = $this->db->query($sql);

      if ($resql)
	{
	  if ($this->db->num_rows($resql))
	    {
	      $obj = $this->db->fetch_object($resql);

	      $this->id                = $obj->rowid;
	      $this->ligne             = $obj->ligne;
	      $this->cout_vente        = $obj->cout_vente;
	      $this->cout_vente_remise = $obj->cout_vente_remise;
	      $this->fk_facture        = $obj->fk_facture;

	      $result = 0;
	    }
	  else
	    {
	      dol_syslog("FactureTel::Fetch() Error aucune facture avec cet id=$id", LOG_ERR);
	      $result = -2;
	    }

	  $this->db->free($resql);
	}
      else
	{
	  /* Erreur select SQL */
	  dol_syslog("FactureTel::Fetch() Error SQL id=$id", LOG_ERR);
	  $result = -1;
	}


      return $result;
  }

  /*
   * Met à jout la facture téléphonique avec le numéro de la facture
   * comptable
   */
  function affect_num_facture_compta($facid)
  {

    $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_facture";
    $sql .= " SET ";
    $sql .= " fk_facture = ".$facid ;
    $sql .= " WHERE rowid = ".$this->id;

    if ( $this->db->query($sql) )
      {
	return 0;
      }
    else
      {
	/* Erreur select SQL */
	dol_syslog("FactureTel::affect_num_facture_compta() Error SQL id=$facid", LOG_ERR);
	$result = -1;
	return 1;
      }
  }

  /*
   *
   */

  function get_comm_min_date($date)
  {
    $sql = "SELECT ".$this->db->pdate("min(date)");
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    $sql .= " WHERE ligne = '".$this->ligne."'";
    $sql .= " AND fk_telephonie_facture =".$this->id;

    $resql = $this->db->query($sql);

    if ($resql)
      {
	if ($this->db->num_rows($resql))
	  {
	    $row = $this->db->fetch_row($resql);
	
	    return $row[0];
	  }
      }

  }

  /*
   *
   */

  function get_comm_max_date($date)
  {
    $sql = "SELECT ".$this->db->pdate("max(date)");
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    $sql .= " WHERE ligne = '".$this->ligne."'";
    $sql .= " AND fk_telephonie_facture =".$this->id;

    $resql = $this->db->query($sql);

    if ($resql)
      {
	if ($this->db->num_rows($resql))
	  {
	    $row = $this->db->fetch_row($resql);
	
	    return $row[0];
	  }
      }
  }
}
?>
