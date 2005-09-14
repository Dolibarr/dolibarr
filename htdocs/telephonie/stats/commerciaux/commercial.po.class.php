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

class GraphCommercialPO extends GraphBar {

  Function GraphCommercialPO($DB, $file)
  {
    $this->db = $DB;
    $this->file = $file;

    $this->client = 0;
    $this->titre = "Prise d'ordre mensuelle";

    $this->barcolor = "green";
    $this->showframe = true;
  }

  Function GraphMakeGraph($xdatas)
  {
    $datas = array();
    $labels = array();
    $i = 0;
    foreach($xdatas as $key => $value)
      {
	if ($i > 1 && ((substr($key, -2) - $labels[$i-1]) > 1) )
	  {
	    $datas[$i] = 0;
	    $labels[$i] = substr("00".($labels[$i-1] + 1), -2);
	    $i++;
	  }
	$datas[$i] = $value;
	$labels[$i] = substr($key, -2);
	$i++;
      }
	
    /* Mise en forme de la légende */

    if (sizeof($datas))
      {
	$this->GraphDraw($this->file, $datas, $labels);
      }
  }
}   

?>
