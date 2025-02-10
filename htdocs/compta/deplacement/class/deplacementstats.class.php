<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/compta/deplacement/class/deplacementstats.class.php
 *       \ingroup    invoices
 *       \brief      File for class managaging the statistics of travel and expense notes
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';

/**
 *	Class to manage the statistics of travel and expense notes
 */
class DeplacementStats extends Stats
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
	 * Constructor
	 *
	 * @param 	DoliDB		$db		   Database handler
	 * @param 	int			$socid	   Id third party
	 * @param   int|int[]	$userid    Id user for filter or array of user ids
	 * @return 	void
	 */
	public function __construct($db, $socid = 0, $userid = 0)
	{
		global $conf;

		$this->db = $db;
		$this->socid = $socid;
		$this->userid = $userid;

		$object = new Deplacement($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element;
		$this->field = 'km';

		$this->where = " fk_statut > 0";
		$this->where .= " AND entity = ".$conf->entity;
		if ($this->socid > 0) {
			$this->where .= " AND fk_soc = ".((int) $this->socid);
		}
		if (is_array($this->userid) && count($this->userid) > 0) {
			$this->where .= ' AND fk_user IN ('.$this->db->sanitize(implode(',', $this->userid)).')';
		} elseif ($this->userid > 0) {
			$this->where .= ' AND fk_user = '.((int) $this->userid);
		}
	}


	/**
	 * 	Renvoie le nombre de facture par annee
	 *
	 * @return	array<array{0:int,1:int}>				Array of nb each year
	 */
	public function getNbByYear()
	{
		$sql = "SELECT YEAR(dated) as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " GROUP BY dm DESC";
		$sql .= " WHERE ".$this->where;

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	Renvoie le nombre de facture par mois pour une annee donnee
	 *
	 *	@param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT MONTH(dated) as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE YEAR(dated) = ".((int) $year);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	Renvoie le montant de facture par mois pour une annee donnee
	 *
	 *	@param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of values
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, sum(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(dated,'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *	Return average amount
	 *
	 *	@param	int		$year		Year to scan
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}> 	Array with number by month
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, avg(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(dated,'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *  @return array<array{year:string,nb:string,nb_diff:float,total?:float,avg?:float,weighted?:float,total_diff?:float,avg_diff?:float,avg_weighted?:float}>    Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(dated,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
}
