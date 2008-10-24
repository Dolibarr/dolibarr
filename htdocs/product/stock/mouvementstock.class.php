<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	 *      \brief      Add a mouvement in stock (in one direction only)
	 * 		\param		type	Direction of movement: 2=output (stock decrease), 3=input (stock increase)
	 *      \return     int     <0 if KO, >0 if OK
	 */
	function _create($user, $fk_product, $entrepot_id, $qty, $type, $price=0)
	{
		global $conf;
		
		$error = 0;
		dolibarr_syslog("MouvementStock::_Create $user->id, $fk_product, $entrepot_id, qty=$qty, type=$type, $price");

		$this->db->begin();

		// $nbOfSubproduct=$this->nbOfSubProdcuts();

		if (1 == 1)	// Always change stock for current product
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement";
			$sql.= " (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author, price)";
			$sql.= " VALUES (".$this->db->idate(mktime()).", ".$fk_product.", ".$entrepot_id.", ".$qty.", ".$type.", ".$user->id;
			$sql.= ",'".price2num($price)."')";
	
			dolibarr_syslog("MouvementStock::_create sql=".$sql, LOG_DEBUG);
			if ($resql = $this->db->query($sql))
			{
				$mvid = $this->db->last_insert_id($resql);
			}
			else
			{
				dolibarr_syslog("MouvementStock::_Create ".$this->error);
				$error = -1;
			}
			
			// Get current value of stock
			$num = 0;
			if ($error == 0)
			{
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_stock";
				$sql.= " WHERE fk_entrepot = ".$entrepot_id." AND fk_product = ".$fk_product;

				if ($this->db->query($sql))
				{
					$num = $this->db->num_rows($resql);
					$this->db->free($resql);
				}
				else
				{
					dolibarr_syslog("MouvementStock::_Create echec update ".$this->error);
					$error = -2;
				}
			}

			// Update value
			if ($error == 0)
			{
				if ($num > 0)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."product_stock SET reel = reel + ".$qty;
					$sql.= " WHERE fk_entrepot = ".$entrepot_id." AND fk_product = ".$fk_product;
				}
				else
				{
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock";
					$sql.= " (reel, fk_entrepot, fk_product) VALUES ";
					$sql.= " (".$qty.",".$entrepot_id.",".$fk_product.")";
				}

				dolibarr_syslog("MouvementStock::_create sql=".$sql, LOG_DEBUG);
				if ($this->db->query($sql))
				{

				}
				else
				{
					dolibarr_syslog("MouvementStock::_Create ".$this->error, LOG_ERR);
					$error = -3;
				}
			}

			if ($error == 0)
			{
				$valo_mouvement = 0;
				$error = $this->CalculateValoPmp($mvid, $fk_product, $qty, $price, $valo_mouvement);
			}
	
			if ($error == 0)
			{
				$error = $this->CalculateEntrepotValoPmp($user, $entrepot_id, $valo_mouvement);
			}
		}
		
		// Add movement for sub products 
		if ($conf->global->PRODUIT_SOUSPRODUITS)
		{
			$error = $this->_createSubProduct($user, $fk_product, $entrepot_id, $qty, $type, $price=0);
		}
		
		if ($error == 0)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dolibarr_syslog("MouvementStock::_Create ".$this->error);
			return -2;
		}
	}


	/**
	 *      \brief      Cr�e un mouvement en base pour tous les sous-produits
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

		dolibarr_syslog("MouvementStock::_createSubProduct sql=".$sql, LOG_DEBUG);
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
			dolibarr_syslog("MouvementStock::_createSubProduct ".$this->error, LOG_ERROR);
			$error = -2;
		}

		// Create movement for each subproduct
		foreach($pids as $key => $value)
		{
			$this->_create($user, $pids[$key], $entrepot_id, ($qty * $pqtys[$key]), $type, $price);
		}

		return $error;
	}



	/**
	 *      \brief      Calcul ???
	 *      \return     int    		<0 si ko, >0 si ok
	 */
	function CalculateEntrepotValoPmp($user, $entrepot_id, $valo_mouvement)
	{
		$error = 0;
		dolibarr_syslog("MouvementStock::CalculateEntrepotValoPmp $user->id, $entrepot_id, $valo_mouvement");

		if ( $valo_mouvement <> 0 )
		{
	  $entrepot_value_pmp = 0;

	  if ($error === 0)
	  {
	  	$sql = "SELECT valo_pmp,".$this->db->pdate("date_calcul")." FROM ".MAIN_DB_PREFIX."entrepot_valorisation";
	  	$sql.= " WHERE fk_entrepot = $entrepot_id ORDER BY date_calcul DESC LIMIT 1;";
	  	 
	  	if ($this->db->query($sql))
	  	{
	  		while ($row = $this->db->fetch_row($resql) )
	  		{
	  			$entrepot_value_pmp  = $row[0];
	  			$entrepot_value_date = $row[1];
	  		}
	  		$this->db->free($resql);
	  	}
	  	else
	  	{
	  		$error = -26;
	  		dolibarr_syslog("MouvementStock::CalculateEntrepotValoPmp ERRORSQL[$error]");
	  	}
	  }

	  $new_value = $entrepot_value_pmp + $valo_mouvement;

	  $now = time();

	  if ($error === 0)
	  {
	  	if ( strftime('%Y%m%d',$entrepot_value_date) == strftime('%Y%m%d',$now) )
	  	{
	  		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot_valorisation";
	  		$sql.= " SET valo_pmp='".ereg_replace(",",".",$new_value)."'";
	  		$sql.= " WHERE fk_entrepot = $entrepot_id ";
	  		$sql.= " AND ".$this->db->pdate("date_calcul")."='".$entrepot_value_date."';";
	  	}
	  	else
	  	{
	  		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot_valorisation";
	  		$sql.= " (date_calcul, fk_entrepot, valo_pmp)";
	  		$sql.= " VALUES (".$this->db->idate(mktime()).", ".$entrepot_id;
	  		$sql.= ",'".ereg_replace(",",".",$new_value)."');";
	  	}
	  	 
	  	if ($this->db->query($sql))
	  	{

	  	}
	  	else
	  	{
	  		$error = -27;
	  		dolibarr_syslog("MouvementStock::CalculateEntrepotValoPmp ERRORSQL[$error]");
	  	}
	  }

	  if ($error === 0)
	  {
	  	$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot";
	  	$sql.= " SET valo_pmp='".ereg_replace(",",".",$new_value)."'";
	  	$sql.= " WHERE rowid = $entrepot_id ";
	  	 
	  	if ($this->db->query($sql))
	  	{

	  	}
	  	else
	  	{
	  		$error = -28;
	  		dolibarr_syslog("MouvementStock::CalculateEntrepotValoPmp ERRORSQL[$error]");
	  	}
	  }

	  if ($error === 0)
	  {
	  	return 0;
	  }
	  else
	  {
	  	dolibarr_syslog("MouvementStock::CalculateEntrepotValoPmp RETURN IN ERROR[$error]");
	  	return $error;
	  }
		}
		else
		{
	  return 0;
		}
	}


	/**
	 * \brief  ???
	 * \param  mvid         int    Id du mouvement
	 * \param  fk_product   int    Id produit
	 * \param  qty          float  Quantit�
	 * \param  price        float  Prix unitaire du produit
	 * \param  value_ope    float  Valeur du mouvement en retour
	 * \return int          <0 si ko, 0 si ok
	 */
	function CalculateValoPmp($mvid, $fk_product, $qty, $price=0, &$value_ope)
	{
		$error = 0;
		dolibarr_syslog("MouvementStock::CalculateValoPmp $mvid, $fk_product, $qty, $price");

		if ( $qty <> 0 )
		{
			$price_pmp = 0;
			$qty_stock = 0;
			$stock_value_pmp = 0;

			if ($error === 0)
			{
				$sql = "SELECT price_pmp, qty_stock, valo_pmp FROM ".MAIN_DB_PREFIX."stock_valorisation";
				$sql.= " WHERE fk_product = $fk_product ORDER BY date_valo DESC LIMIT 1;";

				if ($this->db->query($sql))
				{
					while ($row = $this->db->fetch_row($resql) )
					{
						$price_pmp = $row[0];
						$qty_stock = $row[1];
						$stock_value_pmp = $row[2];
					}
					$this->db->free($resql);
				}
				else
				{
					dolibarr_syslog("MouvementStock::CalculateValoPmp ERRORSQL[1] ".$this->error);
					$error = -16;
				}
			}

			/*
			 * Calcul
			 */
			if ($qty > 0)
			{
				// on stock
				if (($qty + $qty_stock) <> 0)
				$new_pmp = ( ($qty * $price) + ($qty_stock * $price_pmp ) ) / ($qty + $qty_stock);

				$value_ope = $qty * $price;
				$new_stock_qty = $qty_stock + $qty;
				$new_stock_value_pmp = $stock_value_pmp + $value_ope;
			}
			else
			{
				// on destock
				$new_pmp = $price_pmp;
				$price = $price_pmp;
				$value_ope = $qty * $price_pmp;
			}

			$new_stock_qty = $qty_stock + $qty;
			$new_stock_value_pmp = $stock_value_pmp + $value_ope;
			/*
			 * Fin calcul
			 */
			if ($error === 0)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_valorisation";
				$sql.= " (date_valo, fk_product, fk_stock_mouvement, qty_ope, price_ope, valo_ope, price_pmp, qty_stock, valo_pmp)";
				$sql.= " VALUES (".$this->db->idate(mktime()).", $fk_product, $mvid";
				$sql.= ",'".price2num($qty)."'";
				$sql.= ",'".price2num($price)."'";
				$sql.= ",'".price2num($value_ope)."'";
				$sql.= ",'".price2num($new_pmp)."'";
				$sql.= ",'".price2num($new_stock_qty)."'";
				$sql.= ",'".price2num($new_stock_value_pmp)."')";

				if ($this->db->query($sql))
				{

				}
				else
				{
					dolibarr_syslog("MouvementStock::CalculateValoPmp ERRORSQL[2] insert ".$this->error);
					$error = -17;
				}
			}

			if ($error === 0)
			{
				return 0;
			}
			else
			{
				dolibarr_syslog("MouvementStock::CalculateValoPmp ERROR : $error");
				return -21;
			}
		}
		else
		{
			return 0;
		}
	}


	/**
	 *	\brief		Decrease stock for product and subproducts
	 *
	 */
	function livraison($user, $fk_product, $entrepot_id, $qty)
	{
		return $this->_create($user, $fk_product, $entrepot_id, (0 - $qty), 2);
	}


	/**
	 *	\brief		Increase stock for product and subproducts
	 *
	 */
	function reception($user, $fk_product, $entrepot_id, $qty, $price=0)
	{
		return $this->_create($user, $fk_product, $entrepot_id, $qty, 3, $price);
	}


	/**
	 * Return nb of subproducts for a product
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
