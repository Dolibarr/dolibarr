<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 */

/**     \defgroup   ldap     Module ldap
        \brief      Module pour interfacer les contacts avec un annuaire Ldap
*/

/**
        \file       htdocs/includes/modules/modLdap.class.php
        \ingroup    ldap
        \brief      Fichier de description et activation du module Ldap
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class modLdap
		\brief      Classe de description et activation du module Ldap
*/

class modLdap extends DolibarrModules
{
	/**
	*   \brief      Constructeur. Definit les noms, constantes et boites
	*   \param      DB      handler d'acc�s base
	*/
	function modLdap($DB)
	{
		$this->db = $DB ;
		$this->numero = 200 ;
	
		$this->name = "Ldap";
		$this->family = "technic";
		$this->description = "Synchronisation Ldap";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_LDAP';
		$this->special = 1;
	
		// Dir
		$this->dirs = array();
	
		// Config pages
		$this->config_page_url = array("ldap.php");
	
		// D�pendances
		$this->depends = array();
		$this->requiredby = array();
	
		// Constantes
		$this->const = array();
		$r=0;
	
		$this->const[$r][0] = "LDAP_USER_DN";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "ou=users,dc=my-domain,dc=com";
		$r++;

		$this->const[$r][0] = "LDAP_GROUP_DN";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "ou=groups,dc=my-domain,dc=com";
		$r++;

		$this->const[$r][0] = "LDAP_FILTER_CONNECTION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "&(objectClass=user)(objectCategory=person)";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_LOGIN";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "uid";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_LOGIN_SAMBA";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "samaccountname";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_NAME";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "sn";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_FIRSTNAME";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "givenname";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_MAIL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mail";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_PHONE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "telephonenumber";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_FAX";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "facsimiletelephonenumber";
		$r++;

		$this->const[$r][0] = "LDAP_FIELD_MOBILE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mobile";
		$r++;
			
		// Boites
		$this->boxes = array();
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'ldap';
	}

   /**
    *   \brief      Fonction appel� lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
    *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
    */
  function init()
  {
    $sql = array();

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);   
  }
}
?>
