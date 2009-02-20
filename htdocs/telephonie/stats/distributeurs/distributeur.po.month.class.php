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

class GraphDistributeurPoMensuel extends GraphBar {

  Function GraphDistributeurPoMensuel($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Prise d'ordre mensuelle";

    $this->barcolor = "blue";
    $this->showframe = true;
  }

  Function GraphMakeGraph($id=0, $nom='')
  {
    $num = 0;

    $this->titre = "Prise d'ordre mensuelle pour $nom";

    $labels = array();

    
    $sql = "SELECT date_format(datepo, '%m-%Y'), sum(p.montant)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
    
    $sql .= " WHERE p.fk_distributeur = ".$id;
    $sql .= " GROUP BY date_format(p.datepo, '%Y%m') ASC";


    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	
	$i = 0;
	$datas = array();
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();	    
	    array_push($datas, $row[1]);
	    array_push($labels, $row[0]);
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	dol_syslog($this->db->error());
      }

    if (sizeof($datas) > 0)
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }
  }
}   
?>
