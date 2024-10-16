<?php
/* Copyright (C) 2021		Florian Henry			<florian.henry@scopen.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * 	\defgroup   eventorganization     Module EventOrganization
 *  \brief      EventOrganization module descriptor.
 *
 *  \file       htdocs/core/modules/modEventOrganization.class.php
 *  \ingroup    eventorganization
 *  \brief      Description and activation file for the EventOrganization
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

/**
 *  Description and activation class for module EventOrganization
 *  This module is base on this specification :
 *  https://wiki.dolibarr.org/index.php/Draft:Module_Event_Organization
 */
class modEventOrganization extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->numero = 2450;

		$this->rights_class = 'eventorganization';

		$this->family = "projects";

		$this->module_position = '15';

		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = "EventOrganizationDescription";
		$this->descriptionlong = "EventOrganizationDescriptionLong";

		$this->version = 'dolibarr';


		// Key used in llx_const table to save module status enabled/disabled (where EVENTORGANIZATION is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto = 'conferenceorbooth';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/eventorganization/css/eventorganization.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/eventorganization/js/eventorganization.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/eventorganization/temp","/eventorganization/subdir");
		$this->dirs = array("/eventorganization/temp");

		// Config pages. Put here list of php page, stored into eventorganization/admin directory, to use to setup module.
		$this->config_page_url = array("eventorganization.php");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
		$this->depends = array('modProjet', 'modCategorie', 'modAgenda');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("eventorganization");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(13, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'EventOrganizationWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('EVENTORGANIZATION_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('EVENTORGANIZATION_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(1 => array('EVENTORGANIZATION_TASK_LABEL', 'chaine', '', '', 0));



		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->eventorganization) || !isset($conf->eventorganization->enabled)) {
			$conf->eventorganization = new stdClass();
			$conf->eventorganization->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@eventorganization:$user->rights->eventorganization->read:/eventorganization/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@eventorganization:$user->rights->othermodule->read:/eventorganization/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in foundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in sales order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		// Add here list of php file(s) stored in eventorganization/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'eventorganizationwidget1.php@eventorganization',
			//      'note' => 'Widget provided by EventOrganization',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
		);


		// Permissions provided by this module
		$this->rights = array();
		$r = 1;

		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of EventOrganization'; // Permission label
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of EventOrganization'; // Permission label
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of EventOrganization'; // Permission label
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->eventorganization->level1)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU CONFERENCEORBOOTH*/
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'EventOrganizationMenuLeft',
			'prefix' => img_picto('', 'eventorganization', 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'project',
			'leftmenu' => 'eventorganization',
			'url' => '',
			'langs' => 'eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "read")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project,fk_leftmenu=eventorganization',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'New',
			'url' => '/projet/card.php?leftmenu=projects&action=create&usage_organize_event=1&usage_opportunity=0',
			'langs' => 'eventorganization@eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "write")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project,fk_leftmenu=eventorganization',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'List',
			'url' => '/projet/list.php?search_usage_event_organization=1&search_status=99&mainmenu=project&contextpage=organizedevents',
			'langs' => 'eventorganization@eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "write")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'ConferenceOrBooth',
			'prefix' => img_picto('', 'conferenceorbooth', 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'project',
			'leftmenu' => 'eventorganizationconforbooth',
			'url' => '',
			'langs' => 'eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "read")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project,fk_leftmenu=eventorganizationconforbooth',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'New',
			'url' => '/eventorganization/conferenceorbooth_card.php?leftmenu=projects&action=create',
			'langs' => 'eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "write")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=project,fk_leftmenu=eventorganizationconforbooth',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type' => 'left',			                // This is a Left menu entry
			'titre' => 'List',
			'url' => '/eventorganization/conferenceorbooth_list.php?mainmenu=project',
			'langs' => 'eventorganization',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("eventorganization")',  // Define condition to show or hide menu entry. Use '$conf->eventorganization->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("eventorganization", "read")',			                // Use 'perms'=>'$user->rights->eventorganization->level1->level2' if you want your menu with a permission rules
			'target' => '',
			'user' => 2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER LEFTMENU CONFERENCEORBOOTH */

		// Exports profiles provided by this module
		$r = 1;

		/* BEGIN MODULEBUILDER EXPORT CONFERENCEORBOOTHATTENDEES */
		$langs->load("eventorganization");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ListOfAttendeesOfEvent';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = $this->picto;
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'ConferenceOrBoothAttendee';
		$keyforclassfile = '/eventorganization/class/conferenceorboothattendee.class.php';
		$keyforelement = 'conferenceorboothattendee';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$this->export_entities_array[$r]['t.fk_invoice'] = 'invoice';
		unset($this->export_fields_array[$r]['t.fk_project']);	// Remove field so we can add it at end just after
		unset($this->export_fields_array[$r]['t.fk_soc']);	// Remove field so we can add it at end just after
		$this->export_fields_array[$r]['t.fk_invoice'] = 'InvoiceId';
		$this->export_fields_array[$r]['t.fk_project'] = 'ProjectId';
		$this->export_fields_array[$r]['p.ref'] = 'ProjectRef';
		$this->export_fields_array[$r]['t.fk_soc'] = 'IdThirdParty';
		$this->export_entities_array[$r]['t.fk_project'] = 'project';
		$this->export_entities_array[$r]['p.ref'] = 'project';
		$this->export_entities_array[$r]['t.fk_soc'] = 'company';
		$this->export_TypeFields_array[$r]['t.fk_project'] = 'Numeric';
		$this->export_TypeFields_array[$r]['t.fk_invoice'] = 'Numeric';
		$this->export_TypeFields_array[$r]['p.ref'] = 'Text';
		$this->export_TypeFields_array[$r]['t.fk_soc'] = 'Numeric';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		$keyforselect = 'conferenceorboothattendee';
		$keyforaliasextra = 'extra';
		$keyforelement = 'conferenceorboothattendee';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('aaaline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'eventorganization_conferenceorboothattendee as t, '.MAIN_DB_PREFIX.'projet as p';
		$this->export_sql_end[$r] .= ' WHERE t.fk_project = p.rowid';
		$this->export_sql_end[$r] .= ' AND p.entity IN ('.getEntity('conferenceorboothattendee').')';
		$r++;
		/* END MODULEBUILDER EXPORT CONFERENCEORBOOTHATTENDEES */

		/* BEGIN MODULEBUILDER EXPORT CONFERENCEORBOOTH */
		$langs->load("eventorganization");
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ListOfConfOrBoothOfEvent';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'conferenceorbooth';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'ConferenceOrBooth';
		$keyforclassfile = '/eventorganization/class/conferenceorbooth.class.php';
		$keyforelement = 'conferenceorbooth';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		unset($this->export_fields_array[$r]['t.fk_action']);	// Remove field so we can add it at end just after
		unset($this->export_fields_array[$r]['t.fk_project']);	// Remove field so we can add it at end just after
		unset($this->export_fields_array[$r]['t.fk_soc']);	// Remove field so we can add it at end just after
		$this->export_fields_array[$r]['t.fk_action'] = 'ConferenceOrBoothFormatID';
		$this->export_fields_array[$r]['ca.code'] = 'ConferenceOrBoothFormatCode';
		$this->export_fields_array[$r]['ca.libelle'] = 'ConferenceOrBoothFormatLabel';
		$this->export_fields_array[$r]['t.fk_project'] = 'ProjectId';
		$this->export_fields_array[$r]['p.ref'] = 'ProjectRef';
		$this->export_fields_array[$r]['t.fk_soc'] = 'IdThirdParty';
		$this->export_fields_array[$r]['s.nom'] = 'ThirdPartyName';
		$this->export_entities_array[$r]['ca.code'] = 'conferenceorbooth';
		$this->export_entities_array[$r]['ca.libelle'] = 'conferenceorbooth';
		$this->export_entities_array[$r]['t.fk_project'] = 'project';
		$this->export_entities_array[$r]['p.ref'] = 'project';
		$this->export_entities_array[$r]['t.fk_soc'] = 'company';
		$this->export_entities_array[$r]['s.nom'] = 'company';
		$this->export_TypeFields_array[$r]['ca.code'] = 'Text';
		$this->export_TypeFields_array[$r]['ca.libelle'] = 'Text';
		$this->export_TypeFields_array[$r]['t.fk_project'] = 'Numeric';
		$this->export_TypeFields_array[$r]['p.ref'] = 'Text';
		$this->export_TypeFields_array[$r]['t.fk_soc'] = 'Numeric';
		$this->export_TypeFields_array[$r]['s.nom'] = 'Text';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		$keyforselect = 'conferenceorbooth';
		$keyforaliasextra = 'extra';
		$keyforelement = 'conferenceorbooth';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('aaaline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'actioncomm as t';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON t.fk_soc = s.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'projet as p,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'c_actioncomm as ca';
		$this->export_sql_end[$r] .= ' WHERE t.fk_project = p.rowid';
		$this->export_sql_end[$r] .= ' AND ca.id = t.fk_action';
		$this->export_sql_end[$r] .= " AND t.code LIKE 'AC_EO_%'";
		$this->export_sql_end[$r] .= ' AND p.usage_organize_event = 1';
		$this->export_sql_end[$r] .= ' AND p.entity IN ('.getEntity('conferenceorboothattendee').')';
		$r++;
		/* END MODULEBUILDER EXPORT CONFERENCEORBOOTH */


		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT CONFERENCEORBOOTH */
		/* END MODULEBUILDER IMPORT CONFERENCEORBOOTH */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user;

		/*$result = run_sql(DOL_DOCUMENT_ROOT.'/install/mysql/data/llx_c_email_templates.sql', 1, '', 1);
		if ($result <= 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}
		TODO Instead use the array merge of the sql found into llx_c_email_templates for this module
		*/

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = 'eventorganization';
		$myTmpObjects = array();
		$myTmpObjects['ConferenceOrBooth'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/eventorganization/template_conferenceorbooths.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/eventorganization';
				$dest = $dirodt.'/template_conferenceorbooths.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, '0', 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."','".$this->db->escape(strtolower($myTmpObjectKey))."',".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		$init = $this->_init($sql, $options);


		// Insert some vars
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($this->db);

		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		if (!is_object($user)) {
			$user = new User($this->db); // To avoid error during migration
		}

		$template = $formmail->getEMailTemplate($this->db, 'conferenceorbooth', $user, $langs, 0, 1, '(EventOrganizationEmailAskConf)');
		if ($template->id > 0) {
			dolibarr_set_const($this->db, 'EVENTORGANIZATION_TEMPLATE_EMAIL_ASK_CONF', $template->id, 'chaine', 0, '', $conf->entity);
		}
		$template = $formmail->getEMailTemplate($this->db, 'conferenceorbooth', $user, $langs, 0, 1, '(EventOrganizationEmailAskBooth)');
		if ($template->id > 0) {
			dolibarr_set_const($this->db, 'EVENTORGANIZATION_TEMPLATE_EMAIL_ASK_BOOTH', $template->id, 'chaine', 0, '', $conf->entity);
		}
		$template = $formmail->getEMailTemplate($this->db, 'conferenceorbooth', $user, $langs, 0, 1, '(EventOrganizationEmailBoothPayment)');
		if ($template->id > 0) {
			dolibarr_set_const($this->db, 'EVENTORGANIZATION_TEMPLATE_EMAIL_AFT_SUBS_BOOTH', $template->id, 'chaine', 0, '', $conf->entity);
		}
		$template = $formmail->getEMailTemplate($this->db, 'conferenceorbooth', $user, $langs, 0, 1, '(EventOrganizationEmailRegistrationPayment)');
		if ($template->id > 0) {
			dolibarr_set_const($this->db, 'EVENTORGANIZATION_TEMPLATE_EMAIL_AFT_SUBS_EVENT', $template->id, 'chaine', 0, '', $conf->entity);
		}

		return $init;
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
