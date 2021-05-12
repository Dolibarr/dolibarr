<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *	\file       htdocs/adherents/class/adherentstats.class.php
 *	\ingroup    member
 *	\brief      Fichier de la classe de gestion des stats des adhérents
 */

include_once DOL_DOCUMENT_ROOT . '/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT . '/adherents/class/subscription.class.php';


/**
 *	Class to manage statistics of members
 */
class AdherentStats extends Stats
{
<<<<<<< HEAD
    public $table_element;

    var $socid;
    var $userid;

    var $from;
    var $field;
    var $where;
=======
    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element;

    public $socid;
    public $userid;

    public $from;
    public $field;
    public $where;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db			Database handler
	 * 	@param 		int			$socid	   	Id third party
     * 	@param   	int			$userid    	Id user for filter
	 */
<<<<<<< HEAD
	function __construct($db, $socid=0, $userid=0)
=======
	public function __construct($db, $socid = 0, $userid = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user, $conf;

		$this->db = $db;
        $this->socid = $socid;
        $this->userid = $userid;

		$object=new Subscription($this->db);

		$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
		$this->from.= ", ".MAIN_DB_PREFIX."adherent as m";

		$this->field='subscription';

		$this->where.= " m.statut != 0";
		$this->where.= " AND p.fk_adherent = m.rowid AND m.entity IN (".getEntity('adherent').")";
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $this->where .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if($this->memberid)
		{
			$this->where .= " AND m.rowid = ".$this->memberid;
		}
        //if ($this->userid > 0) $this->where.=' AND fk_user_author = '.$this->userid;
	}


	/**
	 * Return the number of proposition by month for a given year
	 *
     * @param   int		$year       Year
     *	@param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
     * @return	array				Array of nb each month
	 */
<<<<<<< HEAD
	function getNbByMonth($year, $format=0)
=======
	public function getNbByMonth($year, $format = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
<<<<<<< HEAD
        $sql.= $this->db->order('dm','DESC');
=======
        $sql.= $this->db->order('dm', 'DESC');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $this->_getNbByMonth($year, $sql, $format);
	}

	/**
	 * Return the number of subscriptions by year
	 *
     * @return	array				Array of nb each year
	 */
<<<<<<< HEAD
	function getNbByYear()
=======
	public function getNbByYear()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%Y') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm";
<<<<<<< HEAD
        $sql.= $this->db->order('dm','DESC');
=======
        $sql.= $this->db->order('dm', 'DESC');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $this->_getNbByYear($sql);
	}

	/**
	 * Return the number of subscriptions by month for a given year
	 *
     * @param   int		$year       Year
     * @param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
     * @return	array				Array of amount each month
	 */
<<<<<<< HEAD
	function getAmountByMonth($year, $format=0)
=======
	public function getAmountByMonth($year, $format = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, sum(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
<<<<<<< HEAD
        $sql.= $this->db->order('dm','DESC');
=======
        $sql.= $this->db->order('dm', 'DESC');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $this->_getAmountByMonth($year, $sql, $format);
	}

	/**
	 * Return average amount each month
	 *
     * @param   int		$year       Year
     * @return	array				Array of average each month
	 */
<<<<<<< HEAD
	function getAverageByMonth($year)
=======
	public function getAverageByMonth($year)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, avg(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
<<<<<<< HEAD
        $sql.= $this->db->order('dm','DESC');
=======
        $sql.= $this->db->order('dm', 'DESC');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		return $this->_getAverageByMonth($year, $sql);
	}


	/**
	 *	Return nb, total and average
	 *
	 * 	@return		array					Array with nb, total amount, average for each year
	 */
<<<<<<< HEAD
	function getAllByYear()
=======
	public function getAllByYear()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
<<<<<<< HEAD
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}

=======
        $sql.= $this->db->order('year', 'DESC');

		return $this->_getAllByYear($sql);
	}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
