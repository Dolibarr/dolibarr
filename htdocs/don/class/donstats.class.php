<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
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
 *  \file       htdocs/don/class/donstats.class.php
 *  \ingroup    donations
 *  \brief      File of class to manage donations statistics
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to manage donations statistics
 */
class DonationStats extends Stats
{
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;

	public $socid;
    public $userid;

    /**
     * @var string FROM
     */
    public $from;

    /**
     * @var string field
     */
	public $field;

	/**
	 * @var string WHERE
	 */
    public $where;


    /**
     * Constructor
     *
     * @param	DoliDB	$db      	Database handler
	 * @param 	int		$socid	   	Id third party for filter
	 * @param 	string	$mode	   	Option (not used)
	 * @param   int		$userid    	Id user for filter (creation user)
     */
    public function __construct($db, $socid, $mode, $userid = 0)
    {
		global $user, $conf;

		$this->db = $db;

		$this->socid = ($socid > 0 ? $socid : 0);
        $this->userid = $userid;
		$this->cachefilesuffix = $mode;

        $object = new Don($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element." as d";
		//$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
		//$this->field='weight';	// Warning, unit of weight is NOT USED AND MUST BE
		$this->where .= " d.fk_statut > 0"; // Not draft and not cancelled

		//$this->where.= " AND c.fk_soc = s.rowid AND c.entity = ".$conf->entity;
		$this->where .= " AND d.entity = ".$conf->entity;
		if ($this->userid > 0) $this->where .= ' WHERE c.fk_user_author = '.$this->userid;
    }

    /**
     *  Return shipment number by month for a year
     *
	 *  @param	int		$year		Year to scan
     *  @param	int		$format		0=Label of abscissa is a translated text, 1=Label of abscissa is month number, 2=Label of abscissa is first letter of month
	 *  @return	array				Array with number by month
     */
    public function getNbByMonth($year, $format = 0)
    {
        global $user;

        $sql = "SELECT date_format(d.datedon,'%m') as dm, COUNT(*) as nb";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE d.datedon BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql .= " AND ".$this->where;
		$sql .= " GROUP BY dm";
        $sql .= $this->db->order('dm', 'DESC');

		$res = $this->_getNbByMonth($year, $sql, $format);
		return $res;
    }

	/**
	 * Return shipments number per year
	 *
	 * @return	array	Array with number by year
	 *
	 */
	public function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(d.datedon,'%Y') as dm, COUNT(*) as nb, SUM(d.".$this->field.")";
		$sql .= " FROM ".$this->from;
		$sql .= " WHERE ".$this->where;
		$sql .= " GROUP BY dm";
        $sql .= $this->db->order('dm', 'DESC');

		return $this->_getNbByYear($sql);
	}

    /**
     *  Return nb, total and average
     *
     *  @return	array	Array of values
     */
    public function getAllByYear()
    {
        global $user;

        $sql = "SELECT date_format(d.datedon,'%Y') as year, COUNT(*) as nb, SUM(d.".$this->field.") as total, AVG(".$this->field.") as avg";
        $sql .= " FROM ".$this->from;
        $sql .= " WHERE ".$this->where;
        $sql .= " GROUP BY year";
        $sql .= $this->db->order('year', 'DESC');

        return $this->_getAllByYear($sql);
    }
}
