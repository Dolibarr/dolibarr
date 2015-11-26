<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   mailing  Module emailing
 *	\brief      Module to manage EMailings
 *	\file       htdocs/core/modules/modMailing.class.php
 *	\ingroup    mailing
 *	\brief      Fichier de description et activation du module Mailing
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 22;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des EMailings";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='email';

		// Data directories to create when module is enabled
		$this->dirs = array("/mailing/temp");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("mails");

		// Config pages
		$this->config_page_url = array("mailing.php");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'mailing';
		$r=0;

		$r++;
		$this->rights[$r][0] = 221; // id de la permission
		$this->rights[$r][1] = 'Consulter les mailings'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
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
		$this->rights[$r][4] = 'mailing_advance';		// Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'recipient';

		$r++;
		$this->rights[$r][0] = 238;
		$this->rights[$r][1] = 'Manually send mailings';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mailing_advance';		// Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 239;
		$this->rights[$r][1] = 'Delete mailings after validation and/or sent';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mailing_advance';		// Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'delete';

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
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
