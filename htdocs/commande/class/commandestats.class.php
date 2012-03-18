<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/commande/class/commandestats.class.php
 *       \ingroup    commandes
 *       \brief      Fichier de la classe de gestion des stats des commandes
 */
include_once DOL_DOCUMENT_ROOT . "/core/class/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php";
include_once DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.commande.class.php";


/**
 *       \class      CommandeStats
 *       \brief      Classe permettant la gestion des stats des commandes
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
	 * @param 	DoliDB	$DB		   Database handler
	 * @param 	int		$socid	   Id third party for filter
	 * @param 	string	$mode	   Option
	 * @param   int		$userid    Id user for filter
	 */
	function CommandeStats($db, $socid, $mode, $userid=0)
	{
		global $user, $conf;

		$this->db = $db;

		$this->socid = $socid;
        $this->userid = $userid;

		if ($mode == 'customer')
		{
			$object=new Commande($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
			$this->field='total_ht';
			$this->where.= " c.fk_statut > 0";
		}
		if ($mode == 'supplier')
		{
			$object=new CommandeFournisseur($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as c";
			$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
			$this->field='total_ht';
			$this->where.= " c.fk_statut > 0";
		}
		$this->where.= " AND c.fk_soc = s.rowid AND c.entity = ".$conf->entity;

		if (!$user->rights->societe->client->voir && !$this->socid) $this->where .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if($this->socid)
		{
			$this->where .= " AND c.fk_soc = ".$this->socid;
		}
        if ($this->userid > 0) $this->where.=' AND c.fk_user_author = '.$this->userid;
	}

	/**
	 * Return orders number by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	function getNbByMonth($year)
	{
		global $conf;
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, count(*) nb";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_valid,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByMonth($year, $sql);
	}

	/**
	 * Return orders number by year
	 *
	 * @return	array	array with number by year
	 *
	 */
	function getNbByYear()
	{
		global $conf;
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%Y') as dm, count(*), sum(c.".$this->field.")";
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
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	function getAmountByMonth($year)
	{
		global $conf;
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, sum(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_valid,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getAmountByMonth($year, $sql);
	}

	/**
	 * Return the orders amount average by month for a year
	 *
	 * @param	int		$year	year for stats
	 * @return	array			array with number by month
	 */
	function getAverageByMonth($year)
	{
		global $conf;
		global $user;

		$sql = "SELECT date_format(c.date_valid,'%m') as dm, avg(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_valid,'%Y') = '".$year."'";
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

		$sql = "SELECT date_format(c.date_valid,'%Y') as year, count(*) as nb, sum(c.".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}
}

?>
