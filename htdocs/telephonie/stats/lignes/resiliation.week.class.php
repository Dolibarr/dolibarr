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


  Function ReadDatas()
  {
    $num = 0;

    $sql = "SELECT count(*), date_format(tms,'%y%v')";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne_statut";
    $sql .= " WHERE statut = 6";
    $sql .= " GROUP BY date_format(tms,'%y%v') ASC";
    
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	
	$i = 0;
	$j = 0;
	$this->datas = array();
	$this->labels = array();
	$oldweek = 0;
	$clients = array();
		
	while ($i < $num)
	  {
	    $row = $this->db->fetch_row();

	    $this->datas[$row[1]] = $row[0];
	    $this->labels[$i] = $row[1];

	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	print $this->db->error() . ' ' . $sql;
      }                  
  }
  /*
   *
   *
   */
  Function GraphMakeGraph()
  {

    $this->ReadDatas();

    $datas_new = array();
    $labels_new = array();

    $max = strftime("%y%V", time());
    $week = $max;
    $year = substr($week,0,2);
    $smwee = substr($max, -2);

    for ($i = 0 ; $i < 18 ; $i++)
      {

	$datas_new[$i] = $this->datas[$year.$smwee];

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
    $labels_new = array_reverse($labels_new);

    $this->GraphDraw($this->file, $datas_new, $labels_new);

  }
}
?>
