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

class GraphLignesCommandesWeek extends GraphBar {

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

    $sql = "SELECT date_format(date_commande,'%x%v'), count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    $sql .= " WHERE date_commande IS NOT NULL ";

    if ($commercial > 0)
      {
	$sql .= " AND fk_commercial = ".$commercial;
      }

    $sql .= " GROUP BY date_format(date_commande,'%x%v') ASC";

    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
	
	$i = 0;
	$j = 0;
	$datas = array();
	$labels = array();
	$oldyear == 0;
		
	while ($i < $num)
	  {

	    $row = $this->db->fetch_row();
	    
	    if ($oldyear == 0) 
	      {
		$oldyear = substr($row[0],0,4);
	      }
	    
	    
	    if ($oldyear == substr($row[0],0,4) && $j > 0 && substr($row[0],-2) <> ($labels[$j-1] +1 ) )
	      {
		
		$datas[$j] = 0;
		$labels[$j] = $labels[$j-1] + 1;
		$j++;
		
	      }
	    
	    $datas[$j] = $row[1];
	    $labels[$j] = substr($row[0],-2);
	    
	    $i++;
	    $j++;
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
