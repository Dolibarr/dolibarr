<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 *	\defgroup   mailmanspip      Module mailmanspip
 *	\brief      Module to manage mailman and spip
 *	\file       htdocs/core/modules/modMailmanSpip.class.php
 *	\ingroup    mailmanspip
 *	\brief      Description and activation file for the module mailmanspip
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Click to Dial
 */
class modMailmanSpip extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 105;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '70';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Mailman or Spip interface for member module";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr_deprecated';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array('modAdherent'); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module

		// Config pages
		$this->config_page_url = array('mailman.php');

		// Constants
		$this->const = array();
		$this->const[1] = array("ADHERENT_MAILMAN_UNSUB_URL", "chaine", "http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&user=%EMAIL%", "Url de désinscription aux listes mailman");
		$this->const[2] = array("ADHERENT_MAILMAN_URL", "chaine", "http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&send_welcome_msg_to_this_batch=1&subscribees=%EMAIL%", "Url pour les inscriptions mailman");
		$this->const[3] = array("ADHERENT_MAILMAN_LISTS", "chaine", "", "Mailing-list to subscribe new members to");

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'mailmanspip';

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
	}
}
