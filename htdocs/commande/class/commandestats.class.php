<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *       \file       htdocs/commande/class/commandestats.class.php
 *       \ingroup    commandes
 *       \brief      File of class to manage order statistics
 */
include_once DOL_DOCUMENT_ROOT . '/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


/**
 *    Class to manage order statistics (customer and supplier)
 */
class CommandeStats extends Stats
{
	public $table_element;

	var $socid;
    var $userid;

    var $from;
	var $field;
    var $where;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		   Database handler
	 * @param 	int		$socid	   Id third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * @param 	string	$mode	   Option ('customer', 'supplier')
	 * @param   int		$userid    Id user for filter (creation user)
	 */
	function __construct($db, $socid, $mode, $userid=0)
	{
		global $user, $conf;

		$this->db = $db;

		$this->socid = ($socid > 0 ? $socid : 0);
        $this->userid = $userid;
		$this->cachefilesuffix = $mode; 
        
		if ($mode == 'customer')
		{
			$object=new Commande($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field='total_ht';
			$this->field_line='total_ht';
			$this->where.= " c.fk_statut > 0";    // Not draft and not cancelled
		}
		if ($mode == 'supplier')
		{
			$object=new CommandeFournisseur($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field='total_ht';
			$this->field_line='total_ht';
			$this->where.= " c.fk_statut > 2";    // Only approved & ordered
		}
		//$this->where.= " AND c.fk_soc = s.rowid AND c.entity = ".$conf->entity;
		$this->where.= ' AND c.entity IN ('.getEntity('commande', 1).')';
		
		if (!$user->rights->societe->client->voir && !$this->socid) $this->where .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($this->socid)
		{
			$this->where.=" AND c.fk_soc = ".$this->socid;
		}
        if ($this->userid > 0) $this->where.=' AND c.fk_user_author = '.$this->userid;
	}

	/**
	 * Return orders number by month for a year
	 *
	 * @param	int		$year		Year to scan
	 * @return	array				Array with number by month
	 */
	function getNbByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, COUNT(*) as nb";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getNbByMonth($year, $sql);
		return $res;
	}

	/**
	 * Return orders number per year
	 *
	 * @return	array	Array with number by year
	 *
	 */
	function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%Y') as dm, COUNT(*) as nb, SUM(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the orders amount by month for a year
	 *
	 * @param	int		$year	Year to scan
	 * @return	array			Array with amount by month
	 */
	function getAmountByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, SUM(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getAmountByMonth($year, $sql);
		return $res;
	}

	/**
	 * Return the orders amount average by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%m') as dm, AVG(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array	Array of values
	 */
	function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(c.date_commande,'%Y') as year, COUNT(*) as nb, SUM(c.".$this->field.") as total, AVG(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}

	/**
	 *	Return nb, amount of predefined product for year
	 *
	 *	@param	int		$year	Year to scan
	 *	@return	array	Array of values
	 */
	function getAllByProduct($year)
	{
		global $user;

		$sql = "SELECT product.ref, COUNT(product.ref) as nb, SUM(tl.".$this->field_line.") as total, AVG(tl.".$this->field_line.") as avg";
		$sql.= " FROM ".$this->from.", ".$this->from_line.", ".MAIN_DB_PREFIX."product as product";
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " AND c.rowid = tl.fk_commande AND tl.fk_product = product.rowid";
    	$sql.= " AND c.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year,1,false))."' AND '".$this->db->idate(dol_get_last_day($year,12,false))."'";
		$sql.= " GROUP BY product.ref";
        $sql.= $this->db->order('nb','DESC');
        //$sql.= $this->db->plimit(20);

		return $this->_getAllByProduct($sql);
	}
		
}

