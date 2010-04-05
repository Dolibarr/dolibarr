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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/compta/deplacement/stats/deplacementstats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des deplacement et notes de frais
 *       \version    $Id$
 */
include_once DOL_DOCUMENT_ROOT . "/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/compta/deplacement/deplacement.class.php";

/**
 *       \class      DeplacementStats
 *       \brief      Classe permettant la gestion des stats des deplacements et notes de frais
 */
class DeplacementStats extends Stats
{
	var $db ;

	var $socid;
	var $where;

	var $table_element;
	var $field;

	/**
	 * Constructor
	 *
	 * @param 	$DB		Database handler
	 * @param 	$socid	Id third party
	 * @return FactureStats
	 */
	function DeplacementStats($DB, $socid=0)
	{
		global $conf;

		$this->db = $DB;

		$object=new Deplacement($this->db);
		$this->from = MAIN_DB_PREFIX.$object->table_element;
		$this->field='km';

		$this->socid = $socid;
		$this->where = " fk_statut > 0";
		$this->where.= " AND entity = ".$conf->entity;
		if ($this->socid)
		{
			$this->where.=" AND fk_soc = ".$this->socid;
		}
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
		$sql.= " GROUP BY dm DESC";

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
		$sql.= " WHERE date_format(dated,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

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
		$sql.= " WHERE date_format(dated,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

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
		$sql.= " GROUP BY year DESC";

		return $this->_getAllByYear($sql);
	}
}

?>
