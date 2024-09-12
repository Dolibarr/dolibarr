<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2023      Waël Almoman         <info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/adherents/class/adherentstats.class.php
 *	\ingroup    member
 *	\brief      File for class managing statistics of members
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';


/**
 *	Class to manage statistics of members
 */
class AdherentStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var int
	 */
	public $memberid;
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
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db			Database handler
	 * 	@param 		int			$socid	   	Id third party
	 * 	@param   	int			$userid    	Id user for filter
	 */
	public function __construct($db, $socid = 0, $userid = 0)
	{
		$this->db = $db;
		$this->socid = $socid;
		$this->userid = $userid;

		$object = new Subscription($this->db);

		$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
		$this->from .= ", ".MAIN_DB_PREFIX."adherent as m";

		$this->field = 'subscription';

		$this->where .= " m.statut != -1";
		$this->where .= " AND p.fk_adherent = m.rowid AND m.entity IN (".getEntity('adherent').")";
		if ($this->memberid) {
			$this->where .= " AND m.rowid = ".((int) $this->memberid);
		}
		//if ($this->userid > 0) $this->where .= " AND fk_user_author = ".((int) $this->userid);
	}


	/**
	 * Return the number of proposition by month for a given year
	 *
	 *	@param	int		$year       Year
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *	@return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array of nb each month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(p.dateadh,'%m') as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".dolSqlDateFilter('p.dateadh', 0, 0, (int) $year, 1);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByMonth($year, $sql, $format);
	}

	/**
	 * Return the number of subscriptions by year
	 *
	 * @return	array<array{0:int,1:int}>				Array of nb each year
	 */
	public function getNbByYear()
	{
		$sql = "SELECT date_format(p.dateadh,'%Y') as dm, count(*)";
		$sql .= " FROM ".$this->from;
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
	 *	@return	array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of values by month
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(p.dateadh,'%m') as dm, sum(p.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".dolSqlDateFilter('p.dateadh', 0, 0, (int) $year, 1);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAmountByMonth($year, $sql, $format);
	}

	/**
	 * Return average amount each month
	 *
	 *	@param	int		$year       Year
	 *	@return	array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of average each month
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(p.dateadh,'%m') as dm, avg(p.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".dolSqlDateFilter('p.dateadh', 0, 0, (int) $year, 1);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}


	/**
	 *	Return nb, total and average
	 *
	 *  @return array<array{year:string,nb:string,nb_diff:float,total_diff:float,avg_diff:float,avg_weighted:float}>    Array with nb, total amount, average for each year
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(p.dateadh,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}

	/**
	 *	Return count of member by status group by adh type, total and average
	 *
	 *	@param	int		$numberYears    Number of years to scan (0 = all)
	 *	@return	array<int|string,array{label:string,members_draft:int,members_pending:int,members_uptodate:int,members_expired:int,members_excluded:int,members_resiliated:int,all?:float|int,total_adhtype:float|int}>		Array with total of draft, pending, uptodate, expired, resiliated for each member type
	 */
	public function countMembersByTypeAndStatus($numberYears = 0)
	{
		global $user;

		$now = dol_now();
		$endYear = (int) date('Y');
		$startYear = $endYear - $numberYears;

		$sql = "SELECT t.rowid as fk_adherent_type, t.libelle as label";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_DRAFT, "'members_draft'", 'NULL').") as members_draft";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin IS NULL AND t.subscription = '1')", "'members_pending'", 'NULL').") as members_pending";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin >= '".$this->db->idate($now)."' OR t.subscription = 0)", "'members_uptodate'", 'NULL').") as members_uptodate";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin < '".$this->db->idate($now)."' AND t.subscription = 1)", "'members_expired'", 'NULL').") as members_expired";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_EXCLUDED, "'members_excluded'", 'NULL').") as members_excluded";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_RESILIATED, "'members_resiliated'", 'NULL').") as members_resiliated";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d ON t.rowid = d.fk_adherent_type AND d.entity IN (" . getEntity('adherent') . ")";
		if ($numberYears) {
			$sql .= " AND d.datefin > '".$this->db->idate(dol_get_first_day($startYear))."'";
		}
		$sql .= " WHERE t.entity IN (".getEntity('member_type').")";
		$sql .= " AND t.statut = 1";
		$sql .= " GROUP BY t.rowid, t.libelle";

		dol_syslog("box_members_by_type::select nb of members per type", LOG_DEBUG);
		$result = $this->db->query($sql);

		$MembersCountArray = array();

		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			$totalstatus = array(
				'members_draft' => 0,
				'members_pending' => 0,
				'members_uptodate' => 0,
				'members_expired' => 0,
				'members_excluded' => 0,
				'members_resiliated' => 0
			);
			while ($i < $num) {
				$objp = $this->db->fetch_object($result);
				$MembersCountArray[$objp->fk_adherent_type] = array(
					'label' => $objp->label,
					'members_draft' => (int) $objp->members_draft,
					'members_pending' => (int) $objp->members_pending,
					'members_uptodate' => (int) $objp->members_uptodate,
					'members_expired' => (int) $objp->members_expired,
					'members_excluded' => (int) $objp->members_excluded,
					'members_resiliated' => (int) $objp->members_resiliated
				);
				$totalrow = 0;
				foreach ($MembersCountArray[$objp->fk_adherent_type] as $key => $nb) {
					if ($key != 'label') {
						$totalrow += $nb;
						$totalstatus[$key] += $nb;
					}
				}
				$MembersCountArray[$objp->fk_adherent_type]['total_adhtype'] = $totalrow;
				$i++;
			}
			$this->db->free($result);
			$MembersCountArray['total'] = $totalstatus;
			$MembersCountArray['total']['all'] = array_sum($totalstatus);
		}

		return $MembersCountArray;
	}

	/**
	 *	Return count of member by status group by adh type, total and average
	 *
	 * @param	int		$numberYears    Number of years to scan (0 = all)
	 * @return	array<string,array{label:string,members_draft:int,members_pending:0,members_uptodate:int,members_expired:int,members_excluded:int,members_resiliated:int,all?:float|int,total_adhtag:float|int}>		Array with total of draft, pending, uptodate, expired, resiliated for each member tag
	 */
	public function countMembersByTagAndStatus($numberYears = 0)
	{
		global $user;

		$now = dol_now();
		$endYear = (int) date('Y');
		$startYear = $endYear - $numberYears;
		$MembersCountArray = [];

		$sql = "SELECT c.rowid as fk_categorie, c.label as label";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_DRAFT, "'members_draft'", 'NULL').") as members_draft";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin IS NULL AND t.subscription = '1')", "'members_pending'", 'NULL').") as members_pending";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin >= '".$this->db->idate($now)."' OR t.subscription = 0)", "'members_uptodate'", 'NULL').") as members_uptodate";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_VALIDATED."  AND (d.datefin < '".$this->db->idate($now)."' AND t.subscription = 1)", "'members_expired'", 'NULL').") as members_expired";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_EXCLUDED, "'members_excluded'", 'NULL').") as members_excluded";
		$sql .= ", COUNT(".$this->db->ifsql("d.statut = ".Adherent::STATUS_RESILIATED, "'members_resiliated'", 'NULL').") as members_resiliated";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_member as ct ON c.rowid = ct.fk_categorie";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d ON d.rowid = ct.fk_member";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent_type as t ON t.rowid = d.fk_adherent_type";
		$sql .= " WHERE c.entity IN (".getEntity('member_type').")";
		$sql .= " AND d.entity IN (" . getEntity('adherent') . ")";
		$sql .= " AND t.entity IN (" . getEntity('adherent') . ")";
		if ($numberYears) {
			$sql .= " AND d.datefin > '".$this->db->idate(dol_get_first_day($startYear))."'";
		}
		$sql .= " AND c.fk_parent = 0";
		$sql .= " GROUP BY c.rowid, c.label";
		$sql .= " ORDER BY label ASC";

		dol_syslog("box_members_by_tag::select nb of members per tag", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			$totalstatus = array(
				'members_draft' => 0,
				'members_pending' => 0,
				'members_uptodate' => 0,
				'members_expired' => 0,
				'members_excluded' => 0,
				'members_resiliated' => 0
			);
			while ($i < $num) {
				$objp = $this->db->fetch_object($result);
				$MembersCountArray[$objp->fk_categorie] = array(
					'label' => $objp->label,
					'members_draft' => (int) $objp->members_draft,
					'members_pending' => (int) $objp->members_pending,
					'members_uptodate' => (int) $objp->members_uptodate,
					'members_expired' => (int) $objp->members_expired,
					'members_excluded' => (int) $objp->members_excluded,
					'members_resiliated' => (int) $objp->members_resiliated
				);
				$totalrow = 0;
				foreach ($MembersCountArray[$objp->fk_categorie] as $key => $nb) {
					if ($key != 'label') {
						$totalrow += $nb;
						$totalstatus[$key] += $nb;
					}
				}
				$MembersCountArray[$objp->fk_categorie]['total_adhtag'] = $totalrow;
				$i++;
			}
			$this->db->free($result);
			$MembersCountArray['total'] = $totalstatus;
			$MembersCountArray['total']['all'] = array_sum($totalstatus);
		}

		return $MembersCountArray;
	}
}
