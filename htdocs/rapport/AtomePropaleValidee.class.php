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

class AtomePropaleValidee extends Atome
{
  var $id;
  var $db;


  /**
   * Initialisation de la classe
   *
   */
  function AtomePropaleValidee($DB,$periode, $daystart)
    {
      $this->db = $DB ;
      $this->AtomeInitialize($periode, 'AtomePropaleValidee', $daystart);
    }

  /**
   *
   *
   *
   */
  function fetch()
    {
      $sql = "SELECT date_format(f.datep,'%Y%m'), sum(f.price) as am";
      $sql .= " FROM ".MAIN_DB_PREFIX."propal as f";
      $sql .= " WHERE f.fk_statut = 2";

      if ($this->year)
	{
	  $sql .= " AND date_format(f.datep,'%Y') = $this->year ";
	  $sql .= " GROUP BY date_format(f.datep,'%Y%m') ASC ;";
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

	      $i++;
	    }
	  return $arr;

	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	  return -3;
	}
    }
}
?>
