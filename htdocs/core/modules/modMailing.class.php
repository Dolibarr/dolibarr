<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\defgroup   mailing  Module emailing
 *	\brief      Module to manage EMailings
 *	\file       htdocs/core/modules/modMailing.class.php
 *	\ingroup    mailing
 *	\brief      Description and activation file for the module Mailing
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Mailing
 */
class modMailing extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 22;

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "interface";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '23';

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des EMailings";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'email';

		// Data directories to create when module is enabled
		$this->dirs = array("/mailing/temp");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->langfiles = array("mails");

		// Config pages
		$this->config_page_url = array("mailing.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "MAILING_CONTACT_DEFAULT_BULK_STATUS";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Default value for field "Refuse bulk email" when creating a contact';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'mailing';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 221; // id de la permission
		$this->rights[$r][1] = 'Consulter les mailings'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 222;
		$this->rights[$r][1] = 'Creer/modifier les mailings (sujet, destinataires...)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 223;
		$this->rights[$r][1] = 'Valider les mailings (permet leur envoi)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 229;
		$this->rights[$r][1] = 'Supprimer les mailings';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 237;
		$this->rights[$r][1] = 'View recipients and info';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mailing_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'recipient';

		$r++;
		$this->rights[$r][0] = 238;
		$this->rights[$r][1] = 'Manually send mailings';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mailing_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 239;
		$this->rights[$r][1] = 'Delete mailings after validation and/or sent';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mailing_advance'; // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'delete';

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
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
		$result = $this->_load_tables('/install/mysql/', 'mailing');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
