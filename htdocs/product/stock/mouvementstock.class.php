<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/product/stock/mouvementstock.class.php
 *	\ingroup    stock
 *	\brief      Fichier de la classe de gestion des mouvements de stocks
 *	\version    $Revision$
 */


/**
 *	\class      MouvementStock
 *	\brief      Classe permettant la gestion des mouvements de stocks
 */
class MouvementStock
{

	function MouvementStock($DB)
	{
		$this->db = $DB;
	}

	/**
	 *      \brief      Add a movement in stock (in one direction only)
	 * 		\param		user		User object
	 * 		\param		fk_product	Id of product
	 * 		\param		entrepot_id	Id of warehouse
	 * 		\param		qty			Qty of movement (can be <0 or >0)
	 * 		\param		type		Direction of movement: 2=output (stock decrease), 3=input (stock increase)
	 * 		\param		type		Unit price HT of product
	 *      \return     int     	<0 if KO, >0 if OK
	 */
	function _create($user, $fk_product, $entrepot_id, $qty, $type, $price=0)
	{
		global $conf;

		$error = 0;
		dol_syslog("MouvementStock::_create start userid=$user->id, fk_product=$fk_product, warehouse=$entrepot_id, qty=$qty, type=$type, price=$price");

		$this->db->begin();

		$product = new Product($this->db);
		$result=$product->fetch($fk_product);
		if (! $result > 0)
		{
			dol_print_error('',"Failed to fetch product");
			return -1;
		}

		if (1 == 1)	// Always change stock for current product, change for subproduct is done after
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement";
			$sql.= " (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author, price)";
			$sql.= " VALUES (".$this->db->idate(gmmktime()).", ".$fk_product.", ".$entrepot_id.", ".$qty.", ".$type.", ".$user->id;
			$sql.= ",'".price2num($price)."')";

			dol_syslog("MouvementStock::_create sql=".$sql, LOG_DEBUG);
			if ($resql = $this->db->query($sql))
			{
				$mvid = $this->db->last_insert_id($resql);
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("MouvementStock::_create ".$this->error, LOG_ERR);
				$error = -1;
			}

			// Define current values for qty and pmp
			$oldqty=$product->stock_reel;
			$oldqtywarehouse=0;
			$oldpmp=$product->pmp;
			$oldpmpwarehouse=0;

			// Test if there is already a record for couple (warehouse / product)
			$num = 0;
			if (! $error)
			{
				$sql = "SELECT rowid, reel, pmp FROM ".MAIN_DB_PREFIX."product_stock";
				$sql.= " WHERE fk_entrepot = ".$entrepot_id." AND fk_product = ".$fk_product;

				dol_syslog("MouvementStock::_create sql=".$sql);
				if ($this->db->query($sql))
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						$num = 1;
						$oldqtywarehouse = $obj->reel;
						$oldpmpwarehouse = $obj->pmp;
					}
					$this->db->free($resql);
				}
				else
				{
					$this->error=$this->db->lasterror();
					dol_syslog("MouvementStock::_create echec update ".$this->error, LOG_ERR);
					$error = -2;
				}
			}

			// Calculate new PMP.
			if (! $error)
			{
				$newpmp=0;
				$newpmpwarehouse=0;
				// Note: PMP is calculated on stock input only (type = 3). If type == 3, qty should be > 0.
				// Note: Price should always be >0 or 0. PMP should be always >0 (calculated on input)
				if ($type == 3 && $price > 0)
				{
					$oldqtytouse=($oldqty >= 0?$oldqty:0);
					// We make a test on oldpmp>0 to avoid to use normal rule on old data with no pmp field defined
					if ($oldpmp > 0) $newpmp=price2num((($oldqtytouse * $oldpmp) + ($qty * $price)) / ($oldqtytouse + $qty), 'MU');
					else $newpmp=$price;
					$oldqtywarehousetouse=($oldqtywarehouse >= 0?$oldqty:0);
					if ($oldpmpwarehouse > 0) $newpmpwarehouse=price2num((($oldqtywarehousetouse * $oldpmpwarehouse) + ($qty * $price)) / ($oldqtywarehousetouse + $qty), 'MU');
					else $newpmpwarehouse=$price;
				}
				else
				{
					$newpmp = $oldpmp;
					$newpmpwarehouse = $oldpmpwarehouse;
				}
			}

			// Update denormalized value of stock in product_stock and product
			if (! $error)
			{
				if ($num > 0)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."product_stock SET pmp = ".$newpmpwarehouse.", reel = reel + ".$qty;
					$sql.= " WHERE fk_entrepot = ".$entrepot_id." AND fk_product = ".$fk_product;
				}
				else
				{
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock";
					$sql.= " (pmp, reel, fk_entrepot, fk_product) VALUES ";
					$sql.= " (".$newpmpwarehouse.", ".$qty.", ".$entrepot_id.", ".$fk_product.")";
				}

				dol_syslog("MouvementStock::_create sql=".$sql);
				$resql=$this->db->query($sql);
				if (! $resql)
				{
					$this->error=$this->db->lasterror();
					dol_syslog("MouvementStock::_create ".$this->error, LOG_ERR);
					$error = -3;
				}
			}

			if (! $error)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."product SET pmp = ".$newpmp.", stock = stock + ".$qty;
				$sql.= " WHERE rowid = ".$fk_product;

				dol_syslog("MouvementStock::_create sql=".$sql);
				$resql=$this->db->query($sql);
				if (! $resql)
				{
					$this->error=$this->db->lasterror();
					dol_syslog("MouvementStock::_create ".$this->error, LOG_ERR);
					$error = -4;
				}
			}
		}

		// Add movement for sub products
		if (! $error && $conf->global->PRODUIT_SOUSPRODUITS)
		{
			$error = $this->_createSubProduct($user, $fk_product, $entrepot_id, $qty, $type, 0);	// pmp is not change for subproduct
		}

		// composition module
		if (! $error && $qty < 0 && $conf->global->MAIN_MODULE_COMPOSITION)
		{
			$error = $this->_createProductComposition($user, $fk_product, $entrepot_id, $qty, $type, 0);	// pmp is not change for subproduct
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_syslog("MouvementStock::_create error code=".$error, LOG_ERR);
			return -6;
		}
	}


	/**
	 *      \brief      Create movement in database for all subproducts
	 *      \return     int     <0 si ko, 0 si ok
	 */
	function _createSubProduct($user, $idProduct, $entrepot_id, $qty, $type, $price=0)
	{
		$error = 0;
		$pids = array();
		$pqtys = array();

		$sql = "SELECT fk_product_pere, fk_product_fils, qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_association";
		$sql.= " WHERE fk_product_pere = ".$idProduct;

		dol_syslog("MouvementStock::_createSubProduct sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj=$this->db->fetch_object($resql))
			{
				$pids[$i]=$obj->fk_product_fils;
				$pqtys[$i]=$obj->qty;
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_syslog("MouvementStock::_createSubProduct ".$this->error, LOG_ERR);
			$error = -2;
		}

		// Create movement for each subproduct
		foreach($pids as $key => $value)
		{
			$this->_create($user, $pids[$key], $entrepot_id, ($qty * $pqtys[$key]), $type, 0);
		}

		return $error;
	}


	/**
	 *      \brief      Cree un mouvement en base pour toutes les compositions de produits
	 *      \return     int     <0 si ko, 0 si ok
	 */
	function _createProductComposition($user, $fk_product, $entrepot_id, $qty, $type, $price=0)
	{
		dol_syslog("MouvementStock::_createProductComposition $user->id, $fk_product, $entrepot_id, $qty, $type, $price");
		$products_compo = array();

		$sql = "SELECT fk_product_composition, qte, etat_stock";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_composition";
		$sql.= " WHERE fk_product = $fk_product;";

		$all = $this->db->query($sql);

		if ($all)
		{
			while($item = $this->db->fetch_object($all)	)
			{
				if($item->etat_stock != 0) array_push($products_compo,$item);
			}
			$this->db->free($resql);
		}
		else
		{
			dol_syslog("MouvementStock::_createProductComposition echec update ".$this->error, LOG_ERR);
			return -1;
		}

		// Create movement for each subproduct
		foreach($products_compo as $product)
		{
			$this->_create($user, $product->fk_product_composition, $entrepot_id, ($qty*$product->qte), $type, 0);
		}

		return 0;
	}


	/**
	 *	\brief		Decrease stock for product and subproducts
	 *	\return		int		<0 if KO, >0 if OK
	 */
	function livraison($user, $fk_product, $entrepot_id, $qty, $price=0)
	{
		return $this->_create($user, $fk_product, $entrepot_id, (0 - $qty), 2, $price);
	}


	/**
	 *	\brief		Increase stock for product and subproducts
	 *	\return		int		<0 if KO, >0 if OK
	 */
	function reception($user, $fk_product, $entrepot_id, $qty, $price=0)
	{
		return $this->_create($user, $fk_product, $entrepot_id, $qty, 3, $price);
	}


	/**
	 * Return nb of subproducts lines for a product
	 *
	 * @param unknown_type $id
	 * @return unknown
	 */
	function nbOfSubProdcuts($id)
	{
		$nbSP=0;

		$resql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."product_association";
		$resql.= " WHERE fk_product_pere = ".$id;
		if ($this->db->query($resql))
		{
			$obj=$this->db->fetch_object($resql);
			$nbSP=$obj->nb;
		}
		$this->db->free($resql);
		return $nbSP;
	}

}
?>
