<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/commande/stats/commandestats.class.php
 *       \ingroup    commandes
 *       \brief      Fichier de la classe de gestion des stats des commandes
 *       \version    $Id$
 */
include_once DOL_DOCUMENT_ROOT . "/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/commande/commande.class.php";
include_once DOL_DOCUMENT_ROOT . "/fourn/fournisseur.commande.class.php";


/**
 *       \class      CommandeStats
 *       \brief      Classe permettant la gestion des stats des commandes
 */
class CommandeStats extends Stats
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
	 * @param 	$mode	Option
	 * @return 	PropaleStats
	 */
	function CommandeStats($DB, $socid=0, $mode)
	{
		global $user;
		
		$this->db = $DB;
		
		$this->socid = $socid;
		
		if ($mode == 'customer')
		{
			$object=new Commande($this->db);
			$this->table_element=$object->table_element;
			$this->field='total_ht';
			$this->where.= " c.fk_statut > 0";
		}
		if ($mode == 'supplier')
		{
			$object=new CommandeFournisseur($this->db);
			$this->table_element=$object->table_element;
			$this->field='total_ht';
			$this->where.= " c.fk_statut >= 3 AND c.date_commande IS NOT NULL";
		}
				
		if (!$user->rights->societe->client->voir && !$this->socid) $this->where .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if($this->socid)
		{
			$this->where .= " AND c.fk_soc = ".$this->socid;
		}
		
	}

	/**
	 *    \brief      Renvoie le nombre de commande par mois pour une année donnée
	 *
	 */
	function getNbByMonth($year)
	{
		global $conf;
		global $user;
		 
		$sql = "SELECT date_format(c.date_commande,'%m') as dm, count(*) nb";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_commande,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getNbByMonth($year, $sql);
	}

	/**
	 * Renvoie le nombre de commande par année
	 *
	 */
	function getNbByYear()
	{
		global $conf;
		global $user;
		 
		$sql = "SELECT date_format(c.date_commande,'%Y') as dm, count(*), sum(c.".$this->field.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getNbByYear($sql);
	}

	/**
	 * Renvoie le nombre de commande par mois pour une année donnée
	 *
	 */
	function getAmountByMonth($year)
	{
		global $conf;
		global $user;
		 
		$sql = "SELECT date_format(c.date_commande,'%m') as dm, sum(c.".$this->field.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_commande,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

		return $this->_getAmountByMonth($year, $sql);
	}

	/**
	 * Renvoie le nombre de commande par mois pour une année donnée
	 *
	 */
	function getAverageByMonth($year)
	{
		global $conf;
		global $user;
		 
		$sql = "SELECT date_format(c.date_commande,'%m') as dm, avg(c.".$this->field.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE date_format(c.date_commande,'%Y') = ".$year;
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
		
		$sql = "SELECT date_format(c.date_commande,'%Y') as year, count(*) as nb, sum(c.".$this->field.") as total, avg(".$this->field.") as avg";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", sc.fk_soc, sc.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year DESC";

		return $this->_getAllByYear($sql);
	}	
}

?>
