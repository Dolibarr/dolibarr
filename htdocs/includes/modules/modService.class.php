<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
 \defgroup   service     Module service
 \brief      Module pour g�rer le suivi de services pr�d�finis
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
	 *   \param      DB      handler d'acc�s base
	 */
	function modService($DB)
	{
		$this->db = $DB ;
		$this->numero = 53 ;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des services";

		$this->revision = explode(" ","$Revision$");
		$this->version = $this->revision[1];

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='service';

		// D�pendances
		$this->depends = array("modProduit");
		$this->requiredby = array("modContrat");

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();
		$this->boxes[0][1] = "box_services_vendus.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'service';

		/* Pour l'instant droits sur services non g�r�s
		 $this->rights[1][0] = 331; // id de la permission
		 $this->rights[1][1] = 'Lire les services'; // libelle de la permission
		 $this->rights[1][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		 $this->rights[1][3] = 1; // La permission est-elle une permission par d�faut
		 $this->rights[1][4] = 'lire';

		 $this->rights[2][0] = 332; // id de la permission
		 $this->rights[2][1] = 'Cr�er/modifier les services'; // libelle de la permission
		 $this->rights[2][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		 $this->rights[2][3] = 0; // La permission est-elle une permission par d�faut
		 $this->rights[2][4] = 'creer';

		 $this->rights[3][0] = 333; // id de la permission
		 $this->rights[3][1] = 'Commander un service'; // libelle de la permission
		 $this->rights[3][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		 $this->rights[3][3] = 0; // La permission est-elle une permission par d�faut
		 $this->rights[3][4] = 'commander';

		 $this->rights[4][0] = 334; // id de la permission
		 $this->rights[4][1] = 'Supprimer les services'; // libelle de la permission
		 $this->rights[4][2] = 'd'; // type de la permission (d�pr�ci� � ce jour)
		 $this->rights[4][3] = 0; // La permission est-elle une permission par d�faut
		 $this->rights[4][4] = 'supprimer';
		 */

	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		// Permissions et valeurs par d�faut
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}
}
?>
