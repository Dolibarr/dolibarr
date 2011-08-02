<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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
 * 	\file       htdocs/fourn/class/fournisseur.product.class.php
 * 	\ingroup    produit
 * 	\brief      File of class to manage predefined suppliers products
 * 	\version    $Id: fournisseur.product.class.php,v 1.7 2011/07/31 23:57:02 eldy Exp $
 */

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php";


/**
 * 	\class      ProductFournisseur
 * 	\brief      Class to manage predefined suppliers products
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
	*    \brief    Remove all prices for this couple supplier-product
	*    \param    id_fourn    id du fournisseur
	*    \return   int         < 0 si erreur, > 0 si ok
	*/
	function remove_fournisseur($id_fourn)
	{
		$ok=1;

		$this->db->begin();

		// Search all links
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;

		dol_syslog("ProductFournisseur::remove_fournisseur sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// For each link, delete price line
			while ($obj=$this->db->fetch_object($resql))
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
				$sql.= " WHERE fk_product_fournisseur = ".$obj->rowid;

				dol_syslog("ProductFournisseur::remove_fournisseur sql=".$sql);
				$resql2=$this->db->query($sql);
				if (! $resql2)
				{
					$this->error=$this->db->lasterror();
					dol_syslog("ProductFournisseur::remove_fournisseur ".$this->error, LOG_ERR);
					$ok=0;
				}
			}

			// Now delete all link supplier-product (they have no more childs)
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur";
			$sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;

			dol_syslog("ProductFournisseur::remove_fournisseur sql=".$sql);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->error=$this->db->lasterror();
				dol_syslog("ProductFournisseur::remove_fournisseur ".$this->error, LOG_ERR);
				$ok=0;
			}

			if ($ok)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -2;
		}
	}


   /**
	*    \brief    Remove supplier product
	*    \param    rowid     Product id
	*    \return   int       < 0 si erreur, > 0 si ok
	*/
	function remove_product_fournisseur($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog("ProductFournisseur::remove_product_fournisseur sql=".$sql);
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

	/**
	 * 	\brief		Remove a price for a couple supplier-product
	 * 	\param		rowid	Line id of price
	 *	\return		int		<0 if KO, >0 if OK
	 */
	function remove_product_fournisseur_price($rowid)
	{
		global $conf;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog("ProductFournisseur::remove_product_fournisseur_price sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// Remove all entries with no childs
			$sql = "SELECT pf.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur as pf";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pfp.fk_product_fournisseur = pf.rowid";
			$sql.= " WHERE pfp.rowid IS NULL";
			$sql.= " AND pf.entity = ".$conf->entity;

			dol_syslog("ProductFournisseur::remove_product_fournisseur_price sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$ok=1;

				while ($obj=$this->db->fetch_object($resql))
				{
					$rowidpf=$obj->rowid;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur";
					$sql.= " WHERE rowid = ".$rowidpf;

					dol_syslog("ProductFournisseur::remove_product_fournisseur_price sql=".$sql);
					$resql2 = $this->db->query($sql);
					if (! $resql2)
					{
						$this->error=$this->db->lasterror();
						dol_syslog("ProductFournisseur::remove_product_fournisseur_price ".$this->error,LOG_ERR);
						$ok=0;
					}
				}

				if ($ok)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("ProductFournisseur::remove_product_fournisseur_price ".$this->error,LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("ProductFournisseur::remove_product_fournisseur_price ".$this->error,LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


   /**
	*
	*
	*/
	function update($ref, $qty, $buyprice, $user)
	{
		$this->fourn_ref = $ref;

		/* Mise a jour du prix */

		$this->update_buyprice($qty, $buyprice, $user);

		/* Mise a jour de la reference */

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur ";
		$sql .= " SET ref_fourn = '" . $this->fourn_ref ."'";
		$sql .= " WHERE fk_product = " . $this->id;
		$sql .="  AND fk_soc = ".$this->fourn->id;

		$resql = $this->db->query($sql) ;
	}


   /**
	*    \brief  Modifie le prix d'achat pour un fournisseur
	*    \param  qty             	Quantite min pour lequel le prix est valide
	*    \param  buyprice        	Prix d'achat pour la quantite min
	*    \param  user            	Objet user de l'utilisateur qui modifie
	*    \param  price_base_type	HT or TTC
	*    \param  fourn				Supplier
	*/
	function update_buyprice($qty, $buyprice, $user, $price_base_type='HT', $fourn)
	{
		global $mysoc;

		$buyprice=price2num($buyprice);
		$qty=price2num($qty);

		$error=0;
		$this->db->begin();

		// Supprime prix courant du fournisseur pour cette quantite
		$sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price";
		if ($this->product_fourn_price_id)
		{
			$sql.= " WHERE rowid = ".$this->product_fourn_price_id;
		}
		else
		{
			$sql.= " WHERE fk_product_fournisseur = ".$this->product_fourn_id;
			$sql.= " AND quantity = ".$qty;
		}

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($price_base_type == 'TTC')
			{
				$ttx = get_default_tva($fourn,$mysoc,$this->id);
				$buyprice = $buyprice/(1+($ttx/100));
			}
			$unitBuyPrice = price2num($buyprice/$qty,'MU');

			$now=dol_now();

			// Ajoute prix courant du fournisseur pour cette quantite
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
			$sql.= "datec, fk_product_fournisseur, fk_user, price, quantity, unitprice)";
			$sql.= " values('".$this->db->idate($now)."',";
			$sql.= " ".$this->product_fourn_id.",";
			$sql.= " ".$user->id.",";
			$sql.= " ".price2num($buyprice).",";
			$sql.= " ".$qty.",";
			$sql.= " ".$unitBuyPrice;
            $sql.=")";

			dol_syslog("ProductFournisseur::update_buyprice sql=".$sql);
			if (! $this->db->query($sql))
			{
				$error++;
			}

			if (! $error)
			{
				// Ajoute modif dans table log
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log(";
				$sql.= "datec, fk_product_fournisseur,fk_user,price,quantity)";
				$sql.= "values('".$this->db->idate($now)."',";
				$sql.= " ".$this->product_fourn_id.",";
				$sql.= " ".$user->id.",";
				$sql.= " ".price2num($buyprice).",";
				$sql.= " ".$qty;
                $sql.=")";

				$resql=$this->db->query($sql);
				if (! $resql)
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
	 * 	\brief  Modifie le prix d'achat pour un fournisseur par la referecne du produit chez le fournisseur
	 * 	\param  id_fourn        		Id du fournisseur
	 * 	\param  product_fourn_ref 		Ref du produit chez le fournisseur
	 * 	\param  qty             		Quantite pour lequel le prix est valide
	 * 	\param  buyprice        		Prix d'achat pour la quantite
	 * 	\param  user            		Objet user de l'utilisateur qui modifie
	 * 	\return	int						<0 si KO, >0 si OK
	 */
	function UpdateBuyPriceByFournRef($id_fourn, $product_fourn_ref, $qty, $buyprice, $user, $price_base_type='HT')
	{
		global $conf;

		$result=0;

		// Recherche id produit pour cette ref et fournisseur
		$sql = "SELECT fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur";
		$sql.= " WHERE fk_soc = '".$id_fourn."'";
		$sql.= " AND ref_fourn = '".$product_fourn_ref."'";
		$sql.= " AND entity = ".$conf->entity;

		if ($this->db->query($sql))
		{
			if ($obj = $this->db->fetch_object($resql))
			{
				// Met a jour prix pour la qte
				$this->id = $obj->fk_product;
				$result = $this->update_buyprice($id_fourn, $qty, $buyprice, $user, $price_base_type);
			}
		}

		return $result;
	}


   /**
	*    \brief      Charge les informations relatives a un fournisseur
	*    \param      fournid         id du fournisseur
	*    \return     int             < 0 si erreur, > 0 si ok
	*/
	function fetch_fourn_data($fournid)
	{
		global $conf;

		// Check parameters
		if (empty($fournid)) return -1;

		$sql = "SELECT rowid, ref_fourn";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur ";
		$sql.= " WHERE fk_product = ".$this->id;
		$sql.= " AND fk_soc = ".$fournid;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Product::fetch_fourn_data sql=".$sql);
		$result = $this->db->query($sql) ;
		if ($result)
		{
			$result = $this->db->fetch_array($result);
			$this->ref_fourn = $result["ref_fourn"];
			$this->product_fourn_id = $result["rowid"];
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("ProductFournisseur::fetch_fourn_data error=".$this->error, LOG_ERR);
			return -1;
		}
	}

   /**
	*    \brief      Charge les informations relatives a un prix de fournisseur
	*    \param      rowid	         id ligne
	*    \return     int             < 0 if KO, 0 if OK but not found, > 0 if OK
	*/
	function fetch_product_fournisseur_price($rowid)
	{
		$sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice";
		$sql.= ", pf.rowid as product_fourn_id, pf.fk_soc, pf.ref_fourn, pf.fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql.= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
		$sql.= " WHERE pfp.rowid = ".$rowid;
		$sql.= " AND pf.rowid = pfp.fk_product_fournisseur";

		dol_syslog("ProductFournisseur::fetch_product_fournisseur_price sql=".$sql, LOG_DEBUG);
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
			dol_syslog("ProductFournisseur::fetch_product_fournisseur_price error=".$this->error, LOG_ERR);
			return -1;
		}
	}
}
?>
