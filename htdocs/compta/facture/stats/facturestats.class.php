<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/compta/facture/stats/facturestats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des factures
 *       \version    $Id$
 */
include_once DOL_DOCUMENT_ROOT . "/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/facture.class.php";
include_once DOL_DOCUMENT_ROOT . "/fourn/fournisseur.facture.class.php";

/**
 *       \class      FactureStats
 *       \brief      Classe permettant la gestion des stats des factures
 */
class FactureStats extends Stats
{
	var $db ;
	
	var $socid;
	var $where;
	
	var $table_element;
	var $field;
	
	function FactureStats($DB, $socid=0, $mode)
	{
		$this->db = $DB;
		if ($mode == 'customer')
		{
			$object=new Facture($this->db);
			$this->table_element=$object->table_element;
			$this->field='total';
		}
		if ($mode == 'supplier')
		{
			$object=new FactureFournisseur($this->db);
			$this->table_element=$object->table_element;
			$this->field='total_ht';
		}
		
		$this->socid = $socid;
		$this->where =" fk_statut > 0";
		if ($mode == 'customer') $this->where.=" AND (fk_statut != 3 OR close_code != 'replaced')";	// Exclude replaced invoices
		if ($this->socid)
		{
			$this->where.=" AND fk_soc = ".$this->socid;
		}
		
	}


	/**
	 * 	\brief		Renvoie le nombre de facture par année
	 *	\return		array	Array of values
	 */
	function getNbByYear()
	{
		$sql = "SELECT date_format(datef,'%Y') as dm, count(*)";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." GROUP BY dm DESC";
		$sql.= " WHERE ".$this->where;
		
		return $this->_getNbByYear($sql);
	}

	
	/**
	 * 	\brief	Renvoie le nombre de facture par mois pour une année donnée
	 *	\param	year	Year to scan
	 *	\return	array	Array of values
	 */
	function getNbByMonth($year)
	{
		$sql = "SELECT date_format(datef,'%m') as dm, count(*)";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE date_format(datef,'%Y') = ".$year;
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm DESC";

		$res=$this->_getNbByMonth($year, $sql);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	\brief	Renvoie le montant de facture par mois pour une année donnée
	 *	\param	year	Year to scan
	 *	\return	array	Array of values
	 */
	function getAmountByMonth($year)
	{
		$sql = "SELECT date_format(datef,'%m') as dm, sum(".$this->field.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE date_format(datef,'%Y') = ".$year;
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
		$sql = "SELECT date_format(datef,'%m') as dm, avg(".$this->field.")";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE date_format(datef,'%Y') = ".$year;
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
		$sql = "SELECT date_format(datef,'%Y') as year, count(*) as nb, sum(".$this->field.") as total, avg(".$this->field.") as avg";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year DESC";

		return $this->_getAllByYear($sql);
	}	
}

?>
