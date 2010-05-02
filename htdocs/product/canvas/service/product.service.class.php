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
 *	\file       htdocs/product/canvas/service/product.service.class.php
 *	\ingroup    service
 *	\brief      Fichier de la classe des services par defaut
 *	\version    $Id$
 */

/**
 *	\class      ProductService
 *	\brief      Classe permettant la gestion services par defaut, cette classe surcharge la classe produit
 */
class ProductService extends Product
{
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;
	
	var $tpl = array();
	
	/**
	 *    \brief      Constructeur de la classe
	 *    \param      DB          Handler acces base de donnees
	 *    \param      id          Id service (0 par defaut)
	 */
	function ProductService($DB=0, $id=0, $user=0)
	{
		$this->db = $DB;
		$this->id = $id ;
		$this->user = $user;
		$this->module = "service";
		$this->canvas = "service";
		$this->name = "service";
		$this->definition = "Canvas des services";

		$this->next_prev_filter = "canvas='service'";
	}
	
	function getTitle()
	{
		return 'Services';
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
		
		// Duration
		$this->tpl['duration_value'] = $this->duration_value;
		
		// Hidden
		if ($this->user->rights->service->hidden)
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
			$this->tpl['title'] = load_fiche_titre($langs->trans("NewService"));
			
			// Price
			$this->tpl['price'] = $this->price;
			$this->tpl['price_min'] = $this->price_min;
			$this->tpl['price_base_type'] = $html->load_PriceBaseType($this->price_base_type, "price_base_type");
			
			// VAT
			$this->tpl['tva_tx'] = $html->load_tva("tva_tx",$conf->defaulttx,$mysoc,'');
		}
		
		if ($action == 'edit')
		{
			$this->tpl['title'] = load_fiche_titre($langs->trans('Modify').' '.$langs->trans('Service').' : '.$this->ref, "");
		}
		
		if ($action == 'create' || $action == 'edit')
		{
			// Status
			$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			$this->tpl['status'] = $html->selectarray('statut',$statutarray,$this->status);
			
			// Hidden
			if ($this->user->rights->service->hidden)
			{
				$this->tpl['hidden'] = $html->selectyesno('hidden',$this->hidden);
			}
			
			// Duration unit
			// TODO creer fonction
			$duration_unit = '<input name="duration_unit" type="radio" value="h"'.($this->duration_unit=='h'?' checked':'').'>'.$langs->trans("Hour");
			$duration_unit.= '&nbsp; ';
			$duration_unit.= '<input name="duration_unit" type="radio" value="d"'.($this->duration_unit=='d'?' checked':'').'>'.$langs->trans("Day");
			$duration_unit.= '&nbsp; ';
			$duration_unit.= '<input name="duration_unit" type="radio" value="w"'.($this->duration_unit=='w'?' checked':'').'>'.$langs->trans("Week");
			$duration_unit.= '&nbsp; ';
			$duration_unit.= '<input name="duration_unit" type="radio" value="m"'.($this->duration_unit=='m'?' checked':'').'>'.$langs->trans("Month");
			$duration_unit.= '&nbsp; ';
			$duration_unit.= '<input name="duration_unit" type="radio" value="y"'.($this->duration_unit=='y'?' checked':'').'>'.$langs->trans("Year");
			$this->tpl['duration_unit'] = $duration_unit;
			
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

			// Duration
			if ($this->duration_value > 1)
			{
				$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
			}
			else if ($this->duration_value > 0)
			{
				$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
			}
			$this->tpl['duration_unit'] = $langs->trans($dur[$this->duration_unit]);
		}
	}
	
	/**
	 * 	\brief	Fetch datas list
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf;
		
		$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
		$sql.= ' p.fk_product_type, p.tms as datem,';
		$sql.= ' p.duration, p.envente as statut, p.seuil_stock_alerte';
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
		if (!$user->rights->service->hidden) $sql.=' AND (p.hidden=0 OR p.fk_product_type != 1)';
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
				$datas["ref"]       = $obj->ref;
				$datas["label"]     = $obj->label;
				$datas["barcode"]   = $obj->barcode;
				$datas["statut"]    = $obj->statut;

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