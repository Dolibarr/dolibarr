<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \defgroup   ldap     Module ldap
 *  \brief		Module to manage LDAP interfaces with contacts or users
 *	\file       htdocs/core/modules/modLdap.class.php
 *	\ingroup    ldap
 *	\brief		Description and activation file for the module LDAP
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Ldap
 */
class modLdap extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 200;

		$this->family = "interface";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '30';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Synchronisation Ldap";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/images directory, use this->picto=DOL_URL_ROOT.'/module/images/file.png'
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/ldap/temp");

		// Config pages
		$this->config_page_url = array("ldap.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module

		// Constants
		$this->const = array(
		0=>array('LDAP_SERVER_TYPE', 'chaine', 'openldap', '', 0),
		1=>array('LDAP_SERVER_PROTOCOLVERSION', 'chaine', '3', '', 0),
		2=>array('LDAP_SERVER_HOST', 'chaine', 'localhost', '', 0),
		3=>array('LDAP_USER_DN', 'chaine', 'ou=users,dc=example,dc=com', '', 0),
		4=>array('LDAP_GROUP_DN', 'chaine', 'ou=groups,dc=example,dc=com', '', 0),
		5=>array('LDAP_FILTER_CONNECTION', 'chaine', '&(objectClass=inetOrgPerson)', '', 0),
		6=>array('LDAP_FIELD_LOGIN', 'chaine', 'uid', '', 0),
		7=>array('LDAP_FIELD_FULLNAME', 'chaine', 'cn', '', 0),
		8=>array('LDAP_FIELD_NAME', 'chaine', 'sn', '', 0),
		9=>array('LDAP_FIELD_FIRSTNAME', 'chaine', 'givenname', '', 0),
		10=>array('LDAP_FIELD_MAIL', 'chaine', 'mail', '', 0),
		11=>array('LDAP_FIELD_PHONE', 'chaine', 'telephonenumber', '', 0),
		12=>array('LDAP_FIELD_FAX', 'chaine', 'facsimiletelephonenumber', '', 0),
		13=>array('LDAP_FIELD_MOBILE', 'chaine', 'mobile', '', 0),
		14=>array('LDAP_GROUP_FILTER', 'chaine', '&(objectClass=groupOfNames)', '', 0),
		15=>array('LDAP_USERACCOUNTCONTROL', 'int', 512, '', 0),
		);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'ldap';
	}
}
