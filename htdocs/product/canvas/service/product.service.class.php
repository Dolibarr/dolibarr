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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/canvas/service/product.service.class.php
 *	\ingroup    service
 *	\brief      Fichier de la classe des services par defaut
 *	\version    $Id: product.service.class.php,v 1.15 2011/07/31 23:24:03 eldy Exp $
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
		$this->db 				= $DB;
		$this->id 				= $id ;
		$this->user 			= $user;
		$this->module 			= "service";
		$this->canvas 			= "service";
		$this->name 			= "service";
		$this->definition 		= "Services canvas";
		$this->fieldListName	= "product_service";

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
	function fetch($id='', $ref='', $action='')
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

		parent::assign_values($action);

		// Duration
		$this->tpl['duration_value'] = $this->duration_value;

		if ($action == 'create')
		{
			// Title
			$this->tpl['title'] = load_fiche_titre($langs->trans("NewService"));
		}

		if ($action == 'edit')
		{
			$this->tpl['title'] = load_fiche_titre($langs->trans('Modify').' '.$langs->trans('Service').' : '.$this->ref, "");
		}

		if ($action == 'create' || $action == 'edit')
		{
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
		}

		if ($action == 'view')
		{
			// Photo
			$this->tpl['nblignes'] = 4;
			if ($this->is_photo_available($conf->service->dir_output))
			{
				$this->tpl['photos'] = $this->show_photos($conf->service->dir_output,1,1,0,0,0,80);
			}

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
		$sql.= ' p.duration, p.tosell as statut, p.seuil_stock_alerte';
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
		if ($sall)
		{
			$sql.= " AND (p.ref like '%".$this->db->escape($sall)."%' OR p.label like '%".$this->db->escape($sall)."%' OR p.description like '%".$this->db->escape($sall)."%' OR p.note like '%".$this->db->escape($sall)."%')";
		}
		if ($sref)     $sql.= " AND p.ref like '%".$sref."%'";
		if ($sbarcode) $sql.= " AND p.barcode like '%".$sbarcode."%'";
		if ($snom)     $sql.= " AND p.label like '%".$this->db->escape($snom)."%'";
		if (isset($_GET["tosell"]) && dol_strlen($_GET["tosell"]) > 0)
		{
			$sql.= " AND p.tosell = ".$this->db->escape($_GET["tosell"]);
		}
		if (isset($_GET["canvas"]) && dol_strlen($_GET["canvas"]) > 0)
		{
			$sql.= " AND p.canvas = '".$this->db->escape($_GET["canvas"])."'";
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
			$sql .= " AND cp.fk_categorie = ".$this->db->escape($search_categ);
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