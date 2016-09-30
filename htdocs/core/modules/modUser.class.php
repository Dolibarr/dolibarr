<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\defgroup   user  Module user management
 *	\brief      Module pour gerer les utilisateurs
 *	\file       htdocs/core/modules/modUser.class.php
 *	\ingroup    user
 *	\brief      Fichier de description et activation du module Utilisateur
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

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
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 0;

		$this->family = "hr";		// Family for module (or "base" if core module)
		$this->module_position = 10;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des utilisateurs (requis)";
		$this->always_enabled = true;	// Can't be disabled

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='group';

		// Data directories to create when module is enabled
		$this->dirs = array("/users/temp");

		// Config pages
		$this->config_page_url = array("user.php");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("main","users","companies","members");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'user';
		$this->rights_admin_allowed = 1;	// Admin is always granted of permission (even when module is disabled)
		$r=0;

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
		$this->rights[$r][4] = 'self_advance';        // Visible if option MAIN_USE_ADVANCED_PERMS is on
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
		$this->rights[$r][4] = 'self_advance';          // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'writeperms';

		$r++;
		$this->rights[$r][0] = 351;
		$this->rights[$r][1] = 'Consulter les groupes';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance';        // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 352;
		$this->rights[$r][1] = 'Consulter les permissions des groupes';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance';       // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'readperms';

		$r++;
		$this->rights[$r][0] = 353;
		$this->rights[$r][1] = 'Creer/modifier les groupes et leurs permissions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 354;
		$this->rights[$r][1] = 'Supprimer ou desactiver les groupes';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'group_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'delete';

		$r++;
		$this->rights[$r][0] = 358;
		$this->rights[$r][1] = 'Exporter les utilisateurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'export';

		
        // Menus
        //-------
        
        $this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		
		
		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Liste des utilisateurs Dolibarr et attributs';
		$this->export_permission[$r]=array(array("user","user","export"));
		$this->export_fields_array[$r]=array('u.rowid'=>"Id",'u.login'=>"Login",'u.lastname'=>"Lastname",'u.firstname'=>"Firstname",'u.accountancy_code'=>"UserAccountancyCode",'u.office_phone'=>'Phone','u.office_fax'=>'Fax','u.email'=>'EMail','u.datec'=>"DateCreation",'u.tms'=>"DateLastModification",'u.admin'=>"Administrator",'u.statut'=>'Status','u.note'=>"Note",'u.datelastlogin'=>'LastConnexion','u.datepreviouslogin'=>'PreviousConnexion','u.fk_socpeople'=>"IdContact",'u.fk_soc'=>"IdCompany",'u.fk_member'=>"MemberId");
		$this->export_TypeFields_array[$r]=array('u.login'=>"Text",'u.lastname'=>"Text",'u.firstname'=>"Text",'u.accountancy_code'=>'Text','u.office_phone'=>'Text','u.office_fax'=>'Text','u.email'=>'Text','u.datec'=>"Date",'u.tms'=>"Date",'u.admin'=>"Boolean",'u.statut'=>'Status','u.note'=>"Text",'u.datelastlogin'=>'Date','u.datepreviouslogin'=>'Date','u.fk_soc'=>"List:societe:nom:rowid",'u.fk_member'=>"List:adherent:firstname");
		$this->export_entities_array[$r]=array('u.rowid'=>"user",'u.login'=>"user",'u.lastname'=>"user",'u.firstname'=>"user",'u.accountancy_code'=>'user','u.office_phone'=>'user','u.office_fax'=>'user','u.email'=>'user','u.datec'=>"user",'u.tms'=>"user",'u.admin'=>"user",'u.statut'=>'user','u.note'=>"user",'u.datelastlogin'=>'user','u.datepreviouslogin'=>'user','u.fk_socpeople'=>"contact",'u.fk_soc'=>"company",'u.fk_member'=>"member");
        if (empty($conf->adherent->enabled))
        {
            unset($this->export_fields_array[$r]['u.fk_member']);
            unset($this->export_entities_array[$r]['u.fk_member']);
        }
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .=' WHERE u.entity IN ('.getEntity('user',1).')';
	}


    /**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
