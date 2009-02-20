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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/baracc.class.php");

class GraphLignesCommandesWeek extends GraphBarAcc {

  Function GraphLignesCommandesWeek($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Commandes Lignes par semaine";

    $this->barcolor = "blue";
    $this->showframe = true;
  }

  Function GraphMakeGraph($commercial=0)
  {
    $num = 0;

    $labels = array();

    $sql = "SELECT date_format(date_commande,'%y%v'), count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " WHERE date_commande IS NOT NULL ";

    if ($commercial > 0)
      {
	$sql .= " AND fk_commercial = ".$commercial;
      }

    $sql .= " GROUP BY date_format(date_commande,'%y%v') ASC";


    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	
	$i = 0;
	$datas = array();
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();	    
	    $datas[$row[0]] = $row[1];
	    array_push($labels, $row[0]);
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	dol_syslog($this->db->error());
      }

    /* Lignes rejetées */
    $sql = "SELECT date_format(date_commande,'%y%v'), count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " WHERE date_commande IS NOT NULL ";
    $sql .= " AND statut = 7";

    if ($commercial > 0)
      {
	$sql .= " AND fk_commercial = ".$commercial;
      }

    $sql .= " GROUP BY date_format(date_commande,'%y%v') ASC";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$datas_rej = array();

	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();
	    	    
	    $datas_rej[$row[0]] = $row[1];
	    array_push($labels, $row[0]);
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	dol_syslog($this->db->error());
      }

    /* == */

    $datas_new = array();
    $labels_new = array();
    $j = 0 ;

    $max = max($labels);
    $week = $max;
    $year = substr($week,0,2);
    $smwee = substr($max, -2);

    for ($i = 0 ; $i < 18 ; $i++)
      {

	$datas_new[$i] = $datas[$year.$smwee] - $datas_rej[$year.$smwee];
	$datas_new_rej[$i] = $datas_rej[$year.$smwee];
	$labels_new[$i] = ceil($smwee);

	if (($smwee - 1) == 0)
	  {
	    $smwee = strftime("%V",mktime(12,0,2,12,31,"20".substr("00".($year - 1), -2)));

	    if ($smwee == '01')
	      {
		$smwee = 52;
	      }

	    $year = substr("00".($year - 1), -2);
	  }
	else
	  {
	    $smwee = substr("00".($smwee -1), -2);
	  }
      }

    $datas_new = array_reverse($datas_new);
    $datas_new_rej = array_reverse($datas_new_rej);
    $labels_new = array_reverse($labels_new);

    $this->LabelAngle = 0;

    /*
     * Insertion Base
     *
     */
    $type = "commandes.hebdomadaire";
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats";
    $sql .= " WHERE graph = '".$type."'";
    $this->db->query($sql);

    for ($i = 0 ; $i < sizeof($datas_new) ; $i++)
      {

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats";
	$sql .= " (graph, ord, valeur) VALUES (";
	$sql .= "'".$type."'";
	$sql .= ",'".$labels_new[$i]."'";
	$sql .= ",'".$datas_new[$i]."');";
	if (! $this->db->query($sql))
	  {
	    print $this->db->error();
	  }
      }
    /*
     *
     *
     */

    $this->GraphDraw($this->file, $datas_new, $labels_new, $datas_new_rej);
  }
}   
?>
