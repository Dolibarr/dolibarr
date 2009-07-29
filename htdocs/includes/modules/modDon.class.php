<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 \defgroup   don     Module donation
 \brief      Module pour g�rer le suivi des dons
 \version	$Id$
 */

/**
 *	\file       htdocs/includes/modules/modDon.class.php
 *	\ingroup    don
 *	\brief      Fichier de description et activation du module Don
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modDon
 *	\brief      Classe de description et activation du module Don
 */
class modDon  extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modDon($DB)
	{
		$this->db = $DB ;
		$this->numero = 700 ;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des dons";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;

		// Data directories to create when module is enabled
		$this->dirs = array("/dons/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("dons.php");

		// Constants
		$this->const = array();
		$this->const[0][0] = "DON_FORM";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "fsfe.fr.php";
		$this->const[0][3] = 'Nom du gestionnaire de formulaire de dons';
		$this->const[0][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'don';

		$this->rights[1][0] = 700;
		$this->rights[1][1] = 'Lire les dons';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 701;
		$this->rights[2][1] = 'Creer/modifier les dons';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 702;
		$this->rights[3][1] = 'Supprimer les dons';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';

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
