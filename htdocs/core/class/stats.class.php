<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2008-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/core/class/stats.class.php
 *  \ingroup    core
 *  \brief      Common class to manage statistics reports
 */

/**
 * 	Parent class of statistics class
 */
abstract class Stats
{
	protected $db;


	/**
	 * Return nb of entity by month for several years
	 *
	 * @param 	int		$endyear	Start year
	 * @param 	int		$startyear	End year
	 * @return 	array				Array of values
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
	 * @param	int		$endyear		Start year
	 * @param	int		$startyear		End year
	 * @return 	array					Array of values
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
	 * Return average of entity by month for several years
	 *
	 * @param	int		$endyear		Start year
	 * @param	int		$startyear		End year
	 * @return 	array					Array of values
	 */
	function getAverageByMonthWithPrevYear($endyear,$startyear)
	{
        if ($startyear > $endyear) return -1;

        $datay=array();

		$year=$startyear;
		while($year <= $endyear)
		{
			$datay[$year] = $this->getAverageByMonth($year);
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
	 *	@param	string	$sql		SQL request
	 * 	@return	array
	 */
	function _getNbByYear($sql)
	{
		$result = array();

		dol_syslog(get_class($this)."::_getNbByYear sql=".$sql);
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
	 * 	Return nb of elements, total amount and avg amount each year
	 *
	 *	@param	string	$sql	SQL request
	 * 	@return	array			Array with nb, total amount, average for each year
	 */
	function _getAllByYear($sql)
	{
		$result = array();

		dol_syslog(get_class($this)."::_getAllByYear sql=".$sql);
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
     *     @param   int		$year       Year
     *     @param   string	$sql        SQL
     *     @return	array				Array of nb each month
	 */
	function _getNbByMonth($year, $sql)
	{
		$result=array();
		$res=array();

		dol_syslog(get_class($this)."::_getNbByMonth sql=".$sql);
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
			$res[$i] = (isset($result[$i])?$result[$i]:0);
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
	 *     @param	int		$year        Year
	 *     @param   string	$sql         SQL
	 *     @return	array
	 */
	function _getAmountByMonth($year, $sql)
	{
		$result=array();
		$res=array();

		dol_syslog(get_class($this)."::_getAmountByMonth sql=".$sql);

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
			$res[$i] = (int) round((isset($result[$i])?$result[$i]:0));
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
     *      @param	int		$year        Year
     *      @param  string	$sql         SQL
     *      @return	array
	 */
	function _getAverageByMonth($year, $sql)
	{
		$result=array();
		$res=array();

		dol_syslog(get_class($this)."::_getAverageByMonth sql=".$sql);
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
			$res[$i] = (isset($result[$i])?$result[$i]:0);
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
}

?>
