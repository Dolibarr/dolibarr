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

class GraphDistributeurResultat extends GraphBar {

  Function GraphDistributeurResultat($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;
    $this->client = 0;
    $this->titre = "Resultat mensuel";

    $this->barcolor = "green";
    $this->showframe = true;
  }

  Function GraphMakeGraph($distributeur=0)
  {
    $comms = array();
    $gains = array();
    $num = 0;

    $this->no_xaxis_title=1;

    $sql = "SELECT legend, valeur";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
    $sql .= " WHERE graph = 'distributeur.gain.mensuel.".$distributeur."'";
    $sql .= " ORDER BY ord ASC";
    $resql = $this->db->query($sql);

    if ($resql)
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $gains[$row[0]] = $row[1];
	  }
      }

    $sql = "SELECT legend, valeur";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
    $sql .= " WHERE graph = 'distributeur.commission.mensuel.".$distributeur."'";
    $sql .= " ORDER BY ord ASC";
    $resql = $this->db->query($sql);

    if ($resql)
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $comms[$row[0]] = $row[1];
	  }
      }

    $datas = array();
    $labels = array();
    $year = strftime("%Y",time());
    for ($i = 1 ; $i < 13 ; $i++)
      {
	$idx = $year.substr('0'.$i,-2);
	$datas[$i-1] = $gains[$idx] - $comms[$idx];
	$labels[$i-1] = $i;
      }

    if (sizeof($datas))
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }
  }
}   
?>
