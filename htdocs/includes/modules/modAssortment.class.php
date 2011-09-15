<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * 		\defgroup   mymodule     Module Assortment
 *      \brief      This module will be used when limeted item list should be avaible by Customer/Supplier.
 *				
 */

/**
 *      \file       htdocs/includes/modules/modAssortment.class.php
 *      \ingroup    Assortment
 *      \brief      Description and activation file for module Assortment
 *		\version	$Id: modAssortment.class.php,v 1.55 2010/12/13 13:16:04 eldy Exp $
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 * 		\class      modAssortment
 *      \brief      Description and activation class for module Assortment
 */
class modAssortment extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function modAssortment($DB)
	{
		$this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 50500;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'assortment';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Used for limit item list should be avaible by Customer/Supplier.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where Assortment is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='assortment';

		// Defined if the directory /Assortment/inc/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/Assortment/temp");
		$this->dirs = array();
		$r=0;

		// Relative path to module style sheet if exists. Example: '/Assortment/css/mycss.css'.
		//$this->style_sheet = '/Assortment/Assortment.css.php';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("../assortment/admin/assortment.php");

		// Dependencies
		$this->depends = array("modProduct","modSociete");		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("orders","products","companies","assortment","categorie");

		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r=0;
		
		$this->const[$r][0] = "ASSORTMENT_BY_CAT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Manage assortment by Customer/Supplier category';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		
		$r++;
		
		$this->const[$r][0] = "ASSORTMENT_BY_CAT_RECURSIVE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Manage assortment by Customer/Supplier category recursive parameters';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		
		$r++;
		$this->const[$r][0] = "ASSORTMENT_ON_ORDER";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Use item selection by Customer assortment in order and others screen';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		
		$r++;
		$this->const[$r][0] = "ASSORTMENT_ON_ORDER_FOUR";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Use item selection by Supplier assortment in order and invoice screen';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		// Array to add new pages in new tabs
		$this->tabs = array('thirdparty:Assortment:@assortment:/assortment/assortment.php?socid=__ID__&type=1',
							'product:Assortment:@assortment:/assortment/assortment.php?prodid=__ID__&type=0');

		// Permissions
		$this->rights = array();
		$this->rights_class = 'assortment';

		$r=0;

		$r++;
		$this->rights[$r][0] = 50501;
		$this->rights[$r][1] = 'See assortment';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 50502;
		$this->rights[$r][1] = 'Create/Update assortment';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 50503;
		$this->rights[$r][1] = 'Delete assortment';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		
		$r++;
		$this->rights[$r][0] = 50504;
		$this->rights[$r][1] = 'Export assortment';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'export';

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		//No new menu entry, Only tabs in thirdparty and product


		// Exports
		$r=0;

		// Example:
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='CustomersAssortment';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("assortment","export"));
		$this->export_fields_array[$r]=array('assort.rowid'=>"IdAssortment",'assort.label'=>'AssortmentName','assort.fk_prod'=>'idProduct','prod.ref'=>'ProductRef','prod.label'=>'ProductLabel','assort.fk_soc'=>'idCustomer','soc.nom'=>'CompagnyName');
		$this->export_entities_array[$r]=array('assort.rowid'=>"assortment",'assort.label'=>'assortment','assort.fk_prod'=>'product','prod.ref'=>'product','prod.label'=>'product','assort.fk_soc'=>'company','soc.nom'=>'company');
		$this->export_alias_array[$r]=array('assort.rowid'=>"IdAssortment",'assort.label'=>'AssortmentName','assort.fk_prod'=>'idProduct','prod.ref'=>'ProductRef','prod.label'=>'ProductLabel','assort.fk_soc'=>'idCustomer','soc.nom'=>'CompagnyName');
		$this->export_sql_start[$r] = "SELECT DISTINCT ";
		$this->export_sql_end[$r] = "	FROM ".MAIN_DB_PREFIX."assortment as assort"; 
		$this->export_sql_end[$r] .= "	INNER JOIN ".MAIN_DB_PREFIX."product as prod ON prod.rowid=assort.fk_prod";
		$this->export_sql_end[$r] .= "	INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid=assort.fk_soc";
		$this->export_sql_end[$r] .= "	ORDER BY soc.nom";
		$r++;
	}

	/**
	 *		\brief      Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		//$sql = array("DROP TABLE ".MAIN_DB_PREFIX."assortment;");
		$sql = array("DELETE FROM ".MAIN_DB_PREFIX."const WHERE name like 'ASSORTMENT_%';");
		
		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/assortment/sql/');
	}
}

?>
