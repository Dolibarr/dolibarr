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

class GraphAppelsDureeMoyenne extends GraphBrouzouf{


  Function GraphAppelsDureeMoyenne($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Durée moyenne des appels";

    //$this->type = "LinePlot";

    $this->barcolor = "pink";
  }


  Function GraphDraw()
  {
    $num = 0;
    $ligne = new LigneTel($this->db);
    
    if ($this->client == 0)
      {
	$sql = "SELECT date_format(date,'%Y%m'), sum(duree), count(duree)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
	$sql .= " GROUP BY date_format(date,'%Y%m') ASC ";
      }
    else
      {
	$sql = "SELECT date_format(td.date,'%Y%m'), sum(td.duree), count(td.duree)";
	$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as td";
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   

	$sql .= " WHERE td.ligne = s.ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;

	$sql .= " GROUP BY date_format(td.date,'%Y%m') ASC ";
      }
    
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$labels = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $labels[$i] = substr($row[0],4,2) . '/'.substr($row[0],2,2);
	    $datas[$i] = ($row[1] / $row[2] ) ;	    
	    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
	exit ;
      }
    

    if ($this->show_console)
      {
	print $this->client . " " . $cv[$i - 1]."\n";
      }    

    if ($num > 1)
      {
	$this->GraphMakeGraph($datas, $labels);
      }
  }

}   
?>
