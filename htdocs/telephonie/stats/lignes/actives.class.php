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
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/line.class.php");

class GraphLignesActives extends GraphLine {

  Function GraphLignesActives($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Lignes en présélections";

    $this->barcolor = "green";
    $this->showframe = true;
  }


  Function GraphMakeGraph()
  {
    $num = 0;

    $sql = "SELECT dates, statut,nb";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
    $sql .= " WHERE statut = 3";
    $sql .= " ORDER BY dates ASC";
    
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$j = -1;
	$attente = array();
	$acommander = array();
	$commandee = array();
	$active = array();
	$last = 0;
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	

	    $j++;
	    $labels[$j] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    $attente[$j]    = 0;
	    $acommander[$j] = 0;
	    $commandee[$j]  = 0;
	    $active[$j]     = 0;
	    $last = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
	    if ($row[1] == 3)
	      {
		$active[$j] = $row[2];
	      }
	    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }
    
    $this->LabelInterval = 1;

    $a = round($num / 20,0);

    if ($a > 1)
      {
	$this->LabelInterval = $a;
      }

    $this->GraphDraw($this->file, $active, $labels);
  }
}


class GraphLignesCommandees extends GraphLine {

  Function GraphLignesCommandees($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Lignes en commandes";

    $this->barcolor = "green";
    $this->showframe = true;
  }


  Function GraphMakeGraph()
  {
    $num = 0;

    $sql = "SELECT dates, statut,nb";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_ligne_statistique";
    $sql .= " WHERE statut = 2";
    $sql .= " ORDER BY dates ASC";
    
    $resql = $this->db->query($sql);
    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
	$j = -1;
	$attente = array();
	$acommander = array();
	$commandee = array();
	$active = array();
	$last = 0;
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	

	    $j++;
	    $labels[$j] = substr($row[0],5,2)."/".substr($row[0],2,2);
	    $attente[$j]    = 0;
	    $acommander[$j] = 0;
	    $commandee[$j]  = 0;
	    $active[$j]     = 0;
	    $last = substr($row[0],5,2)."/".substr($row[0],2,2);
	    
	    if ($row[1] == 2)
	      {
		$active[$j] = $row[2];
	      }
	    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }
    
    $this->LabelInterval = 1;

    $a = round($num / 20,0);

    if ($a > 1)
      {
	$this->LabelInterval = $a;
      }

    $this->GraphDraw($this->file, $active, $labels);
  }
}


?>
