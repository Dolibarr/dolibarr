<?php
/* Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
 *	\defgroup   DroitPret     Module pret
 *	\version	$Id$
 *	\brief      Module pour gerer le suivi des droits de prets
 */

/**
 *	\file       htdocs/includes/modules/modDroitPret.class.php
 *	\ingroup    don
 *	\brief      Fichier de description et activation du module DroitPret
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modDroitPret
 *	\brief      Classe de description et activation du module DroitPret
 */

class modDroitPret  extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modDroitPret($DB)
	{
		$this->db = $DB ;
		$this->numero = 2200 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion du droit de prets";
		$this->version = 'development';    // 'development' or 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 3;

		// Dir
		global $dolibarr_smarty_compile;
		global $dolibarr_smarty_cache;
		$this->dirs = array($dolibarr_smarty_compile,
							$dolibarr_smarty_cache);

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->needleftmenu = array();
		$this->needtotopmenu = array();
		$this->langfiles = array("orders","bills","companies");

		// Config pages
		$this->config_page_url = array("droitpret.php");

		// Constantes
		$this->const=array(0=>array('PRODUCT_CANVAS_ABILITY','chaine',1,'This is a constant',1),
						   1=>array('MAIN_NEED_SMARTY','chaine',1,'Need smarty',0)
						   );

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'droitpret';

		$this->rights[1][0] = 2200;
		$this->rights[1][1] = 'Lire les droits de prets';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 2201;
		$this->rights[2][1] = 'Creer/modifier les droits de prets';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';
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
