<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**     \defgroup   ldap     Module ldap
 \brief      Module pour interfacer les contacts avec un annuaire Ldap
 */

/**
 *	\file       htdocs/includes/modules/modLdap.class.php
 *	\ingroup    ldap
 *	\brief      Fichier de description et activation du module Ldap
 *	\version	$Id$
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class 		modLdap
 *	\brief      Classe de description et activation du module Ldap
 */
class modLdap extends DolibarrModules
{
	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modLdap($DB)
	{
		$this->db = $DB ;
		$this->numero = 200 ;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Synchronisation Ldap";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 1;

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
		3=>array('LDAP_USER_DN','chaine','ou=users,dc=my-domain,dc=com','',0),
		4=>array('LDAP_GROUP_DN','chaine','ou=groups,dc=my-domain,dc=com','',0),
		5=>array('LDAP_FILTER_CONNECTION','chaine','&(objectClass=user)(objectCategory=person)','',0),
		6=>array('LDAP_FIELD_LOGIN','chaine','uid','',0),
		7=>array('LDAP_FIELD_NAME','chaine','sn','',0),
		8=>array('LDAP_FIELD_FIRSTNAME','chaine','givenname','',0),
		9=>array('LDAP_FIELD_MAIL','chaine','mail','',0),
		10=>array('LDAP_FIELD_PHONE','chaine','telephonenumber','',0),
		11=>array('LDAP_FIELD_FAX','chaine','facsimiletelephonenumber','',0),
		12=>array('LDAP_FIELD_MOBILE','chaine','mobile','',0),
		);

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'ldap';
	}

	/**
	 *   \brief      Fonction appele lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>
