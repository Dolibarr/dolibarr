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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/brouzouf.class.php");

class GraphCaMoyen extends GraphBrouzouf{


  Function GraphCaMoyen($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->show_console = 0;

    $this->client = 0;
    $this->titre = "Chiffre d'affaire moyen par client (euros HT)";

    $this->barcolor = "yellow";
  }


  Function GraphDraw()
  {
    $num = 0;
    $ligne = new LigneTel($this->db);
        
    $sql = "SELECT date, sum(gain), sum(cout_vente), sum(fourn_montant)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";   
    $sql .= " WHERE fk_facture is not null";    
    $sql .= " GROUP BY date ASC";
    
    $result = $this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	$labels = array();
	$cf = array();
	$cv = array();
	$gg = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $g[$i]  = $row[1];	    
	    $cv[$i] = $row[2];
	    $cf[$i] = $row[3];

	    $labels[$i] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }
    
    $sql = "SELECT f.date, count(l.fk_client_comm)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";   
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";   
    $sql .= " WHERE f.fk_facture is not null";
    $sql .= " AND l.rowid = f.fk_ligne";
    $sql .= " GROUP BY f.date ASC";
    
    $result = $this->db->query($sql);
    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;

	$nbc = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $nbc[$i]  = $row[1];	    
	    
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
	print $this->client . " " . $cv[$i - 1]."\n";
      }



    for ($j = 0 ; $j < sizeof($nbc) ; $j++)
      {
	$camoy[$j] = $cv[$j] / $nbc[$j];

	if ($this->show_console)
	  {
	    print $labels[$j] . "\t" . $nbc[$j] ."\t" . price($cv[$j]) ."\t\t" . price($camoy[$j]) ."\n";
	  }
      }


    if ($num > 0)
      {
	$this->GraphMakeGraph($camoy, $labels);
      }
  }

}   
?>
