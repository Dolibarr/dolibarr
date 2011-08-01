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
 *	\version    $Id: fournisseur.class.php,v 1.8 2011/07/31 23:57:02 eldy Exp $
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
	 *    \brief  Constructeur de la classe
	 *    \param  DB     handler acces base de donnees
	 *    \param  id     id societe (0 par defaut)
	 */

	function Fournisseur($DB, $id=0, $user=0)
	{
		global $config;

		$this->db = $DB;
		$this->id = $id;
		$this->user = $user;
		$this->client = 0;
		$this->fournisseur = 0;
		$this->effectif_id  = 0;
		$this->forme_juridique_code  = 0;

		return 0;
	}


	function nb_open_commande()
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

	function NbProduct()
	{
		$sql = "SELECT count(pf.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur as pf,";
		$sql.= " ".MAIN_DB_PREFIX."product_fournisseur_price as ppf";
		$sql .= " WHERE fk_soc = ".$this->id." AND ppf.fk_product_fournisseur = pf.rowid";

		$resql = $this->db->query($sql) ;
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


	function ProductCommande($user, $fk_product)
	{
		include_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

		$commf = new CommandeFournisseur($this->db);

		$nbc = $this->nb_open_commande();

		dol_syslog("Fournisseur::ProductCommande : nbc = ".$nbc);

		if ($nbc == 0)
		{
			if ( $this->create_commande($user) == 0 )
			{
				$idc = $this->single_open_commande;
			}
		}
		elseif ($nbc == 1)
		{

			$idc = $this->single_open_commande;
		}

		if ($idc > 0)
		{
			$prod = new ProductFournisseur($this->db);
			$prod->fetch($fk_product);
			$prod->fetch_fourn_data($this->id);

			$commf->fetch($idc);
			$commf->addline("Toto",120,1,$prod->tva, $prod->id, 0, $prod->ref_fourn);
		}
	}

	/**
	 *      \brief      Charge indicateurs this->nb de tableau de bord
	 *      \return     int         <0 si ko, >0 si ok
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
	 *    	\brief      Create a supplier category
	 *    	\param      user        User asking creation
	 *		\param		name		Nom categorie
	 *    	\return     int         <0 si ko, 0 si ok
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
	 * Retourne la liste des fournisseurs
	 *
	 *
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='supplier',$maxlen=0)
	{
		return parent::getNomUrl($withpicto,$option,$maxlen);
	}
}

?>
