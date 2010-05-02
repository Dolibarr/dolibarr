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
		$this->definition 		= "Canvas des produits (défaut)";
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
	 */
	function fetch($id='', $action='')
	{
		$result = parent::fetch($id);

		return $result;
	}
	
	/**
	 *    \brief      Assigne les valeurs pour les templates
	 *    \param      object     object
	 */
	function assign_values($action='')
	{
		global $conf,$langs;
		global $html;
		global $formproduct;
		
		// canvas
		$this->tpl['canvas'] = $this->canvas;
		
		// id
		$this->tpl['id'] = $this->id;
		
		// Ref
		$this->tpl['ref'] = $this->ref;
		
		// Label
		$this->tpl['label'] = $this->libelle;
		
		// Description
		$this->tpl['description'] = nl2br($this->description);
		
		// Statut
		$this->tpl['status'] = $this->getLibStatut(2);
		
		// Note
		$this->tpl['note'] = nl2br($this->note);
		
		// Hidden
		if ($this->user->rights->produit->hidden)
		{
			$this->tpl['hidden'] = yn($this->hidden);
		}
		else
		{
			$this->tpl['hidden'] = yn("No");
		}
		
		// Stock alert
		$this->tpl['seuil_stock_alerte'] = $this->seuil_stock_alerte;
		
		if ($action == 'create')
		{
			// Title
			$this->tpl['title'] = load_fiche_titre($langs->trans("NewProduct"));
			
			// Price
			$this->tpl['price'] = $this->price;
			$this->tpl['price_min'] = $this->price_min;
			$this->tpl['price_base_type'] = $html->load_PriceBaseType($this->price_base_type, "price_base_type");
			
			// VAT
			$this->tpl['tva_tx'] = $html->load_tva("tva_tx",$conf->defaulttx,$mysoc,'');
		}
		
		if ($action == 'edit')
		{
			$this->tpl['title'] = load_fiche_titre($langs->trans('Modify').' '.$langs->trans('Product').' : '.$this->ref, "");
		}
		
		if ($action == 'create' || $action == 'edit')
		{
			// Status
			$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			$this->tpl['status'] = $html->selectarray('statut',$statutarray,$this->status);
			
			// Finished
			$statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$this->tpl['finished'] = $html->selectarray('finished',$statutarray,$this->finished);
			
			// Weight
			$this->tpl['weight'] = $this->weight;
			$this->tpl['weight_units'] = $formproduct->load_measuring_units("weight_units","weight",$this->weight_units);
			
			// Length
			$this->tpl['length'] = $this->length;
			$this->tpl['length_units'] = $formproduct->load_measuring_units("length_units","size",$this->length_units);
			
			// Surface
			$this->tpl['surface'] = $this->surface;
			$this->tpl['surface_units'] = $formproduct->load_measuring_units("surface_units","surface",$this->surface_units);
			
			// Volume
			$this->tpl['volume'] = $this->volume;
			$this->tpl['volume_units'] = $formproduct->load_measuring_units("volume_units","volume",$this->volume_units);
			
			// Hidden
			if ($this->user->rights->produit->hidden)
			{
				$this->tpl['hidden'] = $html->selectyesno('hidden',$this->hidden);
			}
			
			// TODO creer fonction
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				
				$doleditor=new DolEditor('desc',$this->description,160,'dolibarr_notes','',false);
				$this->tpl['doleditor_description'] = $doleditor;
				
				$doleditor=new DolEditor('note',$this->note,180,'dolibarr_notes','',false);
				$this->tpl['doleditor_note'] = $doleditor;
			}
			else
			{
				$textarea = '<textarea name="desc" rows="4" cols="90">';
				$textarea.= $this->description;
				$textarea.= '</textarea>';
				$this->tpl['textarea_description'] = $textarea;
				
				$textarea = '<textarea name="note" rows="8" cols="70">';
				$textarea.= $this->note;
				$textarea.= '</textarea>';
				$this->tpl['textarea_note'] = $textarea;
			}
		}
		
		if ($action == 'view')
		{
			// Ref
			$this->tpl['ref'] = $html->showrefnav($this,'ref','',1,'ref');
			
			// Photo
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

			// Nature
			$this->tpl['finished'] = $this->getLibFinished();

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