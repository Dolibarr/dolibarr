<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *       \file       htdocs/expensereport/class/expensereportstats.class.php
 *       \ingroup    expensereport
 *       \brief      File of lass to manage the statistics of the expensereports et expense notes
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

/**
 *  Class to manage the statistics of the expensereports and expense notes
 */
class ExpenseReportStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var int ID
	 */
	public $socid;

	/**
	 * @var int ID
	 */
	public $userid;

	/**
	 * @var string
	 */
	public $from;

	/**
	 * @var string
	 */
	public $field;

	/**
	 * @var string
	 */
	public $where;

	/**
	 * @var string
	 */
	private $datetouse = 'date_valid';


	/**
	 * Constructor
	 *
	 * @param 	DoliDB		$db		   Database handler
	 * @param 	int			$socid	   Id third party
	 * @param   int			$userid    Id user for filter
	 * @return 	void
	 */
	public function __construct($db, $socid = 0, $userid = 0)
	{
		global $conf, $user;

		$this->db = $db;
		$this->socid = $socid;
		$this->userid = $userid;

		$object = new ExpenseReport($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as e";
		$this->field = 'total_ht';

		//$this->where = " e.fk_statut > 0";
		//$this->where.= " AND e.date_valid > '2000-01-01'";    // To filter only correct "valid date". If date is invalid, the group by on it will fails. Launch a repair.php if you have.
		$this->where .= ' e.entity IN ('.getEntity('expensereport').')';

		//$this->where.= " AND entity = ".$conf->entity;
		if ($this->socid) {
			$this->where .= " AND e.fk_soc = ".((int) $this->socid);
		}

		// Only me and subordinates
		if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')) {
			$childids = $user->getAllChildIds();
			$childids[] = $user->id;
			$this->where .= " AND e.fk_user_author IN (".$this->db->sanitize(implode(',', $childids)).")";
		}

		if ($this->userid > 0) {
			$this->where .= ' AND e.fk_user_author = '.((int) $this->userid);
		}
	}


	/**
	 * 	Return nb of expense report per year
	 *
	 *	@return		array	Array of values
	 */
	public function getNbByYear()
	{
		$sql = "SELECT YEAR(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).") as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " GROUP BY dm DESC";
		$sql .= " WHERE ".$this->where;

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	Return the quantity of invoices per month for a given year
	 *
	 *	@param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *	@return	array				Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT MONTH(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).") as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE YEAR(e.".$this->datetouse.") = ".((int) $year);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);

		return $res;
	}


	/**
	 * 	Renvoie le montant de facture par mois pour une annee donnee
	 *
	 *	@param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *	@return	array				Array of values
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).",'%m') as dm, sum(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).",'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);

		return $res;
	}

	/**
	 *	Return average amount
	 *
	 *	@param	int		$year		Year to scan
	 *	@return	array				Array of values
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).",'%m') as dm, avg(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).",'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array				Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(".$this->db->ifsql("e.".$this->datetouse." IS NULL", "e.date_create", "e.".$this->datetouse).",'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
}
