<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/fourn/fournisseur.product.class.php
		\ingroup    produit
		\brief      Fichier de la classe des produits prédéfinis
		\version    $Revision$
*/

require_once DOL_DOCUMENT_ROOT."/product.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php";


/**
		\class      Product
		\brief      Classe permettant la gestion des produits prédéfinis
*/
class ProductFournisseur extends Product
{
	var $db ;

	var $id ;
	var $fourn_ref;
	var $fourn;
	var $fourn_qty;
	var $product_fourn_id;
	var $product_fourn_price_id;


	function ProductFournisseur($db)
	{
		$this->db = $db;
	}



	/**
	*    \brief    Délie un fournisseur au produit/service
	*    \param    user        utilisateur qui défait le lien
	*    \param    id_fourn    id du fournisseur
	*    \return   int         < 0 si erreur, > 0 si ok
	*/
	function remove_fournisseur($id_fourn)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur ";
		$sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;

		dolibarr_syslog("ProductFournisseur::remove_fournisseur sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	function remove_product_fournisseur($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE rowid = ".$rowid;

		dolibarr_syslog("ProductFournisseur::remove_product_fournisseur sql=".$sql);
		$resql = $this->db->query($sql);  
		if ($resql)
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}

	/*
	*	\return		int		<0 si KO, 0 si non trouve, >0 si efface
	*/
	function remove_product_fournisseur_price($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql.= " WHERE rowid = ".$rowid;

		dolibarr_syslog("ProductFournisseur::remove_product_fournisseur_price sql=".$sql);
		$resql = $this->db->query($sql);  
		if ($resql)
		{
			if ($this->db->affected_rows() > 0) return 1;
			else return 0;
		}
		else
		{
			return -1;
		}
	}
	
	
	/*
	*
	*
	*/
	function update($ref, $qty, $buyprice, $user) 
	{
		$this->fourn_ref = $ref;

		/* Mise à jour du prix */

		$this->update_buyprice($qty, $buyprice, $user);

		/* Mise à jour de la référence */

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur ";
		$sql .= " SET ref_fourn = '" . $this->fourn_ref ."'";
		$sql .= " WHERE fk_product = " . $this->id;
		$sql .="  AND fk_soc = ".$this->fourn->id;    

		$resql = $this->db->query($sql) ;
	}


