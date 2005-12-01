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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/bar.class.php");

class GraphCommercialGain extends GraphBar {

  Function GraphCommercialGain($DB, $file, $year=0)
  {
    $this->db = $DB;
    $this->file = $file;
    $this->year = $year;
    $this->client = 0;
    $this->titre = "Gain mensuel";
    if ($this->year > 0)
      {
	$this->titre = "Gain mensuel ".$this->year;
      }
    $this->barcolor = "pink";
    $this->showframe = true;
  }

  Function GraphMakeGraph($commercial=0)
  {
    $num = 0;

    if ($commercial > 0)
      {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";     
	$sql .= " WHERE graph='commercial.gain.mensuel.".$commercial."'";
	if ($this->year > 0)
	  {
	    $sql .= " AND legend like '".$this->year."%'";
	  }
	$resql = $this->db->query($sql);
      }

    $sql = "SELECT date_format(f.date,'%Y%m'), sum(f.gain)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
    $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.rowid = f.fk_ligne";
    $sql .= " AND l.fk_commercial_sign = ".$commercial;
    if ($this->year > 0)
      {
	$sql .= " AND date_format(f.date,'%Y')='".$this->year."'";
      }
    $sql .= " GROUP BY date_format(f.date,'%Y%m') ASC";

    $resql = $this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$j = -1;
	$datas = array();
	$labels = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	
	    	    
	    $datas[$i] = $row[1];
	    $labels[$i] = substr($row[0],-2);

	    if ($commercial > 0)
	      {
		$sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
		$sqli .= " (graph,ord,legend,valeur)";
		$sqli .= " VALUES ('commercial.gain.mensuel.".$commercial."'";
		$sqli .= ",'$i','".$row[0]."','".$datas[$i]."');";
		$resqli = $this->db->query($sqli);
	      }

	    $i++;
	  }
	
	$this->db->free($resql);
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }                  

    if (sizeof($datas))
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }

  }
}   
?>
