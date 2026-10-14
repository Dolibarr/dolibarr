<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France			<frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   product     Module product variants
 *  \brief      Module to manage product combinations based on product attributes
 *  \file       htdocs/core/modules/modVariants.class.php
 *  \ingroup    product
 *  \brief      Description and activation file for the module product variants
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Product variants
 */
class modVariants extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 610;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'variants';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "products";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '50';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = 'Allows creating products variant based on new attributes';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'product';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/variants/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into variants/admin directory, to use to setup module.
		$this->config_page_url = array('admin.php@variants');

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array('modProduct'); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("products");

		// Constants
		$this->const = array();

		// Array to add new pages in new tabs
		$this->tabs = array(
		//	'product:+combinations:Combinaciones:products:1:/variants/combinations.php?id=__ID__'
		);

		// Dictionaries
		if (!isset($conf->variants->enabled)) {
			$conf->variants = new stdClass();
			$conf->variants->enabled = 0;
		}
		$this->dictionaries = array();

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // List of boxes

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = $this->numero + 1; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read attributes of variants'; // Permission label
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;
		$this->rights[$r][0] = $this->numero + 2; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update attributes of variants'; // Permission label
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;
		$this->rights[$r][0] = $this->numero + 3; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete attributes of variants'; // Permission label
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;

		// Imports
		//--------
		// A variant spans 4 tables and the import engine handles one line of file as one record
		// of one table, so the import is split into single table datasets. The upsert is the
		// native import_updatekeys_array. Note that 'entity' is never declared: the engine adds
		// it by itself for every table that has the column. import_permission is not declared
		// either, it is dead code replaced by the global 'import run' permission.
		$r = 0;

		// Import of variant attributes
		$r++;
		$this->import_code[$r] = $this->rights_class.'_attribute';
		$this->import_label[$r] = "VariantAttributes";	// Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('pa' => MAIN_DB_PREFIX.'product_attribute', 'extra' => MAIN_DB_PREFIX.'product_attribute_extrafields');
		$this->import_fields_array[$r] = array(
			'pa.ref' => "VariantAttributeRef*",
			'pa.label' => "VariantAttributeLabel*",
			'pa.ref_ext' => "VariantAttributeRefExt",
			'pa.position' => "VariantAttributeRank"
		);
		// Add extra fields of the attribute
		$keyforselect = 'product_attribute';
		$keyforelement = 'productattribute';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'product_attribute');
		$this->import_examplevalues_array[$r] = array(
			'pa.ref' => "COL",
			'pa.label' => "Color",
			'pa.ref_ext' => "",
			'pa.position' => "10"
		);
		$this->import_convertvalue_array[$r] = array(
			'pa.position' => array('rule' => 'zeroifnull')
		);
		$this->import_updatekeys_array[$r] = array('pa.ref' => 'VariantAttributeRef');

		// Import of variant attribute values
		$r++;
		$this->import_code[$r] = $this->rights_class.'_value';
		$this->import_label[$r] = "VariantAttributeValues";	// Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('pav' => MAIN_DB_PREFIX.'product_attribute_value', 'extra' => MAIN_DB_PREFIX.'product_attribute_value_extrafields');
		$this->import_fields_array[$r] = array(
			'pav.fk_product_attribute' => "VariantAttributeRef*",
			'pav.ref' => "VariantValueRef*",
			'pav.value' => "VariantValueLabel*",
			'pav.position' => "VariantValueRank"
		);
		$this->import_convertvalue_array[$r] = array(
			'pav.fk_product_attribute' => array('rule' => 'fetchidfromref', 'classfile' => '/variants/class/ProductAttribute.class.php', 'class' => 'ProductAttribute', 'method' => 'fetch', 'element' => 'ProductAttribute'),
			'pav.position' => array('rule' => 'zeroifnull')
		);
		// Add extra fields of the attribute value
		$keyforselect = 'product_attribute_value';
		$keyforelement = 'productattributevalue';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinimport.inc.php';
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'product_attribute_value');
		$this->import_examplevalues_array[$r] = array(
			'pav.fk_product_attribute' => "ref:COL",
			'pav.ref' => "BLUE",
			'pav.value' => "Blue",
			'pav.position' => "10"
		);
		$this->import_updatekeys_array[$r] = array('pav.fk_product_attribute' => 'VariantAttributeRef', 'pav.ref' => 'VariantValueRef');

		// Import of variants (combinations). Both the parent and the child products must exist:
		// this import creates the variant links, not the products themselves.
		$r++;
		$this->import_code[$r] = $this->rights_class.'_combination';
		$this->import_label[$r] = "ProductCombinations";	// Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('pac' => MAIN_DB_PREFIX.'product_attribute_combination');
		$this->import_fields_array[$r] = array(
			'pac.fk_product_parent' => "ParentProductOfVariant*",
			'pac.fk_product_child' => "VariantProductRef*",
			'pac.variation_price' => "VariantPriceImpact",
			'pac.variation_price_percentage' => "VariantPriceImpactIsPercent",
			'pac.variation_weight' => "VariantWeightImpact",
			'pac.variation_ref_ext' => "VariantRefExt"
		);
		$this->import_convertvalue_array[$r] = array(
			'pac.fk_product_parent' => array('rule' => 'fetchidfromref', 'classfile' => '/product/class/product.class.php', 'class' => 'Product', 'method' => 'fetch', 'element' => 'Product'),
			'pac.fk_product_child' => array('rule' => 'fetchidfromref', 'classfile' => '/product/class/product.class.php', 'class' => 'Product', 'method' => 'fetch', 'element' => 'Product'),
			'pac.variation_price' => array('rule' => 'zeroifnull'),
			'pac.variation_price_percentage' => array('rule' => 'zeroifnull'),
			'pac.variation_weight' => array('rule' => 'zeroifnull')
		);
		// No 'rowid@table' regex on the foreign keys: the rule loads the whole target table into
		// memory for every imported file, and the existence of the record is already enforced by
		// the convertvalue rule for a ref, by the import trigger for an id, and by the foreign keys.
		$this->import_regex_array[$r] = array(
			'pac.variation_price_percentage' => '^[0-1]$'
		);
		// The engine reads a numeric value as an id, so a product whose ref is a number has to be
		// prefixed with 'ref:'. The examples show the prefix on purpose.
		$this->import_examplevalues_array[$r] = array(
			'pac.fk_product_parent' => "ref:SHIRT (or the id of the parent product)",
			'pac.fk_product_child' => "ref:SHIRT_BLUE_L (or the id of an existing product)",
			'pac.variation_price' => "5.00",
			'pac.variation_price_percentage' => "0",
			'pac.variation_weight' => "0",
			'pac.variation_ref_ext' => ""
		);
		$this->import_updatekeys_array[$r] = array('pac.fk_product_parent' => 'ParentProductOfVariant', 'pac.fk_product_child' => 'VariantProductRef');
		// Preselected: a second row for the same (parent, child) is refused by the import trigger,
		// so a plain insert would only ever fail. Note that imports/import.php cannot tell "no
		// update key chosen" from "no choice made", so a preselection cannot be cancelled by the
		// user: it is reserved to the tables where an insert has no meaning.
		$this->import_preselected_updatekeys_array[$r] = array('pac.fk_product_parent', 'pac.fk_product_child');

		// Import of the attribute values of each variant, one line per variant and attribute
		$r++;
		$this->import_code[$r] = $this->rights_class.'_combination2val';
		$this->import_label[$r] = "VariantFeatures";	// Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('pac2v' => MAIN_DB_PREFIX.'product_attribute_combination2val');
		$this->import_fields_array[$r] = array(
			'pac2v.fk_prod_combination' => "VariantProductRef*",
			'pac2v.fk_prod_attr' => "VariantAttributeRef*",
			'pac2v.fk_prod_attr_val' => "VariantValueRef*"
		);
		$this->import_convertvalue_array[$r] = array(
			'pac2v.fk_prod_combination' => array('rule' => 'fetchidfromref', 'classfile' => '/variants/class/ProductCombination.class.php', 'class' => 'ProductCombination', 'method' => 'fetch', 'element' => 'ProductCombination'),
			'pac2v.fk_prod_attr' => array('rule' => 'fetchidfromref', 'classfile' => '/variants/class/ProductAttribute.class.php', 'class' => 'ProductAttribute', 'method' => 'fetch', 'element' => 'ProductAttribute'),
			// A value ref is only unique for a given attribute, and fetchidfromref resolves every
			// column without any context, so the value is resolved by a computed field that reads
			// the attribute ref of the same line.
			'pac2v.fk_prod_attr_val' => array('rule' => 'compute', 'type' => 'int', 'classfile' => '/variants/class/ProductCombination.class.php', 'class' => 'ProductCombination', 'method' => 'resolveAttributeValueId', 'element' => 'ProductCombination')
		);
		$this->import_examplevalues_array[$r] = array(
			'pac2v.fk_prod_combination' => "ref:SHIRT_BLUE_L (ref of the variant product)",
			'pac2v.fk_prod_attr' => "ref:COL",
			'pac2v.fk_prod_attr_val' => "ref:BLUE"
		);
		$this->import_updatekeys_array[$r] = array('pac2v.fk_prod_combination' => 'VariantProductRef', 'pac2v.fk_prod_attr' => 'VariantAttributeRef');
		// Preselected: the unique index of the table forbids a second value for the same attribute
		$this->import_preselected_updatekeys_array[$r] = array('pac2v.fk_prod_combination', 'pac2v.fk_prod_attr');

		// Import of the price impact of each price level
		// PRODUIT_MULTIPRICES only: the whole propagation chain of the price levels
		// (ProductCombination::create(), update(), fetch(), updateProperties()) tests this
		// constant alone, so exposing the dataset under another one would write rows that
		// never reach llx_product_price.
		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_pricelevel';
			$this->import_label[$r] = "VariantPriceLevels";	// Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array();
			$this->import_tables_array[$r] = array('pacpl' => MAIN_DB_PREFIX.'product_attribute_combination_price_level');
			$this->import_fields_array[$r] = array(
				'pacpl.fk_product_attribute_combination' => "VariantProductRef*",
				'pacpl.fk_price_level' => "VariantPriceLevel*",
				'pacpl.variation_price' => "VariantPriceImpact",
				'pacpl.variation_price_percentage' => "VariantPriceImpactIsPercent"
			);
			$this->import_convertvalue_array[$r] = array(
				'pacpl.fk_product_attribute_combination' => array('rule' => 'fetchidfromref', 'classfile' => '/variants/class/ProductCombination.class.php', 'class' => 'ProductCombination', 'method' => 'fetch', 'element' => 'ProductCombination'),
				'pacpl.variation_price' => array('rule' => 'zeroifnull'),
				'pacpl.variation_price_percentage' => array('rule' => 'zeroifnull')
			);
			$this->import_regex_array[$r] = array(
				'pacpl.fk_price_level' => '^[1-9][0-9]*$',
				'pacpl.variation_price_percentage' => '^[0-1]$'
			);
			$this->import_examplevalues_array[$r] = array(
				'pacpl.fk_product_attribute_combination' => "ref:SHIRT_BLUE_L (ref of the variant product)",
				'pacpl.fk_price_level' => "1",
				'pacpl.variation_price' => "5.00",
				'pacpl.variation_price_percentage' => "0"
			);
			$this->import_updatekeys_array[$r] = array('pacpl.fk_product_attribute_combination' => 'VariantProductRef', 'pacpl.fk_price_level' => 'VariantPriceLevel');
			// Preselected: the reconciliation of a variant already creates one row per price level,
			// so a plain insert would always hit the unique index of the table.
			$this->import_preselected_updatekeys_array[$r] = array('pacpl.fk_product_attribute_combination', 'pacpl.fk_price_level');
		}

		// Exports
		//--------
		$r = 0;

		// Export of the attributes and their values
		$r++;
		$this->export_code[$r] = $this->rights_class.'_attribute';
		$this->export_label[$r] = "VariantAttributes";	// Translation key
		$this->export_icon[$r] = $this->picto;
		$this->export_permission[$r] = array(array("variants", "read"), array("produit", "export"));
		$this->export_fields_array[$r] = array(
			'pa.rowid' => "Id",
			'pa.ref' => "VariantAttributeRef",
			'pa.label' => "VariantAttributeLabel",
			'pa.ref_ext' => "VariantAttributeRefExt",
			'pa.position' => "VariantAttributeRank",
			'pav.ref' => "VariantValueRef",
			'pav.value' => "VariantValueLabel",
			'pav.position' => "VariantValueRank"
		);
		$this->export_TypeFields_array[$r] = array(
			'pa.ref' => "Text",
			'pa.label' => "Text",
			'pa.ref_ext' => "Text",
			'pa.position' => "Numeric",
			'pav.ref' => "Text",
			'pav.value' => "Text",
			'pav.position' => "Numeric"
		);
		$this->export_entities_array[$r] = array(
			'pa.rowid' => "productattribute",
			'pa.ref' => "productattribute",
			'pa.label' => "productattribute",
			'pa.ref_ext' => "productattribute",
			'pa.position' => "productattribute",
			'pav.ref' => "productattributevalue",
			'pav.value' => "productattributevalue",
			'pav.position' => "productattributevalue"
		);
		$this->export_dependencies_array[$r] = array('productattributevalue' => 'pa.rowid');
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM '.MAIN_DB_PREFIX.'product_attribute as pa';
		// An entity condition on a LEFT JOIN belongs to the ON, otherwise the attributes carrying
		// no value at all disappear from the export.
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute_value as pav ON pav.fk_product_attribute = pa.rowid AND pav.entity IN ('.getEntity('product').')';
		$this->export_sql_end[$r] .= ' WHERE pa.entity IN ('.getEntity('product').')';
		$this->export_sql_order[$r] = ' ORDER BY pa.position, pa.ref, pav.position, pav.ref';

		// Export of the variants, one line per variant and feature
		$r++;
		$this->export_code[$r] = $this->rights_class.'_combination';
		$this->export_label[$r] = "ProductCombinations";	// Translation key
		$this->export_icon[$r] = $this->picto;
		$this->export_permission[$r] = array(array("variants", "read"), array("produit", "export"));
		$this->export_fields_array[$r] = array(
			'p.ref' => "ParentProductOfVariant",
			'p.label' => "ParentProductLabel",
			'p2.ref' => "VariantProductRef",
			'p2.label' => "VariantProductLabel",
			'pac.variation_price' => "VariantPriceImpact",
			'pac.variation_price_percentage' => "VariantPriceImpactIsPercent",
			'pac.variation_weight' => "VariantWeightImpact",
			'pac.variation_ref_ext' => "VariantRefExt",
			'pa.ref' => "VariantAttributeRef",
			'pa.label' => "VariantAttributeLabel",
			'pav.ref' => "VariantValueRef",
			'pav.value' => "VariantValueLabel"
		);
		$this->export_TypeFields_array[$r] = array(
			'p.ref' => "Text",
			'p.label' => "Text",
			'p2.ref' => "Text",
			'p2.label' => "Text",
			'pac.variation_price' => "Numeric",
			'pac.variation_price_percentage' => "Boolean",
			'pac.variation_weight' => "Numeric",
			'pac.variation_ref_ext' => "Text",
			'pa.ref' => "Text",
			'pa.label' => "Text",
			'pav.ref' => "Text",
			'pav.value' => "Text"
		);
		$this->export_entities_array[$r] = array(
			'p.ref' => "product",
			'p.label' => "product",
			'p2.ref' => "productcombination",
			'p2.label' => "productcombination",
			'pac.variation_price' => "productcombination",
			'pac.variation_price_percentage' => "productcombination",
			'pac.variation_weight' => "productcombination",
			'pac.variation_ref_ext' => "productcombination",
			'pa.ref' => "productattribute",
			'pa.label' => "productattribute",
			'pav.ref' => "productattributevalue",
			'pav.value' => "productattributevalue"
		);
		// p2.ref, not pac.rowid: export.php uses the dependency as a key of export_fields_array
		// and only an exportable field can be auto selected. A product ref identifies a variant.
		$this->export_dependencies_array[$r] = array('productattribute' => 'p2.ref', 'productattributevalue' => 'p2.ref');
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM '.MAIN_DB_PREFIX.'product_attribute_combination as pac';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p ON p.rowid = pac.fk_product_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p2 ON p2.rowid = pac.fk_product_child';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute_combination2val as pac2v ON pac2v.fk_prod_combination = pac.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute as pa ON pa.rowid = pac2v.fk_prod_attr AND pa.entity IN ('.getEntity('product').')';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute_value as pav ON pav.rowid = pac2v.fk_prod_attr_val AND pav.entity IN ('.getEntity('product').')';
		$this->export_sql_end[$r] .= ' WHERE p.entity IN ('.getEntity('product').')';
		$this->export_sql_end[$r] .= ' AND p2.entity IN ('.getEntity('product').')';
		// The combination carries its own entity, which may differ from the one of its products as
		// soon as product sharing is enabled: it has to be filtered on its own. The link table
		// pac2v holds no entity column, it is scoped through its combination.
		$this->export_sql_end[$r] .= ' AND pac.entity IN ('.getEntity('product').')';
		$this->export_sql_order[$r] = ' ORDER BY p.ref, p2.ref, pa.position, pa.ref';

		// Export of the price impact per price level
		// PRODUIT_MULTIPRICES only: the whole propagation chain of the price levels
		// (ProductCombination::create(), update(), fetch(), updateProperties()) tests this
		// constant alone, so exposing the dataset under another one would write rows that
		// never reach llx_product_price.
		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$r++;
			$this->export_code[$r] = $this->rights_class.'_pricelevel';
			$this->export_label[$r] = "VariantPriceLevels";	// Translation key
			$this->export_icon[$r] = $this->picto;
			$this->export_permission[$r] = array(array("variants", "read"), array("produit", "export"));
			$this->export_fields_array[$r] = array(
				'p.ref' => "ParentProductOfVariant",
				'p2.ref' => "VariantProductRef",
				'pacpl.fk_price_level' => "VariantPriceLevel",
				'pacpl.variation_price' => "VariantPriceImpact",
				'pacpl.variation_price_percentage' => "VariantPriceImpactIsPercent"
			);
			$this->export_TypeFields_array[$r] = array(
				'p.ref' => "Text",
				'p2.ref' => "Text",
				'pacpl.fk_price_level' => "Numeric",
				'pacpl.variation_price' => "Numeric",
				'pacpl.variation_price_percentage' => "Boolean"
			);
			$this->export_entities_array[$r] = array(
				'p.ref' => "product",
				'p2.ref' => "productcombination",
				'pacpl.fk_price_level' => "productcombination",
				'pacpl.variation_price' => "productcombination",
				'pacpl.variation_price_percentage' => "productcombination"
			);
			$this->export_sql_start[$r] = 'SELECT DISTINCT ';
			$this->export_sql_end[$r] = ' FROM '.MAIN_DB_PREFIX.'product_attribute_combination_price_level as pacpl';
			$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product_attribute_combination as pac ON pac.rowid = pacpl.fk_product_attribute_combination';
			$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p ON p.rowid = pac.fk_product_parent';
			$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p2 ON p2.rowid = pac.fk_product_child';
			$this->export_sql_end[$r] .= ' WHERE p.entity IN ('.getEntity('product').')';
			$this->export_sql_end[$r] .= ' AND p2.entity IN ('.getEntity('product').')';
			// The price level table holds no entity column, it is scoped through its combination
			$this->export_sql_end[$r] .= ' AND pac.entity IN ('.getEntity('product').')';
			$this->export_sql_order[$r] = ' ORDER BY p.ref, p2.ref, pacpl.fk_price_level';
		}
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function adds constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/install/mysql/', 'variants');
		if ($result < 0) {
			// Do not activate module if error 'not allowed' returned when loading module SQL queries
			// (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
			return -1;
		}

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
