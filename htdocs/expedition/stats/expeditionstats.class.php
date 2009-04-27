<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

class ExpeditionStats 
{
  var $db;

  function ExpeditionStats($DB)
  {
    $this->db = $DB;
  }
  /**
   * Renvoie le nombre de expedition par année
   *
   */
  function getNbExpeditionByYear()
  {
  	global $conf;
  	
    $result = array();
    $sql = "SELECT count(*), date_format(e.date_expedition,'%Y') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s ";
    $sql.= " WHERE e.fk_statut > 0";
    $sql.= " AND e.fk_soc = s.rowid";
    $sql.= " s.entity = ".$conf->entity;
    $sql.= " GROUP BY dm DESC";
    
    if ($this->db->query($sql))
    {
    	$num = $this->db->num_rows();
    	$i = 0;
    	while ($i < $num)
    	{
    		$row = $this->db->fetch_row($i);
    		$result[$i] = $row;
    		
    		$i++;
    	}
    	$this->db->free();
    }
    return $result;
  }
  /**
   * Renvoie le nombre de expedition par mois pour une année donnée
   *
   */
  function getNbExpeditionByMonth($year)
  {
  	global $conf;
  	
    $result = array();
    $sql = "SELECT count(*), date_format(e.date_expedition,'%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s ";
    $sql.= " WHERE date_format(e.date_expedition,'%Y') = ".$year;
    $sql.= " AND fk_statut > 0";
    $sql.= " AND e.fk_soc = s.rowid";
    $sql.= " s.entity = ".$conf->entity;
    $sql.= " GROUP BY dm DESC";

    if ($this->db->query($sql))
    {
    	$num = $this->db->num_rows();
    	$i = 0;
    	while ($i < $num)
    	{
    		$row = $this->db->fetch_row($i);
    		$j = $row[0] * 1;
    		$result[$j] = $row[1];
    		$i++;
    	}
    	$this->db->free();
    }
    for ($i = 1 ; $i < 13 ; $i++)
    {
    	$res[$i] = $result[$i] + 0;
    }
    
    $data = array();
    
    for ($i = 1 ; $i < 13 ; $i++)
    {
    	$data[$i-1] = array(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"), $res[$i]);
    }
    
    return $data;
  }


  function getNbExpeditionByMonthWithPrevYear($year)
  {
    $data1 = $this->getNbExpeditionByMonth($year);
    $data2 = $this->getNbExpeditionByMonth($year - 1);

    $data = array();

    for ($i = 1 ; $i < 13 ; $i++)
    {
    	$data[$i-1] = array(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"), 
			$data1[$i][1],
			$data2[$i][1]);
    }
    return $data;
  }

}

?>
