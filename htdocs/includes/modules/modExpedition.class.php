<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *	\defgroup   expedition     Module expedition
 *	\brief      Module pour gerer les expeditions de produits
 */

/**
 *	\file       htdocs/includes/modules/modExpedition.class.php
 *	\ingroup    expedition
 *	\brief      Fichier de description et activation du module Expedition
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class modExpedition
 *	\brief      Classe de description et activation du module Expedition
 */
class modExpedition extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modExpedition($DB)
	{
		$this->db = $DB ;
		$this->numero = 80 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des expeditions";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "sending";

		// Data directories to create when module is enabled
		$this->dirs = array("/expedition/temp",
							"/expedition/sending",
		                    "/expedition/sending/temp",
		                    "/expedition/receipt",
		                    "/expedition/receipt/temp"
		                    );

		// Config pages
		$this->config_page_url = array("confexped.php");

		// Dependances
		$this->depends = array("modCommande");
		$this->requiredby = array();

		// Constantes
		$this->const = array();
		$this->const[0][0] = "LIVRAISON_ADDON_PDF";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "typhon";
		$this->const[0][3] = 'Nom du gestionnaire de generation des commandes en PDF';
		$this->const[0][4] = 0;

		$this->const[1][0] = "LIVRAISON_ADDON";
		$this->const[1][1] = "chaine";
		$this->const[1][2] = "mod_livraison_jade";
		$this->const[1][3] = 'Nom du gestionnaire de numerotation des bons de livraison';
		$this->const[1][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'expedition';
		$r=0;

		$r++;
		$this->rights[$r][0] = 101;
		$this->rights[$r][1] = 'Lire les expeditions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 102;
		$this->rights[$r][1] = 'Creer modifier les expeditions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 104;
		$this->rights[$r][1] = 'Valider les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 109;
		$this->rights[$r][1] = 'Supprimer les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1101;
		$this->rights[$r][1] = 'Lire les bons de livraison';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 1102;
		$this->rights[$r][1] = 'Creer modifier les bons de livraison';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 1104;
		$this->rights[$r][1] = 'Valider les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'valider';

		$r++;
		$this->rights[$r][0] = 1109;
		$this->rights[$r][1] = 'Supprimer les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'supprimer';

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
