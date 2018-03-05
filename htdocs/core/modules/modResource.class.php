<?php
/* Copyright (C) 2013-2014 Jean-François Ferry <jfefe@aternatik.fr>
 * Copyright (C) 2015      Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Module to manage resources into Dolibarr ERP/CRM
 */

/**
 * 	\defgroup	resource	Module resource
 * 	\brief		Resource module descriptor.
 * 	\file		core/modules/modResource.class.php
 * 	\ingroup	resource
 * 	\brief		Description and activation file for module Resource
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Resource
 */
class modResource extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use a free id here
		// (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 63000;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'resource';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "projects";
		$this->module_position = 20;
		// Module label (no space allowed)
		// used if translation string 'ModuleXXXName' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description
		// used if translation string 'ModuleXXXDesc' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->description = "Manage resources (printers, cars, room, ...) you can then share into events";
		// Possible values for version are: 'development', 'experimental' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled
		// (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png
		// use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png
		// use this->picto='pictovalue@module'
		$this->picto = 'resource'; // mypicto@resource
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /resource/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /resource/core/modules/barcode)
		// for specific css file (eg: /resource/css/resource.css.php)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory
			//'triggers' => 1,
			// Set this to 1 if module has its own login method directory
			//'login' => 0,
			// Set this to 1 if module has its own substitution function file
			//'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory
			//'menus' => 0,
			// Set this to 1 if module has its own barcode directory
			//'barcode' => 0,
			// Set this to 1 if module has its own models directory
			//'models' => 0,
			// Set this to relative path of css if module has its own css file
			//'css' => '/resource/css/resource.css.php',
			// Set here all hooks context managed by module
			// 'hooks' => array('actioncard','actioncommdao','resource_card','element_resource')
			// Set here all workflow context managed by module
			//'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/resource/temp");
		//$this->dirs = array("/resource");

		// Config pages. Put here list of php pages
		// stored into resource/admin directory, used to setup module.
		$this->config_page_url = array("resource.php");

		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->depends = array();
		// List of modules id to disable if this one is disabled
		$this->requiredby = array('modPlace');
		// Minimum version of PHP required by module
		$this->phpmin = array(5, 3);

		$this->langfiles = array("resource"); // langfiles@resource
		// Constants
		// List of particular constants to add when module is enabled
		// (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example:
		$this->const = array();

		// Array to add new pages in new tabs
		// Example:
		$this->tabs = array(
			//	// To add a new tab identified by code tabname1
			//	'objecttype:+tabname1:Title1:langfile@resource:$user->rights->resource->read:/resource/mynewtab1.php?id=__ID__',
			//	// To add another new tab identified by code tabname2
			//	'objecttype:+tabname2:Title2:langfile@resource:$user->rights->othermodule->read:/resource/mynewtab2.php?id=__ID__',
			//	// To remove an existing tab identified by code tabname
			//	'objecttype:-tabname'
		);
		// where objecttype can be
		// 'thirdparty'			to add a tab in third party view
		// 'intervention'		to add a tab in intervention view
		// 'order_supplier'		to add a tab in supplier order view
		// 'invoice_supplier'	to add a tab in supplier invoice view
		// 'invoice'			to add a tab in customer invoice view
		// 'order'				to add a tab in customer order view
		// 'product'			to add a tab in product view
		// 'stock'				to add a tab in stock view
		// 'propal'				to add a tab in propal view
		// 'member'				to add a tab in fundation member view
		// 'contract'			to add a tab in contract view
		// 'user'				to add a tab in user view
		// 'group'				to add a tab in group view
		// 'contact'			to add a tab in contact view
		// 'categories_x'		to add a tab in category view
		// (reresource 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		$r = 0;
		// Example:

		//$this->boxes[$r][1] = "MyBox@resource";
		//$r ++;
		/*
		  $this->boxes[$r][1] = "myboxb.php";
		  $r++;
		 */

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 63001;
		$this->rights[$r][1] = 'Read resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 63002;
		$this->rights[$r][1] = 'Create/Modify resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = 63003;
		$this->rights[$r][1] = 'Delete resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		$this->rights[$r][0] = 63004;
		$this->rights[$r][1] = 'Link resources to agenda events';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'link';
		$r++;


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		// Menus declaration
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=tools',
			'type'=>'left',
			'titre'=> 'MenuResourceIndex',
			'mainmenu'=>'tools',
			'leftmenu'=> 'resource',
			'url'=> '/resource/list.php',
			'langs'=> 'resource',
			'position'=> 100,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'user'=> 0
		);
		$r++;

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=resource', //On utilise les ancres définis dans le menu parent déclaré au dessus
			'type'=> 'left', // Toujours un menu gauche
			'titre'=> 'MenuResourceAdd',
			'mainmenu'=> 'tools',
			'leftmenu'=> 'resource_add',
			'url'=> '/resource/card.php?action=create',
			'langs'=> 'resource',
			'position'=> 101,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'target'=> '',
			'user'=> 0
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=resource', //On utilise les ancres définis dans le menu parent déclaré au dessus
			'type'=> 'left', // Toujours un menu gauche
			'titre'=> 'List',
			'mainmenu'=> 'tools',
			'leftmenu'=> 'resource_list',
			'url'=> '/resource/list.php',
			'langs'=> 'resource',
			'position'=> 102,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'target'=> '',
			'user'=> 0
		);


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="ResourceSingular";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("resource","read"));
		$this->export_fields_array[$r]=array('r.rowid'=>'IdResource','r.ref'=>'ResourceFormLabel_ref','c.code'=>'ResourceTypeCode','c.label'=>'ResourceType','r.description'=>'ResourceFormLabel_description','r.note_private'=>"NotePrivate",'r.note_public'=>"NotePublic",'r.asset_number'=>'AssetNumber','r.datec'=>"DateCreation",'r.tms'=>"DateLastModification");
		$this->export_TypeFields_array[$r]=array('r.rowid'=>'List:resource:ref','r.ref'=>'Text','r.asset_number'=>'Text','r.description'=>'Text','c.code'=>'Text','c.label'=>'List:c_type_resource:label','r.datec'=>'Date','r.tms'=>'Date','r.note_private'=>'Text','r.note_public'=>'Text');
		$this->export_entities_array[$r]=array('r.rowid'=>'resource','r.ref'=>'resource','c.code'=>'resource','c.label'=>'resource','r.description'=>'resource','r.note_private'=>"resource",'r.resource'=>"resource",'r.asset_number'=>'resource','r.datec'=>"resource",'r.tms'=>"resource");
		$keyforselect='resource'; $keyforelement='resource'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_dependencies_array[$r]=array('resource'=>array('r.rowid')); // We must keep this until the aggregate_array is used. To add unique key if we ask a field of a child to avoid the DISTINCT to discard them.
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'resource as r ';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_resource as c ON c.rowid=r.fk_code_type_resource';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'resource_extrafields as extra ON extra.fk_object = c.rowid';
		$this->export_sql_end[$r] .=' AND r.entity IN ('.getEntity('resource').')';


		// Imports
		//--------
		$r=0;

		// Import list of third parties and attributes
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ImportDataset_resource_1';
		$this->import_icon[$r]='resource';
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('r'=>MAIN_DB_PREFIX.'resource','extra'=>MAIN_DB_PREFIX.'resource_extrafields');	// List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r]=array('r.ref'=>"ResourceFormLabel_ref*",'r.fk_code_type_resource'=>'ResourceTypeCode','r.description'=>'ResourceFormLabel_description','r.note_private'=>"NotePrivate",'r.note_public'=>"NotePublic",'r.asset_number'=>'AssetNumber','r.datec'=>'DateCreation');
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'resource' AND entity = ".$conf->entity;
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
		$this->import_fieldshidden_array[$r]=array('r.fk_user_author'=>'user->id','extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'resource');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r]=array(
				'r.fk_code_type_resource'=>array('rule'=>'fetchidfromcodeorlabel','classfile'=>'/core/class/ctyperesource.class.php','class'=>'Ctyperesource','method'=>'fetch','dict'=>'DictionaryResourceType'),
		);
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
		$this->import_regex_array[$r]=array('s.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$');
		$this->import_examplevalues_array[$r]=array('r.ref'=>"REF1",'r.fk_code_type_resource'=>"Code from dictionary resource type",'r.datec'=>"2017-01-01 or 2017-01-01 12:30:00");
		$this->import_updatekeys_array[$r]=array('r.rf'=>'ResourceFormLabel_ref');

	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /resource/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/resource/sql/');
	}
}
