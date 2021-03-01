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
	 * Return nb of orders
	 *
	 * @return 	int		Nb of orders
	 */
	public function getNbOfOrders()
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
		$sql .= " WHERE cf.fk_soc = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			if ($num == 1)
			{
				$row = $this->db->fetch_row($resql);

				$this->single_open_commande = $row[0];
			}
		}
		return $num;
	}

	/**
	 * Returns number of ref prices (not number of products).
	 *
	 * @return	int		Nb of ref prices, or <0 if error
	 */
	public function nbOfProductRefs()
	{
		global $conf;

		$sql = "SELECT count(pfp.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql .= " WHERE pfp.entity = ".$conf->entity;
		$sql .= " AND pfp.fk_soc = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			return $obj->nb;
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load statistics indicators
	 *
	 * @return     int         <0 if KO, >0 if OK
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $conf, $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(s.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$user->socid)
		{
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".$user->id;
			$clause = "AND";
		}
		$sql .= " ".$clause." s.fournisseur = 1";
		$sql .= " AND s.entity IN (".getEntity('societe').")";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
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
	 *  @return     int         		<0 if KO, 0 if OK
	 */
	public function CreateCategory($user, $name)
	{
		// phpcs:enable
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label,visible,type)";
		$sql .= " VALUES ";
		$sql .= " ('".$this->db->escape($name)."',1,1)";

		dol_syslog("Fournisseur::CreateCategory", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
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
		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE s.fournisseur = 1";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;

		$resql = $this->db->query($sql);

		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$arr[$obj->rowid] = $obj->name;
			}
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
		}
		return $arr;
	}
}
