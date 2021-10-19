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
class TimeStats extends Stats
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
	}

	/**
	 * Return Consumed time by month for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array of values
	 */
	public function getConsumedTimeByMonth($startyear, $endyear, $format = 0)
	{
		if ($startyear > $endyear) {
			return -1;
		}

		$datay = array();
		$year = $startyear;
		$sm = 0;

		while ($year <= $endyear)
		{
			$this->yearmonth = $year;

			$sql = "SELECT date_format(t.task_date,'%m') as dm, ROUND(SUM(t.task_duration)/3600) AS nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
			$sql .= $this->buildWhere();
			$sql .= " GROUP BY dm";
			$sql .= $this->db->order('dm', 'DESC');

			$datay[$year] = $this->_getNbByMonth($year, $sql, $format);
			$year++;
		}

		$data = array();

		for ($i = 0; $i < 12; $i++) {
			$data[$i][] = $datay[$endyear][($i + $sm) % 12][0];
			$year = $startyear;
			while ($year <= $endyear) {
				$data[$i][] = $datay[$year - (1 - ((int) ($i + $sm) / 12)) + ($sm == 0 ? 1 : 0)][($i + $sm) % 12][1];
				$year++;
			}
		}

		// var_dump($data);print '<br>';
		return $data;
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

		if (!empty($this->yearmonth)) {
			$sqlwhere[] = " t.task_date BETWEEN '".$this->db->idate(dol_get_first_day($this->yearmonth))."' AND '".$this->db->idate(dol_get_last_day($this->yearmonth))."'";
		}

		if (count($sqlwhere) > 0) {
			$sqlwhere_str = ' WHERE '.implode(' AND ', $sqlwhere);
		}

		return $sqlwhere_str;
	}

	/**
	 * Return Consumed time by user for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array of values
	 */
	public function getConsumedTimeByUser($year, $format = 0)
	{
		$sql = "SELECT  CONCAT(lastname, ' ', firstname) AS username, ROUND(SUM(task_duration)/3600) AS nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as ptt";
		$sql .= " LEFT JOIN gmelectronics.".MAIN_DB_PREFIX."user ON ".MAIN_DB_PREFIX."user.rowid = ptt.fk_user";
		$sql .= " WHERE YEAR(task_date)=".$year." AND ".MAIN_DB_PREFIX."user.statut=1";
		$sql .= " GROUP BY ptt.fk_user";

		// var_dump($data);print '<br>';
		return $this->_getNbByEntity($year, $sql, $format);
	}

	/**
	 * Return Consumed time by project for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array of values
	 */
	public function getConsumedTimeByProject($year, $format = 0)
	{
		$sql = "SELECT p.title AS projectTitle, ROUND(SUM(task_duration)/3600) AS nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet AS p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task ON ".MAIN_DB_PREFIX."projet_task.fk_projet = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_time ON ".MAIN_DB_PREFIX."projet_task.rowid = ".MAIN_DB_PREFIX."projet_task_time.fk_task";
		$sql .= " WHERE YEAR(task_date)=".$year;
		$sql .= " GROUP BY p.rowid ORDER BY nb DESC";

		// var_dump($data);print '<br>';
		return $this->_getNbByEntity($year, $sql, $format, 10);
	}

	/**
	 * Return Consumed time by team for a year
	 *
	 * @param 	int 	$year 		Year to scan
	 * @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 * @return 	array 				Array of values
	 */
	public function getConsumedTimeByTeam($year, $format = 0)
	{
		$sql = "SELECT ".MAIN_DB_PREFIX."projet_task.label AS taskTitle, ROUND(SUM(task_duration)/3600) AS nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as ptt";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task ON ".MAIN_DB_PREFIX."projet_task.rowid = ptt.fk_task";
		$sql .= " WHERE YEAR(task_date)=".$year." GROUP BY ".MAIN_DB_PREFIX."projet_task.label";

		// var_dump($data);print '<br>';
		return $this->_getNbByEntityWithExplode($year, $sql, $format, "-");
	}

}
