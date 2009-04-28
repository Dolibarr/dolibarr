<?php
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\defgroup   	document     Module de generation de documents
 *	\brief      	Module pour gerer des generations de documents
 *	\version		$Id$
 */

/**
 *	\file       htdocs/includes/modules/modDocument.class.php
 *	\ingroup    document
 *	\brief      Fichier de description et activation du module Generation document
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modDocument
 *	\brief      Classe de description et activation du module Document
 */

class modDocument extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modDocument($DB)
	{
		$this->db = $DB ;
		$this->numero = 51 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Generation de courriers/publipostages";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 3;
		$this->picto='dir';

		// Dir
		$this->dirs = array();
		$this->dirs[0] = DOL_DATA_ROOT."/ged";

		// Config pages
		//$this->config_page_url = array("document.php");

		// D�pendances
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("orders","bills","companies");

		// Constantes

		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'document';

		$r=0;

		$this->rights[$r][0] = 510;
		$this->rights[$r][1] = 'Lire les documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 511;
		$this->rights[$r][1] = 'Supprimer les documents clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
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
