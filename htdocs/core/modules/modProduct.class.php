<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
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
 *	\defgroup   produit     Module products
 *	\brief      Module to manage catalog of predefined products
 *	\file       htdocs/core/modules/modProduct.class.php
 *	\ingroup    produit
 *	\brief      File to describe module to manage catalog of predefined products
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class descriptor of Product module
 */
class modProduct extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 50;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des produits";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='product';

		// Data directories to create when module is enabled
		$this->dirs = array("/product/temp");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array("modStock","modBarcode");

		// Config pages
		$this->config_page_url = array("product.php@product");
		$this->langfiles = array("products","companies","stocks","bills");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "PRODUCT_CODEPRODUCT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_codeproduct_leopard";
		$this->const[$r][3] = 'Module to control product codes';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_SEARCHFORM_PRODUITSERVICE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Show form for quick product search";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array(
			0=>array('file'=>'box_produits.php','enabledbydefaulton'=>'Home'),
			1=>array('file'=>'box_produits_alerte_stock.php','enabledbydefaulton'=>''),
			2=>array('file'=>'box_graph_product_distribution.php','enabledbydefaulton'=>'Home')
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'produit';
		$r=0;

		$this->rights[$r][0] = 31; // id de la permission
		$this->rights[$r][1] = 'Lire les produits'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';
		$r++;

		$this->rights[$r][0] = 32; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les produits'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';
		$r++;

		$this->rights[$r][0] = 34; // id de la permission
		$this->rights[$r][1] = 'Supprimer les produits'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';
        $r++;

		$this->rights[$r][0] = 38;	// Must be same permission than in service module
		$this->rights[$r][1] = 'Exporter les produits';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
        $r++;


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Products";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("produit","export"));
		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.label'=>"Label",'p.description'=>"Description",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.customcode'=>'CustomCode','p.price_base_type'=>"PriceBase",'p.price'=>"UnitPriceHT",'p.price_ttc'=>"UnitPriceTTC",'p.tva_tx'=>'VATRate','p.tosell'=>"OnSell",'p.tobuy'=>"OnBuy",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
		if (! empty($conf->stock->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.stock'=>'Stock','p.pmp'=>'PMPValue'));
		if (! empty($conf->barcode->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.barcode'=>'BarCode'));
		$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.length'=>"Number",'p.surface'=>"Number",'p.volume'=>"Number",'p.weight'=>"Number",'p.customcode'=>'Text','p.price_base_type'=>"Text",'p.price'=>"Number",'p.price_ttc'=>"Number",'p.tva_tx'=>'Number','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.datec'=>'Date','p.tms'=>'Date');
		if (! empty($conf->stock->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.pmp'=>'Number'));
		if (! empty($conf->barcode->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.barcode'=>'Text'));
		$this->export_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",'p.label'=>"product",'p.description'=>"product",'p.accountancy_code_sell'=>'product','p.note'=>"product",'p.length'=>"product",'p.surface'=>"product",'p.volume'=>"product",'p.weight'=>"product",'p.customcode'=>'product','p.price_base_type'=>"product",'p.price'=>"product",'p.price_ttc'=>"product",'p.tva_tx'=>"product",'p.tosell'=>"product",'p.tobuy'=>"product",'p.datec'=>"product",'p.tms'=>"product");
		if (! empty($conf->stock->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.stock'=>'product','p.pmp'=>'product'));
		if (! empty($conf->barcode->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.barcode'=>'product'));
		// Add extra fields
		$sql="SELECT name, label, type FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$typeFilter="List:".$obj->param;
						break;
				}
		        $this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
		        $this->export_entities_array[$r][$fieldname]='product';
		    }
		}
		// End add axtra fields

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON p.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity("product", 1).')';


		// Imports
		//--------
		$r=0;

		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="Products";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('p'=>MAIN_DB_PREFIX.'product','extra'=>MAIN_DB_PREFIX.'product_extrafields');
		$this->import_tables_creator_array[$r]=array('p'=>'fk_user_author');	// Fields to store import user id
		$this->import_fields_array[$r]=array('p.ref'=>"Ref*",'p.label'=>"Label*",'p.description'=>"Description",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.duration'=>"Duration",'p.customcode'=>'CustomCode','p.price'=>"SellingPriceHT",'p.price_ttc'=>"SellingPriceTTC",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell*",'p.tobuy'=>"OnBuy*",'p.fk_product_type'=>"Type*",'p.finished'=>'Nature','p.datec'=>'DateCreation*');
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND entity = ".$conf->entity;
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
		    }
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r]=array('extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'product');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_regex_array[$r]=array('p.ref'=>'[^ ]','p.tosell'=>'^[0|1]$','p.tobuy'=>'^[0|1]$','p.fk_product_type'=>'^[0|1]$','p.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$');
		$this->import_examplevalues_array[$r]=array('p.ref'=>"PREF123456",'p.label'=>"My product",'p.description'=>"This is a description example for record",'p.note'=>"Some note",'p.price'=>"100",'p.price_ttc'=>"110",'p.tva_tx'=>'10','p.tosell'=>"0 or 1",'p.tobuy'=>"0 or 1",'p.fk_product_type'=>"0 for product/1 for service",'p.finished'=>'','p.duration'=>"1y",'p.datec'=>'2008-12-31');


		if (! empty($conf->fournisseur->enabled))
		{
			// Import product suppliers
			$r++;
			$this->import_code[$r]=$this->rights_class.'_'.$r;
			$this->import_label[$r]="SuppliersPrices";	// Translation key
			$this->import_icon[$r]='product';
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r]=array('sp'=>MAIN_DB_PREFIX.'product_fournisseur_price');
			$this->import_tables_creator_array[$r]=array('sp'=>'fk_user');
			$this->import_fields_array[$r]=array('sp.fk_product'=>"Product*",
					'sp.fk_soc'=>"Supplier*", 'sp.ref_fourn'=>'SupplierRef', 'sp.quantity'=>"QtyMin*", 'sp.tva_tx'=>'VATRate',
					'sp.price'=>"PriceQtyMinHT*",
					'sp.unitprice'=>'UnitPriceHT*',	// TODO Make this file not required and calculate it from price and qty
					'sp.remise_percent'=>'DiscountQtyMin'
			);

			$this->import_convertvalue_array[$r]=array(
					'sp.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty'),
					'sp.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'Product')
			);
			$this->import_examplevalues_array[$r]=array('sp.fk_product'=>"PREF123456",
					'sp.fk_soc'=>"My Supplier",'sp.ref_fourn'=>"SupplierRef", 'sp.quantity'=>"1", 'sp.tva_tx'=>'21',
					'sp.price'=>"50",
					'sp.unitprice'=>'50',
					'sp.remise_percent'=>'0'
			);
		}

		if (! empty($conf->global->PRODUIT_MULTIPRICES)) {
		// Exports product multiprice
		//--------
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="ProductsMultiPrice";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("produit","export"));
		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",
			'pr.price_base_type'=>"PriceLevelPriceBase",'pr.price_level'=>"PriceLevel",
			'pr.price'=>"PriceLevelUnitPriceHT",'pr.price_ttc'=>"PriceLevelUnitPriceTTC",
			'pr.price_min'=>"MinPriceLevelUnitPriceHT",'pr.price_min_ttc'=>"MinPriceLevelUnitPriceTTC",
			'pr.tva_tx'=>'PriceLevelVATRate',
			'pr.date_price'=>'DateCreation');
		$this->export_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",
			'pr.price_base_type'=>"product",'pr.price_level'=>"product",'pr.price'=>"product",
			'pr.price_ttc'=>"product",
			'pr.price_min'=>"MinPriceLevelUnitPriceHT",'pr.price_min_ttc'=>"MinPriceLevelUnitPriceTTC",
			'pr.tva_tx'=>'product',
			'pr.date_price'=>"product");
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_price as pr ON p.rowid = pr.fk_product';
		$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity("product", 1).')';


		// Import product multiprice
		//--------
		$r=0;

		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ProductsMultiPrice";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('pr'=>MAIN_DB_PREFIX.'product_price');
		$this->import_tables_creator_array[$r]=array('pr'=>'fk_user_author');	// Fields to store import user id
		$this->import_fields_array[$r]=array('pr.fk_product'=>"ProductRowid*",
			'pr.price_base_type'=>"PriceLevelPriceBase",'pr.price_level'=>"PriceLevel",
			'pr.price'=>"PriceLevelUnitPriceHT",'pr.price_ttc'=>"PriceLevelUnitPriceTTC",
			'pr.price_min'=>"MinPriceLevelUnitPriceHT",'pr.price_min_ttc'=>"MinPriceLevelUnitPriceTTC",
			'pr.tva_tx'=>'PriceLevelVATRate',
			'pr.date_price'=>'DateCreation*');
		$this->import_regex_array[$r]=array('pr.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$');
		$this->import_examplevalues_array[$r]=array('pr.fk_product'=>"1",
			'pr.price_base_type'=>"HT",'pr.price_level'=>"1",
			'pr.price'=>"100",'pr.price_ttc'=>"110",
			'pr.price_min'=>"100",'pr.price_min_ttc'=>"110",
			'pr.tva_tx'=>'19.6',
			'pr.date_price'=>'2013-04-10');
		}

	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
