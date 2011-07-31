<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 *	\defgroup   contrat     Module contract
 *	\brief      Module pour gerer la tenue de contrat de services
 *	\version	$Id: modContrat.class.php,v 1.41 2011/07/31 23:28:11 eldy Exp $
 */

/**
 *	\file       htdocs/includes/modules/modContrat.class.php
 *	\ingroup    contrat
 *	\brief      Fichier de description et activation du module Contrat
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modContrat
 \brief      Classe de description et activation du module Contrat
 */

class modContrat extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modContrat($DB)
	{
		$this->db = $DB ;
		$this->numero = 54 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des contrats de services";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='contract';

		// Data directories to create when module is enabled
		$this->dirs = array("/contracts/temp");

		// Dependances
		$this->depends = array("modService");
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("contract.php");

		// Constantes
		$this->const = array();
		$this->const[0][0] = "CONTRACT_ADDON";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "mod_contract_serpis";
		$this->const[0][3] = 'Nom du gestionnaire de numerotation des contrats';
		$this->const[0][4] = 0;

		// Boxes
		$this->boxes = array();
		$this->boxes[0][1] = "box_contracts.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'contrat';

		$this->rights[1][0] = 161;
		$this->rights[1][1] = 'Lire les contrats';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 162;
		$this->rights[2][1] = 'Creer / modifier les contrats';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 163;
		$this->rights[3][1] = 'Activer un service d\'un contrat';
		$this->rights[3][2] = 'w';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'activer';

		$this->rights[4][0] = 164;
		$this->rights[4][1] = 'Desactiver un service d\'un contrat';
		$this->rights[4][2] = 'w';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'desactiver';

		$this->rights[5][0] = 165;
		$this->rights[5][1] = 'Supprimer un contrat';
		$this->rights[5][2] = 'd';
		$this->rights[5][3] = 0;
		$this->rights[5][4] = 'supprimer';

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

		// Nettoyage avant activation
		$this->remove();

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
