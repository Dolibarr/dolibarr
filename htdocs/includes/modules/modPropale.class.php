<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**     \defgroup   propale     Module propale
 *		\brief      Module pour gerer la tenue de propositions commerciales
 */

/**
 *	\file       htdocs/includes/modules/modPropale.class.php
 *	\ingroup    propale
 *	\brief      Fichier de description et activation du module Propale
 * 	\version	$Id$
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class 		modPropale
 *	\brief      Classe de description et activation du module Propale
 */
class modPropale extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modPropale($DB)
	{
		global $conf;
		
		$this->db = $DB ;
		$this->numero = 20 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des propositions commerciales";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='propal';

		// Data directories to create when module is enabled
		$this->dirs = array();
		$r=0;
		
		$this->dirs[$r][0] = "output";
		$this->dirs[$r][1] = "/propale";
		
		$r++;
		$this->dirs[$r][0] = "temp";
		$this->dirs[$r][1] = "/propale/temp";

		// Dependances
		$this->depends = array("modSociete","modCommercial");
		$this->config_page_url = array("propale.php");

		// Constantes
		$this->const = array();

		$this->const[0][0] = "PROPALE_ADDON_PDF";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "azur";
		$this->const[0][3] = 'Nom du gestionnaire de generation des propales en PDF';
		$this->const[0][4] = 0;

		$this->const[1][0] = "PROPALE_ADDON";
		$this->const[1][1] = "chaine";
		$this->const[1][2] = "mod_propale_marbre";
		$this->const[1][3] = 'Nom du gestionnaire de numerotation des propales';
		$this->const[1][4] = 0;

		// Boxes
		$this->boxes = array();
		$this->boxes[0][1] = "box_propales.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'propale';

		$this->rights[1][0] = 21; // id de la permission
		$this->rights[1][1] = 'Lire les propositions commerciales'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 22; // id de la permission
		$this->rights[2][1] = 'Creer/modifier les propositions commerciales'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 24; // id de la permission
		$this->rights[3][1] = 'Valider les propositions commerciales'; // libelle de la permission
		$this->rights[3][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[3][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[3][4] = 'valider';

		$this->rights[4][0] = 25; // id de la permission
		$this->rights[4][1] = 'Envoyer les propositions commerciales aux clients'; // libelle de la permission
		$this->rights[4][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[4][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[4][4] = 'envoyer';

		$this->rights[5][0] = 26; // id de la permission
		$this->rights[5][1] = 'Cloturer les propositions commerciales'; // libelle de la permission
		$this->rights[5][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[5][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[5][4] = 'cloturer';

		$this->rights[6][0] = 27; // id de la permission
		$this->rights[6][1] = 'Supprimer les propositions commerciales'; // libelle de la permission
		$this->rights[6][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[6][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[6][4] = 'supprimer';

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;
		// Permissions et valeurs par defaut
		$this->remove();

		$sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
		 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','propal',".$conf->entity.")",
		);

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
