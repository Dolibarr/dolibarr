<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\defgroup   user  Module user management
 *	\brief      Module pour gerer les utilisateurs
 *	\file       htdocs/core/modules/modUser.class.php
 *	\ingroup    user
 *	\brief      Fichier de description et activation du module Utilisateur
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe and enable module User
 */
class modUser extends DolibarrModules
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
		$this->numero = 0;

		$this->family = "hr"; // Family for module (or "base" if core module)
		$this->module_position = '05';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des utilisateurs (requis)";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'group';

		// Data directories to create when module is enabled
		$this->dirs = array("/users/temp");

		// Config pages
		$this->config_page_url = array("user.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 4); // Minimum version of PHP required by module
		$this->langfiles = array("main", "users", "companies", "members", "salaries", "hrm");
		$this->always_enabled = true; // Can't be disabled

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array(
		    0=>array('file'=>'box_lastlogin.php', 'enabledbydefaulton'=>'Home'),
            1=>array('file'=>'box_birthdays.php', 'enabledbydefaulton'=>'Home')
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'user';
		$this->rights_admin_allowed = 1; // Admin is always granted of permission (even when module is disabled)
		$r = 0;

		$r++;
		$this->rights[$r][0] = 251;
		$this->rights[$r][1] = 'Consulter les autres utilisateurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 252;
		$this->rights[$r][1] = 'Consulter les permissions des autres utilisateurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user_advance';
		$this->rights[$r][5] = 'readperms';

		$r++;
		$this->rights[$r][0] = 253;
		$this->rights[$r][1] = 'Creer/modifier utilisateurs internes et externes';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 254;
		$this->rights[$r][1] = 'Creer/modifier utilisateurs externes seulement';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user_advance';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 255;
		$this->rights[$r][1] = 'Modifier le mot de passe des autres utilisateurs';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'password';

		$r++;
		$this->rights[$r][0] = 256;
		$this->rights[$r][1] = 'Supprimer ou desactiver les autres utilisateurs';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 341;
		$this->rights[$r][1] = 'Consulter ses propres permissions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'self_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'readperms';

		$r++;
		$this->rights[$r][0] = 342;
		$this->rights[$r][1] = 'Creer/modifier ses propres infos utilisateur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'self';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 343;
		$this->rights[$r][1] = 'Modifier son propre mot de passe';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'self';
		$this->rights[$r][5] = 'password';

		$r++;
		$this->rights[$r][0] = 344;
		$this->rights[$r][1] = 'Modifier ses propres permissions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'self_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'writeperms';

		$r++;
		$this->rights[$r][0] = 351;
		$this->rights[$r][1] = 'Consulter les groupes';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 352;
		$this->rights[$r][1] = 'Consulter les permissions des groupes';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'readperms';

		$r++;
		$this->rights[$r][0] = 353;
		$this->rights[$r][1] = 'Creer/modifier les groupes et leurs permissions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 354;
		$this->rights[$r][1] = 'Supprimer ou desactiver les groupes';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'delete';

		$r++;
		$this->rights[$r][0] = 358;
		$this->rights[$r][1] = 'Exporter les utilisateurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'export';


        // Menus
        $this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		$r = 0;

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'List of users and attributes';
		$this->export_permission[$r] = array(array("user", "user", "export"));
		$this->export_fields_array[$r] = array(
		    'u.rowid'=>"Id", 'u.login'=>"Login", 'u.lastname'=>"Lastname", 'u.firstname'=>"Firstname", 'u.employee'=>"Employee", 'u.job'=>"PostOrFunction", 'u.gender'=>"Gender",
		    'u.accountancy_code'=>"UserAccountancyCode",
		    'u.address'=>"Address", 'u.zip'=>"Zip", 'u.town'=>"Town",
		    'u.office_phone'=>'Phone', 'u.user_mobile'=>"Mobile", 'u.office_fax'=>'Fax',
		    'u.email'=>"Email", 'u.note'=>"Note", 'u.signature'=>'Signature',
		    'u.fk_user'=>'HierarchicalResponsible', 'u.thm'=>'THM', 'u.tjm'=>'TJM', 'u.weeklyhours'=>'WeeklyHours',
		    'u.dateemployment'=>'DateEmployment', 'u.salary'=>'Salary', 'u.color'=>'Color', 'u.api_key'=>'ApiKey',
		    'u.birth'=>'BirthdayDate',
		    'u.datec'=>"DateCreation", 'u.tms'=>"DateLastModification",
			'u.admin'=>"Administrator", 'u.statut'=>'Status', 'u.datelastlogin'=>'LastConnexion', 'u.datepreviouslogin'=>'PreviousConnexion',
			'u.fk_socpeople'=>"IdContact", 'u.fk_soc'=>"IdCompany", 'u.fk_member'=>"MemberId"
		);
		$this->export_TypeFields_array[$r] = array(
			'u.rowid'=>'Numeric', 'u.login'=>"Text", 'u.lastname'=>"Text", 'u.firstname'=>"Text", 'u.employee'=>'Boolean', 'u.job'=>'Text',
		    'u.accountancy_code'=>'Text',
		    'u.address'=>"Text", 'u.zip'=>"Text", 'u.town'=>"Text",
		    'u.office_phone'=>'Text', 'u.user_mobile'=>'Text', 'u.office_fax'=>'Text',
			'u.email'=>'Text', 'u.datec'=>"Date", 'u.tms'=>"Date", 'u.admin'=>"Boolean", 'u.statut'=>'Status', 'u.note'=>"Text", 'u.datelastlogin'=>'Date',
		    'u.fk_user'=>"List:user:login",
		    'u.birth'=>'Date',
		    'u.datepreviouslogin'=>'Date', 'u.fk_soc'=>"List:societe:nom:rowid", 'u.fk_member'=>"List:adherent:firstname"
		);
		$this->export_entities_array[$r] = array(
			'u.rowid'=>"user", 'u.login'=>"user", 'u.lastname'=>"user", 'u.firstname'=>"user", 'u.employee'=>'user', 'u.job'=>'user', 'u.gender'=>'user',
		    'u.accountancy_code'=>'user',
		    'u.address'=>"user", 'u.zip'=>"user", 'u.town'=>"user",
		    'u.office_phone'=>'user', 'u.user_mobile'=>'user', 'u.office_fax'=>'user',
		    'u.email'=>'user', 'u.note'=>"user", 'u.signature'=>'user',
		    'u.fk_user'=>'user', 'u.thm'=>'user', 'u.tjm'=>'user', 'u.weeklyhours'=>'user',
		    'u.dateemployment'=>'user', 'u.salary'=>'user', 'u.color'=>'user', 'u.api_key'=>'user',
		    'u.birth'=>'user',
		    'u.datec'=>"user", 'u.tms'=>"user",
		    'u.admin'=>"user", 'u.statut'=>'user', 'u.datelastlogin'=>'user', 'u.datepreviouslogin'=>'user',
		    'u.fk_socpeople'=>"contact", 'u.fk_soc'=>"company", 'u.fk_member'=>"member"
		);
		$keyforselect = 'user'; $keyforelement = 'user'; $keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		if (empty($conf->adherent->enabled))
        {
            unset($this->export_fields_array[$r]['u.fk_member']);
            unset($this->export_entities_array[$r]['u.fk_member']);
        }
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as extra ON u.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' WHERE u.entity IN ('.getEntity('user').')';

		// Imports
		$r = 0;

		// Import list of users attributes
		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'ImportDataset_user_1';
		$this->import_icon[$r] = 'user';
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('u'=>MAIN_DB_PREFIX.'user', 'extra'=>MAIN_DB_PREFIX.'user_extrafields'); // List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r] = array(
		    'u.login'=>"Login*", 'u.lastname'=>"Name*", 'u.firstname'=>"Firstname", 'u.employee'=>"Employee*", 'u.job'=>"PostOrFunction", 'u.gender'=>"Gender",
		    'u.accountancy_code'=>"UserAccountancyCode",
			'u.pass_crypted'=>"Password", 'u.admin'=>"Administrator", 'u.fk_soc'=>"Company*", 'u.address'=>"Address", 'u.zip'=>"Zip", 'u.town'=>"Town",
			'u.fk_state'=>"StateId", 'u.fk_country'=>"CountryCode",
		    'u.office_phone'=>"Phone", 'u.user_mobile'=>"Mobile", 'u.office_fax'=>"Fax",
		    'u.email'=>"Email", 'u.note'=>"Note", 'u.signature'=>'Signature',
		    'u.fk_user'=>'HierarchicalResponsible', 'u.thm'=>'THM', 'u.tjm'=>'TJM', 'u.weeklyhours'=>'WeeklyHours',
			'u.dateemployment'=>'DateEmployment', 'u.salary'=>'Salary', 'u.color'=>'Color', 'u.api_key'=>'ApiKey',
		    'u.birth'=>'BirthdayDate',
		    'u.datec'=>"DateCreation",
		    'u.statut'=>'Status'
		);
		// Add extra fields
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'user' AND entity IN (0,".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj = $this->db->fetch_object($resql))
		    {
		        $fieldname = 'extra.'.$obj->name;
		        $fieldlabel = ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
		    }
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r] = array('u.fk_user_creat'=>'user->id', 'extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'user'); // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r] = array(
			'u.fk_state'=>array('rule'=>'fetchidfromcodeid', 'classfile'=>'/core/class/cstate.class.php', 'class'=>'Cstate', 'method'=>'fetch', 'dict'=>'DictionaryState'),
		    'u.fk_country'=>array('rule'=>'fetchidfromcodeid', 'classfile'=>'/core/class/ccountry.class.php', 'class'=>'Ccountry', 'method'=>'fetch', 'dict'=>'DictionaryCountry'),
		    'u.salary'=>array('rule'=>'numeric')
		);
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
		$this->import_regex_array[$r] = array(
			'u.employee'=>'^[0|1]',
			'u.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$',
            'u.dateemployment'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
            'u.birth'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$'
		);
		$this->import_examplevalues_array[$r] = array(
			'u.lastname'=>"Doe", 'u.firstname'=>'John', 'u.login'=>'jdoe', 'u.employee'=>'0 or 1', 'u.job'=>'CTO', 'u.gender'=>'0 or 1',
			'u.pass_crypted'=>'Encrypted password',
			'u.fk_soc'=>'0 (internal user) or company name (external user)', 'u.datec'=>dol_print_date(dol_now(), '%Y-%m-%d'), 'u.address'=>"61 jump street",
			'u.zip'=>"123456", 'u.town'=>"Big town", 'u.fk_country'=>'US, FR, DE...', 'u.office_phone'=>"0101010101", 'u.office_fax'=>"0101010102",
			'u.email'=>"test@mycompany.com", 'u.salary'=>"10000", 'u.note'=>"This is an example of note for record", 'u.datec'=>"2015-01-01 or 2015-01-01 12:30:00",
		    'u.statut'=>"0 (closed) or 1 (active)",
		);
		$this->import_updatekeys_array[$r] = array('u.lastname'=>'Lastname', 'u.firstname'=>'Firstname', 'u.login'=>'Login');
	}


    /**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    public function init($options = '')
    {
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
    }
}
