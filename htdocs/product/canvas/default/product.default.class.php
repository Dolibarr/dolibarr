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
	 *    \brief      Lecture des donnees dans la base
	 *    \param      id          Product id
	 *    \param      ref         Product ref
	 */
	function fetchCanvas($id='', $ref='', $action='')
	{
		$result = $this->fetch($id,$ref);

		return $result;
	}
	
	/**
	 * 	\brief	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;
		
		$this->list_datas = array();
		
		//$_GET["sall"] = 'LL';
		// Clean parameters
		$sall=trim(isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"]);
		
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['name'];
				$$fieldname = trim(isset($_GET[$fieldname])?$_GET[$fieldname]:$_POST[$fieldname]);
			}
		}
		
		$sql = 'SELECT DISTINCT ';
		
		// Fields requiered
		$sql.= 'p.rowid, p.price_base_type, p.fk_product_type, p.seuil_stock_alerte, p.price_ttc';
		
		// Fields not requiered
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$sql.= ", p.".$field['name']." as ".$field['alias'];
			}
		}

		$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if (!$user->rights->produit->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 0)';
		
		if ($sall)
		{
			$clause = '';
			$sql.= " AND (";
			foreach($this->field_list as $field)
			{
				if ($field['enabled'])
				{
					$sql.= $clause." p.".$field['name']." LIKE '%".addslashes($sall)."%'";
					if ($clause=='') $clause = ' OR';
				}
			}
			$sql.= ")";
		}
		
		// Search fields
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['name'];
				if (${$fieldname}) $sql.= " AND p.".$field['name']." LIKE '%".addslashes(${$fieldname})."%'";
			}
		}
		
		if (isset($_GET["envente"]) && strlen($_GET["envente"]) > 0)
		{
			$sql.= " AND p.envente = ".addslashes($_GET["envente"]);
		}
		if (isset($_GET["canvas"]) && strlen($_GET["canvas"]) > 0)
		{
			$sql.= " AND p.canvas = '".addslashes($_GET["canvas"])."'";
		}
		$sql.= $this->db->order($sortfield,$sortorder);
		$sql.= $this->db->plimit($limit + 1 ,$offset);
		//print $sql;
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
				
				foreach($this->field_list as $field)
				{
					if ($field['enabled'])
					{
						$alias = $field['alias'];
						
						if ($alias == 'ref')
						{
							$this->id 		= $obj->rowid;
							$this->ref 		= $obj->$alias;
							$this->type 	= $obj->fk_product_type;
							$datas[$alias] 	= $this->getNomUrl(1,'',24);
						}
						else if ($alias == 'sellingprice')
						{
							if ($obj->price_base_type == 'TTC') $datas[$alias] = price($obj->price_ttc).' '.$langs->trans("TTC");
							else $datas[$alias] = price($obj->$alias).' '.$langs->trans("HT");
						}
						else if ($alias == 'stock')
						{
							$this->load_stock();
							if ($this->stock_reel < $obj->seuil_stock_alerte) $datas[$alias] = $this->stock_reel.' '.img_warning($langs->trans("StockTooLow"));
							else $datas[$alias] = $this->stock_reel;
						}
						else if ($alias == 'datem') $datas[$alias] = dol_print_date($this->db->jdate($obj->$alias),'day');
						else if ($alias == 'status') $datas[$alias] = $this->LibStatut($obj->$alias,5);
						else $datas[$alias] = $obj->$alias;
					}
				}

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