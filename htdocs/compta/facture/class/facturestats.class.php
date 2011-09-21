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
 *       \file       htdocs/compta/facture/class/facturestats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des factures
 */
include_once DOL_DOCUMENT_ROOT . "/core/class/stats.class.php";
include_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";
include_once DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php";
include_once DOL_DOCUMENT_ROOT . "/lib/date.lib.php";

/**
 *       \class      FactureStats
 *       \brief      Classe permettant la gestion des stats des factures
 */
class FactureStats extends Stats
{
    var $socid;
    var $userid;

    public $table_element;
    var $from;
    var $field;
    var $where;


	/**
     * 	Constructor
     *
	 * 	@param	DoliDB		$DB			Database handler
	 * 	@param 	int			$socid		Id third party
	 * 	@param 	string		$mode	   	Option
     * 	@param	int			$userid    	Id user for filter
	 * 	@return FactureStats
	 */
	function FactureStats($DB, $socid=0, $mode, $userid=0)
	{
		global $conf;

		$this->db = $DB;
        $this->socid = $socid;
        $this->userid = $userid;

		if ($mode == 'customer')
		{
			$object=new Facture($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element;
			$this->field='total';
		}
		if ($mode == 'supplier')
		{
			$object=new FactureFournisseur($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element;
			$this->field='total_ht';
		}

		$this->where = " fk_statut > 0";
		$this->where.= " AND entity = ".$conf->entity;
		if ($mode == 'customer') $this->where.=" AND (fk_statut <> 3 OR close_code <> 'replaced')";	// Exclude replaced invoices as they are duplicated (we count closed invoices for other reasons)
		if ($this->socid)
		{
			$this->where.=" AND fk_soc = ".$this->socid;
		}
        if ($this->userid > 0) $this->where.=' AND fk_user_author = '.$this->userid;
	}


	/**
	 * 	Renvoie le nombre de facture par annee
	 *	@return		array	Array of values
	 */
	function getNbByYear()
	{
		$sql = "SELECT YEAR(datef) as dm, COUNT(*)";
		$sql.= " FROM ".$this->from;
        $sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	Renvoie le nombre de facture par mois pour une annee donnee
	 *	@param	year	Year to scan
	 *	@return	array	Array of values
	 */
	function getNbByMonth($year)
	{
		$sql = "SELECT MONTH(datef) as dm, COUNT(*)";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getNbByMonth($year, $sql);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	Renvoie le montant de facture par mois pour une annee donnee
	 *	@param	year	Year to scan
	 *	@return	array	Array of values
	 */
	function getAmountByMonth($year)
	{
		$sql = "SELECT date_format(datef,'%m') as dm, SUM(".$this->field.")";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE date_format(datef,'%Y') = '".$year."'";
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
		$sql = "SELECT date_format(datef,'%m') as dm, AVG(".$this->field.")";
		$sql.= " FROM ".$this->from;
        $sql.= " WHERE datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
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
		$sql = "SELECT date_format(datef,'%Y') as year, COUNT(*) as nb, SUM(".$this->field.") as total, AVG(".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}
}

?>
