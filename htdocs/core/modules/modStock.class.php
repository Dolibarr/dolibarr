<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
		$this->module_position = '40';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des stocks";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='stock';

		// Data directories to create when module is enabled
		$this->dirs = array("/stock/temp");

		$this->config_page_url = array("stock.php");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array("modProduct");		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array("modProductBatch");	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
		$this->langfiles = array("stocks");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r] = array('STOCK_ALLOW_NEGATIVE_TRANSFER','chaine','1','',1);

		$r++;
		$this->const[$r][0] = "STOCK_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "Standard";
		$this->const[$r][3] = 'Name of PDF model of stock';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "MOUVEMENT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "StdMouvement";
		$this->const[$r][3] = 'Name of PDF model of stock mouvement';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "STOCK_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/stocks";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "MOUVEMENT_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/stocks/mouvements";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

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

		if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
		{
    		$this->rights[5][0] = 1011;
    		$this->rights[5][1] = 'inventoryReadPermission';	// Permission label
    		$this->rights[5][3] = 0; 					// Permission by default for new user (0/1)
    		$this->rights[5][4] = 'inventory_advance';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
    		$this->rights[5][5] = 'read';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)

    		$this->rights[6][0] = 1012;
    		$this->rights[6][1] = 'inventoryCreatePermission';	// Permission label
    		$this->rights[6][3] = 0; 					// Permission by default for new user (0/1)
    		$this->rights[6][4] = 'inventory_advance';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
    		$this->rights[6][5] = 'write';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)

    		$this->rights[8][0] = 1014;
    		$this->rights[8][1] = 'inventoryValidatePermission';	// Permission label
    		$this->rights[8][3] = 0; 					// Permission by default for new user (0/1)
    		$this->rights[8][4] = 'inventory_advance';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
    		$this->rights[8][5] = 'validate';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)

    		$this->rights[9][0] = 1015;
    		$this->rights[9][1] = 'inventoryChangePMPPermission';	// Permission label
    		$this->rights[9][3] = 0; 					// Permission by default for new user (0/1)
    		$this->rights[9][4] = 'inventory_advance';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
    		$this->rights[9][5] = 'changePMP';			// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		}

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class;
		$this->export_label[$r]="WarehousesAndProducts";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("stock","lire"));
		$this->export_fields_array[$r]=array(
			'e.rowid'=>'IdWarehouse','e.ref'=>'LocationSummary','e.description'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address',
			'e.zip'=>'Zip','e.town'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",
			'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell",'p.tobuy'=>'OnBuy','p.duration'=>"Duration",'p.datec'=>'DateCreation',
			'p.tms'=>'DateModification','p.pmp'=>'PMPValue','p.cost_price'=>'CostPrice'
		);
		$this->export_TypeFields_array[$r]=array(
			'e.rowid'=>'List:entrepot:ref::stock','e.ref'=>'Text','e.lieu'=>'Text','e.address'=>'Text','e.zip'=>'Text','e.town'=>'Text','p.rowid'=>"List:product:label::product",
			'p.ref'=>"Text",'p.fk_product_type'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.note'=>"Text",'p.price'=>"Numeric",'p.tva_tx'=>'Numeric',
			'p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date','p.pmp'=>'Numeric','p.cost_price'=>'Numeric',
			'ps.reel'=>'Numeric'
		);
		$this->export_entities_array[$r]=array(
			'e.rowid'=>'warehouse','e.ref'=>'warehouse','e.description'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.zip'=>'warehouse',
			'e.town'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",
			'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.tosell'=>"product",'p.tobuy'=>"product",'p.duration'=>"product",
			'p.datec'=>'product','p.tms'=>'product','p.pmp'=>'product','p.cost_price'=>'product','ps.reel'=>'stock'
		);
		$this->export_aggregate_array[$r]=array('ps.reel'=>'SUM');    // TODO Not used yet
		$this->export_dependencies_array[$r]=array('stock'=>array('p.rowid','e.rowid')); // We must keep this until the aggregate_array is used. To have a unique key, if we ask a field of a child, to avoid the DISTINCT to discard them.
		$keyforselect='product'; $keyforelement='product'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('ps.reel'=>'Stock'));

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON extra.fk_object = p.rowid, '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'entrepot as e';
		$this->export_sql_end[$r] .=' WHERE p.rowid = ps.fk_product AND ps.fk_entrepot = e.rowid';
		$this->export_sql_end[$r] .=' AND e.entity IN ('.getEntity('stock').')';
		if ($conf->productbatch->enabled)
		{
			// Export of stock including lot number
			$langs->load("productbatch");

			// This request is same than previous but without field ps.stock (real stock in warehouse) and with link to subtable productbatch
			$r++;

			$this->export_code[$r]=$this->rights_class.'_lot';
			$this->export_label[$r]="WarehousesAndProductsBatchDetail";	// Translation key (used only if key ExportDataset_xxx_z not found)
			$this->export_permission[$r]=array(array("stock","lire"));
			$this->export_fields_array[$r]=array(
				'e.rowid'=>'IdWarehouse','e.ref'=>'LocationSummary','e.description'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address',
				'e.zip'=>'Zip','e.town'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",
				'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell",'p.tobuy'=>'OnBuy','p.duration'=>"Duration",'p.datec'=>'DateCreation',
				'p.tms'=>'DateModification','pb.rowid'=>'Id','pb.batch'=>'Batch','pb.qty'=>'Qty','pl.eatby'=>'EatByDate','pl.sellby'=>'SellByDate'
			);
			$this->export_TypeFields_array[$r]=array(
				'e.rowid'=>'List:entrepot:ref::stock','e.ref'=>'Text','e.lieu'=>'Text','e.description'=>'Text','e.address'=>'Text','e.zip'=>'Text','e.town'=>'Text',
				'p.rowid'=>"List:product:label::product",'p.ref'=>"Text",'p.fk_product_type'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.note'=>"Text",
				'p.price'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date',
				'pb.batch'=>'Text','pb.qty'=>'Numeric','pl.eatby'=>'Date','pl.sellby'=>'Date'
			);
			$this->export_entities_array[$r]=array(
				'e.rowid'=>'warehouse','e.ref'=>'warehouse','e.description'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.zip'=>'warehouse',
				'e.town'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",
				'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.tosell'=>"product",'p.tobuy'=>"product",'p.duration'=>"product",
				'p.datec'=>'product','p.tms'=>'product','pb.rowid'=>'stockbatch','pb.batch'=>'stockbatch','pb.qty'=>'stockbatch','pl.eatby'=>'batch',
				'pl.sellby'=>'batch'
			);
			$this->export_aggregate_array[$r]=array('ps.reel'=>'SUM');    // TODO Not used yet
			$this->export_dependencies_array[$r]=array('stockbatch'=>array('pb.rowid'),'batch'=>array('pb.rowid')); // We must keep this until the aggregate_array is used. To add unique key if we ask a field of a child to avoid the DISTINCT to discard them.
			$keyforselect='product_lot'; $keyforelement='batch'; $keyforaliasextra='extra';
			include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
			$this->export_sql_start[$r]='SELECT DISTINCT ';
			$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_stock as ps, '.MAIN_DB_PREFIX.'product_batch as pb)';
			$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot as pl ON pl.fk_product = p.rowid AND pl.batch = pb.batch';
			$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_lot_extrafields as extra ON extra.fk_object = pl.rowid,';
			$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'entrepot as e';
			$this->export_sql_end[$r] .=' WHERE p.rowid = ps.fk_product AND ps.fk_entrepot = e.rowid AND ps.rowid = pb.fk_product_stock';
			$this->export_sql_end[$r] .=' AND e.entity IN ('.getEntity('stock').')';
		}

		// Export of stock movement
		$r++;
		$this->export_code[$r]=$this->rights_class.'_movement';
		$this->export_label[$r]="StockMovements";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("stock","lire"));
		$this->export_fields_array[$r]=array(
			'e.rowid'=>'IdWarehouse','e.ref'=>'LocationSummary','e.description'=>'DescWareHouse','e.lieu'=>'LieuWareHouse','e.address'=>'Address','e.zip'=>'Zip',
			'e.town'=>'Town','p.rowid'=>"ProductId",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",
			'p.price'=>"Price",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell",'p.tobuy'=>'OnBuy','p.duration'=>"Duration",'p.datec'=>'DateCreation',
			'p.tms'=>'DateModification','sm.rowid'=>'MovementId','sm.value'=>'Qty','sm.datem'=>'DateMovement','sm.label'=>'MovementLabel',
			'sm.inventorycode'=>'InventoryCode'
		);
		$this->export_TypeFields_array[$r]=array(
			'e.rowid'=>'List:entrepot:ref::stock','e.ref'=>'Text','e.description'=>'Text','e.lieu'=>'Text','e.address'=>'Text','e.zip'=>'Text','e.town'=>'Text',
			'p.rowid'=>"List:product:label::product",'p.ref'=>"Text",'p.fk_product_type'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.note'=>"Text",
			'p.price'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date',
			'sm.rowid'=>'Numeric','sm.value'=>'Numeric','sm.datem'=>'Date','sm.batch'=>'Text','sm.label'=>'Text','sm.inventorycode'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			'e.rowid'=>'warehouse','e.ref'=>'warehouse','e.description'=>'warehouse','e.lieu'=>'warehouse','e.address'=>'warehouse','e.zip'=>'warehouse',
			'e.town'=>'warehouse','p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",
			'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.tosell'=>"product",'p.tobuy'=>"product",'p.duration'=>"product",'p.datec'=>'product',
			'p.tms'=>'product','sm.rowid'=>'movement','sm.value'=>'movement','sm.datem'=>'movement','sm.label'=>'movement','sm.inventorycode'=>'movement'
		);
		if ($conf->productbatch->enabled)
		{
			$this->export_fields_array[$r]['sm.batch']='Batch';
			$this->export_TypeFields_array[$r]['sm.batch']='Text';
			$this->export_entities_array[$r]['sm.batch']='movement';
		}
		$this->export_aggregate_array[$r]=array('sm.value'=>'SUM');    // TODO Not used yet
		$this->export_dependencies_array[$r]=array('movement'=>array('sm.rowid')); // We must keep this until the aggregate_array is used. To add unique key if we ask a field of a child to avoid the DISTINCT to discard them.
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'stock_mouvement as sm, '.MAIN_DB_PREFIX.'entrepot as e';
		$this->export_sql_end[$r] .=' WHERE p.rowid = sm.fk_product AND sm.fk_entrepot = e.rowid';
		$this->export_sql_end[$r] .=' AND e.entity IN ('.getEntity('stock').')';

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
		$this->import_fields_array[$r]=array('e.ref'=>"LocationSummary*",
				'e.description'=>"DescWareHouse",'e.lieu'=>"LieuWareHouse",
				'e.address'=>"Address",'e.zip'=>'Zip','e.fk_pays'=>'CountryCode',
				'e.statut'=>'Status'
		);

		$this->import_convertvalue_array[$r]=array(
				'e.fk_pays'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/ccountry.class.php','class'=>'Ccountry','method'=>'fetch','dict'=>'DictionaryCountry')
		);
		$this->import_regex_array[$r]=array('e.statut'=>'^[0|1]');
		$this->import_examplevalues_array[$r]=array('e.ref'=>"ALM001",
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
			'ps.fk_entrepot'=>array('rule'=>'fetchidfromref','classfile'=>'/product/stock/class/entrepot.class.php','class'=>'Entrepot','method'=>'fetch','element'=>'ref')
		  );
		$this->import_examplevalues_array[$r]=array(
		    'ps.fk_product'=>"PREF123456",'ps.fk_entrepot'=>"ALM001",'ps.reel'=>"10"
		);
		$this->import_run_sql_after_array[$r]=array(    // Because we may change data that are denormalized, we must update dernormalized data after.
		    'UPDATE '.MAIN_DB_PREFIX.'product p SET p.stock= (SELECT SUM(ps.reel) FROM '.MAIN_DB_PREFIX.'product_stock ps WHERE ps.fk_product = p.rowid);'
		);
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf,$langs;

		// Permissions
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/stock/template_stock.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/stock';
		$dest=$dirodt.'/template_stock.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array();

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[1][2])."' AND type = 'stock' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[1][2])."','stock',".$conf->entity.")",
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[2][2])."' AND type = 'mouvement' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[2][2])."','mouvement',".$conf->entity.")",
		);

		return $this->_init($sql,$options);
	}
}
