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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/pie.class.php");

class GraphLignesStatut extends GraphPie {

  Function GraphLignesStatut($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Statuts des lignes";

    $this->barcolor = "yellow";
    $this->showframe = true;
  }

  Function GraphMakeGraph($commercial_id=0)
  {
    $num = 0;

    if ($commercial_id > 0)
      {
	$cuser = new User($this->db, $commercial_id);
	$cuser->fetch();
	$this->titre = "Statuts des lignes de ".$cuser->fullname;
      }


    $sql = "SELECT statut, count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " WHERE statut in (2,3,6,7)";

    if ($commercial_id > 0)
      {
	$sql .= " AND fk_commercial_sign =".$commercial_id;
      }

    $sql .= " GROUP BY statut ASC";
    

    $statuts[-1] = "Attente";
    $statuts[1] = "A commander";
    $statuts[2] = "Commandée";
    $statuts[3] = "Activée";
    $statuts[4] = "A résilier";
    $statuts[5] = "Resil en cours";
    $statuts[6] = "Resiliée";
    $statuts[7] = "Rejetée";


    $colors_def[2] = 'blue';
    $colors_def[3] = 'green';
    $colors_def[6] = 'red';
    $colors_def[7] = 'black';

    $this->colors = array();

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	$datas = array();
	$labels = array();
	
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();
	    
	    $datas[$i] = $row[1];
	    $labels[$i] = $statuts[$row[0]];
	    array_push($this->colors, $colors_def[$row[0]]);    
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }

  
    $this->GraphDraw($this->file, $datas, $labels);
  }
}   
?>
