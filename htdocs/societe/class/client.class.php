<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2020       Open-Dsi         		<support@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       htdocs/societe/class/client.class.php
 *		\ingroup    societe
 *		\brief      File for class of customers
 */
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 *	Class to manage customers or prospects
 */
class Client extends Societe
{
	/**
	 * @var string Used to add a filter in Form::showrefnav method
	 */
	public $next_prev_filter = "te.client:in:1,2,3";

	/**
	 * @var array<int,array{id:int,code:string,label:string,picto:string}>
	 */
	public $cacheprospectstatus = array();


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->client = 3;
		$this->fournisseur = 0;
	}


	/**
	 *    Load a third party from database into memory
	 *
	 *    @param	int		$rowid			Id of third party to load
	 *    @param    string	$ref			Reference of third party, name (Warning, this can return several records)
	 *    @param    string	$ref_ext       	External reference of third party (Warning, this information is a free field not provided by Dolibarr)
	 *    @param    string	$barcode       	Barcode of third party to load
	 *    @param    string	$idprof1		Prof id 1 of third party (Warning, this can return several records)
	 *    @param    string	$idprof2		Prof id 2 of third party (Warning, this can return several records)
	 *    @param    string	$idprof3		Prof id 3 of third party (Warning, this can return several records)
	 *    @param    string	$idprof4		Prof id 4 of third party (Warning, this can return several records)
	 *    @param    string	$idprof5		Prof id 5 of third party (Warning, this can return several records)
	 *    @param    string	$idprof6		Prof id 6 of third party (Warning, this can return several records)
	 *    @param    string	$email   		Email of third party (Warning, this can return several records)
	 *    @param    string	$ref_alias 		Name_alias of third party (Warning, this can return several records)
	 * 	  @param	int		$is_client		Is the thirdparty a client ?
	 *    @param	int		$is_supplier	Is the thirdparty a supplier ?
	 *    @return   int						>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '', $is_client = 1, $is_supplier = 0)
	{
		return parent::fetch($rowid, $ref, $ref_ext, $barcode, $idprof1, $idprof2, $idprof3, $idprof4, $idprof5, $idprof6, $email, $ref_alias, $is_client, $is_supplier);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load indicators into this->nb for board
	 *
	 *  @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user, $hookmanager;

		$this->nb = array("prospects" => 0, "customers" => 0);
		$clause = "WHERE";

		$sql = "SELECT count(s.rowid) as nb, s.client";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." s.client IN (1,2,3)";
		$sql .= ' AND s.entity IN ('.getEntity($this->element).')';
		// Add where from hooks
		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $this); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
		}
		$sql .= " GROUP BY s.client";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if ($obj->client == 1 || $obj->client == 3) {
					$this->nb["customers"] += $obj->nb;
				}
				if ($obj->client == 2 || $obj->client == 3) {
					$this->nb["prospects"] += $obj->nb;
				}
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Load array of prospect status
	 *
	 *  @param	int		$active     1=Active only, 0=Not active only, -1=All
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function loadCacheOfProspStatus($active = 1)
	{
		global $langs;

		$sql = "SELECT id, code, libelle as label, picto, sortorder";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm";
		if ($active >= 0) {
			$sql .= " WHERE active = ".((int) $active);
		}
		$sql .= $this->db->order('sortorder,id', 'ASC,ASC');

		$resql = $this->db->query($sql);
		$num = $this->db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $this->db->fetch_object($resql);
			$this->cacheprospectstatus[$obj->id] = array('id'=>$obj->id, 'code'=>$obj->code, 'label'=>($langs->trans("ST_".strtoupper($obj->code)) == "ST_".strtoupper($obj->code)) ? $obj->label : $langs->trans("ST_".strtoupper($obj->code)), 'picto'=>$obj->picto);
			$i++;
		}
		return 1;
	}
}
