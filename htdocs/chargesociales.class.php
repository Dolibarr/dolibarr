<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 *
 *
 *
 *
 *
 */
class ChargeSociales {
  var $db;

  var $note;

  Function ChargeSociales($DB) {
    global $config;

    $this->db = $DB;
    
    return 1;
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
