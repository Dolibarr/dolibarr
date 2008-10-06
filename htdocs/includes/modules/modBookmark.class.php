<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \defgroup   bookmark    Module Bookmark
 \brief      Module pour g�rer les Bookmarks
 \version	$Id$
 */

/**
 \file       htdocs/includes/modules/modBookmark.class.php
 \ingroup    bookmark
 \brief      Fichier de description et activation du module Bookmarks
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modBookmark
 \brief      Classe de description et activation du module Bookmark
 */

class modBookmark extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modBookmark($DB)
	{
		$this->db = $DB ;
		$this->numero = 330;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des Bookmarks";
		$this->revision = explode(' ','$Revision$');
		$this->version = $this->revision[1];

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto='bookmark';

		// Dir
		$this->dirs = array();

		// D�pendances
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		//$this->config_page_url = array();

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();
		$this->boxes[0][1] = "box_bookmarks.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'bookmark';
		$r=0;

		$r++;
		$this->rights[$r][0] = 331; // id de la permission
		$this->rights[$r][1] = 'Lire les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 332; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 333; // id de la permission
		$this->rights[$r][1] = 'Supprimer les bookmarks'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par d�faut
		$this->rights[$r][4] = 'supprimer';

	}

	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
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
