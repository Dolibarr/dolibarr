<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**     \defgroup   export      Module export
 *		\brief      Module generique pour realiser des exports de donnees en base
 * 		\version	$Id: modExport.class.php,v 1.17 2011/07/31 23:28:12 eldy Exp $
 */

/**
 *	\file       htdocs/includes/modules/modExport.class.php
 *	\ingroup    export
 *	\brief      Fichier de description et activation du module export
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modExport
 \brief      Classe de description et activation du module export
 */

class modExport extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modExport($DB)
	{
		$this->db = $DB ;
		$this->numero = 240;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Outils d'exports de donnees Dolibarr (via un assistant)";
		$this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='technic';

		// Data directories to create when module is enabled
		$this->dirs = array("/export/temp");

		// Config pages
		$this->config_page_url = array();

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4,2,0);
		$this->phpmax = array();

		// Constantes
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'export';
		$r=0;

		$r++;
		$this->rights[$r][0] = 1201;
		$this->rights[$r][1] = 'Lire les exports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1202;
		$this->rights[$r][1] = 'Creer/modifier un export';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
	}

	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
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
