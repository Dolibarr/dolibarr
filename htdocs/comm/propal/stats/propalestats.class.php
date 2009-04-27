<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/comm/propal/stats/propalestats.class.php
 *	\ingroup    propales
 *	\brief      Fichier de la classe de gestion des stats des propales
 *	\version    $Id$
 */

include_once DOL_DOCUMENT_ROOT . "/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/propal.class.php";


/**
 *	\class      PropaleStats
 *	\brief      Classe permettant la gestion des stats des propales
 */
class PropaleStats extends Stats
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
	 * @return 	PropaleStats
	 */
	function PropaleStats($DB, $socid=0)
	{
		global $user, $conf;
		
		$this->db = $DB;

		$object=new Propal($this->db);

		$this->from = MAIN_DB_PREFIX.$object->table_element." as p";
		$this->from.= ", ".MAIN_DB_PREFIX."societe as s";
		
		$this->field='total';
		
		$this->socid = $socid;
		$this->where.= " fk_statut > 0";
		$this->where.= " AND p.fk_soc = s.rowid AND s.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $this->where .= " AND p.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if($this->socid)
		{
			$this->where .= " AND p.fk_soc = ".$this->socid;
		}
	}


	/**
	 * Renvoie le nombre de proposition par mois pour une année donnée
	 *
	 */
	function getNbByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(p.datep,'%m') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.datep,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getNbByMonth($year, $sql);
	}

	/**
	 * Renvoie le nombre de propale par année
	 *
	 */
	function getNbByYear()
	{
		global $user;
		 
		$sql = "SELECT date_format(p.datep,'%Y') as dm, count(*)";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getNbByYear($sql);
	}
	/**
	 * Renvoie le nombre de propale par mois pour une année donnée
	 *
	 */
	function getAmountByMonth($year)
	{
		global $user;
		 
		$sql = "SELECT date_format(p.datep,'%m') as dm, sum(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.datep,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getAmountByMonth($year, $sql);
	}
	/**
	 *
	 *
	 */
	function getAverageByMonth($year)
	{
		global $user;
		 
		$sql = "SELECT date_format(p.datep,'%m') as dm, avg(p.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(p.datep,'%Y') = ".$year;
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
		global $user;
		
		$sql = "SELECT date_format(p.datep,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year DESC";

		return $this->_getAllByYear($sql);
	}

}
?>
