<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/class/stats.class.php
 *       \ingroup    core
 *       \brief      Common class to manage statistics reports
 */

/**
 * 		\class		Stats
 * 		\brief		Parent class of statistics class
 */
abstract class Stats
{
	protected $db;


	/**
	 * Return nb of entity by month for several years
	 *
	 * @param 	endyear		Start year
	 * @param 	startyear	End year
	 * @return 	array		Array of values
	 */
	function getNbByMonthWithPrevYear($endyear,$startyear)
	{
	    if ($startyear > $endyear) return -1;

		$datay=array();

		$year=$startyear;
		while ($year <= $endyear)
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

		// return array(array('Month',val1,val2,val3),...)
		return $data;
	}

	/**
	 * Return amount of entity by month for several years
	 *
	 * @param 	endyear		Start year
	 * @param 	startyear	End year
	 * @return 	array		Array of values
	 */
	function getAmountByMonthWithPrevYear($endyear,$startyear)
	{
        if ($startyear > $endyear) return -1;

        $datay=array();

		$year=$startyear;
		while($year <= $endyear)
		{
			$datay[$year] = $this->getAmountByMonth($year);
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
	 * 	Return nb of elements by year
	 *
	 *	@param		sql		SQL request
	 * 	@return		array
	 */
	function _getNbByYear($sql)
	{
		$result = array();

		dol_syslog("Stats::_getNbByYear sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$result[$i] = $row;
				$i++;
			}
			$this->db->free($resql);
		}
		else {
			dol_print_error($this->db);
		}
		return $result;
	}

	/**
	 * 	Return nb of elements, total amount and avg amount by year
	 *
	 *	@param		sql		SQL request
	 * 	@return		array
	 */
	function _getAllByYear($sql)
	{
		$result = array();

		dol_syslog("Stats::_getAllByYear sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_object($resql);
				$result[$i]['year'] = $row->year;
				$result[$i]['nb'] = $row->nb;
				$result[$i]['total'] = $row->total;
				$result[$i]['avg'] = $row->avg;
				$i++;
			}
			$this->db->free($resql);
		}
		else {
			dol_print_error($this->db);
		}
		return $result;
	}

	/**
	 *     Renvoie le nombre de proposition par mois pour une annee donnee
	 *
     *     @param      year        Year
     *     @param      sql         SQL
	 */
	function _getNbByMonth($year, $sql)
	{
		$result = array();

		dol_syslog("Stats::_getNbByMonth sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0; $j = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$j = $row[0] * 1;
				$result[$j] = $row[1];
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$res[$i] = $result[$i] + 0;
		}

		$data = array();

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b");
			$month=dol_substr($month,0,3);
			$data[$i-1] = array(ucfirst($month), $res[$i]);
		}

		return $data;
	}


	/**
	 *     Renvoie le nombre d'element par mois pour une annee donnee
	 *
	 *     @param      year        Year
	 *     @param      sql         SQL
	 */
	function _getAmountByMonth($year, $sql)
	{
		$result = array();

		dol_syslog("Stats::_getAmountByMonth sql=".$sql);

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
        else dol_print_error($this->db);

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$res[$i] = (int) round($result[$i]) + 0;
		}

		$data = array();

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$month=dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b");
			$month=dol_substr($month,0,3);
			$data[$i-1] = array(ucfirst($month), $res[$i]);
		}

		return $data;
	}

	/**
	 *	    Renvoie le montant moyen par mois pour une annee donnee
	 *
     *      @param      year        Year
     *      @param      sql         SQL
	 */
	function _getAverageByMonth($year, $sql)
	{
		$result = array();

		dol_syslog("Stats::_getAverageByMonth sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0; $j = 0;
			while ($i < $num)
			{
		  		$row = $this->db->fetch_row($resql);
		  		$j = $row[0] * 1;
		  		$result[$j] = $row[1];
		  		$i++;
		  	}
		  	$this->db->free($resql);
		}
        else dol_print_error($this->db);

		for ($i = 1 ; $i < 13 ; $i++)
		{
			$res[$i] = $result[$i] + 0;
		}

		return $res;
	}
}

?>
