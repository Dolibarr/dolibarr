<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class GraphJoursemaine extends GraphBrouzouf{


  Function GraphJoursemaine($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Jour de la semaine";

    $this->width = 400;
    $this->LabelAngle = 45;

    $this->barcolor = "green";
  }

  Function GraphDraw()
  {
    $sql = "SELECT ".$this->db->pdate("date")." as date, duree";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    
    if ($this->db->query($sql))
      {

	$jour_semaine_nb = array();
	$jour_semaine_duree = array();
	
	$num = $this->db->num_rows();

	$i = 0;
	
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();	
    
	    $u = strftime("%u",$obj->date) - 1; // 1 pour Lundi
	    
	    $jour_semaine_nb[$u]++;
	    $jour_semaine_duree[$u] += $obj->duree;
	    
	    $i++;
	  }
      }

    if ($num > 0)
      {        
	$this->GraphMakeGraph($jour_semaine_nb,array('Lun','Mar','Mer','Jeu','Ven','Sam','Dim'));
      }
  }

}
?>
