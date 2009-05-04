<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 */

/**
 \defgroup   service     Module service
 \brief      Module pour gerer le suivi de services predefinis
 */

/**
 \file       htdocs/includes/modules/modService.class.php
 \ingroup    service
 \brief      Fichier de description et activation du module Service
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class modService
 \brief      Classe de description et activation du module Service
 */

class modService extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modService($DB)
	{
		$this->db = $DB ;
		$this->numero = 53 ;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des services";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='service';

		// Data directories to create when module is enabled
		$this->dirs = array("/produit/temp");

		// Dependancies
		$this->depends = array("modProduit");
		$this->requiredby = array("modContrat");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();
		$this->boxes[0][1] = "box_services_vendus.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'service';

		/* Pour l'instant droits sur services non geres
		 $this->rights[1][0] = 331; // id de la permission
		 $this->rights[1][1] = 'Lire les services'; // libelle de la permission
		 $this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		 $this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		 $this->rights[1][4] = 'lire';

		 $this->rights[2][0] = 332; // id de la permission
		 $this->rights[2][1] = 'Creer/modifier les services'; // libelle de la permission
		 $this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		 $this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		 $this->rights[2][4] = 'creer';

		 $this->rights[3][0] = 333; // id de la permission
		 $this->rights[3][1] = 'Commander un service'; // libelle de la permission
		 $this->rights[3][2] = 'w'; // type de la permission (deprecie a ce jour)
		 $this->rights[3][3] = 0; // La permission est-elle une permission par defaut
		 $this->rights[3][4] = 'commander';

		 $this->rights[4][0] = 334; // id de la permission
		 $this->rights[4][1] = 'Supprimer les services'; // libelle de la permission
		 $this->rights[4][2] = 'd'; // type de la permission (deprecie a ce jour)
		 $this->rights[4][3] = 0; // La permission est-elle une permission par defaut
		 $this->rights[4][4] = 'supprimer';
		 */

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		// Permissions et valeurs par defaut
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
