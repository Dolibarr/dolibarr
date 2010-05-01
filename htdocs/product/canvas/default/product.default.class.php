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
	
	var $tpl = array();
	
	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id produit (0 par defaut)
	 */
	function ProductDefault($DB=0, $id=0, $user=0)
	{
		$this->db 				= $DB;
		$this->id 				= $id ;
		$this->user 			= $user;
		$this->module 			= "produit";
		$this->canvas 			= "default";
		$this->name 			= "default";
		$this->description 		= "Canvas des produits (défaut)";
		$this->fieldListName    = "product_default";

		$this->next_prev_filter = "canvas='default'";
	}
	
	function getTitle()
	{
		global $langs;
		
		return $langs->trans("Products");
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
	 *    \brief      Assigne les valeurs pour les templates
	 *    \param      object     object
	 */
	function assign_values($action='')
	{
		global $conf,$html;
		
		$this->tpl['showrefnav'] = $html->showrefnav($this,'ref','',1,'ref');
		$this->tpl['label'] = $this->libelle;
		
		if ($action == 'view')
		{
			$this->tpl['nblignes'] = 4;
			if ($this->is_photo_available($conf->produit->dir_output))
			{
				$this->tpl['photos'] = $this->show_photos($conf->produit->dir_output,1,1,0,0,0,80);
			}

			// Accountancy buy code
			$this->tpl['accountancyBuyCodeKey'] = $html->editfieldkey("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,'id',$this->id,$user->rights->produit->creer);
			$this->tpl['accountancyBuyCodeVal'] = $html->editfieldval("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,'id',$this->id,$user->rights->produit->creer);

			// Accountancy sell code
			$this->tpl['accountancySellCodeKey'] = $html->editfieldkey("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,'id',$this->id,$user->rights->produit->creer);
			$this->tpl['accountancySellCodeVal'] = $html->editfieldval("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,'id',$this->id,$user->rights->produit->creer);

			// Statut
			$this->tpl['status'] = $this->getLibStatut(2);

			// Description
			$this->tpl['description'] = nl2br($this->description);

			// Nature
			if($this->type!=1)
			{
				$this->tpl['finishedLabel'] = $this->getLibFinished();
			}

			if ($this->isservice())
			{
				// Duration
				if ($this->duration_value > 1)
				{
					$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
				}
				else if ($this->duration_value > 0)
				{
					$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
				}
				$this->tpl['duration'] = $langs->trans($dur[$this->duration_unit]);
			}
			else
			{
				// Weight
				if ($this->weight != '')
				{
					$this->tpl['weight'] = $this->weight." ".measuring_units_string($this->weight_units,"weight");
				}

				// Length
				if ($this->length != '')
				{
					$this->tpl['length'] = $this->length." ".measuring_units_string($this->length_units,"size");
				}

				// Surface
				if ($this->surface != '')
				{
					$this->tpl['surface'] = $this->surface." ".measuring_units_string($this->surface_units,"surface");
				}

				// Volume
				if ($this->volume != '')
				{
					$this->tpl['volume'] = $this->volume." ".measuring_units_string($this->volume_units,"volume");
				}
			}

			// Hidden
			if ((! $this->isservice() && $user->rights->produit->hidden)
			|| ($this->isservice() && $user->rights->service->hidden))
			{
				$this->tpl['hidden'] = yn($this->hidden);
			}
			else
			{
				$this->tpl['hidden'] = yn("No");
			}

			// Note
			$this->tpl['note'] = nl2br($this->note);
		}
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
				$fieldname = "s".$field['alias'];
				$$fieldname = trim(isset($_GET[$fieldname])?$_GET[$fieldname]:$_POST[$fieldname]);
			}
		}
		
		$sql = 'SELECT DISTINCT ';
		
		// Fields requiered
		$sql.= 'p.rowid, p.price_base_type, p.fk_product_type, p.seuil_stock_alerte';
		
		// Fields not requiered
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$sql.= ", ".$field['name']." as ".$field['alias'];
			}
		}

		$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if (!$user->rights->produit->hidden) $sql.=' AND p.hidden = 0';
		
		if ($sall)
		{
			$clause = '';
			$sql.= " AND (";
			foreach($this->field_list as $field)
			{
				if ($field['enabled'])
				{
					$sql.= $clause." ".$field['name']." LIKE '%".addslashes($sall)."%'";
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
				$fieldname = "s".$field['alias'];
				if (${$fieldname}) $sql.= " AND ".$field['name']." LIKE '%".addslashes(${$fieldname})."%'";
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
						else if ($alias == 'stock')
						{
							$this->load_stock();
							if ($this->stock_reel < $obj->seuil_stock_alerte) $datas[$alias] = $this->stock_reel.' '.img_warning($langs->trans("StockTooLow"));
							else $datas[$alias] = $this->stock_reel;
						}
						else if ($alias == 'label')	$datas[$alias] = dol_trunc($obj->$alias,40);
						else if (preg_match('/price/i',$alias))	$datas[$alias] = price($obj->$alias);
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