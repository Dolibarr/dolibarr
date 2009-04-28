<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   comptabilite     Module comptabilite
        \brief      Module pour inclure des fonctions de comptabilit� (gestion de comptes comptables et rapports)
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modComptabilite.class.php
        \ingroup    comptabilite
        \brief      Fichier de description et activation du module Comptabilite
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
		\class 		modComptabilite
        \brief      Classe de description et activation du module Comptabilite
*/
class modComptabilite extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acces base
    */
	function modComptabilite($DB)
	{
		global $conf;
	
		$this->db = $DB ;
		$this->numero = 10 ;
	
		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion sommaire de comptabilite";
	
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
			
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
        $this->picto='';
	
		// Config pages
		$this->config_page_url = array("compta.php");
	
		// Dependances
		$this->depends = array("modFacture","modBanque");
		$this->requiredby = array();
		$this->conflictwith = array("modComptabiliteExpert");
		$this->langfiles = array("compta");
	
		// Constantes
		$this->const = array();
		
		// Data directories to create when module is enabled
		$this->dirs = array();
		$r=0;
		
		$this->dirs[$r][0] = "output";
		$this->dirs[$r][1] = "/compta";
		
		$r++;
		$this->dirs[$r][0] = "temp";
		$this->dirs[$r][1] = "/compta/temp";
		
		$r++;
		$this->dirs[$r][0] = "rapport";
		$this->dirs[$r][1] = "/compta/rapport";
		
		$r++;
		$this->dirs[$r][0] = "export";
		$this->dirs[$r][1] = "/compta/export";
	
		// Boites
		$this->boxes = array();
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'compta';
		$r=0;
	
		$r++;
		$this->rights[$r][0] = 95;
		$this->rights[$r][1] = 'Lire CA, bilans, resultats';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'resultat';
		$this->rights[$r][5] = 'lire';
	
		$r++;
		$this->rights[$r][0] = 96;
		$this->rights[$r][1] = 'Parametrer la ventilation';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ventilation';
		$this->rights[$r][5] = 'parametrer';
	
		$r++;
		$this->rights[$r][0] = 97;
		$this->rights[$r][1] = 'Lire les ventilations de factures';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'ventilation';
		$this->rights[$r][5] = 'lire';
	
		$r++;
		$this->rights[$r][0] = 98;
		$this->rights[$r][1] = 'Ventiler les lignes de factures';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ventilation';
		$this->rights[$r][5] = 'creer';
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
