<?PHP
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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/pie.class.php");

class GraphContratModeReglement extends GraphPie {

  Function GraphContratModeReglement($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Mode de réglement des contrats";

    $this->barcolor = "yellow";
    $this->showframe = true;
  }

  Function GraphMakeGraph()
  {
    $num = 0;

    $sql = "SELECT mode_paiement, count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat";
    //    $sql .= " WHERE statut = 3";
    $sql .= " GROUP BY mode_paiement";
    
    $result = $this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	$datas = array();
	$labels = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();
	    
	    $datas[$i] = $row[1];
	    $labels[$i] = $row[0];
	    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }
    
    $this->GraphDraw($this->file, $datas, $labels);
  }
}   
?>
