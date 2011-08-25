<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/compta/deplacement/class/deplacementstats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des deplacement et notes de frais
 *       \version    $Id: deplacementstats.class.php,v 1.6 2011/07/31 22:23:20 eldy Exp $
 */
include_once DOL_DOCUMENT_ROOT . "/core/class/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/compta/deplacement/class/deplacement.class.php";

/**
 *       \class      DeplacementStats
 *       \brief      Classe permettant la gestion des stats des deplacements et notes de frais
 */
class DeplacementStats extends Stats
{
    var $db;

    var $socid;
    var $userid;

    var $table_element;
    var $from;
    var $field;
    var $where;

	/**
	 * Constructor
	 *
	 * @param 	$DB		   Database handler
	 * @param 	$socid	   Id third party
     * @param   $userid    Id user for filter
	 * @return  DeplacementStats
	 */
	function DeplacementStats($DB, $socid=0, $userid=0)
	{
		global $conf;

		$this->db = $DB;
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
        if ($this->userid > 0) $this->where.=' AND fk_user_author = '.$this->userid;
	}


	/**
	 * 	\brief		Renvoie le nombre de facture par annee
	 *	\return		array	Array of values
	 */
	function getNbByYear()
	{
		$sql = "SELECT YEAR(datef) as dm, count(*)";
		$sql.= " FROM ".$this->from;
		$sql.= " GROUP BY dm DESC";
		$sql.= " WHERE ".$this->where;

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	\brief	Renvoie le nombre de facture par mois pour une annee donnee
	 *	\param	year	Year to scan
	 *	\return	array	Array of values
	 */
	function getNbByMonth($year)
	{
		$sql = "SELECT MONTH(dated) as dm, count(*)";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE YEAR(dated) = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getNbByMonth($year, $sql);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	\brief	Renvoie le montant de facture par mois pour une annee donnee
	 *	\param	year	Year to scan
	 *	\return	array	Array of values
	 */
	function getAmountByMonth($year)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, sum(".$this->field.")";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE date_format(dated,'%Y') = '".$year."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
		$sql.= $this->db->order('dm','DESC');

		$res=$this->_getAmountByMonth($year, $sql);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *	\brief	Return average amount
	 *	\param	year	Year to scan
	 *	\return	array	Array of values
	 */
	function getAverageByMonth($year)
	{
		$sql = "SELECT date_format(dated,'%m') as dm, avg(".$this->field.")";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE date_format(dated,'%Y') = '".$year."'";
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
		$sql = "SELECT date_format(dated,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}
}

?>
