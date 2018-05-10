<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   bookmark    Module bookmarks
 *	\brief      Module to manage Bookmarks
 *	\file       htdocs/core/modules/modBookmark.class.php
 *	\ingroup    bookmark
 *	\brief      Fichier de description et activation du module Bookmarks
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Bookmark
 */
class modBookmark extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numero = 330;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des Bookmarks";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='bookmark';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("bookmarks");

		// Config pages
		$this->config_page_url = array('bookmark.php@bookmarks');

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array(0=>array('file'=>'box_bookmarks.php','enabledbydefaulton'=>'Home'));

		// Permissions
		$this->rights = array();
		$this->rights_class = 'bookmark';
		$r=0;

		$r++;
		$this->rights[$r][0] = 331; // id de la permission
		$this->rights[$r][1] = 'Lire les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 332; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 333; // id de la permission
		$this->rights[$r][1] = 'Supprimer les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'supprimer';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.

	}
}
