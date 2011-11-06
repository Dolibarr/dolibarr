<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/fourn/class/fournisseur.class.php
 *	\ingroup    fournisseur,societe
 *	\brief      File of class to manage suppliers
 */
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");


/**
 *	\class 	Fournisseur
 *	\brief 	Class to manage suppliers
 */
class Fournisseur extends Societe
{
	var $db;

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function Fournisseur($db)
	{
		global $config;

		$this->db = $db;
		$this->client = 0;
		$this->fournisseur = 0;
		$this->effectif_id  = 0;
		$this->forme_juridique_code  = 0;

		return 0;
	}


	/**
	 * Return nb of orders
	 *
	 * @return 	int		Nb of orders
	 */
	function getNbOfOrders()
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
	 * FIXME This returns number of prices, not number of products. Is it what we want ?
	 */
	function NbProduct()
	{
		$sql = "SELECT count(pfp.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql .= " WHERE pfp.fk_soc = ".$this->id;

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}
		else
		{
			return -1;
		}
	}

	/**
	 *      \brief      Cree la commande au statut brouillon
	 *      \param      user        Utilisateur qui cree
	 *      \return     int         <0 si ko, id de la commande creee si ok
	 */
	function updateFromCommandeClient($user, $idc, $comclientid)
	{
		$comm = new CommandeFournisseur($this->db);
		$comm->socid = $this->id;

		$comm->updateFromCommandeClient($user, $idc, $comclientid);
	}

	/**
	 *      \brief      Cree la commande au statut brouillon
	 *      \param      user        Utilisateur qui cree
	 *      \return     int         <0 si ko, id de la commande creee si ok
	 */
	function create_commande($user)
	{
		dol_syslog("Fournisseur::Create_Commande");
		$comm = new CommandeFournisseur($this->db);
		$comm->socid = $this->id;

		if ($comm->create($user) > 0)
		{
			$this->single_open_commande = $comm->id;
			return $comm->id;
		}
		else
		{
			$this->error=$comm->error;
			dol_syslog("Fournisseur::Create_Commande Failed ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Load statistics indicators
	 *
	 * @return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();
		$clause = "WHERE";

		$sql = "SELECT count(s.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= " ".$clause." s.fournisseur = 1";
		$sql.= " AND s.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["suppliers"]=$obj->nb;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}

	}

	/**
	 *  Create a supplier category
	 *
	 *  @param      user        User asking creation
	 *	@param		name		Nom categorie
	 *  @return     int         <0 if KO, 0 if OK
	 */
	function CreateCategory($user, $name)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label,visible,type)";
		$sql.= " VALUES ";
		$sql.= " ('".$this->db->escape($name)."',1,1)";

		dol_syslog("Fournisseur::CreateCategory sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			dol_syslog("Fournisseur::CreateCategory : Success");
			return 0;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Fournisseur::CreateCategory : Failed (".$this->error.")");
			return -1;
		}
	}

	/**
	 * 	Retourne la liste des fournisseurs
	 *
	 *	@return		array		Array of suppliers
	 */
	function ListArray()
	{
		global $conf;

		$arr = array();

		$sql = "SELECT s.rowid, s.nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$this->user->rights->societe->client->voir && !$this->user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE s.fournisseur = 1";
		$sql.= " AND s.entity = ".$conf->entity;
		if (!$this->user->rights->societe->client->voir && !$this->user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$this->user->id;

		$resql=$this->db->query($sql);

		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
	  {
	  	$arr[$obj->rowid] = stripslashes($obj->nom);
	  }

		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();

		}
		return $arr;
	}

	/**
	 *	Return a link on thirdparty (with picto)
	 *
	 *	@param		int		$withpicto		Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
	 *	@param		string	$option			Target of link ('', 'customer', 'prospect', 'supplier')
	 *	@param		int		$maxlen			Max length of text
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='supplier',$maxlen=0)
	{
		return parent::getNomUrl($withpicto,$option,$maxlen);
	}
}

?>
