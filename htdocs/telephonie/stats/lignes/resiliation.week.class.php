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

class GraphLignesResiliationWeek extends GraphBar {


  Function GraphLignesResiliationWeek($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Résiliation de lignes par semaine";

    $this->barcolor = "red";
    $this->showframe = true;
  }


  Function GraphMakeGraph()
  {
    $num = 0;

    $sql = "SELECT count(*), date_format(tms,'%x%v')";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
    $sql .= " WHERE statut = 6";
    $sql .= " GROUP BY date_format(tms,'%x%v') ASC";
    
    $result = $this->db->query($sql);


    if ($result)
      {
	$num = $this->db->num_rows();
	
	$i = 0;
	$j = 0;
	$datas = array();
	$labels = array();
	$oldweek = 0;
	$clients = array();
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    if ($oldweek == 0) 
	      {
		$oldweek = $row[1];
		array_push($clients, $row[0]);

		$datas[$j] = 1;
		$labels[$j] = $row[1];
	      }	    
	    else
	      {

		if ($row[1] == $labels[$j])
		  {
		    if (! in_array($row[0], $clients))
		    {
		      array_push($clients, $row[0]);
		      $datas[$j]++;
		    }
		  }
		else
		  {
		    $j++;
		    if (! in_array($row[0], $clients))
		      {
			array_push($clients, $row[0]);
			$datas[$j] = 1;
		      }
		    else
		      {
			$datas[$j] = 0;
		      }

		    $labels[$j] = $row[1];
		  }

	      }

	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }                  

    $datas_new = array();
    $labels_new = array();
    $j = 0 ;

    $datas_new[0] = $datas[0];
    $labels_new[0] = ceil(substr($labels[0],-2));

    for ($i = 1 ; $i < sizeof($labels) ; $i++)
      {
	if (substr($labels[$i], -2) - substr($labels[$i-1], -2) > 1)
	  {
	    for ($k = 1 ; $k < ($labels[$i] - $labels[$i-1]) ; $k++)
	      {
		$datas_new[$i+$j] = 0;
		$labels_new[$i+$j] = ceil(substr($labels[$i-1], -2) + $k) ; // suppression du 0

		$j++;
	      }
	  }

	$datas_new[$i+$j] = $datas[$i];
	$labels_new[$i+$j] = ceil(substr($labels[$i], - 2));
      }

    $nbel = sizeof($datas_new);

    for ($i = 0 ; $i < ($nbel - 18) ; $i++)
      {
	array_shift($datas_new);
	array_shift($labels_new);
      }

    $this->GraphDraw($this->file, $datas_new, $labels_new);
  }
}
?>