	/**
	*    \brief  Modifie le prix d'achat pour un fournisseur
	*    \param  qty             	Quantite min pour lequel le prix est valide
	*    \param  buyprice        	Prix d'achat pour la quantité min
	*    \param  user            	Objet user de l'utilisateur qui modifie
	*    \param  price_base_type	HT or TTC
	*    \param  fourn				Supplier
	*/
	function update_buyprice($qty, $buyprice, $user, $price_base_type='HT', $fourn)
	{
		global $mysoc;
		
		$buyprice=price2num($buyprice);
		
		$error=0;
		$this->db->begin();
		
		// Supprime prix courant du fournisseur pour cette quantité
		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price ";
		if ($this->product_fourn_price_id)
		{
			$sql .= " WHERE rowid = ".$this->product_fourn_price_id;
		}
		else
		{
			$sql .= " WHERE fk_product_fournisseur = ".$this->product_fourn_id." AND quantity = ".$qty;
		}
		
		if ($this->db->query($sql))
		{
			if ($price_base_type == 'TTC')
			{
				$ttx = get_default_tva($fourn,$mysoc,($this->tva_tx?$this->tva_tx:0));
				$buyprice = $buyprice/(1+($ttx/100));
			}
			$unitBuyPrice = price2num($buyprice/$qty,'MU');

			// Ajoute prix courant du fournisseur pour cette quantité
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql.= " SET datec = ".$this->db->idate(mktime());
			$sql.= " ,fk_product_fournisseur = ".$this->product_fourn_id;
			$sql.= " ,fk_user = ".$user->id;
			$sql.= " ,price = ".price2num($buyprice);
			$sql.= " ,quantity = ".$qty;
			$sql.= " ,unitprice = ".$unitBuyPrice;

			dolibarr_syslog("ProductFournisseur::update_buyprice sql=".$sql);
			if (! $this->db->query($sql))
			{
				$error++;
			}

			if (! $error)
			{
				// Ajoute modif dans table log
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log ";
				$sql .= " SET datec = ".$this->db->idate(mktime());
				$sql .= " ,fk_product_fournisseur = ".$this->product_fourn_id;
				$sql .= " ,fk_user = ".$user->id;
				$sql .= " ,price = ".price2num($buyprice);
				$sql .= " ,quantity = ".$qty;

				if (! $this->db->query($sql))
				{
					$error++;
				}
			}

			if (! $error)
			{
				$this->db->commit();
				return 0;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}


	/**
		\brief  Modifie le prix d'achat pour un fournisseur par la référecne du produit chez le fournisseur
		\param  id_fourn        		Id du fournisseur
		\param  product_fourn_ref 		Ref du produit chez le fournisseur
		\param  qty             		Quantite pour lequel le prix est valide
		\param  buyprice        		Prix d'achat pour la quantité
		\param  user            		Objet user de l'utilisateur qui modifie
		\return	int						<0 si KO, >0 si OK
	*/
	function UpdateBuyPriceByFournRef($id_fourn, $product_fourn_ref, $qty, $buyprice, $user, $price_base_type='HT')
	{
		$result=0;

		// Recherche id produit pour cette ref et fournisseur
		$sql = "SELECT fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE fk_soc ='".$id_fourn."'";
		$sql.= " AND ref_fourn='".$product_fourn_ref."'";

		if ($this->db->query($sql))
		{
			if ($obj = $this->db->fetch_object($resql))
			{
				// Met a jour prix pour la qté
				$this->id = $obj->fk_product;
				$result = $this->update_buyprice($id_fourn, $qty, $buyprice, $user, $price_base_type);
			}
		}

		return $result;
	}

	
	/**
	*    \brief      Charge les informations relatives à un fournisseur
	*    \param      fournid         id du fournisseur
	*    \return     int             < 0 si erreur, > 0 si ok
	*/
	function fetch_fourn_data($fournid)
	{
		$sql = "SELECT rowid, ref_fourn";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur ";
		$sql.= " WHERE fk_product = ".$this->id;
		$sql.= " AND fk_soc = ".$fournid;

		dolibarr_syslog("Product::fetch_fourn_data sql=".$sql);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			$result = $this->db->fetch_array();
			$this->ref_fourn = $result["ref_fourn"];
			$this->product_fourn_id = $result["rowid"];
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Product::fetch_fourn_data error=".$this->error);
			return -1;
		}
	}
	
		/**
	*    \brief      Charge les informations relatives à un prix de fournisseur
	*    \param      rowid	         id ligne
	*    \return     int             < 0 if KO, 0 if OK but not found, > 0 if OK
	*/
	function fetch_product_fournisseur_price($rowid)
	{
		$sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice";
		$sql.= ", pf.rowid as product_fourn_id, pf.fk_soc, pf.ref_fourn, pf.fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product_fournisseur as pf";
		$sql.= " WHERE pfp.rowid = ".$rowid." AND pf.rowid = pfp.fk_product_fournisseur";

		dolibarr_syslog("Product::fetch_product_fournisseur_price sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql) ;
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->product_fourn_price_id = $rowid;
				$this->product_fourn_id       = $obj->product_fourn_id;
				$this->fourn_ref              = $obj->ref_fourn;
				$this->fourn_price            = $obj->price;
				$this->fourn_qty              = $obj->quantity;
				$this->fourn_unitprice        = $obj->unitprice;
				$this->product_id             = $obj->fk_product;	// deprecated
				$this->fk_product             = $obj->fk_product;
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dolibarr_syslog("Product::fetch_product_fournisseur_price error=".$this->error, LOG_ERR);
			return -1;
		}
	}
}
?>
