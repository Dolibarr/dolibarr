<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011	   Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/fourn/class/fournisseur.class.php
 *	\ingroup    fournisseur,societe
 *	\brief      File of class to manage suppliers
 */
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';


/**
 * 	Class to manage suppliers
 */
class Fournisseur extends Societe
{
	public $next_prev_filter = "te.fournisseur = 1"; // Used to add a filter in Form::showrefnav method


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->client = 0;
		$this->fournisseur = 1;
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
	public function fetch($rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '', $is_client = 0, $is_supplier = 1)
	{
		return parent::fetch($rowid, $ref, $ref_ext, $barcode, $idprof1, $idprof2, $idprof3, $idprof4, $idprof5, $idprof6, $email, $ref_alias, $is_client, $is_supplier);
	}

	/**
	 * Return nb of orders
	 *
	 * @return 	int		Nb of orders for current supplier
	 */
	public function getNbOfOrders()
	{
		$num = 0;

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
		$sql .= " WHERE cf.fk_soc = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
		}

		return $num;
	}

	/**
	 * Returns number of ref prices (not number of products) for current supplier
	 *
	 * @return	int		Nb of ref prices, or <0 if error
	 */
	public function nbOfProductRefs()
	{
		global $conf;

		$sql = "SELECT count(pfp.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql .= " WHERE pfp.entity = ".$conf->entity;
		$sql .= " AND pfp.fk_soc = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			return $obj->nb;
		} else {
			return -1;
		}
	}

	/**
	 * Load statistics indicators
	 *
	 * @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $conf, $user, $hookmanager;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(s.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." s.fournisseur = 1";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		// Add where from hooks
		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $this); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["suppliers"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Create a supplier category
	 *
	 *  @param      User	$user       User asking creation
	 *	@param		string	$name		Category name
	 *  @return     int         		Return integer <0 if KO, 0 if OK
	 */
	public function CreateCategory($user, $name)
	{
		// phpcs:enable
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label,visible,type)";
		$sql .= " VALUES ";
		$sql .= " ('".$this->db->escape($name)."',1,1)";

		dol_syslog("Fournisseur::CreateCategory", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			dol_syslog("Fournisseur::CreateCategory : Success");
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("Fournisseur::CreateCategory : Failed (".$this->error.")");
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Return the suppliers list
	 *
	 *	@return		array		Array of suppliers
	 */
	public function ListArray()
	{
		// phpcs:enable
		global $conf;
		global $user;

		$arr = array();

		$sql = "SELECT s.rowid, s.nom as name";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE s.fournisseur = 1";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}

		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$arr[$obj->rowid] = $obj->name;
			}
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
		}
		return $arr;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'facture_fourn'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}
}
