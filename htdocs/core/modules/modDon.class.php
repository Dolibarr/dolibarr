<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro@open-dsi.fr>
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
 *	\defgroup   don     Module donations
 *	\brief      Module to manage the follow-up of the donations
 *	\file       htdocs/core/modules/modDon.class.php
 *	\ingroup    donations
 *	\brief      Description and activation file for the module Donation
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Donation
 */
class modDon extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 700;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des dons";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto = 'donation';

		// Data directories to create when module is enabled
		$this->dirs = array("/don/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("donation.php@don");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "DON_ADDON_MODEL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "html_cerfafr";
		$this->const[$r][3] = 'Nom du gestionnaire de generation de recu de dons';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "DONATION_ART200";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Option Française - Eligibilité Art200 du CGI';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "DONATION_ART238";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Option Française - Eligibilité Art238 bis du CGI';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "DONATION_ART978";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Option Française - Eligibilité Art978 du CGI';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "DONATION_MESSAGE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "Thank you";
		$this->const[$r][3] = 'Message affiché sur le récépissé de versements ou dons';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "DONATION_ACCOUNTINGACCOUNT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "758";
		$this->const[$r][3] = 'Compte comptable de remise des versements ou dons';
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'don';

		$this->rights[1][0] = 701;
		$this->rights[1][1] = 'Lire les dons';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 702;
		$this->rights[2][1] = 'Creer/modifier les dons';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 703;
		$this->rights[3][1] = 'Supprimer les dons';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
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

		$result = $this->_load_tables('/install/mysql/', 'don');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'donation' AND entity = ".((int) $conf->entity),
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','donation',".((int) $conf->entity).")",
		);

		return $this->_init($sql, $options);
	}
}
