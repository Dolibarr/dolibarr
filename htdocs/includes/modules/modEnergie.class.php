<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 \defgroup   energie     Module energie
 \brief      Module pour le suivi de la consommation d'energie
 */

/**
 \file       htdocs/includes/modules/modEnergie.class.php
 \ingroup    energie
 \brief      Fichier de description et activation du module Energie
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class modEnergie
 \brief      Classe de description et activation du module Energie
 */

class modEnergie extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modEnergie($DB)
	{
		$this->db = $DB ;
		$this->numero = 23 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Suivi de la consommation des energies";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto='energie';

		// Dir
		$this->dirs = array();

		// D�pendances
		$this->depends = array();

		// Config pages
		$this->config_page_url = array("energie.php");

		// Constantes
		$this->const = array();

		// Boxes
		$this->boxes = array();
		$r=0;
		$this->boxes[$r][1] = "box_energie_releve.php";
		$r++;
		$this->boxes[$r][1] = "box_energie_graph.php";
		$r++;

		// Permissions
		$this->rights = array();

	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		global $conf;
		// Permissions et valeurs par d�faut
		$this->remove();

		$sql = array();

		$this->dirs[0] = DOL_DATA_ROOT."/energie";
		$this->dirs[1] = DOL_DATA_ROOT."/energie/graph";

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
