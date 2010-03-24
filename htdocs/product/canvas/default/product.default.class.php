<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/product/canvas/default/product.default.class.php
 *	\ingroup    produit
 *	\brief      Fichier de la classe des produits par defaut
 *	\version    $Id$
 */

/**
 *	\class      ProductDefault
 *	\brief      Classe permettant la gestion produits par defaut, cette classe surcharge la classe produit
 */
class ProductDefault extends Product
{
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;
	
	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function ProductDefault($DB=0, $id=0, $user=0)
	{
		$this->db 			= $DB;
		$this->id 			= $id ;
		$this->user 		= $user;
		$this->module 		= "produit";
		$this->canvas 		= "default";
		$this->name 		= "default";
		$this->list			= "product_default";
		$this->description 	= "Canvas par défaut";

		$this->next_prev_filter = "canvas='default'";
	}
	
	function getTitle()
	{
		return 'Produits';
	}
	
	/**
	 * 	\brief	Fetch field list
	 */
	function getFieldList()
	{
		global $conf, $langs;
		
		$this->field_list = array();
		
		$sql = "SELECT rowid, name, alias, title, align, search, enabled, rang";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_field_list";
		$sql.= " WHERE element = '".$this->list."'";
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " ORDER BY rang ASC";
		
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num)
			{
				$fieldlist = array();
				
				$obj = $this->db->fetch_object($resql);
				
				$fieldlist["id"]		= $obj->rowid;
				$fieldlist["name"]		= $obj->name;
				$fieldlist["alias"]		= ($obj->alias?$obj->alias:$obj->name);
				$fieldlist["title"]		= $langs->trans($obj->title);
				$fieldlist["align"]		= $obj->align;
				$fieldlist["search"]	= $obj->search;
				$fieldlist["enabled"]	= $obj->enabled;
				$fieldlist["order"]		= $obj->rang;
				
				array_push($this->field_list,$fieldlist);
				
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			print $sql;
		}
	}
	
	/**
	 * 	\brief	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;
		
		$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
		$sql.= ' p.fk_product_type, p.tms as datem,';
		$sql.= ' p.envente as statut, p.seuil_stock_alerte';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
		// We'll need this table joined to the select in order to filter by categ
		if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_product as cp";
		if ($_GET["fourn_id"] > 0)
		{
			$fourn_id = $_GET["fourn_id"];
			$sql.= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
		}
		$sql.= " WHERE p.entity = ".$conf->entity;
		if ($search_categ) $sql.= " AND p.rowid = cp.fk_product";	// Join for the needed table to filter by categ
		if (!$user->rights->produit->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 0)';
		if ($sall)
		{
			$sql.= " AND (p.ref like '%".addslashes($sall)."%' OR p.label like '%".addslashes($sall)."%' OR p.description like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%')";
		}
		if ($sref)     $sql.= " AND p.ref like '%".$sref."%'";
		if ($sbarcode) $sql.= " AND p.barcode like '%".$sbarcode."%'";
		if ($snom)     $sql.= " AND p.label like '%".addslashes($snom)."%'";
		if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
		{
			$sql.= " AND p.envente = ".addslashes($_GET["envente"]);
		}
		if (isset($_GET["canvas"]) && strlen($_GET["canvas"]) > 0)
		{
			$sql.= " AND p.canvas = '".addslashes($_GET["canvas"])."'";
		}
		if($catid)
		{
			$sql.= " AND cp.fk_categorie = ".$catid;
		}
		if ($fourn_id > 0)
		{
			$sql.= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
		}
		// Insert categ filter
		if ($search_categ)
		{
			$sql .= " AND cp.fk_categorie = ".addslashes($search_categ);
		}
		$sql.= $this->db->order($sortfield,$sortorder);
		$sql.= $this->db->plimit($limit + 1 ,$offset);

		$this->list_datas = array();

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < min($num,$limit))
			{
				$datas = array();
				
				$obj = $this->db->fetch_object($resql);

				$datas["id"]        = $obj->rowid;
				
				// Ref
				$this->id 				= $obj->rowid;
				$this->ref 				= $obj->ref;
				$this->type 			= $obj->fk_product_type;
				$datas["ref"]       	= $this->getNomUrl(1,'',24);
				
				// Label
				$datas["label"]     	= $obj->label;
				
				// Barcode
				$datas["barcode"]   	= $obj->barcode;
				
				// Date modification
				$datas["datem"]			= dol_print_date($this->db->jdate($obj->datem),'day');
				
				// Selling price
				if ($obj->price_base_type == 'TTC') $datas["sellingprice"] = price($obj->price_ttc).' '.$langs->trans("TTC");
				else $datas["sellingprice"] = price($obj->price).' '.$langs->trans("HT");
				
				// Stock
				$this->load_stock();
				if ($this->stock_reel < $obj->seuil_stock_alerte) $datas["stock"] = $this->stock_reel.' '.img_warning($langs->trans("StockTooLow"));
				else $datas["stock"] 	= $this->stock_reel;
				
				// Status
				$datas["status"]    = $this->LibStatut($obj->statut,5);

				array_push($this->list_datas,$datas);

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			print $sql;
		}
	}
	
}
 
?>