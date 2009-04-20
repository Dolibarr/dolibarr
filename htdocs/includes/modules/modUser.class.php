<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *	\defgroup   user  Module user
 *	\brief      Module pour gerer les utilisateurs
 */

/**
 *	\file       htdocs/includes/modules/modUser.class.php
 *	\ingroup    user
 *	\brief      Fichier de description et activation du module Utilisateur
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *	\class      modUser
 *	\brief      Classe de description et activation du module User
 */
class modUser extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modUser($DB)
	{
		$this->db = $DB ;
		$this->numero = 0;

		$this->family = "base";		// Family for module (or "base" if core module)
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des utilisateurs (requis)";
		$this->always_enabled = 1;	// Can't be disabled

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='group';

		// Dir
		$this->dirs = array();

		// Config pages
		// $this->config_page_url = array("/user/admin/index.php");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("main","users","companies");

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'user';
		$this->rights_admin_allowed = 1;	// Admin is always granted of permission (even when module is disabled)
		$r=0;

		$r++;
		$this->rights[$r][0] = 251;
		$this->rights[$r][1] = 'Consulter les autres utilisateurs, leurs groupes et permissions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 252;
		$this->rights[$r][1] = 'Creer/modifier les autres utilisateurs, les groupes et leurs permissions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 253;
		$this->rights[$r][1] = 'Modifier mot de passe des autres utilisateurs';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'password';

		$r++;
		$this->rights[$r][0] = 254;
		$this->rights[$r][1] = 'Supprimer ou desactiver les autres utilisateurs';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 255;
		$this->rights[$r][1] = 'Creer/modifier ses propres infos utilisateur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'self';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 256;
		$this->rights[$r][1] = 'Modifier son propre mot de passe';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'self';
		$this->rights[$r][5] = 'password';

		$r++;
		$this->rights[$r][0] = 258;
		$this->rights[$r][1] = 'Exporter les utilisateurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'user';
		$this->rights[$r][5] = 'export';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Liste des utilisateurs Dolibarr et attributs';
		$this->export_permission[$r]=array(array("user","user","export"));
		$this->export_fields_array[$r]=array('u.rowid'=>"Id",'u.login'=>"Login",'u.name'=>"Lastname",'u.firstname'=>"Firstname",'u.office_phone'=>'Tel','u.office_fax'=>'Fax','u.email'=>'EMail','u.datec'=>"DateCreation",'u.tms'=>"DateLastModification",'u.admin'=>"Admin",'u.statut'=>'Status','u.note'=>"Note",'u.datelastlogin'=>'LastConnexion','u.datepreviouslogin'=>'PreviousConnexion','u.fk_socpeople'=>"IdContact",'u.fk_societe'=>"IdCompany",'u.fk_member'=>"MemberId");
		$this->export_entities_array[$r]=array('u.rowid'=>"user",'u.login'=>"user",'u.name'=>"user",'u.firstname'=>"user",'u.office_phone'=>'user','u.office_fax'=>'user','u.email'=>'user','u.datec'=>"user",'u.tms'=>"user",'u.admin'=>"user",'u.statut'=>'user','u.note'=>"user",'u.datelastlogin'=>'user','u.datepreviouslogin'=>'user','u.fk_socpeople'=>"contact",'u.fk_societe'=>"company",'u.fk_member'=>"member");
		$this->export_alias_array[$r]=array('u.rowid'=>"rowid",'u.login'=>"login",'u.name'=>"name",'u.firstname'=>"firstname",'u.office_phone'=>'tel','u.office_fax'=>'fax','u.email'=>'email','u.datec'=>"datecreation",'u.tms'=>"datelastmodification",'u.admin'=>"admin",'u.statut'=>'status','u.note'=>"note",'u.datelastlogin'=>'datelastlogin','u.datepreviouslogin'=>'datepreviouslogin','u.fk_socpeople'=>"idcontact",'u.fk_societe'=>"idcompany",'u.fk_member'=>"idmember");
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'user as u';
	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 \brief      Fonction appelee lors de la desactivation d'un module.
	 Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>
