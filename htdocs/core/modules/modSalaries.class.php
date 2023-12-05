<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014-2019  Alexandre Spangaro	 <aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \defgroup   salaries		Module salaries
 *  \brief      Module to include salaries management
 *  \file       htdocs/core/modules/modSalaries.class.php
 *  \ingroup    salaries
 *  \brief      Description and activation file for the module salaries
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to manage salaries module
 */
class modSalaries extends DolibarrModules
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
		$this->numero = 510; // Perms from 501..519

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Payment of salaries";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'payment';

		// Data directories to create when module is enabled
		$this->dirs = array("/salaries/temp");

		// Config pages
		$this->config_page_url = array('salaries.php@salaries');

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->langfiles = array("salaries", "bills");

		// Constants
		$this->const = array();
		$this->const[0] = array(
				"SALARIES_ACCOUNTING_ACCOUNT_PAYMENT",
				"chaine",
				"421"
		);
		$this->const[1] = array(
				"SALARIES_ACCOUNTING_ACCOUNT_CHARGE",
				"chaine",
				"641"
		);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'salaries';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 511;
		$this->rights[$r][1] = 'Read employee salaries and payments (yours and your subordinates)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 512;
		$this->rights[$r][1] = 'Create/modify payments of empoyee salaries';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 514;
		$this->rights[$r][1] = 'Delete payments of employee salary';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 517;
		$this->rights[$r][1] = 'Read salaries and payments of all employees';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'readall';

		$r++;
		$this->rights[$r][0] = 519;
		$this->rights[$r][1] = 'Export payments of employee salaries';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
		$this->rights[$r][5] = '';



		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r = 0;

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'SalariesAndPayments';
		$this->export_permission[$r] = array(array("salaries", "export"));
		$this->export_fields_array[$r] = array('u.firstname'=>"Firstname", 'u.lastname'=>"Lastname", 'u.login'=>"Login", 'u.salary'=>'CurrentSalary', 'p.datep'=>'DatePayment', 'p.datesp'=>'DateStartPeriod', 'p.dateep'=>'DateEndPeriod', 'p.amount'=>'AmountPayment', 'p.num_payment'=>'Numero', 'p.label'=>'Label', 'p.note'=>'Note');
		$this->export_TypeFields_array[$r] = array('u.firstname'=>"Text", 'u.lastname'=>"Text", 'u.login'=>'Text', 'u.salary'=>"Numeric", 'p.datep'=>'Date', 'p.datesp'=>'Date', 'p.dateep'=>'Date', 'p.amount'=>'Numeric', 'p.num_payment'=>'Numeric', 'p.label'=>'Text');
		$this->export_entities_array[$r] = array('u.firstname'=>'user', 'u.lastname'=>'user', 'u.login'=>'user', 'u.salary'=>'user', 'p.datep'=>'payment', 'p.datesp'=>'payment', 'p.dateep'=>'payment', 'p.amount'=>'payment', 'p.label'=>'payment', 'p.note'=>'payment', 'p.num_payment'=>'payment');

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'payment_salary as p ON p.fk_user = u.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_typepayment = cp.id';
		$this->export_sql_end[$r] .= ' AND u.entity IN ('.getEntity('user').')';
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Clean before activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
