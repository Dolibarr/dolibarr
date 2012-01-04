<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 *	\defgroup   expedition     Module shipping
 *	\brief      Module pour gerer les expeditions de produits
 *	\file       htdocs/core/modules/modExpedition.class.php
 *	\ingroup    expedition
 *	\brief      Fichier de description et activation du module Expedition
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 	\class modExpedition
 *	\brief      Classe de description et activation du module Expedition
 */
class modExpedition extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modExpedition($db)
	{
		$this->db = $db;
		$this->numero = 80;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
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
		$r=0;

		$this->const[$r][0] = "EXPEDITION_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "rouget";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons expeditions en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPEDITION_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "elevement";
		$this->const[$r][3] = 'Nom du gestionnaire du type d\'expedition';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "typhon";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons de reception en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_livraison_jade";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des bons de reception';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPEDITION_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_expedition_safor";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des expeditions';
		$this->const[$r][4] = 0;
		$r++;

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
		$this->rights[$r][0] = 105; // id de la permission
		$this->rights[$r][1] = 'Envoyer les expeditions aux clients'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'shipping_advance';
        $this->rights[$r][5] = 'send';

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
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove();

		$sql = array();

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','shipping',".$conf->entity.")",
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[1][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[1][2]."','delivery',".$conf->entity.")",
		);

		return $this->_init($sql,$options);
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
