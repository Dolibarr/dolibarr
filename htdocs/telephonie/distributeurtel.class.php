<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class DistributeurTelephonie {
  var $db;
  var $id;

  /**
   * Créateur
   *
   *
   */
  function DistributeurTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;

    return 0;
  }
  /**
   *
   *
   */
  function fetch($id)
    {
      $this->id = $id;

      $sql = "SELECT d.rowid, d.nom";
      $sql .= " , d.avance_pourcent";
      $sql .= " , d.rem_pour_prev, d.rem_pour_autr";

      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
      $sql .= " WHERE d.rowid = ".$this->id;
	  
      if ($this->db->query($sql))
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->nom          = stripslashes($obj->nom);
	      $this->remun_avance = $obj->remun_avance;

	      $this->remun_pourcent_prev = $obj->remun_pourcent_prev;
	      $this->remun_pourcent_autr = $obj->remun_pourcent_autr;

	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("DistributeurTelephonie::Fetch Erreur id=".$this->id);
	      return -1;
	    }
	}
      else
	{
	  dolibarr_syslog("DistributeurTelephonie::Fetch Erreur SQL id=".$this->id);
	  return -2;
	}
    }


}
?>
