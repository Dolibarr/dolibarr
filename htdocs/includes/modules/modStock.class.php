<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   	stock     Module stock
 *	\brief      	Module pour gerer la tenue de stocks produits
 *	\version		$Id$
 */

/**
 *	\file       htdocs/includes/modules/modStock.class.php
 *	\ingroup    stock
 *	\brief      Fichier de description et activation du module Stock
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modStock
 *	\brief      Classe de description et activation du module Stock
 */
class modStock extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modStock($DB)
	{
		$this->db = $DB ;
		$this->numero = 52 ;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des stocks";

		$this->revision = explode(' ','$Revision$');
		$this->version = $this->revision[1];

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='stock';

		// Dir
		$this->dirs = array();

		$this->config_page_url = array("stock.php");

		// Dependencies
		$this->depends = array("modProduit");
		$this->requiredby = array();
		$this->langfiles = array("stocks");

		// Constantes
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'stock';

		$this->rights[0][0] = 1001;
		$this->rights[0][1] = 'Lire les stocks';
		$this->rights[0][2] = 'r';
		$this->rights[0][3] = 1;
		$this->rights[0][4] = 'lire';
		$this->rights[0][5] = '';

		$this->rights[1][0] = 1002;
		$this->rights[1][1] = 'Creer/Modifier les stocks';
		$this->rights[1][2] = 'w';
		$this->rights[1][3] = 0;
		$this->rights[1][4] = 'creer';
		$this->rights[1][5] = '';

		$this->rights[2][0] = 1003;
		$this->rights[2][1] = 'Supprimer les stocks';
		$this->rights[2][2] = 'd';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'supprimer';
		$this->rights[2][5] = '';

		$this->rights[3][0] = 1004;
		$this->rights[3][1] = 'Lire mouvements de stocks';
		$this->rights[3][2] = 'r';
		$this->rights[3][3] = 1;
		$this->rights[3][4] = 'mouvement';
		$this->rights[3][5] = 'lire';

		$this->rights[4][0] = 1005;
		$this->rights[4][1] = 'Creer/modifier mouvements de stocks';
		$this->rights[4][2] = 'w';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'mouvement';
		$this->rights[4][5] = 'creer';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="WarehousesAndProducts";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("stock","lire"));
		$this->export_fields_array[$r]=array('e.rowid'=>'IdWarehouse','e.label'=>'LabelWareHouse','e.label'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address','e.cp'=>'Zip','e.ville'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.envente'=>"OnSell",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification','ps.reel'=>'Stock');
		$this->export_entities_array[$r]=array('e.rowid'=>'warehouse','e.label'=>'warehouse','e.label'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.cp'=>'warehouse','e.ville'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.envente'=>"product",'p.duration'=>"product",'p.datec'=>'product','p.tms'=>'product','ps.reel'=>'stock');
		$this->export_alias_array[$r]=array('e.rowid'=>'idwarehouse','e.label'=>'labelwarehouse','e.label'=>'descwarehouse','e.lieu'=>'lieuwarehouse','e.address'=>'addresswarehouse','e.cp'=>'zipwarehouse','e.ville'=>'townwarehouse','p.rowid'=>"id",'p.ref'=>"ref",'p.fk_product_type'=>"type",'p.label'=>"label",'p.description'=>"description",'p.note'=>"note",'p.price'=>"price",'p.tva_tx'=>'vat','p.envente'=>"onsell",'p.duration'=>"duration",'p.datec'=>'datecreation','p.tms'=>'datemodification','ps.reel'=>'quantity');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'entrepot as e';
		$this->export_sql_end[$r] .=' WHERE p.rowid = ps.fk_product AND ps.fk_entrepot = e.rowid';
	}

	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);

	}
}
?>
