<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\defgroup   service     Module services
 *	\brief      Module pour gerer le suivi de services predefinis
 *	\file       htdocs/core/modules/modService.class.php
 *	\ingroup    service
 *	\brief      Fichier de description et activation du module Service
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Service
 */
class modService extends DolibarrModules
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
		$this->numero = 53;

		$this->family = "products";
		$this->module_position = 30;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des services";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='service';

		// Data directories to create when module is enabled
		$this->dirs = array("/product/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("product.php@product");
		$this->langfiles = array("products","companies","bills");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array(
			0=>array('file'=>'box_services_contracts.php','enabledbydefaulton'=>'Home'),
			1=>array('file'=>'box_graph_product_distribution.php','enabledbydefaulton'=>'Home')
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'service';
		$r=0;

		$this->rights[$r][0] = 531; // id de la permission
		$this->rights[$r][1] = 'Lire les services'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';
        $r++;

		$this->rights[$r][0] = 532; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les services'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';
        $r++;

		$this->rights[$r][0] = 534; // id de la permission
		$this->rights[$r][1] = 'Supprimer les services'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';
        $r++;

		$this->rights[$r][0] = 538;	// Must be same permission than in product module
		$this->rights[$r][1] = 'Exporter les services';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
        $r++;


		/* We can't enable this here because it must be enabled in both product and service module and this create duplicate insert
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'ProductVatMassChange',
								'url'=>'/product/admin/product_tools.php?mainmenu=home&leftmenu=admintools',
								'langs'=>'products',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>300,
								'enabled'=>'$conf->product->enabled && $leftmenu=="admintools"',   // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		*/


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.
        
        
        // Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Services";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("service","export"));
		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.label'=>"Label",'p.description'=>"Description",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.price_base_type'=>"PriceBase",'p.price'=>"UnitPriceHT",'p.price_ttc'=>"UnitPriceTTC",'p.tva_tx'=>'VATRate','p.tosell'=>"OnSell",'p.tobuy'=>"OnBuy",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
		if (! empty($conf->stock->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.stock'=>'Stock'));
		//$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date');
		$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.duration'=>"Duree",'p.datec'=>'Date','p.tms'=>'Date');
		if (! empty($conf->stock->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.stock'=>'Numeric'));
		if (! empty($conf->barcode->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.barcode'=>'Text'));
		$this->export_entities_array[$r]=array('p.rowid'=>"service",'p.ref'=>"service",'p.label'=>"service",'p.description'=>"service",'p.accountancy_code_sell'=>'service','p.note'=>"service",'p.price_base_type'=>"service",'p.price'=>"service",'p.price_ttc'=>"service",'p.tva_tx'=>"service",'p.tosell'=>"service",'p.tobuy'=>"service",'p.duration'=>"service",'p.datec'=>"service",'p.tms'=>"service");
		if (! empty($conf->stock->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.stock'=>'service'));
		if (! empty($conf->barcode->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.barcode'=>'service'));
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
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
						if ($tmpparam['options'] && is_array($tmpparam['options'])) $tmp=array_shift(array_keys($tmpparam['options']));
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='product';
		    }
		}
		// End add extra fields

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON p.rowid = extra.fk_object';
		if (! empty($conf->fournisseur->enabled)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price as pf ON pf.fk_product = p.rowid LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON s.rowid = pf.fk_soc';
        $this->export_sql_end[$r] .=' WHERE p.fk_product_type = 1 AND p.entity IN ('.getEntity("product", 1).')';


		if (empty($conf->product->enabled))	// We enable next import templates only if module product not already enabled (to avoid duplicate entries)
		{
			if (! empty($conf->global->PRODUIT_MULTIPRICES))
			{
				// Exports product multiprice
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
				//$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.url'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.length'=>"Numeric",'p.surface'=>"Numeric",'p.volume'=>"Numeric",'p.weight'=>"Numeric",'p.customcode'=>'Text','p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.datec'=>'Date','p.tms'=>'Date');
				$this->export_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",
					'pr.price_base_type'=>"product",'pr.price_level'=>"product",'pr.price'=>"product",
					'pr.price_ttc'=>"product",
					'pr.price_min'=>"product",'pr.price_min_ttc'=>"product",
					'pr.tva_tx'=>'product',
					'pr.date_price'=>"product");
				$this->export_sql_start[$r]='SELECT DISTINCT ';
				$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
				$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_price as pr ON p.rowid = pr.fk_product';
				$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity("product", 1).')';
			}
		}


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
        $this->import_fields_array[$r]=array('p.ref'=>"Ref*",'p.label'=>"Label*",'p.description'=>"Description",'p.url'=>"PublicUrl",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.duration'=>"Duration",'p.customcode'=>'CustomCode','p.price'=>"SellingPriceHT",'p.price_ttc'=>"SellingPriceTTC",'p.tva_tx'=>'VAT','p.tosell'=>"OnSell*",'p.tobuy'=>"OnBuy*",'p.fk_product_type'=>"Type*",'p.finished'=>'Nature','p.datec'=>'DateCreation');
		if (! empty($conf->barcode->enabled)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.barcode'=>'BarCode'));
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


		if (empty($conf->product->enabled))	// We enable next import templates only if module product not already enabled (to avoid duplicate entries)
		{
			if (! empty($conf->fournisseur->enabled))
			{
				// Import suppliers prices (note: this code is duplicated into module product)
				$r++;
				$this->import_code[$r]=$this->rights_class.'_supplierprices';
				$this->import_label[$r]="SuppliersPricesOfProductsOrServices";	// Translation key
				$this->import_icon[$r]=$this->picto;
				$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
				$this->import_tables_array[$r]=array('sp'=>MAIN_DB_PREFIX.'product_fournisseur_price');
				$this->import_tables_creator_array[$r]=array('sp'=>'fk_user');
				$this->import_fields_array[$r]=array('sp.fk_product'=>"ProductOrService*",
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

			if (! empty($conf->global->PRODUIT_MULTIPRICES))
			{
				// Import product multiprice
				$r++;
				$this->import_code[$r]=$this->rights_class.'_multiprice';
				$this->import_label[$r]="ProductsOrServiceMultiPrice";	// Translation key
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
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
