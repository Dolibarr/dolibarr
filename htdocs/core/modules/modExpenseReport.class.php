<?php
/* Copyright (C) 2011 Dimitri Mouillard   <dmouillard@teclib.com>
 * Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 		\defgroup   expensereport	Module expensereport
 *      \brief      Module to manage expense report. Replace old module Deplacement.
 *      \file       htdocs/core/modules/modExpenseReport.class.php
 *      \ingroup    expensereport
 *      \brief      Description and activation file for the module ExpenseReport
 */
include_once DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php";


/**
 *	Description and activation class for module ExpenseReport
 */
class modExpenseReport extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		DoliDb	$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $user; // Required by some include code

		$this->db = $db;
		$this->numero = 770;

		$this->family = "hr";
		$this->module_position = '42';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Manage and claim expense reports (transportation, meal, ...)";
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'trip';

		// Data directories to create when module is enabled.
		$this->dirs = array("/expensereport/temp");
		$r = 0;

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('expensereport.php');

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		// $this->conflictwith = array("modDeplacement"); // Deactivate for access on old information
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 7); // Minimum version of Dolibarr required by module
		$this->langfiles = array("companies", "trips");

		// Constants
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r = 0;

		$this->const[$r][0] = "EXPENSEREPORT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "standard";
		$this->const[$r][3] = 'Name of manager to build PDF expense reports documents';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPENSEREPORT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_expensereport_jade";
		$this->const[$r][3] = 'Name of manager to generate expense report ref number';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_DELAY_EXPENSEREPORTS";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "15";
		$this->const[$r][3] = 'Tolerance delay (in days) before alert for expense reports to approve';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_DELAY_EXPENSEREPORTS_TO_PAY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "15";
		$this->const[$r][3] = 'Tolerance delay (in days) before alert for expense reports to pay';
		$this->const[$r][4] = 0;
		$r++;

		// Array to add new pages in new tabs
		$this->tabs[] = array();

		// Boxes
		$this->boxes = array(); // List of boxes
		$r = 0;

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$this->rights_class = 'expensereport';

		$this->rights[$r][0] = 771;
		$this->rights[$r][1] = 'Read expense reports (yours and your subordinates)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';
		$r++;

		$this->rights[$r][0] = 772;
		$this->rights[$r][1] = 'Create/modify expense reports';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
		$r++;

		$this->rights[$r][0] = 773;
		$this->rights[$r][1] = 'Delete expense reports';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		$r++;

		$this->rights[$r][0] = 775;
		$this->rights[$r][1] = 'Approve expense reports';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'approve';
		$r++;

		$this->rights[$r][0] = 776;
		$this->rights[$r][1] = 'Pay expense reports';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'to_paid';
		$r++;

		$this->rights[$r][0] = 777;
		$this->rights[$r][1] = 'Read expense reports of everybody';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'readall';
		$r++;

		$this->rights[$r][0] = 778;
		$this->rights[$r][1] = 'Create expense reports for everybody';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'writeall_advance';
		$r++;

		$this->rights[$r][0] = 779;
		$this->rights[$r][1] = 'Export expense reports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
		$r++;

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.

		// Exports
		$r = 0;

		$r++;
		$this->export_code[$r] = 'expensereport_'.$r;
		$this->export_label[$r] = 'ListTripsAndExpenses';
		$this->export_icon[$r] = 'trip';
		$this->export_permission[$r] = array(array("expensereport", "export"));
		$this->export_fields_array[$r] = array(
			'd.rowid'=>"TripId", 'd.ref'=>'Ref', 'd.date_debut'=>'DateStart', 'd.date_fin'=>'DateEnd', 'd.date_create'=>'DateCreation', 'd.date_approve'=>'DateApprove',
			'd.total_ht'=>"TotalHT", 'd.total_tva'=>'TotalVAT', 'd.total_ttc'=>'TotalTTC',
			'd.fk_statut'=>'Status', 'd.paid'=>'Paid',
			'd.note_private'=>'NotePrivate', 'd.note_public'=>'NotePublic', 'd.detail_cancel'=>'MOTIF_CANCEL', 'd.detail_refuse'=>'MOTIF_REFUS',
			'ed.rowid'=>'LineId', 'tf.code'=>'Type', 'ed.date'=>'Date', 'ed.tva_tx'=>'VATRate',
			'ed.total_ht'=>'TotalHT', 'ed.total_tva'=>'TotalVAT', 'ed.total_ttc'=>'TotalTTC', 'ed.comments'=>'Comment', 'p.rowid'=>'ProjectId', 'p.ref'=>'Ref',
			'u.lastname'=>'Lastname', 'u.firstname'=>'Firstname', 'u.login'=>"Login",
			'user_rib.iban_prefix' => 'IBAN', 'user_rib.bic' => 'BIC', 'user_rib.code_banque' => 'BankCode', 'user_rib.bank' => 'BankName', 'user_rib.proprio' => 'BankAccountOwner',
			'user_rib.owner_address' => 'BankAccountOwnerAddress'
		);
		$this->export_TypeFields_array[$r] = array(
			'd.rowid'=>"Numeric", 'd.ref'=>'Text', 'd.date_debut'=>'Date', 'd.date_fin'=>'Date', 'd.date_create'=>'Date', 'd.date_approve'=>'Date',
			'd.total_ht'=>"Numeric", 'd.total_tva'=>'Numeric', 'd.total_ttc'=>'Numeric',
			'd.fk_statut'=>"Numeric", 'd.paid'=>'Numeric',
			'd.note_private'=>'Text', 'd.note_public'=>'Text', 'd.detail_cancel'=>'Text', 'd.detail_refuse'=>'Text',
			'ed.rowid'=>'Numeric', 'tf.code'=>'Code', 'ed.date'=>'Date', 'ed.tva_tx'=>'Numeric',
			'ed.total_ht'=>'Numeric', 'ed.total_tva'=>'Numeric', 'ed.total_ttc'=>'Numeric', 'ed.comments'=>'Text', 'p.rowid'=>'Numeric', 'p.ref'=>'Text',
			'u.lastname'=>'Text', 'u.firstname'=>'Text', 'u.login'=>"Text",
			'user_rib.iban_prefix' => 'Text', 'user_rib.bic' => 'Text', 'user_rib.code_banque' => 'Text', 'user_rib.bank' => 'Text', 'user_rib.proprio' => 'Text',
			'user_rib.owner_address' => 'Text'
		);
		$this->export_entities_array[$r] = array(
			'ed.rowid'=>'expensereport_line', 'ed.date'=>'expensereport_line',
			'ed.tva_tx'=>'expensereport_line', 'ed.total_ht'=>'expensereport_line', 'ed.total_tva'=>'expensereport_line', 'ed.total_ttc'=>'expensereport_line',
			'ed.comments'=>'expensereport_line', 'tf.code'=>'expensereport_line', 'p.project_ref'=>'expensereport_line', 'p.rowid'=>'project', 'p.ref'=>'project',
			'u.lastname'=>'user', 'u.firstname'=>'user', 'u.login'=>'user',
			'user_rib.iban_prefix' => 'user', 'user_rib.bic' => 'user', 'user_rib.code_banque' => 'user', 'user_rib.bank' => 'user', 'user_rib.proprio' => 'user',
			'user_rib.owner_address' => 'user'

		);
		$this->export_alias_array[$r] = array('d.rowid'=>"idtrip", 'd.type'=>"type", 'd.note_private'=>'note_private', 'd.note_public'=>'note_public', 'u.lastname'=>'name', 'u.firstname'=>'firstname', 'u.login'=>'login');
		$this->export_dependencies_array[$r] = array('expensereport_line'=>'ed.rowid', 'type_fees'=>'tf.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$keyforselect = 'expensereport';
		$keyforelement = 'expensereport';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'user'; $keyforelement = 'user'; $keyforaliasextra = 'extrau';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'expensereport as d';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expensereport_extrafields as extra on d.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_rib as user_rib ON user_rib.fk_user = d.fk_user_author,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as extrau ON u.rowid = extrau.fk_object,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'expensereport_det as ed LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as tf ON ed.fk_c_type_fees = tf.id';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON ed.fk_projet = p.rowid';
		$this->export_sql_end[$r] .= ' WHERE ed.fk_expensereport = d.rowid AND d.fk_user_author = u.rowid';
		$this->export_sql_end[$r] .= ' AND d.entity IN ('.getEntity('expensereport').')';
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories.
	 *
	 *  @param      string  $options    Options
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Remove permissions and default values
		$this->remove($options);

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard' AND type='expensereport' AND entity = ".((int) $conf->entity),
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard','expensereport',".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
}
