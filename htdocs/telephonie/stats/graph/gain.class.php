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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/brouzouf.class.php");

class GraphGain extends GraphBrouzouf{


  Function GraphGain($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Gain (euros HT)";

    $this->barcolor = "blue";
  }


  Function GraphDraw()
  {
    $num = 0;
    $ligne = new LigneTel($this->db);
    
    if ($this->client == 0)
      {
	$sql = "SELECT date, sum(gain), sum(cout_vente), sum(fourn_montant)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";   
	$sql .= " WHERE fk_facture is not null";    
	$sql .= " GROUP BY date ASC";
      }
    else
      {
	$sql = "SELECT date, sum(gain), sum(cout_vente), sum(fourn_montant)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";   
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   
	$sql .= " WHERE fk_facture is not null";
	$sql .= " AND s.rowid = fk_ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;
	$sql .= " GROUP BY date ASC";
      }
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	$j = -1;
	$labels = array();
	$cf = array();
	$cv = array();
	$gg = array();
	
	$this->total_gain = 0;
	$this->total_ca = 0;
	$this->total_cout = 0;

	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);	
	    
	    $cf[$i] = $row[3];
	    $cv[$i] = $row[2];
	    $g[$i]  = $row[1];

	    $labels[$i] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
	    $this->total_gain += $row[1];
	    $this->total_ca   += $row[2];
	    $this->total_cout += $row[3];

	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }
    
    if ($this->show_console)
      {
	print $this->client . " " . $g[$i - 1]."\n";
      }    


    if ($num > 0)
      {
	$this->GraphMakeGraph($g, $labels);
      }
  }

}   
?>
