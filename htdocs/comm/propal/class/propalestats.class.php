<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (c) 2011      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
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
 *	\file       htdocs/comm/propal/class/propalestats.class.php
 *	\ingroup    propales
 *	\brief      File of class to manage proposals statistics
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to manage proposals statistics
 */
class PropaleStats extends Stats
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
	public $join;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		    Database handler
	 * @param 	int		$socid	    Id third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * @param   int		$userid     Id user for filter (creation user)
	 * @param 	string	$mode	    Option ('customer', 'supplier')
	 * @param	int		$typentid   Id typent of thirdpary for filter
	 * @param	int		$categid    Id category of thirdpary for filter
	 */
	public function __construct($db, $socid = 0, $userid = 0, $mode = 'customer', $typentid = 0, $categid = 0)
	{
		$this->db = $db;
		$this->socid = ($socid > 0 ? $socid : 0);
		$this->userid = $userid;
		$this->join = '';

		if ($mode == 'customer') {
			$object = new Propal($this->db);

			$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field_date = 'p.datep';
			$this->field = 'total_ht';
			$this->field_line = 'total_ht';

			//$this->where .= " p.fk_statut > 0";
		}
		if ($mode == 'supplier') {
			$object = new SupplierProposal($this->db);

			$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field_date = 'p.date_valid';
			$this->field = 'total_ht';
			$this->field_line = 'total_ht';

			//$this->where .= " p.fk_statut > 0"; // Validated, accepted, refused and closed
		}
		//$this->where.= " AND p.fk_soc = s.rowid AND p.entity = ".$conf->entity;
		$this->where .= ($this->where ? ' AND ' : '')."p.entity IN (".getEntity('propal').")";
		if ($this->socid) {
			$this->where .= " AND p.fk_soc = ".((int) $this->socid);
		}
		if ($this->userid > 0) {
			$this->where .= ' AND fk_user_author = '.((int) $this->userid);
		}

		if ($typentid) {
			$this->join .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
			$this->where .= ' AND s.fk_typent = '.((int) $typentid);
		}

		if ($categid) {
			$this->where .= ' AND EXISTS (SELECT rowid FROM '.MAIN_DB_PREFIX.'categorie_societe as cats WHERE cats.fk_soc = p.fk_soc AND cats.fk_categorie = '.((int) $categid).')';
		}
	}


	/**
	 * Return propals number by month for a year
	 *
	 * @param	int		$year		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array				Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(".$this->field_date.",'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->field_date." BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return propals number per year
	 *
	 * @return	array	Array with number by year
	 *
	 */
	public function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(".$this->field_date.",'%Y') as dm, COUNT(*) as nb, SUM(c.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the propals amount by month for a year
	 *
	 * @param	int		$year		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array				Array with amount by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		global $user;

		$sql = "SELECT date_format(".$this->field_date.",'%m') as dm, SUM(p.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->field_date." BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);
		return $res;
	}

	/**
	 * Return the propals amount average by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	public function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(".$this->field_date.",'%m') as dm, AVG(p.".$this->field.")";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->field_date." BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
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

		$sql = "SELECT date_format(".$this->field_date.",'%Y') as year, COUNT(*) as nb, SUM(".$this->field.") as total, AVG(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}



	/**
	 *	Return nb, amount of predefined product for year
	 *
	 *	@param	int		$year			Year to scan
	 *  @param 	int     $limit      	Limit
	 *	@return	array					Array of values
	 */
	public function getAllByProduct($year, $limit = 10)
	{
		global $user;

		$sql = "SELECT product.ref, COUNT(product.ref) as nb, SUM(tl.".$this->field_line.") as total, AVG(tl.".$this->field_line.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " INNER JOIN ".$this->from_line." ON p.rowid = tl.fk_propal";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product as product ON tl.fk_product = product.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->join;
		$sql .= " WHERE ".$this->where;
		$sql .= " AND ".$this->field_date." BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."' AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		$sql .= " GROUP BY product.ref";
		$sql .= $this->db->order('nb', 'DESC');
		//$sql.= $this->db->plimit(20);

		return $this->_getAllByProduct($sql, $limit);
	}
}
