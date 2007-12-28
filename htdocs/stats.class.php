<?php
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
 */

class Stats 
{
  var $db ;

  function Stats($DB)
    {
      $this->db = $DB;
    }

  function getNbByMonthWithPrevYear($endyear,$startyear)
  {
  	$datay=array();
  	
    $year=$startyear;
	while($year <= $endyear)
	{
  		$datay[$year] = $this->getNbByMonth($year);
		$year++;
	}

    $data = array();

    for ($i = 0 ; $i < 12 ; $i++)
      {
		$data[$i][]=$datay[$endyear][$i][0];
		$year=$startyear;
		while($year <= $endyear)
		{
			$data[$i][]=$datay[$year][$i][1];
			$year++;
		}
      }
    return $data;
  }
	
  /**
   * \brief  Renvoie le nombre de proposition par mois pour une annee donnee
   *
   */
    function _getNbByMonth($year, $sql)
    {
        $result = array();
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $j = $row[0] * 1;
                $result[$j] = $row[1];
                $i++;
            }
            $this->db->free($resql);
        }
    
        for ($i = 1 ; $i < 13 ; $i++)
        {
            $res[$i] = $result[$i] + 0;
        }
    
        $data = array();
    
        for ($i = 1 ; $i < 13 ; $i++)
        {
            $data[$i-1] = array(ucfirst(substr(strftime("%b",mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
        }
    
        return $data;
    }


  /**
   * \brief  Renvoie le nombre d'element par ann�e
   *
   */
    function _getNbByYear($sql)
    {
        $result = array();
    
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
        else {
            dolibarr_print_error($this->db);
        }
        return $result;
    }

  /**
   * \brief  Renvoie le nombre d'element par mois pour une ann�e donn�e
   *
   */
	 
  function _getAmountByMonth($year, $sql)
  {
    $result = array();

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
  function _getAverageByMonth($year, $sql)
  {
    $result = array();

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
