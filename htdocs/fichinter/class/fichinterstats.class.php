<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/fichinter/class/fichinterstats.class.php
 *       \ingroup    fichinter
 *       \brief      File of class to manage intervention statistics
 */
include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *    Class to manage intervention statistics
 */
class FichinterStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	public $socid;
	public $userid;

	public $from;
	public $field;
	public $where;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		   Database handler
	 * @param 	int		$socid	   Id third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * @param 	string	$mode	   Option ('customer', 'supplier')
	 * @param   int		$userid    Id user for filter (creation user)
	 */
	public function __construct($db, $socid, $mode, $userid = 0)
	{
		global $user, $conf;

		$this->db = $db;

		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->cachefilesuffix = $mode;

		if ($mode == 'customer') {
			$object = new Fichinter($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field = '0';
			$this->field_line = '0';
			//$this->where.= " AND c.fk_statut > 0";    // Not draft and not cancelled
		}
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$this->where .= (!empty($this->where) ? ' AND ' : '')." c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$this->where .= ($this->where ? ' AND ' : '')."c.entity IN (".getEntity('intervention').')';

		if ($this->socid) {
			$this->where .= " AND c.fk_soc = ".((int) $this->socid);
		}
		if ($this->userid > 0) {
			$this->where .= ' AND c.fk_user_author = '.((int) $this->userid);
		}
	}

	/**
	 * Return intervention number by month for a year
	 *
	 * @param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array				Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE c.date_valid BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return interventions number per year
	 *
	 * @return	array	Array with number by year
	 *
	 */
	public function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%Y') as dm, COUNT(*) as nb, 0";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the intervention amount by month for a year
	 *
	 * @param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array				Array with amount by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, 0";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE c.date_valid BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return the intervention amount average by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	public function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, 0";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE c.date_valid BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array	Array of values
	 */
	public function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%Y') as year, COUNT(*) as nb, 0 as total, 0 as avg";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}

	/**
	 *	Return nb, amount of predefined product for year
	 *
	 *	@param	int		$year		Year to scan
	 *  @param  int     $limit      Limit
	 *	@return	array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of values
	 */
	public function getAllByProduct($year, $limit = 0)
	{
		global $user;

		$sql = "SELECT product.ref, COUNT(product.ref) as nb, 0 as total, 0 as avg";
		$sql .= " FROM ".$this->from.", ".$this->from_line.", ".MAIN_DB_PREFIX."product as product";
		//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE ".$this->where;
		$sql .= " AND c.rowid = tl.fk_fichinter AND tl.fk_product = product.rowid";
		$sql .= " AND c.date_valid BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."' AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		$sql .= " GROUP BY product.ref";
		$sql .= $this->db->order('nb', 'DESC');
		//$sql.= $this->db->plimit(20);

		return $this->_getAllByProduct($sql, $limit);
	}
}
