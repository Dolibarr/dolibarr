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

class GraphCa extends GraphBrouzouf
{

  Function GraphCa($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->contrat = 0;
    $this->ligne = 0;
    $this->titre = "Chiffre d'affaire (euros HT)";

    $this->barcolor = "green";
  }


  Function GraphDraw()
  {
    $sql = "SELECT tf.date, sum(tf.gain), sum(tf.cout_vente), sum(tf.fourn_montant)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";   

    if ($this->client == 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " WHERE tf.fk_facture is not null";    
      }
    elseif ($this->client > 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   
	$sql .= " WHERE fk_facture is not null";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;
      }
    elseif ($this->client == 0 && $this->contrat > 0 && $this->ligne == 0)
      {

	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   
	$sql .= " WHERE tf.fk_facture is not null";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.fk_contrat = ".$this->contrat;
      }
    elseif ($this->client == 0 && $this->contrat == 0 && $this->ligne > 0)
      {
	$sql .= " WHERE tf.fk_facture is not null";
	$sql .= " AND tf.fk_ligne = ".$this->ligne;
      }

    $sql .= " GROUP BY tf.date ASC";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$j = -1;
	$labels = array();
	$cf = array();
	$cv = array();
	$gg = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($i);	
	    
	    $cf[$i] = $row[3];
	    $cv[$i] = $row[2];
	    $g[$i]  = $row[1];
	    $labels[$i] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
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

    if ($num > 0)
      {
	$this->GraphMakeGraph($cv, $labels);
      }
  }

}   
?>
