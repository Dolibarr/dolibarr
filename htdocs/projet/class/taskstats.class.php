<?php
/* Lead
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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
	private $project;
	public $userid;
	public $socid;
	public $year;

    /**
     * Constructor of the class
     *
     * @param   DoliDb  $db     Database handler
     */
	public function __construct($db)
	{
		$this->db = $db;

		require_once 'task.class.php';
		$this->task = new Task($this->db);
	}


	/**
	 * Return all tasks grouped by status.
	 *
	 * @param  int             $limit Limit results
	 * @return array|int       Array with value or -1 if error
	 * @throws Exception
	 */
	public function getAllTaskByStatus($limit = 5)
	{
		global $conf, $user, $langs;

		$datay = array();

		$sql = "SELECT";
		$sql .= " COUNT(t.rowid), t.priority";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->rights->societe->client->voir && !$user->soc_id)
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=".$user->id;
		$sql .= $this->buildWhere();
		//$sql .= " AND t.fk_statut <> 0";     // We want historic also, so all task not draft
		$sql .= " GROUP BY t.priority";

		$result = array();
		$res = array();

		dol_syslog(get_class($this).'::'.__METHOD__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$other = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit)
				{
					$result[$i] = array(
						$row[1],
						$row[0]
					);
				}
				else
					$other += $row[1];
				$i++;
			}
			if ($num > $limit)
				$result[$i] = array(
						$langs->transnoentitiesnoconv("Other"),
						$other
				);
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
	 * @return array of values
	 */
	public function getAllByYear()
	{
		global $conf, $user, $langs;

		$datay = array();

		$wonlostfilter = 0; // No filter on status WON/LOST

		$sql = "SELECT date_format(t.datec,'%Y') as year, COUNT(t.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->rights->societe->client->voir && !$user->soc_id)
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=".$user->id;
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

		if (!empty($this->userid))
			$sqlwhere[] = ' t.fk_user_resp='.$this->userid;
		// Forced filter on socid is similar to forced filter on project. TODO Use project assignement to allow to not use filter on project
		if (!empty($this->socid))
			$sqlwhere[] = ' p.fk_soc='.$this->socid; // Link on thirdparty is on project, not on task
		if (!empty($this->year) && empty($this->yearmonth))
			$sqlwhere[] = " date_format(t.datec,'%Y')='".$this->db->escape($this->year)."'";
		if (!empty($this->yearmonth))
			$sqlwhere[] = " t.datec BETWEEN '".$this->db->idate(dol_get_first_day($this->yearmonth))."' AND '".$this->db->idate(dol_get_last_day($this->yearmonth))."'";

		if (!empty($this->status))
			$sqlwhere[] = " t.priority IN (".$this->priority.")";

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
	 * @return 	array 				Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		global $user;

		$this->yearmonth = $year;

		$sql = "SELECT date_format(t.datec,'%m') as dm, COUNT(t.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = t.fk_projet";
		if (!$user->rights->societe->client->voir && !$user->soc_id)
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc=t.fk_soc AND sc.fk_user=".$user->id;
		$sql .= $this->buildWhere();
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$this->yearmonth = 0;

		$res = $this->_getNbByMonth($year, $sql, $format);
		// var_dump($res);print '<br>';
		return $res;
	}
}
