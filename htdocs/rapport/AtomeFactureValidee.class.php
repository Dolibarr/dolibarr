<?php
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
 */

include_once(DOL_DOCUMENT_ROOT.'/rapport/Atome.class.php');

class AtomeFactureValidee extends Atome
{
  var $id;
  var $db;
  var $periode;

  /**
   * Initialisation de la classe
   *
   */
  function AtomeFactureValidee($DB, $periode, $daystart)
    {
      $this->db = $DB ;
      $this->name = 'AtomeFactureValidee';
      $this->AtomeInitialize($periode,'AtomeFactureValidee', $daystart);
      $this->datas = array();
    }

  /**
   *
   *
   *
   */
  function fetch()
    {
      if ($this->periode == 'year')
	{
	  $sql = "SELECT date_format(f.datef,'%Y%m'), sum(f.amount) as am";
	  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " WHERE f.fk_statut = 1";

	  $sql .= " AND date_format(f.datef,'%Y') = $this->year ";
	  $sql .= " GROUP BY date_format(f.datef,'%Y%m') ASC ;";
	}

      if ($this->periode == 'month')
	{
	  $sql = "SELECT date_format(f.datef,'%Y%m%d'), sum(f.amount) as am";
	  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " WHERE f.fk_statut = 1";
	  $sql .= " AND date_format(f.datef,'%Y%m') = ".$this->year.$this->month;
	  $sql .= " GROUP BY date_format(f.datef,'%Y%m%d') ASC ;";
	}

      if ($this->db->query($sql) )
	{
	  $i = 0;
	  $num = $this->db->num_rows();
	  $arr = array();
	  while ($i < $num)
	    {
	      $row = $this->db->fetch_row($i);

	      $arr[$row[0]] = $row[1];
	      //print $row[0] .'-'.$row[1]. '<br>';
	      $i++;
	    }

	  $this->db->free();
	  $this->datas = $arr;
	  return $arr;
	}
      else
	{
	  print $this->db->error();
	  print "<br>$sql";
	  return -3;
	}
    }
}
?>
