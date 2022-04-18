<?php
/* Copyright (C) 2014-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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

/** \defgroup   printing     Module Receipt Printer
 *  \brief      Module for activation of printing icon to make receipt ticket
 */

/**
 *  \file       htdocs/core/modules/modReceiptPrinter.class.php
 *  \ingroup    printing
 *  \brief      Description and activation file for the module Receipt Printer
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';



/**
 *  Class to describe and activate module Receipt Printer
 */
class modReceiptPrinter extends DolibarrModules
{

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 67000;
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
		$this->module_position = '53';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "ReceiptPrinterDesc";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'printer';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array("receiptprinter.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 9, -2); // Minimum version of Dolibarr required by module
		$this->conflictwith = array();
		$this->langfiles = array("receiptprinter");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'receiptprinter';

		$r = 0;
		// $this->rights[$r][0]     Id permission (unique tous modules confondus)
		// $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
		// $this->rights[$r][2]     Non utilise
		// $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
		// $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
		// $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

		$r++;
		$this->rights[$r][0] = 67001;
		$this->rights[$r][1] = 'ReceiptPrinter';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;

		// This is to declare the Top Menu entry:
		//$this->menu[$r]=array(  'fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',               // Put 0 if this is a top menu
		//                        'type'=>'left',                 // This is a Top menu entry
		//                        'titre'=>'MenuDirectPrinting',
		//                        'mainmenu'=>'printing',
		//                        'url'=>'/printing/index.php',
		//                        'langs'=>'printing',            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//                        'position'=>300,
		//                        'enabled'=>'$conf->printing->enabled && preg_match(\'/^(admintools|all)/\',$leftmenu)',
		//                        'perms'=>'$user->rights->printing->read',    // Use 'perms'=>'1' if you want your menu with no permission rules
		//                        'target'=>'',
		//                        'user'=>0);                     // 0=Menu for internal users, 1=external users, 2=both

		$r++;
	}


	/**
	 *      Function called when module is enabled.
	 *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *      It also creates data directories
	 *
	 *      @param      string  $options    Options when enabling module ('', 'noboxes')
	 *      @return     int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Clean before activation
		$this->remove($options);

		$templateexample = '{dol_align_center}\r\n{dol_print_text}{dol_value_mysoc_name}\r\n{dol_print_text}{dol_value_mysoc_address}\r\n{dol_print_text}{dol_value_mysoc_zip}{dol_value_mysoc_town}\r\n{dol_line_feed}\r\n{dol_print_text}Facture {dol_value_object_ref}\r\n{dol_line_feed}\r\n{dol_align_left}\r\n{dol_print_object_lines}\r\n{dol_line_feed}\r\n{dol_print_object_tax}\r\n{dol_line_feed}\r\n{dol_print_object_total}\r\n{dol_line_feed}\r\n{dol_cut_paper_full}';
		$sql = array(
			"CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."printer_receipt (rowid integer AUTO_INCREMENT PRIMARY KEY, name varchar(128), fk_type integer, fk_profile integer, parameter varchar(128), entity integer) ENGINE=innodb;",
			"CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."printer_receipt_template (rowid integer AUTO_INCREMENT PRIMARY KEY, name varchar(128), template text, entity integer) ENGINE=innodb;",
			"DELETE FROM ".MAIN_DB_PREFIX."printer_receipt_template WHERE name = '".$this->db->escape($langs->trans('Example'))."';",
			"INSERT INTO ".MAIN_DB_PREFIX."printer_receipt_template (name,template,entity) VALUES ('".$this->db->escape($langs->trans('Example'))."', '".$this->db->escape($templateexample)."', 1);",
		);
		return $this->_init($sql, $options);
	}
}
