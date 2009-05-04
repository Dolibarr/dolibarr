<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
 \defgroup   telephonie  Module telephonie
 \brief      Module pour gerer la telephonie
 */

/**
 \file       htdocs/includes/modules/modTelephonie.class.php
 \ingroup    telephonie
 \brief      Fichier de description et activation du module de Telephonie
	\version	$Id$
 */

include_once(DOL_DOCUMENT_ROOT."/includes/modules/DolibarrModules.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");


/**
 \class      modTelephonie
 \brief      Classe de description et activation du module Telephonie
 */

class modTelephonie extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modTelephonie($DB)
	{
		$this->db = $DB ;
		$this->numero = 56 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion de la Telephonie";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 3;
		$this->picto='phoning';

		// Data directories to create when module is enabled
		$this->dirs = array("/telephonie/temp",
							"/telephonie/graph",
							"/telephonie/logs",
							"/telephonie/client",
							"/telephonie/rapports",
							"/telephonie/ligne/commande/retour/traite",
							"/telephonie/cdr/archives",
							"/telephonie/cdr/atraiter");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'telephonie';

		$this->rights[1][0] = 211; // id de la permission
		$this->rights[1][1] = 'Consulter la telephonie'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 212; // id de la permission
		$this->rights[2][1] = 'Commander les lignes'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'ligne_commander';

		$this->rights[3][0] = 213;
		$this->rights[3][1] = 'Activer une ligne';
		$this->rights[3][2] = 'w';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'ligne_activer';

		$this->rights[4][0] = 214; // id de la permission
		$this->rights[4][1] = 'Configurer la telephonie'; // libelle de la permission
		$this->rights[4][2] = 'w';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'configurer';

		$this->rights[5][0] = 215;
		$this->rights[5][1] = 'Configurer les fournisseurs';
		$this->rights[5][2] = 'w';
		$this->rights[5][3] = 0;
		$this->rights[5][4] = 'fournisseur';
		$this->rights[5][5] = 'config';

		$this->rights[6][0] = 192;
		$this->rights[6][1] = 'Creer des lignes';
		$this->rights[6][2] = 'w';
		$this->rights[6][3] = 0;
		$this->rights[6][4] = 'ligne';
		$this->rights[6][5] = 'creer';

		$this->rights[7][0] = 202;
		$this->rights[7][1] = 'Creer des liaisons ADSL';
		$this->rights[7][2] = 'w';
		$this->rights[7][3] = 0;
		$this->rights[7][4] = 'adsl';
		$this->rights[7][5] = 'creer';

		$this->rights[8][0] = 203;
		$this->rights[8][1] = "Demander la commande des liaisons";
		$this->rights[8][2] = 'w';
		$this->rights[8][3] = 0;
		$this->rights[8][4] = 'adsl';
		$this->rights[8][5] = 'requete';

		$this->rights[9][0] = 204;
		$this->rights[9][1] = 'Commander les liaisons';
		$this->rights[9][2] = 'w';
		$this->rights[9][3] = 0;
		$this->rights[9][4] = 'adsl';
		$this->rights[9][5] = 'commander';

		$this->rights[10][0] = 205;
		$this->rights[10][1] = 'Gerer les liaisons';
		$this->rights[10][2] = 'w';
		$this->rights[10][3] = 0;
		$this->rights[10][4] = 'adsl';
		$this->rights[10][5] = 'gerer';
		$r = 10;

		$r++;


		$this->rights[$r][0] = 271;
		$this->rights[$r][1] = 'Consulter le CA';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ca';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 272;
		$this->rights[$r][1] = 'Consulter les factures';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 273;
		$this->rights[$r][1] = 'Emmettre les factures';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'ecrire';
		$r++;

		$this->rights[$r][0] = 206;
		$this->rights[$r][1] = 'Consulter les liaisons';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'adsl';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 231;
		$this->rights[$r][1] = 'Definir le mode de reglement';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'contrat';
		$this->rights[$r][5] = 'paiement';
		$r++;

		$this->rights[$r][0] = 193;
		$this->rights[$r][1] = 'Resilier des lignes';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ligne';
		$this->rights[$r][5] = 'resilier';
		$r++;

		$this->rights[$r][0] = 194;
		$this->rights[$r][1] = 'Consulter la marge des lignes';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ligne';
		$this->rights[$r][5] = 'gain';
		$r++;

		$this->rights[$r][0] = 146;
		$this->rights[$r][1] = 'Consulter les fournisseurs';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'fournisseur';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 147;
		$this->rights[$r][1] = 'Consulter les stats';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'stats';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 311;
		$this->rights[$r][1] = 'Consulter les services';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'service';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 312;
		$this->rights[$r][1] = 'Affecter des services a un contrat';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'service';
		$this->rights[$r][5] = 'affecter';
		$r++;

		$this->rights[$r][0] = 291;
		$this->rights[$r][1] = 'Consulter les tarifs';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'tarifs';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 292;
		$this->rights[$r][1] = 'Definir les permissions sur les tarifs';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'tarif';
		$this->rights[$r][5] = 'permission';
		$r++;

		$this->rights[$r][0] = 293;
		$this->rights[$r][1] = 'Modifier les tarifs clients';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'tarif';
		$this->rights[$r][5] = 'client_modifier';
		$r++;
	}

	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		//
		$this->load_tables();
		//
		return $this->_init($sql);
	}

	/**
	 \brief      Fonction appelee lors de la desactivation d'un module.
	 Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}

	/**
	*		\brief		Create tables and keys required by module
	* 					Files mymodule.sql and mymodule.key.sql with create table and create keys
	* 					commands must be stored in directory /mymodule/sql/.
	*					This function is called by this->init.
	* 		\return		int		<=0 if KO, >0 if OK
	*/
	function load_tables()
	{
		return $this->_load_tables('/telephonie/sql/');
	}
}
?>
