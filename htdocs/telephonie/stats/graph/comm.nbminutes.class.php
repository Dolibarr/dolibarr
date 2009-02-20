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

class GraphCommNbMinutes extends GraphBar{


  Function GraphCommNbMinutes($DB, $file)
  {
    $this->name = "comm.nbminutes";
    $this->db = $DB;
    $this->file = $file;
    $this->showframe = true;

    $this->client = 0;
    $this->contrat = 0;
    $this->ligne = 0;

    $this->titre = "Nombre de minutes";
    $this->barcolor = "bisque2";

    $this->datas = array();
    $this->labels = array();
  }

  Function Graph($datas='', $labels='')
  {    
    $this->GetDatas();
    $this->width = 360;
    if (sizeof($this->datas))
      {
	$this->GraphDraw($this->file, $this->datas, $this->labels);
      }
  }

  Function GetDatas()
  {

    $sql = "SELECT ym, sum(duree)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details as td";

    if ($this->client == 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " GROUP BY ym ASC";
      }
    elseif ($this->client > 0 && $this->contrat == 0 && $this->ligne == 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   

	$sql .= " WHERE td.ligne = s.ligne";
	$sql .= " AND s.fk_client_comm = ".$this->client;

	$sql .= " GROUP BY ym ASC ";
      }    
    elseif ($this->client == 0 && $this->contrat > 0 && $this->ligne == 0)
      {
	$sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as s";   

	$sql .= " WHERE td.ligne = s.ligne";
	$sql .= " AND s.fk_contrat = ".$this->contrat;

	$sql .= " GROUP BY ym ASC ";
      }    
    elseif ($this->client == 0 && $this->contrat == 0 && $this->ligne > 0)
      {
	$sql .= " WHERE td.fk_ligne = ".$this->ligne;

	$sql .= " GROUP BY ym ASC ";
      }    

    $resql = $this->db->query($sql); 

    if ($resql)
      {
	$num = $this->db->num_rows($resql);
	$i = 0;
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row($resql);	
	    
	    $this->labels[$i] = substr($row[0],2,2);
	    $this->datas[$i] = ceil($row[1] / 60);
	    
	    $i++;
	  }	
	$this->db->free($resql);
      }
    else 
      {
	dol_syslog("Error in GraphCommNbMinutes");
	dol_syslog($sql);
      }
  }
}  
?>
