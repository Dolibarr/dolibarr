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

class GraphDistributeurCommission extends GraphBar {

  Function GraphDistributeurCommission($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;
    $this->year = strftime("%Y",time());
    $this->client = 0;
    $this->titre = "Commissions mensuelles reversées ".$this->year;

    $this->barcolor = "orange";
    $this->showframe = true;
  }

  Function GraphMakeGraph($distributeur=0)
  {
    $num = 0;
    $this->no_xaxis_title=1;
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
    if ($distributeur > 0) {
      $sql .= " WHERE graph='distributeur.commission.mensuel.".$distributeur."';";
    } else {
      $sql .= " WHERE graph='distributeur.commission.mensuel';";
    }

    $resql = $this->db->query($sql);


    if ($distributeur > 0) {
      $sql = "SELECT date, montant";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commission";
      $sql .= " WHERE fk_distributeur = ".$distributeur;
      $sql .= " ORDER BY date ASC";
    } else {
      $sql = "SELECT legend, sum(valeur)";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
      $sql .= " WHERE graph like 'distributeur.commission.mensuel.%'";
      $sql .= " GROUP BY legend ORDER BY ord ASC";
    }

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
	    $comms[$row[0]] = $row[1];
	    $labels[$i] = substr($row[0],-2)."/".substr($row[0],2,2);
	    
	    $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
	    $sqli .= " (graph,ord,legend,valeur)";
	    if ($distributeur > 0) {
	      $sqli .= " VALUES ('distributeur.commission.mensuel.".$distributeur."'";
	    } else {
	      $sqli .= " VALUES ('distributeur.commission.mensuel'";
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
    $month = array();
    $month[1] = 'J';
    $month[2] = 'F';
    $month[3] = 'M';
    $month[4] = 'A';
    $month[5] = 'M';
    $month[6] = 'J';
    $month[7] = 'J';
    $month[8] = 'A';
    $month[9] = 'S';
    $month[10] = 'O';
    $month[11] = 'N';
    $month[12] = 'D';

    for ($i = 1 ; $i < 13 ; $i++)
      {
	$idx = $this->year.substr('0'.$i,-2);
	$datas[$i-1] = $comms[$idx];
	$labels[$i-1] = $month[$i];
      }

    if (sizeof($datas))
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }
  }
}   
?>
