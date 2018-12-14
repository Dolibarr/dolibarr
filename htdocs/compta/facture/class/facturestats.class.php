<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/compta/facture/class/facturestats.class.php
 *       \ingroup    factures
 *       \brief      Fichier de la classe de gestion des stats des factures
 */
include_once DOL_DOCUMENT_ROOT . '/core/class/stats.class.php';
include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 *	Class to manage stats for invoices (customer and supplier)
 */
class FactureStats extends Stats
{
    var $socid;
    var $userid;

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element;
	
    var $from;
    var $field;
    var $where;


	/**
     * 	Constructor
     *
	 * 	@param	DoliDB		$db			Database handler
	 * 	@param 	int			$socid		Id third party for filter. This value must be forced during the new to external user company if user is an external user.
	 * 	@param 	string		$mode	   	Option ('customer', 'supplier')
     * 	@param	int			$userid    	Id user for filter (creation user)
	 */
	function __construct($db, $socid, $mode, $userid=0)
	{
		global $user, $conf;

		$this->db = $db;
        $this->socid = ($socid > 0 ? $socid : 0);
        $this->userid = $userid;
		$this->cachefilesuffix = $mode;

		if ($mode == 'customer')
		{
			$object=new Facture($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as f";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field='total';
			$this->field_line='total_ht';
		}
		if ($mode == 'supplier')
		{
			$object=new FactureFournisseur($this->db);
			$this->from = MAIN_DB_PREFIX.$object->table_element." as f";
			$this->from_line = MAIN_DB_PREFIX.$object->table_element_line." as tl";
			$this->field='total_ht';
			$this->field_line='total_ht';
		}

		$this->where = " f.fk_statut > 0";
		$this->where.= " AND f.entity = ".$conf->entity;
		if (!$user->rights->societe->client->voir && !$this->socid) $this->where .= " AND f.fk_soc = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($mode == 'customer') $this->where.=" AND (f.fk_statut <> 3 OR f.close_code <> 'replaced')";	// Exclude replaced invoices as they are duplicated (we count closed invoices for other reasons)
		if ($this->socid)
		{
			$this->where.=" AND f.fk_soc = ".$this->socid;
		}
        if ($this->userid > 0) $this->where.=' AND f.fk_user_author = '.$this->userid;
		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $this->where.= " AND f.type IN (0,1,2,5)";
		else $this->where.= " AND f.type IN (0,1,2,3,5)";
	}


	/**
	 * 	Return orders number by month for a year
	 *
	 *	@param	int		$year		Year to scan
     *	@param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
	 *	@return	array				Array of values
	 */
	function getNbByMonth($year, $format=0)
	{
		global $user;

		$sql = "SELECT date_format(f.datef,'%m') as dm, COUNT(*) as nb";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getNbByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}


	/**
	 * 	Return invoices number per year
	 *
	 *	@return		array	Array with number by year
	 */
	function getNbByYear()
	{
		global $user;

		$sql = "SELECT date_format(f.datef,'%Y') as dm, COUNT(*), SUM(c.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getNbByYear($sql);
	}


	/**
	 * 	Return the invoices amount by month for a year
	 *
	 *	@param	int		$year		Year to scan
     *	@param	int		$format		0=Label of absiss is a translated text, 1=Label of absiss is month number, 2=Label of absiss is first letter of month
	 *	@return	array				Array with amount by month
	 */
	function getAmountByMonth($year, $format=0)
	{
		global $user;

		$sql = "SELECT date_format(datef,'%m') as dm, SUM(f.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
        $sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		$res=$this->_getAmountByMonth($year, $sql, $format);
		//var_dump($res);print '<br>';
		return $res;
	}

	/**
	 *	Return average amount
	 *
	 *	@param	int		$year	Year to scan
	 *	@return	array			Array of values
	 */
	function getAverageByMonth($year)
	{
		global $user;

		$sql = "SELECT date_format(datef,'%m') as dm, AVG(f.".$this->field.")";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE f.datef BETWEEN '".$this->db->idate(dol_get_first_day($year))."' AND '".$this->db->idate(dol_get_last_day($year))."'";
		$sql.= " AND ".$this->where;
        $sql.= " GROUP BY dm";
        $sql.= $this->db->order('dm','DESC');

		return $this->_getAverageByMonth($year, $sql);
	}

	/**
	 *	Return nb, total and average
	 *
	 *	@return	array	Array of values
	 */
	function getAllByYear()
	{
		global $user;

		$sql = "SELECT date_format(datef,'%Y') as year, COUNT(*) as nb, SUM(f.".$this->field.") as total, AVG(f.".$this->field.") as avg";
		$sql.= " FROM ".$this->from;
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " GROUP BY year";
        $sql.= $this->db->order('year','DESC');

		return $this->_getAllByYear($sql);
	}

	/**
	 *	Return nb, amount of predefined product for year
	 *
	 *	@param	int		$year	Year to scan
	 *	@return	array	Array of values
	 */
	function getAllByProduct($year)
	{
		global $user;

		$sql = "SELECT product.ref, COUNT(product.ref) as nb, SUM(tl.".$this->field_line.") as total, AVG(tl.".$this->field_line.") as avg";
		$sql.= " FROM ".$this->from.", ".$this->from_line.", ".MAIN_DB_PREFIX."product as product";
		if (!$user->rights->societe->client->voir && !$this->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE ".$this->where;
		$sql.= " AND f.rowid = tl.fk_facture AND tl.fk_product = product.rowid";
    	$sql.= " AND f.datef BETWEEN '".$this->db->idate(dol_get_first_day($year,1,false))."' AND '".$this->db->idate(dol_get_last_day($year,12,false))."'";
		$sql.= " GROUP BY product.ref";
        $sql.= $this->db->order('nb','DESC');
        //$sql.= $this->db->plimit(20);

		return $this->_getAllByProduct($sql);
	}
}

