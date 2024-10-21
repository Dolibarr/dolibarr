<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/don/class/donstats.class.php
 *  \ingroup    donations
 *  \brief      File of class to manage donations statistics
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 *	Class to manage donations statistics
 */
class DonationStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var int
	 */
	public $socid;
	/**
	 * @var int
	 */
	public $userid;

	/**
	 * @var string FROM
	 */
	public $from;

	/**
	 * @var string field
	 */
	public $field;

	/**
	 * @var string WHERE
	 */
	public $where;

	/**
	 * @var string JOIN
	 */
	public $join;

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db      	Database handler
	 * @param 	int		$socid	   	Id third party for filter
	 * @param 	string	$mode	   	Option (not used)
	 * @param   int		$userid    	Id user for filter (creation user)
	 * @param   int		$typentid  	Id of type of third party for filter
	 * @param   int		$status    	Status of donation for filter
	 */
	public function __construct($db, $socid, $mode, $userid = 0, $typentid = 0, $status = 4)
	{
		global $conf;

		$this->db = $db;

		$this->field = 'amount';
		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->cachefilesuffix = $mode;
		$this->join = '';

		if ($status == 0 || $status == 1 || $status == 2) {
			$this->where = ' d.fk_statut IN ('.$this->db->sanitize($status).')';
		} elseif ($status == 3) {
			$this->where = ' d.fk_statut IN (-1)';
		} elseif ($status == 4) {
			$this->where = ' d.fk_statut >= 0';
		}

		$object = new Don($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as d";

		if ($socid !== "-1" && $socid > 0) {
			$this->where .= " AND d.fk_soc = ".((int) $socid);
		}

		$this->where .= " AND d.entity = ".$conf->entity;
		if ($this->userid > 0) {
			$this->where .= ' AND d.fk_user_author = '.((int) $this->userid);
		}

		if ($typentid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = d.fk_soc';
			$this->where .= ' AND s.fk_typent = '.((int) $typentid);
		}
	}

	/**
	 *  Return shipment number by month for a year
	 *
	 *  @param	int		$year		Year to scan
	 *  @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(d.datedon,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE d.datedon BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByMonth($year, $sql, $format);
	}

	/**
	 * Return shipments number per year
	 *
	 * @return	array<array{0:int,1:int}>				Array of nb each year
	 *
	 */
	public function getNbByYear()
	{
		$sql = "SELECT date_format(d.datedon,'%Y') as dm, COUNT(*) as nb, SUM(d.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the number of subscriptions by month for a given year
	 *
	 * @param   int		$year       Year
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of amount each month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(d.datedon,'%m') as dm, sum(d.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".dolSqlDateFilter('d.datedon', 0, 0, (int) $year, 1);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAmountByMonth($year, $sql, $format);
	}

	/**
	 * Return average amount each month
	 *
	 * @param   int		$year       Year
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}> 	Array with number by month
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(d.datedon,'%m') as dm, avg(d.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".dolSqlDateFilter('d.datedon', 0, 0, (int) $year, 1);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *  Return nb, total and average
	 *
	 *  @return array<array{year:string,nb:string,nb_diff:float,total?:float,avg?:float,weighted?:float,total_diff?:float,avg_diff?:float,avg_weighted?:float}>    Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(d.datedon,'%Y') as year, COUNT(*) as nb, SUM(d.".$this->field.") as total, AVG(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
}
