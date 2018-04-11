<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014      Christophe Battarel	<contact@altairis.fr>
 * Copyright (C) 2014      Cedric Gross			<c.gross@kreiz-it.fr>
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
		global $conf, $mysoc;

		$this->db = $db;
		$this->numero = 50;

		$this->family = "products";
		$this->module_position = 20;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Product management";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='product';

		// Data directories to create when module is enabled
		$this->dirs = array("/product/temp");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array("modStock","modBarcode","modProductBatch");

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

		/*$this->const[$r][0] = "PRODUCT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "standard";
		$this->const[$r][3] = 'Default module for document generation';
		$this->const[$r][4] = 0;
		$r++;*/

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
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
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

        // Menus
        //-------

        $this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		/* We can't enable this here because it must be enabled in both product and service module and this create duplicate insert
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'ProductVatMassChange',
								'url'=>'/product/admin/product_tools.php?mainmenu=home&leftmenu=admintools',
								'langs'=>'products',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>300,
								'enabled'=>'$conf->product->enabled && ($leftmenu=="admintools" || $leftmenu=="admintools_info")',   // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;
		*/

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Products";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("produit","export"));
		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.label'=>"Label",'p.description'=>"Description",'p.url'=>"PublicUrl",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.width'=>"Width",'p.height'=>"Height",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.customcode'=>'CustomCode','p.price_base_type'=>"PriceBase",'p.price'=>"UnitPriceHT",'p.price_ttc'=>"UnitPriceTTC",'p.tva_tx'=>'VATRate','p.tosell'=>"OnSell",'p.tobuy'=>"OnBuy",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
		if (is_object($mysoc) && $mysoc->useNPR()) $this->export_fields_array[$r]['p.recuperableonly']='NPR';
		if (! empty($conf->stock->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.stock'=>'Stock','p.seuil_stock_alerte'=>'StockLimit','p.desiredstock'=>'DesiredStock','p.pmp'=>'PMPValue'));
		if (! empty($conf->barcode->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.barcode'=>'BarCode'));
		if (! empty($conf->fournisseur->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('s.nom'=>'Supplier','pf.ref_fourn'=>'SupplierRef','pf.unitprice'=>'BuyingPrice','pf.quantity'=>'QtyMin','pf.remise_percent'=>'DiscountQtyMin','pf.delivery_time_days'=>'NbDaysToDelivery'));
		if (! empty($conf->fournisseur->enabled) || !empty($conf->margin->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.cost_price'=>'CostPrice'));
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('group_concat(cat.label)'=>'Categories'));
		if (! empty($conf->global->MAIN_MULTILANGS)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('l.lang'=>'Language', 'l.label'=>'TranslatedLabel','l.description'=>'TranslatedDescription','l.note'=>'TranslatedNote'));
		if (! empty($conf->global->PRODUCT_USE_UNITS)) $this->export_fields_array[$r]['p.fk_unit'] = 'Unit';
		$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.url'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.length'=>"Numeric",'p.width'=>"Numeric",'p.height'=>"Numeric",'p.surface'=>"Numeric",'p.volume'=>"Numeric",'p.weight'=>"Numeric",'p.customcode'=>'Text','p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.datec'=>'Date','p.tms'=>'Date');
		if (! empty($conf->stock->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.stock'=>'Numeric','p.seuil_stock_alerte'=>'Numeric','p.desiredstock'=>'Numeric','p.pmp'=>'Numeric','p.cost_price'=>'Numeric'));
		if (! empty($conf->barcode->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.barcode'=>'Text'));
		if (! empty($conf->fournisseur->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('s.nom'=>'Text','pf.ref_fourn'=>'Text','pf.unitprice'=>'Numeric','pf.quantity'=>'Numeric','pf.remise_percent'=>'Numeric','pf.delivery_time_days'=>'Numeric'));
		if (! empty($conf->global->MAIN_MULTILANGS)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('l.lang'=>'Text', 'l.label'=>'Text','l.description'=>'Text','l.note'=>'Text'));
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array("group_concat(cat.label)"=>'Text'));
		$this->export_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array("group_concat(cat.label)"=>'category'));
		if (! empty($conf->stock->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.stock'=>'product','p.pmp'=>'product'));
		if (! empty($conf->barcode->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.barcode'=>'product'));
		if (! empty($conf->fournisseur->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('s.nom'=>'company','pf.ref_fourn'=>'product','pf.unitprice'=>'product'));
		if (! empty($conf->global->MAIN_MULTILANGS)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('l.lang'=>'translation', 'l.label'=>'translation','l.description'=>'translation','l.note'=>'translation'));
		$keyforselect='product'; $keyforelement='product'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_dependencies_array[$r]=array('category'=>'p.rowid');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product = p.rowid LEFT JOIN '.MAIN_DB_PREFIX.'categorie as cat ON cp.fk_categorie = cat.rowid';
		if (! empty($conf->global->MAIN_MULTILANGS)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_lang as l ON l.fk_product = p.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON p.rowid = extra.fk_object';
		if (! empty($conf->fournisseur->enabled)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_fournisseur_price as pf ON pf.fk_product = p.rowid LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON s.rowid = pf.fk_soc';
		$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity('product').')';
		if (! empty($conf->global->EXPORTTOOL_CATEGORIES)) $this->export_sql_order[$r] =' GROUP BY p.rowid'; 	// FIXME The group by used a generic value to say "all fields in select except function fields"

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
			if (is_object($mysoc) && $mysoc->useNPR()) $this->export_fields_array[$r]['pr.recuperableonly']='NPR';
			//$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.url'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.length'=>"Numeric",'p.surface'=>"Numeric",'p.volume'=>"Numeric",'p.weight'=>"Numeric",'p.customcode'=>'Text','p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.datec'=>'Date','p.tms'=>'Date');
			$this->export_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",
				'pr.price_base_type'=>"product",'pr.price_level'=>"product",'pr.price'=>"product",
				'pr.price_ttc'=>"product",
				'pr.price_min'=>"product",'pr.price_min_ttc'=>"product",
				'pr.tva_tx'=>'product',
				'pr.recuperableonly'=>'product',
				'pr.date_price'=>"product");
			$this->export_sql_start[$r]='SELECT DISTINCT ';
			$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
			$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_price as pr ON p.rowid = pr.fk_product';
			$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity('product').')';
		}

		if (! empty($conf->global->PRODUIT_SOUSPRODUITS))
		{
        	$r++;
    		$this->export_code[$r]=$this->rights_class.'_'.$r;
    		$this->export_label[$r]="AssociatedProducts";	// Translation key (used only if key ExportDataset_xxx_z not found)
    		$this->export_permission[$r]=array(array("produit","export"));
    		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.label'=>"Label",'p.description'=>"Description",'p.url'=>"PublicUrl",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.customcode'=>'CustomCode','p.price_base_type'=>"PriceBase",'p.price'=>"UnitPriceHT",'p.price_ttc'=>"UnitPriceTTC",'p.tva_tx'=>'VATRate','p.tosell'=>"OnSell",'p.tobuy'=>"OnBuy",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
    		if (! empty($conf->stock->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.stock'=>'Stock','p.seuil_stock_alerte'=>'StockLimit','p.desiredstock'=>'DesiredStock','p.pmp'=>'PMPValue'));
    		if (! empty($conf->barcode->enabled)) $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p.barcode'=>'BarCode'));
    		$this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('pa.qty'=>'Qty','pa.incdec'=>'ComposedProductIncDecStock'));
    		$this->export_TypeFields_array[$r]=array('p.ref'=>"Text",'p.label'=>"Text",'p.description'=>"Text",'p.url'=>"Text",'p.accountancy_code_sell'=>"Text",'p.accountancy_code_buy'=>"Text",'p.note'=>"Text",'p.length'=>"Numeric",'p.surface'=>"Numeric",'p.volume'=>"Numeric",'p.weight'=>"Numeric",'p.customcode'=>'Text','p.price_base_type'=>"Text",'p.price'=>"Numeric",'p.price_ttc'=>"Numeric",'p.tva_tx'=>'Numeric','p.tosell'=>"Boolean",'p.tobuy'=>"Boolean",'p.datec'=>'Date','p.tms'=>'Date');
    		if (! empty($conf->stock->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.stock'=>'Numeric','p.seuil_stock_alerte'=>'Numeric','p.desiredstock'=>'Numeric','p.pmp'=>'Numeric','p.cost_price'=>'Numeric'));
    		if (! empty($conf->barcode->enabled)) $this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('p.barcode'=>'Text'));
    		$this->export_TypeFields_array[$r]=array_merge($this->export_TypeFields_array[$r],array('pa.qty'=>'Numeric'));
    		$this->export_entities_array[$r]=array('p.rowid'=>"virtualproduct",'p.ref'=>"virtualproduct",'p.label'=>"virtualproduct",'p.description'=>"virtualproduct",'p.url'=>"virtualproduct",'p.accountancy_code_sell'=>'virtualproduct','p.accountancy_code_buy'=>'virtualproduct','p.note'=>"virtualproduct",'p.length'=>"virtualproduct",'p.surface'=>"virtualproduct",'p.volume'=>"virtualproduct",'p.weight'=>"virtualproduct",'p.customcode'=>'virtualproduct','p.price_base_type'=>"virtualproduct",'p.price'=>"virtualproduct",'p.price_ttc'=>"virtualproduct",'p.tva_tx'=>"virtualproduct",'p.tosell'=>"virtualproduct",'p.tobuy'=>"virtualproduct",'p.datec'=>"virtualproduct",'p.tms'=>"virtualproduct");
    		if (! empty($conf->stock->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.stock'=>'virtualproduct','p.seuil_stock_alerte'=>'virtualproduct','p.desiredstock'=>'virtualproduct','p.pmp'=>'virtualproduct'));
    		if (! empty($conf->barcode->enabled)) $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p.barcode'=>'virtualproduct'));
            $this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('pa.qty'=>"subproduct",'pa.incdec'=>'subproduct'));
    		$keyforselect='product'; $keyforelement='product'; $keyforaliasextra='extra';
    		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
            $this->export_fields_array[$r]=array_merge($this->export_fields_array[$r],array('p2.rowid'=>"Id",'p2.ref'=>"Ref",'p2.label'=>"Label",'p2.description'=>"Description"));
    		$this->export_entities_array[$r]=array_merge($this->export_entities_array[$r],array('p2.rowid'=>"subproduct",'p2.ref'=>"subproduct",'p2.label'=>"subproduct",'p2.description'=>"subproduct"));
    		$this->export_sql_start[$r]='SELECT DISTINCT ';
    		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
    		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON p.rowid = extra.fk_object,';
    		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'product_association as pa, '.MAIN_DB_PREFIX.'product as p2';
    		$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 0 AND p.entity IN ('.getEntity('product').')';
    		$this->export_sql_end[$r] .=' AND p.rowid = pa.fk_product_pere AND p2.rowid = pa.fk_product_fils';
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
		$this->import_fields_array[$r]=array('p.ref'=>"Ref*",'p.label'=>"Label*",'p.description'=>"Description",'p.url'=>"PublicUrl",'p.accountancy_code_sell'=>"ProductAccountancySellCode",'p.accountancy_code_buy'=>"ProductAccountancyBuyCode",'p.note'=>"Note",'p.length'=>"Length",'p.surface'=>"Surface",'p.volume'=>"Volume",'p.weight'=>"Weight",'p.duration'=>"Duration",'p.customcode'=>'CustomCode','p.price'=>"SellingPriceHT",'p.price_ttc'=>"SellingPriceTTC", 'p.tva_tx'=>'VATRate', 'p.tosell'=>"OnSell*",'p.tobuy'=>"OnBuy*",'p.fk_product_type'=>"Type*",'p.finished'=>'Nature','p.datec'=>'DateCreation');
		if (! empty($conf->stock->enabled)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.seuil_stock_alerte'=>'StockLimit','p.desiredstock'=>'DesiredStock','p.pmp'=>'PMPValue'));
		if (! empty($conf->fournisseur->enabled) || !empty($conf->margin->enabled)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.cost_price'=>'CostPrice'));
		if (is_object($mysoc) && $mysoc->useNPR()) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.recuperableonly'=>'NPR'));
		if (is_object($mysoc) && $mysoc->useLocalTax(1)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.localtax1_tx'=>'LT1', 'p.localtax1_type'=>'LT1Type'));
		if (is_object($mysoc) && $mysoc->useLocalTax(2)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.localtax2_tx'=>'LT2', 'p.localtax2_type'=>'LT2Type'));
		if (! empty($conf->barcode->enabled)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('p.barcode'=>'BarCode'));
		if (! empty($conf->global->PRODUCT_USE_UNITS)) $this->import_fields_array[$r]['p.fk_unit'] = 'Unit';
		// Add extra fields
		$import_extrafield_sample=array();
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'product' AND entity IN (0, ".$conf->entity.')';
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
		        $import_extrafield_sample[$fieldname]=$fieldlabel;
		    }
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r]=array('extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'product');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_regex_array[$r]=array('p.ref'=>'[^ ]','p.tosell'=>'^[0|1]$','p.tobuy'=>'^[0|1]$','p.fk_product_type'=>'^[0|1]$','p.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','p.recuperableonly'=>'^[0|1]$');
		$import_sample=array('p.ref'=>"PREF123456",'p.label'=>"My product",'p.description'=>"This is a description example for record",'p.note'=>"Some note",'p.price'=>"100",'p.price_ttc'=>"110",'p.tva_tx'=>'10','p.tosell'=>"0 or 1",'p.tobuy'=>"0 or 1",'p.fk_product_type'=>"0 for product/1 for service",'p.finished'=>'','p.duration'=>"1y",'p.datec'=>'2008-12-31','p.recuperableonly'=>'0 or 1');
		$this->import_examplevalues_array[$r]=array_merge($import_sample,$import_extrafield_sample);
		$this->import_updatekeys_array[$r]=array('p.ref'=>'Ref','p.barcode'=>'BarCode');

		if (! empty($conf->fournisseur->enabled))
		{
			// Import suppliers prices (note: this code is duplicated into module service)
			$r++;
			$this->import_code[$r]=$this->rights_class.'_supplierprices';
			$this->import_label[$r]="SuppliersPricesOfProductsOrServices";	// Translation key
			$this->import_icon[$r]=$this->picto;
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r]=array('sp'=>MAIN_DB_PREFIX.'product_fournisseur_price');
			$this->import_tables_creator_array[$r]=array('sp'=>'fk_user');
			$this->import_fields_array[$r]=array(
					'sp.fk_product'=>"ProductOrService*",
					'sp.fk_soc'=>"Supplier*", 'sp.ref_fourn'=>'SupplierRef', 'sp.quantity'=>"QtyMin*", 'sp.tva_tx'=>'VATRate', 'sp.default_vat_code'=>'VATCode'
			);
			if (is_object($mysoc) && $mysoc->useNPR())       $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('sp.recuperableonly'=>'VATNPR'));
			if (is_object($mysoc) && $mysoc->useLocalTax(1)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('sp.localtax1_tx'=>'LT1', 'sp.localtax1_type'=>'LT1Type'));
			if (is_object($mysoc) && $mysoc->useLocalTax(2)) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('sp.localtax2_tx'=>'LT2', 'sp.localtax2_type'=>'LT2Type'));
			$this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array(
					'sp.price'=>"PriceQtyMinHT*",
					'sp.unitprice'=>'UnitPriceHT*',	// TODO Make this field not required and calculate it from price and qty
					'sp.remise_percent'=>'DiscountQtyMin'
			));
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
			$this->import_updatekeys_array[$r]=array('sp.fk_product'=>'ProductOrService','sp.ref_fourn'=>'SupplierRef','sp.fk_soc'=>'Supplier');
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
			if (is_object($mysoc) && $mysoc->useNPR()) $this->import_fields_array[$r]=array_merge($this->import_fields_array[$r],array('pr.recuperableonly'=>'NPR'));
			$this->import_regex_array[$r]=array('pr.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','pr.recuperableonly'=>'^[0|1]$');
			$this->import_examplevalues_array[$r]=array('pr.fk_product'=>"1",
				'pr.price_base_type'=>"HT",'pr.price_level'=>"1",
				'pr.price'=>"100",'pr.price_ttc'=>"110",
				'pr.price_min'=>"100",'pr.price_min_ttc'=>"110",
				'pr.tva_tx'=>'20',
			    'pr.recuperableonly'=>'0',
				'pr.date_price'=>'2013-04-10');
		}

		if (! empty($conf->global->MAIN_MULTILANGS))
		{
		    $r++;
		    $this->import_code[$r]=$this->rights_class.'_languages';
		    $this->import_label[$r]="ProductsOrServicesTranslations";
			$this->import_icon[$r]=$this->picto;
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		    $this->import_tables_array[$r]=array('l'=>MAIN_DB_PREFIX.'product_lang');
			// multiline translation, one line per translation
			$this->import_fields_array[$r]=array('l.fk_product'=>'Ref', 'l.lang'=>'Language', 'l.label'=>'TranslatedLabel', 'l.description'=>'TranslatedDescription');
			//$this->import_fields_array[$r]['l.note']='TranslatedNote';
			$this->import_convertvalue_array[$r]=array(
					'l.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'Product')
			);
			$this->import_examplevalues_array[$r]=array('l.fk_product'=>'MyProductRef','l.lang'=>'en_US','l.label'=>'Label in en_US','l.description'=>'Desc in en_US');
			$this->import_updatekeys_array[$r]=array('l.fk_product'=>'Ref','l.lang'=>'Language');
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
