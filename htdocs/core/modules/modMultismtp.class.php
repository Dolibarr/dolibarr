<?php
/* Copyright (C) 2003      Rodolphe Quiedeville 		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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
 * 		\defgroup   multismtp     Module Multismtp
 *      \brief      Module to manage multi smtp account for email
 *      \file       htdocs/core/modules/modMultismtp.class.php
 *		\ingroup    multismtp
 *		\brief      Description class file and activation of the Multismtp module
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Class to describe module multismtp
 */
class modMultismtp extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
        global $langs;

		$this->db = $db;
		$this->numero = 3300;

		$this->family = "technic";
		$this->module_position = '10';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Allows the configuration of an email account for each user";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'experimental';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'email';

  		$this->module_parts = array(
			'hooks' => array('main'),
			'triggers' => 1
		);

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("admin");

		// Config pages
		$this->config_page_url = array("smtp.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "MAIN_ACTIVATE_UPDATESESSIONTRIGGER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = 'Used by MultiSMTP module';
		$this->const[$r][4] = 1;
		$r++;

		$this->const[$r][0] = "MULTISMTP_SMTP_ENABLED";
		$this->const[$r][1] = "int";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 'current';
		$r++;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'multismtp';
		$r = 0;

		$this->rights[1][0] = 3301;
		$this->rights[1][1] = 'Go to the email configuration tab';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'read';
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
     *  @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
