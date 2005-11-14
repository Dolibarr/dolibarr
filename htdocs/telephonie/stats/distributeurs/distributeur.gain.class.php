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

class GraphDistributeurGain extends GraphBar {

  Function GraphDistributeurGain($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->year = strftime("%Y",time());
    $this->titre = "Gain mensuel ".$this->year;

    $this->barcolor = "pink";
    $this->showframe = true;
  }

  Function GraphMakeGraph($distributeur=0)
  {
    $num = 0;
    $year = strftime("%Y",time());

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
    if ($distributeur > 0)
      {
	$sql .= " WHERE graph='distributeur.gain.mensuel.".$distributeur."';";
      }
    else
      {
	$sql .= " WHERE graph='distributeur.gain.mensuel'";
      }
    $resql = $this->db->query($sql);


    if ($distributeur > 0) {
      $sql = "SELECT date_format(f.date,'%Y%m'), sum(f.gain)";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
      $sql .= " WHERE l.rowid = f.fk_ligne";
      $sql .= " AND l.fk_commercial_sign = dc.fk_user";
      $sql .= " AND dc.fk_distributeur = ".$distributeur;
      $sql .= " GROUP BY date_format(f.date,'%Y%m') ASC";     
    } else {
      $sql = "SELECT legend, sum(valeur)";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
      $sql .= " WHERE graph like 'distributeur.gain.mensuel.%'";
      $sql .= " GROUP BY legend ORDER BY ord ASC";
    }

    $resql = $this->db->query($sql);

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$j = -1;
	$datas = array();
	$gains = array();
	$labels = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	
	    	    
	    $datas[$i] = $row[1];
	    $gains[$row[0]] = $row[1];
	    $labels[$i] = substr($row[0],-2)."/".substr($row[0],2,2);
	    
	    $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
	    $sqli .= " (graph,ord,legend,valeur)";
	    if ($distributeur > 0) {
	      $sqli .= " VALUES ('distributeur.gain.mensuel.".$distributeur."'";
	    }
	    else {
	      $sqli .= " VALUES ('distributeur.gain.mensuel'";
	    }
	    $sqli .= ",'$i','".$row[0]."','".$datas[$i]."');";

	    $resqli = $this->db->query($sqli);

	    $i++;
	  }
	
	$this->db->free($resql);
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }                  

    for ($i = 1 ; $i < 13 ; $i++)
      {
	$idx = $year.substr('0'.$i,-2);
	$datas[$i-1] = $gains[$idx];
	$labels[$i-1] = $i;
      }

    if (sizeof($datas))
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }
  }
}   
?>
