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

require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/graph/brouzouf.class.php");

class GraphHeureAppel extends GraphBrouzouf
{
  Function GraphHeureAppel($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Heure d'appel";

    $this->width = 400;
    $this->LabelAngle = 90;

    $this->barcolor = "blue";
  }

  Function GraphDraw($g)
  {

    $sql = "SELECT ".$this->db->pdate("date")." as date, duree";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    
    if ($this->db->query($sql))
      {
	$heure_appel = array();
	$num = $this->db->num_rows();
	$i = 0;
	
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();	
	    
	    $h = ceil(strftime("%H",$obj->date)); // suppression du 0
	    
	    $heure_appel_nb[$h]++;
	    $heure_appel_duree[$h] += $obj->duree;
	    
	    $i++;
	  }
      }

    if ($num > 0)
      {
	$this->GraphMakeGraph($heure_appel_nb, $labels);
      }
  }

}
?>
