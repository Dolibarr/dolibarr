<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/adherents/class/adherentstats.class.php
 *	\ingroup    member
 *	\brief      Fichier de la classe de gestion des stats des adhérents
 */

include_once DOL_DOCUMENT_ROOT . "/core/class/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/adherents/class/cotisation.class.php";


/**
 *	\class      AdherentStats
 *	\brief      Classe permettant la gestion des stats des adherents
 */
class AdherentStats extends Stats
{
    public $table_element;

    var $socid;
    var $userid;

    var $from;
    var $field;
    var $where;


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$DB		Database handler
	 * 	@param 		int			$socid	   Id third party
     * 	@param   	int			$userid    Id user for filter
	 * 	@return 	AdherentStats
	 */
	function AdherentStats($DB, $socid=0, $userid=0)
	{
		global $user, $conf;

		$this->db = $DB;
        $this->socid = $socid;
        $this->userid = $userid;

		$object=new Cotisation($this->db);

		$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
		$this->from.= ", ".MAIN_DB_PREFIX."adherent as m";

		$this->field='cotisation';

		$this->where.= " m.statut != 0";
		$this->where.= " AND p.fk_adherent = m.rowid AND m.entity = ".$conf->entity;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $this->where .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if($this->memberid)
		{
			$this->where .= " AND m.rowid = ".$this->memberid;
		}
        //if ($this->userid > 0) $this->where.=' AND fk_user_author = '.$this->userid;
	}


	/**
	 * Renvoie le nombre de proposition par mois pour une annee donnee
	 */
	function getNbByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByMonth($year, $sql);
	}

	/**
	 * Renvoie le nombre de cotisation par annee
	 *
	 */
	function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%Y') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByYear($sql);
	}
	/**
	 * Renvoie le nombre de cotisation par mois pour une annee donnee
	 *
	 */
	function getAmountByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, sum(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getAmountByMonth($year, $sql);
	}
	/**
	 *
	 *
	 */
	function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%m') as dm, avg(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.dateadh,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getAverageByMonth($year, $sql);
	}


	/**
	 *	\brief	Return nb, total and average
	 *	\return	array	Array of values
	 */
	function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(p.dateadh,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		//if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}

}
?>
