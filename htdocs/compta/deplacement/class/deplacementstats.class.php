<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/compta/deplacement/class/deplacementstats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des deplacement et notes de frais
 */
include_once DOL_DOCUMENT_ROOT . '/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT . '/compta/deplacement/class/deplacement.class.php';

/**
 *	Classe permettant la gestion des stats des deplacements et notes de frais
 */
class DeplacementStats extends Stats
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
	 * @param 	DoliDB		$db		   Database handler
	 * @param 	int			$socid	   Id third party
     * @param   mixed		$userid    Id user for filter or array of user ids
	 * @return 	void
	 */
	public function __construct($db, $socid = 0, $userid = 0)
	{
		global $conf;

		$this->db = $db;
        $this->socid = $socid;
        $this->userid = $userid;

		$object=new Deplacement($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element;
		$this->field='km';

		$this->where = " fk_statut > 0";
		$this->where.= " AND entity = ".$conf->entity;
		if ($this->socid)
		{
			$this->where.=" AND fk_soc = ".$this->socid;
		}
		if (is_array($this->userid) && count($this->userid) > 0) $this->where.=' AND fk_user IN ('.join(',', $this->userid).')';
        elseif ($this->userid > 0) $this->where.=' AND fk_user = '.$this->userid;
	}


	/**
	 * 	Renvoie le nombre de facture par annee
	 *
	 *	@return		array	Array of values
	 */
	public function getNbByYear()
	{
		$sql = "SELECT YEAR(dated) as dm, count(*)";
		$sql.= " FROM ".$this->from;
		$sql.= " GROUP BY dm DESC";
		$sql.= " WHERE ".$this->where;

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	Renvoie le nombre de facture par mois pour une annee donnee
	 *
	 *	@param	string	$year	Year to scan
     *	@param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
	 *	@return	array			Array of values
	 */
	public function getNbByMonth($year, $format = 0)
	{
		$sql = "SELECT MONTH(dated) as dm, count(*)";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE YEAR(dated) = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm', 'DESC');

		$res=$this->_getNbByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	Renvoie le montant de facture par mois pour une annee donnee
	 *
	 *	@param	int		$year		Year to scan
     *	@param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
	 *	@return	array				Array of values
	 */
	public function getAmountByMonth($year, $format = 0)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, sum(".$this->field.")";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE date_format(dated,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
		$sql.= $this->db->order('dm', 'DESC');

		$res=$this->_getAmountByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *	Return average amount
	 *
	 *	@param	int		$year		Year to scan
	 *	@return	array				Array of values
	 */
	public function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, avg(".$this->field.")";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE date_format(dated,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm', 'DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array				Array of values
	 */
    public function getAllByYear()
    {
        $sql = "SELECT date_format(dated,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
        $sql.= " FROM ".$this->from;
        $sql.= " WHERE ".$this->where;
        $sql.= " GROUP BY year";
        $sql.= $this->db->order('year', 'DESC');

        return $this->_getAllByYear($sql);
    }
}
