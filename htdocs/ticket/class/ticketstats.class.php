<?php
/* Copyright (C) 2016      Jean-François Ferry  <hello@librethic.io>
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
 *       \file       /ticket/class/ticketstats.class.php
 *       \ingroup    ticket
 *       \brief      File for class to manage the ticket stats
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
require_once 'ticket.class.php';


/**
 * Class to manage the ticket stats
 */
class TicketStats extends Stats
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
	 * @param  DoliDB $db     Database handler
	 * @param  int    $socid  Id third party
	 * @param  mixed  $userid Id user for filter or array of user ids
	 * @return void
	 */
	public function __construct($db, $socid = 0, $userid = 0)
	{
		global $conf;

		$this->db = $db;
		$this->socid = $socid;
		$this->userid = $userid;

		$object = new Ticket($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element;
		$this->field = 'timing';

		$this->where = " fk_statut > 0";
		$this->where .= " AND entity = ".((int) $conf->entity);
		if ($this->socid > 0) {
			$this->where .= " AND fk_soc = ".((int) $this->socid);
		}
		if (is_array($this->userid) && count($this->userid) > 0) {
			$this->where .= ' AND fk_user_create IN ('.$this->db->sanitize(implode(',', $this->userid)).')';
		} elseif ($this->userid > 0) {
			$this->where .= " AND fk_user_create = ".((int) $this->userid);
		}
	}

	/**
	 *     Renvoie le nombre de tickets par annee
	 *
	 *    @return array    Array of values
	 */
	public function getNbByYear()
	{
		$sql = "SELECT YEAR(datec) as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " GROUP BY dm DESC";
		$sql .= " WHERE ".$this->where;

		return $this->_getNbByYear($sql);
	}

	/**
	 *  Return the number of tickets per month for a given year
	 *
	 *  @param  int 	$year 		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array            	Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT MONTH(datec) as dm, count(*)";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE YEAR(datec) = ".((int) $year);
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *  Return th eamount of tickets for a month and a given year
	 *
	 *  @param  int 	$year 		Year to scan
	 *	@param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return array               Array of values
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(datec,'%m') as dm, sum(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(datec,'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getAmountByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *    Return average amount
	 *
	 *    @param  int $year Year to scan
	 *    @return array                Array of values
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(datec,'%m') as dm, avg(".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE date_format(datec,'%Y') = '".$this->db->escape($year)."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *    Return nb, total and average
	 *
	 *    @return array                Array of values
	 */
	public function getAllByYear()
	{
		$sql = "SELECT date_format(datec,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
}
