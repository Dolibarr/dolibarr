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
    $this->contrat = 0;
    $this->ligne = 0;
    $this->titre = "Gain (euros HT)";

    $this->barcolor = "blue";
  }


  Function GraphDraw()
  {
    $num = 0;

    $sql = "SELECT tf.date, sum(tf.gain)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
    
    if ($this->client == 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " WHERE tf.fk_facture is not null";    
	$sql .= " GROUP BY tf.date ASC";
      }
    elseif ($this->client > 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   
	$sql .= " WHERE tf.fk_facture is not null";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;
	$sql .= " GROUP BY tf.date ASC";
      }
    elseif ($this->client == 0 && $this->contrat > 0 && $this->ligne == 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";
	$sql .= " WHERE tf.fk_facture is not null";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.fk_contrat = ".$this->contrat;
	$sql .= " GROUP BY tf.date ASC";
      }
    elseif ($this->client == 0 && $this->contrat == 0 && $this->ligne > 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";
	$sql .= " WHERE tf.fk_facture is not null";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.rowid = ".$this->ligne;
	$sql .= " GROUP BY tf.date ASC";
      }
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	$labels = array();

	$this->total_gain = 0;

	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();
	    
	    $g[$i] = $row[1];

	    $labels[$i] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
	    $this->total_gain += $row[1];

	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }

    if ($this->client > 0)
      {
    
	/*
	 * Comptage des remises exceptionnelles
	 *
	 */
	$sql = "SELECT sr.amount_ht";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_facture as tf";
	$sql .= " , ".MAIN_DB_PREFIX."societe_remise_except as sr";
	
	$sql .= " WHERE sr.fk_facture = tf.fk_facture";
	$sql .= " AND s.rowid = tf.fk_ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;
	$sql .= " GROUP BY tf.fk_facture";
	
	if ($this->db->query($sql))
	  {
	    $numr = $this->db->num_rows();
	    $i = 0;
	    
	    while ($i < $numr)
	      {
		$row = $this->db->fetch_row($i);	
		if ( $row[0] > 0)
		  {
		    $this->total_gain = ($this->total_gain - $row[0]);
		  }
		$i++;
	      }
	  }
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
