<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*
 * La tva collectée n'est calculée que sur les factures payées.
 *
 */
class ChargeSociales {
  var $id; 
  var $db;
  var $date_ech;
  var $date_pai;
  var $lib;
  var $type;
  var $lib_typ;
  var $amount;
  var $paye;
  var $periode;
  
  Function ChargeSociales($DB)
  {
    $this->db = $DB;
    
    return 1;
  }

  /*
   *
   *
   */
  Function fetch($id) 
    {
      $sql = "SELECT cs.rowid,".$this->db->pdate("cs.date_ech")." as date_ech,".$this->db->pdate("cs.date_pai")." as date_pai";
      $sql .=", cs.libelle as lib, cs.fk_type, cs.amount, cs.paye, cs.periode, c.libelle";
      $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as cs, ".MAIN_DB_PREFIX."c_chargesociales as c";
      $sql .= " WHERE cs.fk_type = c.id";
      $sql .=" AND cs.rowid = ".$id;

      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

          $this->id             = $obj->rowid;
	      $this->date_ech       = $obj->date_ech;
	      $this->date_pai       = $obj->date_pai;
	      $this->lib            = $obj->lib;
	      $this->type           = $obj->fk_type;
	      $this->type_libelle   = $obj->libelle;
	      $this->amount         = $obj->amount;
	      $this->paye           = $obj->paye;
	      $this->periode        = $obj->periode;

	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	  return 0;
	}
    }

  Function solde($year = 0) {

    $sql = "SELECT sum(f.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as f WHERE paye = 0";

    if ($year) {
      $sql .= " AND f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
    }
    
    $result = $this->db->query($sql);

    if ($result) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);
	return $obj->amount;
      } else {
	return 0;
      }

      $this->db->free();

    } else {
      print $this->db->error();
      return -1;
    } 
  }

}
/*
 * $Id$
 * $Source$
 */
?>
