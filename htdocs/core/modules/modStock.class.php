<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\defgroup   	stock     Module stocks
 *	\brief      	Module pour gerer la tenue de stocks produits
 *	\file       htdocs/core/modules/modStock.class.php
 *	\ingroup    stock
 *	\brief      Fichier de description et activation du module Stock
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Stock
 */
class modStock extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->numero = 52;

		$this->family = "products";
		$this->module_position = 40;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des stocks";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='stock';

		// Data directories to create when module is enabled
		$this->dirs = array();

		$this->config_page_url = array("stock.php");

		// Dependencies
		$this->depends = array("modProduct");
		$this->requiredby = array("modProductBatch");
		$this->langfiles = array("stocks");

		// Constants
		$this->const = array(
			0=>array('STOCK_ALLOW_NEGATIVE_TRANSFER','chaine','1','',1)
		);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'stock';

		$this->rights[0][0] = 1001;
		$this->rights[0][1] = 'Lire les stocks';
		$this->rights[0][2] = 'r';
		$this->rights[0][3] = 0;
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
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'mouvement';
		$this->rights[3][5] = 'lire';

		$this->rights[4][0] = 1005;
		$this->rights[4][1] = 'Creer/modifier mouvements de stocks';
		$this->rights[4][2] = 'w';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'mouvement';
		$this->rights[4][5] = 'creer';

		
		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		
		
		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="WarehousesAndProducts";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("stock","lire"));
		$this->export_fields_array[$r]=array('e.rowid'=>'IdWarehouse','e.label'=>'LocationSummary','e.description'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address','e.zip'=>'Zip','e.town'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification','ps.reel'=>'Stock');
		$this->export_TypeFields_array[$r]=array('e.rowid'=>'List:entrepot:label','e.label'=>'Text','e.lieu'=>'Text','e.address'=>'Text','e.zip'=>'Text','e.town'=>'Text','p.rowid'=>"List:product:label",'p.ref'=>"Text",'p.fk_product_type'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.note'=>"Text",'p.price'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date','ps.reel'=>'Numeric');
		$this->export_entities_array[$r]=array('e.rowid'=>'warehouse','e.label'=>'warehouse','e.description'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.zip'=>'warehouse','e.town'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.tosell'=>"product",'p.duration'=>"product",'p.datec'=>'product','p.tms'=>'product','ps.reel'=>'stock');
		$this->export_aggregate_array[$r]=array('ps.reel'=>'SUM');    // TODO Not used yet
		$this->export_dependencies_array[$r]=array('stock'=>array('p.rowid','e.rowid')); // We must keep this until the aggregate_array is used. To add unique key if we ask a field of a child to avoid the DISTINCT to discard them.
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'entrepot as e';
		$this->export_sql_end[$r] .=' WHERE p.rowid = ps.fk_product AND ps.fk_entrepot = e.rowid';
		$this->export_sql_end[$r] .=' AND e.entity IN ('.getEntity('stock',1).')';

		if ($conf->productbatch->enabled)
		{
			$langs->load("productbatch");

			// This request is same than previous but without field ps.stock (real stock in warehouse) and with link to subtable productbatch
			$r++;

			$this->export_code[$r]=$this->rights_class.'_'.$r;
			$this->export_label[$r]="WarehousesAndProductsBatchDetail";	// Translation key (used only if key ExportDataset_xxx_z not found)
			$this->export_permission[$r]=array(array("stock","lire"));
			$this->export_fields_array[$r]=array('e.rowid'=>'IdWarehouse','e.label'=>'LocationSummary','e.description'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address','e.zip'=>'Zip','e.town'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification','pb.rowid'=>'Id','pb.batch'=>'Batch','pb.eatby'=>'EatByDate','pb.sellby'=>'SellByDate','pb.qty'=>'Qty');
			$this->export_TypeFields_array[$r]=array('e.rowid'=>'List:entrepot:label','e.label'=>'Text','e.lieu'=>'Text','e.address'=>'Text','e.zip'=>'Text','e.town'=>'Text','p.rowid'=>"List:product:label",'p.ref'=>"Text",'p.fk_product_type'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.note'=>"Text",'p.price'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date','pb.batch'=>'Text','pb.eatby'=>'Date','pb.sellby'=>'Date','pb.qty'=>'Numeric');
			$this->export_entities_array[$r]=array('e.rowid'=>'warehouse','e.label'=>'warehouse','e.description'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.zip'=>'warehouse','e.town'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.tosell'=>"product",'p.duration'=>"product",'p.datec'=>'product','p.tms'=>'product','pb.rowid'=>'batch','pb.batch'=>'batch','pb.eatby'=>'batch','pb.sellby'=>'batch','pb.qty'=>'batch');
			$this->export_aggregate_array[$r]=array('ps.reel'=>'SUM');    // TODO Not used yet
			$this->export_dependencies_array[$r]=array('batch'=>array('pb.rowid')); // We must keep this until the aggregate_array is used. To add unique key if we ask a field of a child to avoid the DISTINCT to discard them.
			$this->export_sql_start[$r]='SELECT DISTINCT ';
			$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'entrepot as e, '.MAIN_DB_PREFIX.'product_batch as pb';
			$this->export_sql_end[$r] .=' WHERE p.rowid = ps.fk_product AND ps.fk_entrepot = e.rowid AND ps.rowid = pb.fk_product_stock';
			$this->export_sql_end[$r] .=' AND e.entity IN ('.getEntity('stock',1).')';
		}

		// Imports
		//--------

		$r=0;

		// Import warehouses
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="Warehouses";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('e'=>MAIN_DB_PREFIX.'entrepot');
		$this->import_tables_creator_array[$r]=array('e'=>'fk_user_author');
		$this->import_fields_array[$r]=array('e.label'=>"LocationSummary*",
				'e.description'=>"DescWareHouse",'e.lieu'=>"LieuWareHouse",
				'e.address'=>"Address",'e.zip'=>'Zip','e.fk_pays'=>'CountryCode',
				'e.statut'=>'Status'
		);

		$this->import_convertvalue_array[$r]=array(
				'e.fk_pays'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/ccountry.class.php','class'=>'Ccountry','method'=>'fetch','dict'=>'DictionaryCountry')
		);
		$this->import_regex_array[$r]=array('e.statut'=>'^[0|1]');
		$this->import_examplevalues_array[$r]=array('e.label'=>"ALM001",
				'e.description'=>"Central Warehouse",'e.lieu'=>"Central",
				'e.address'=>"Route 66",'e.zip'=>'28080','e.fk_pays'=>'US',
				'e.statut'=>'1');

		// Import stocks
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="Stocks";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('ps'=>MAIN_DB_PREFIX.'product_stock');
		$this->import_fields_array[$r]=array('ps.fk_product'=>"Product*",'ps.fk_entrepot'=>"Warehouse*",'ps.reel'=>"Stock*");

		$this->import_convertvalue_array[$r]=array(
			'ps.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'product'),
			'ps.fk_entrepot'=>array('rule'=>'fetchidfromref','classfile'=>'/product/stock/class/entrepot.class.php','class'=>'Entrepot','method'=>'fetch','element'=>'label')
		  );
		$this->import_examplevalues_array[$r]=array(
		    'ps.fk_product'=>"PREF123456",'ps.fk_entrepot'=>"ALM001",'ps.reel'=>"10"
		);
		$this->import_run_sql_after_array[$r]=array(    // Because we may change data that are denormalized, we must update dernormalized data after.
		    'UPDATE llx_product p SET p.stock= (SELECT SUM(ps.reel) FROM llx_product_stock ps WHERE ps.fk_product = p.rowid);'
		);

	}
}
