<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**     \defgroup   accounting 			Module accounting
 *		\brief      Module pour inclure des fonctions de comptabilite (gestion de comptes comptables et rapports)
 *		\version	$Id$
 */

/**
 *		\file       htdocs/includes/modules/modAccounting.class.php
 *		\ingroup    accounting
 * 		\brief      Fichier de description et activation du module Comptabilite Expert
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modAccounting
 *	\brief      Classe de description et activation du module Comptabilite Expert
 */
class modAccounting extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modAccounting($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 130 ;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion complete de comptabilite (doubles parties)";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		//$this->version = 'dolibarr';
		$this->version = "development";

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;

		// Config pages
		$this->config_page_url = array("accounting.php");

		// Dependancies
		$this->depends = array("modFacture","modBanque");
		$this->requiredby = array();
		$this->conflictwith = array("modComptabilite");
		$this->langfiles = array("compta");

		// Constants
		$this->const = array(0=>array('MAIN_COMPANY_CODE_ALWAYS_REQUIRED','chaine','1','With this constants on, third party codes are always required whatever is numbering module behaviour',0));			// List of particular constants to add when module is enabled

		// Data directories to create when module is enabled
		$this->dirs = array("/accounting/temp");

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'accounting';

		$this->rights[1][0] = 131;
		$this->rights[1][1] = 'Lire le plan de compte';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'plancompte';
		$this->rights[1][5] = 'lire';

		$this->rights[2][0] = 132;
		$this->rights[2][1] = 'Creer/modifier un plan de compte';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'plancompte';
		$this->rights[2][5] = 'creer';

		$this->rights[3][0] = 133;
		$this->rights[3][1] = 'Cloturer plan de compte';
		$this->rights[3][2] = 'w';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'plancompte';
		$this->rights[3][5] = 'cloturer';

		$this->rights[4][0] = 141;
		$this->rights[4][1] = 'Lire les mouvements comptables';
		$this->rights[4][2] = 'r';
		$this->rights[4][3] = 1;
		$this->rights[4][4] = 'mouvements';
		$this->rights[4][5] = 'lire';

		$this->rights[5][0] = 142;
		$this->rights[5][1] = 'Creer/modifier/annuler les mouvements comptables';
		$this->rights[5][2] = 'w';
		$this->rights[5][3] = 0;
		$this->rights[5][4] = 'mouvements';
		$this->rights[5][5] = 'creer';

		$this->rights[6][0] = 145;
		$this->rights[6][1] = 'Lire CA, bilans, resultats, journaux, grands livres';
		$this->rights[6][2] = 'r';
		$this->rights[6][3] = 0;
		$this->rights[6][4] = 'comptarapport';
		$this->rights[6][5] = 'lire';

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees e creer pour ce module.
	 */
	function init($options='')
	{
		// Prevent pb of modules not correctly disabled
		//$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove($options='')
	{
		global $conf;

		$sql = array("DELETE FROM ".MAIN_DB_PREFIX."const where name='MAIN_COMPANY_CODE_ALWAYS_REQUIRED' and entity IN ('0','".$conf->entity."')");

		return $this->_remove($sql,$options);
	}
}
?>
