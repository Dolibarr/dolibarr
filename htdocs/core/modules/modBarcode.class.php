<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *	\defgroup   barcode         Module barcode
 *	\brief      Module pour gerer les codes barres
 *	\file       htdocs/core/modules/modBarcode.class.php
 *	\ingroup    barcode,produit
 *	\brief      Fichier de description et activation du module Barcode
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 *	\class      modBarcode
 *	\brief      Classe de description et activation du module Barcode
 */

class modBarcode extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
	 */
	function modBarcode($DB)
	{
		$this->db = $DB ;
		$this->numero = 55 ;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des codes barres";
		$this->version = 'dolibarr';		// 'development' or 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 2;
		$this->picto='barcode';

		// Data directories to create when module is enabled
		$this->dirs = array("/barcode/temp");

		// Dependances
		$this->depends = array();        // May be used for product or service or third party module
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("barcode.php");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                            1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		$this->const = array(
		                //0=>array('GENBARCODE_LOCATION','chaine',DOL_DOCUMENT_ROOT.'/includes/barcode/php-barcode/genbarcode/genbarcode','Path to genbarcode command line tool',0)
		                );

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'barcode';

		$this->rights[1][0] = 300; // id de la permission
		$this->rights[1][1] = 'Lire les codes barres'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 301; // id de la permission
		$this->rights[2][1] = 'Creer/modifier les codes barres'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'creer';

		$this->rights[4][0] = 302; // id de la permission
		$this->rights[4][1] = 'Supprimer les codes barres'; // libelle de la permission
		$this->rights[4][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[4][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[4][4] = 'supprimer';

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
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
