<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class PropaleStats 
{
  var $db ;

  Function PropaleStats($DB)
    {
      $this->db = $DB;
    }

  Function getNbByMonthWithPrevYear($year)
  {
    $data1 = $this->getNbByMonth($year - 1);
    $data2 = $this->getNbByMonth($year);

    $data = array();

    for ($i = 0 ; $i < 12 ; $i++)
      {
	$data[$i] = array($data1[$i][0], 
			  $data1[$i][1],
			  $data2[$i][1]);
      }
    return $data;
  }
  /**
   * Renvoie le nombre de proposition par mois pour une année donnée
   *
   */
  Function getNbByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(datep,'%m') as dm, count(*)  FROM llx_propal";
    $sql .= " WHERE date_format(datep,'%Y') = $year AND fk_statut > 0";
    $sql .= " GROUP BY dm DESC";

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
	$data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
      }
    
    return $data;
  }


  /**
   * Renvoie le nombre de propale par année
   *
   */
  Function getNbByYear()
  {
    $result = array();
    $sql = "SELECT date_format(datep,'%Y') as dm, count(*) FROM llx_propal GROUP BY dm DESC WHERE fk_statut > 0";
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
   * Renvoie le nombre de propale par mois pour une année donnée
   *
   */
  Function getAmountByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(datep,'%m') as dm, sum(price)  FROM llx_propal";
    $sql .= " WHERE date_format(datep,'%Y') = $year AND fk_statut > 0";
    $sql .= " GROUP BY dm DESC";

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

    return $res;
  }
  /**
   * 
   *
   */
  Function getAverageByMonth($year)
  {
    $result = array();
    $sql = "SELECT date_format(datep,'%m') as dm, avg(price)  FROM llx_propal";
    $sql .= " WHERE date_format(datep,'%Y') = $year AND fk_statut > 0";
    $sql .= " GROUP BY dm DESC";

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

    return $res;
  }
}

?>
