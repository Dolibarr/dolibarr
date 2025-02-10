<?php
/* Copyright (C) 2014-2015  Florian HENRY               <florian.henry@open-concept.pro>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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

include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 * Class to manage statistics on project tasks
 */
class TaskStats extends Stats
{
	/**
	 * @var Project
	 */
	private $project; // @phpstan-ignore-line

	/**
	 * @var int ID of User
	 */
	public $userid;

	/**
	 * @var int ID of Societe
	 */
	public $socid;

	/**
	 * @var int priority
	 */
	public $priority;

	/**
	 * Constructor of the class
	 *
	 * @param   DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Return all tasks grouped by status.
	 *
	 * @param  int             $limit Limit results
	 * @return array<int,array{0:int|string,1:int}>|int<-1,-1>	Array with value or -1 if error
	 * @throws Exception
	 */
	public function getAllTaskByStatus($limit = 5)
	{
		global $user, $langs;

		$sql = "SELECT";
		$sql .= " COUNT(t.rowid), t.priority";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->buildWhere();
		//$sql .= " AND t.fk_statut <> 0";     // We want historic also, so all task not draft
		$sql .= " GROUP BY t.priority";

		$result = array();

		dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$other = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit) {
					$result[$i] = array(
						$row[1],
						$row[0]
					);
				} else {
					$other += $row[1];
				}
				$i++;
			}
			if ($num > $limit) {
				$result[$i] = array(
						$langs->transnoentitiesnoconv("Other"),
						$other
				);
			}
			$this->db->free($resql);
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this).'::'.__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		return $result;
	}

	/**
	 * Return count, and sum of products
	 *
	 *  @return array<array{year:string,nb:string,nb_diff:float,total?:float,avg?:float,weighted?:float,total_diff?:float,avg_diff?:float,avg_weighted?:float}>    Array of values
	 */
	public function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(t.datec,'%Y') as year, COUNT(t.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}


	/**
	 * Build the where part
	 *
	 * @return string
	 */
	public function buildWhere()
	{
		$sqlwhere_str = '';
		$sqlwhere = array();

		$sqlwhere[] = ' t.entity IN ('.getEntity('project').')';

		if (!empty($this->userid)) {
			$sqlwhere[] = ' t.fk_user_resp = '.((int) $this->userid);
		}
		// Forced filter on socid is similar to forced filter on project. TODO Use project assignment to allow to not use filter on project
		if (!empty($this->socid)) {
			$sqlwhere[] = ' p.fk_soc = '.((int) $this->socid); // Link on thirdparty is on project, not on task
		}
		if (!empty($this->year) && empty($this->month)) {
			$sqlwhere[] = " t.datec BETWEEN '".$this->db->idate(dol_get_first_day($this->year, 1))."' AND '".$this->db->idate(dol_get_last_day($this->year, 12))."'";
		}
		if (!empty($this->year) && !empty($this->month)) {
			$sqlwhere[] = " t.datec BETWEEN '".$this->db->idate(dol_get_first_day($this->year, $this->month))."' AND '".$this->db->idate(dol_get_last_day($this->year, $this->month))."'";
		}
		if (!empty($this->priority)) {
			$sqlwhere[] = " t.priority IN (".$this->db->sanitize($this->priority, 1).")";
		}

		if (count($sqlwhere) > 0) {
			$sqlwhere_str = ' WHERE '.implode(' AND ', $sqlwhere);
		}

		return $sqlwhere_str;
	}

	/**
	 * Return Task number by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int}>	Array with number by month
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$this->year = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, COUNT(t.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc=p.fk_soc AND sc.fk_user=".((int) $user->id);
		}
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		// var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * Return the Task amount by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array<int<0,11>,array{0:int<1,12>,1:int|float}>	Array of values
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		// Return an empty array at the moment because task has no amount
		return array();
	}

	/**
	 * Return average of entity by month
	 * @param	int     $year           year number
	 * @return	array<int<0,11>,array{0:int<1,12>,1:int|float}> Array of average each month
	 */
	protected function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(datef,'%m') as dm, AVG(f.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE f.datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}
}
