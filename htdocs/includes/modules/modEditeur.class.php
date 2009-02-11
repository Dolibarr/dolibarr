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
 \defgroup   	editeur     Module editeur
 \brief      	Module pour gerer le suivi des editeurs
 \version		$Id$
 */

/**
 \file       htdocs/includes/modules/modEditeur.class.php
 \ingroup    editeur
 \brief      Fichier de description et activation du module Editeur
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modEditeur
 \brief      Classe de description et activation du module Editeur
 */

class modEditeur extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modEditeur($DB)
	{
		$this->db = $DB ;
		$this->numero = 49 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des livres et editeurs";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 3;
		$this->picto='book';

		// Dir
		global $dolibarr_smarty_compile;
		global $dolibarr_smarty_cache;
		$this->dirs = array($dolibarr_smarty_compile,
							$dolibarr_smarty_cache);

		// Config pages
		$this->config_page_url = array("editeur.php");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->needleftmenu = array();
		$this->needtotopmenu = array();
		$this->langfiles = array("orders","bills","companies");

		// Constantes
		$this->const=array(0=>array('PRODUCT_CANVAS_ABILITY','chaine',1,'This is a constant',1),
						   1=>array('MAIN_NEED_SMARTY','chaine',1,'Need smarty',0));

		// Boites
		$this->boxes = array();

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

		return $this->_init($sql);
	}


	/**
	 *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		global $conf;

		return $this->_remove($sql);
	}
}
?>
