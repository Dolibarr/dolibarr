<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Juanjo Menent        <jmenent@2byte.es>
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
 *	\defgroup   barcode         Module barcode
 *	\brief      Module pour gerer les codes barres
 *	\file       htdocs/core/modules/modBarcode.class.php
 *	\ingroup    barcode,produit
 *	\brief      Fichier de description et activation du module Barcode
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe Barcode
 */
class modBarcode extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 55;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des codes barres";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'barcode';

		// Data directories to create when module is enabled
		$this->dirs = array("/barcode/temp");

		// Dependencies
		$this->depends = array(); // May be used for product or service or third party module
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("barcode.php");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//							  1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		$this->const = array();
		//$this->const[0] = array('BARCODE_LABEL_LEFT_TEXT','chaine','%BARCODE%','Print barcode on left side of label',1);
		//$this->const[1] = array('BARCODE_LABEL_RIGHT_TEXT','chaine','%LOGO%','Print Company logo on right side',1);
		//$this->const[2] = array('BARCODE_LABEL_HEADER_TEXT','chaine','My header','Print header text on label',1);
		//$this->const[3] = array('BARCODE_LABEL_FOOTER_TEXT','chaine','My footer','Print footer text on label',1);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'barcode';

		$this->rights[1][0] = 300; // id de la permission
		$this->rights[1][1] = 'Read barcodes'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire_advance';

		$this->rights[2][0] = 301; // id de la permission
		$this->rights[2][1] = 'Create/modify barcodes'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'creer_advance';

		// Main menu entries
		$r = 0;
		$this->menu[$r] = array('fk_menu'=>'fk_mainmenu=tools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'mainmenu'=>'tools',
								'leftmenu'=>'barcodeprint',
								'type'=>'left', // This is a Left menu entry
								'titre'=>'BarCodePrintsheet',
								'url'=>'/barcode/printsheet.php?mainmenu=tools&leftmenu=barcodeprint',
								'langs'=>'products', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>200,
								'enabled'=>'$conf->barcode->enabled', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'($conf->global->MAIN_USE_ADVANCED_PERMS && $user->rights->barcode->lire_advance) || (! $conf->global->MAIN_USE_ADVANCED_PERMS)', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2); // 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r] = array('fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left', // This is a Left menu entry
								'titre'=>'MassBarcodeInit',
								'url'=>'/barcode/codeinit.php?mainmenu=home&leftmenu=admintools',
								'langs'=>'products', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>300,
								'enabled'=>'$conf->barcode->enabled && preg_match(\'/^(admintools|all)/\',$leftmenu)', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'($conf->global->MAIN_USE_ADVANCED_PERMS && $user->rights->barcode->creer_advance) || (! $conf->global->MAIN_USE_ADVANCED_PERMS)', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>0); // 0=Menu for internal users, 1=external users, 2=both
		$r++;
	}


	/**
	 *      Function called when module is enabled.
	 *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *      It also creates data directories.
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$sql = array(
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('EAN8', 'EAN8', 0, '1234567', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('EAN13', 'EAN13', 0, '123456789012', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('UPC', 'UPC', 0, '123456789012', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('ISBN', 'ISBN', 0, '123456789', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('C39', 'Code 39', 0, '1234567890', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('C128', 'Code 128', 0, 'ABCD1234567890', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('DATAMATRIX', 'Datamatrix', 0, '1234567xyz', __ENTITY__)", 'ignoreerror'=>1),
				array('sql'=>"INSERT INTO ".MAIN_DB_PREFIX."c_barcode_type (code, libelle, coder, example, entity) VALUES ('QRCODE', 'Qr Code', 0, 'www.dolibarr.org', __ENTITY__)", 'ignoreerror'=>1)
		);

		return $this->_init($sql, $options);
	}
}
