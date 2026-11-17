<?php
/* Copyright (C) 2026       Laurent Destailleur         <eldy@users.sourceforge.net>
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
 *  \file       htdocs/mrp/class/mostats.class.php
 *  \ingroup    mrp
 *  \brief      File of class to manage Manufacturing Orders statistics
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *    Class to manage Manufacturing Orders statistics
 */
class MoStats extends Stats
{
	/**
	 * @var int ID of thirdparty for filter
	 */
	public $socid;

	/**
	 * @var int ID of user for filter
	 */
	public $userid;

	/**
	 * @var int ID of product for filter
	 */
	public $productid;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		    	Database handler
	 * @param   int		$userid     	Id user for filter (creation user)
	 * @param   int		$productid     	Id product for filter (product to produce or disassemble)
	 * @param   int		$socid     		Id thirdparty for filter
	 */
	public function __construct($db, $userid = 0, $productid = 0, $socid = 0)
	{
		$this->db = $db;

		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->productid = $productid;
		$this->cachefilesuffix = 'mo';

		$object = new Mo($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as m";
		$this->field_date = 'date_creation';
		$this->field = 'qty';		// There is no amount on a MO, so the field used for "amount" methods is the quantity

		$this->where = 'm.entity IN ('.getEntity('mo').')';
		if ($this->userid > 0) {
			$this->where .= ' AND m.fk_user_creat = '.((int) $this->userid);
		}
		if ($this->productid > 0) {
			$this->where .= ' AND m.fk_product = '.((int) $this->productid);
		}
		if ($this->socid > 0) {
			$this->where .= ' AND m.fk_soc = '.((int) $this->socid);
		}
	}

	/**
	 * Return MO number by month for a year
	 *
	 * @param	int		$year		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(m.date_creation,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->db->sanitize($this->from, 0, 1, 1);
		$sql .= " WHERE m.date_creation BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByMonth($year, $sql, $format);
	}

	/**
	 * Return MO number per year
	 *
	 * @return	array<array{0:int,1:int}>	Array of nb each year
	 */
	public function getNbByYear()
	{
		$sql = "SELECT date_format(m.date_creation,'%Y') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->db->sanitize($this->from, 0, 1, 1);
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the total of quantities to produce by month for a year
	 *
	 * @param	int		$year		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array with amount by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(m.date_creation,'%m') as dm, SUM(m.".$this->db->sanitize($this->field).")";
		$sql .= " FROM ".$this->db->sanitize($this->from, 0, 1, 1);
		$sql .= " WHERE m.date_creation BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAmountByMonth($year, $sql, $format);
	}

	/**
	 * Return the average of quantities to produce by month for a year
	 *
	 * @param	int		$year	Year to scan
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array with average by month
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(m.date_creation,'%m') as dm, AVG(m.".$this->db->sanitize($this->field).")";
		$sql .= " FROM ".$this->db->sanitize($this->from, 0, 1, 1);
		$sql .= " WHERE m.date_creation BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 * Return the number of MOs by year
	 *
	 * @return array<array{year:string,nb:int,nb_diff?:float,total?:float,avg?:float,weighted?:float,total_diff?:float,avg_diff?:float,avg_weighted?:float}>    Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(m.date_creation,'%Y') as year, COUNT(*) as nb";
		$sql .= " FROM ".$this->db->sanitize($this->from, 0, 1, 1);
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
}
