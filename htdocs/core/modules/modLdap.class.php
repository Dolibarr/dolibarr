<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \defgroup   ldap     Module ldap
 *  \brief		Module to manage LDAP interfaces with contacts or users
 *	\file       htdocs/core/modules/modLdap.class.php
 *	\ingroup    ldap
 *	\brief		File to describe and activate Ldap module
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 200;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Synchronisation Ldap";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 1;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/images directory, use this->picto=DOL_URL_ROOT.'/module/images/file.png'
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/ldap/temp");

		// Config pages
		$this->config_page_url = array("ldap.php");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array(
		0=>array('LDAP_SERVER_TYPE','chaine','openldap','',0),
		1=>array('LDAP_SERVER_PROTOCOLVERSION','chaine','3','',0),
		2=>array('LDAP_SERVER_HOST','chaine','localhost','',0),
		3=>array('LDAP_USER_DN','chaine','ou=users,dc=example,dc=com','',0),
		4=>array('LDAP_GROUP_DN','chaine','ou=groups,dc=example,dc=com','',0),
		5=>array('LDAP_FILTER_CONNECTION','chaine','&(objectClass=inetOrgPerson)','',0),
		6=>array('LDAP_FIELD_LOGIN','chaine','uid','',0),
		7=>array('LDAP_FIELD_FULLNAME','chaine','cn','',0),
		8=>array('LDAP_FIELD_NAME','chaine','sn','',0),
		9=>array('LDAP_FIELD_FIRSTNAME','chaine','givenname','',0),
		10=>array('LDAP_FIELD_MAIL','chaine','mail','',0),
		11=>array('LDAP_FIELD_PHONE','chaine','telephonenumber','',0),
		12=>array('LDAP_FIELD_FAX','chaine','facsimiletelephonenumber','',0),
		13=>array('LDAP_FIELD_MOBILE','chaine','mobile','',0),
		);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'ldap';
	}
}
