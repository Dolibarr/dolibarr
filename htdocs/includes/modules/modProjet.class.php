<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**  \defgroup   projet     Module projet
 \brief      Module pour inclure le detail par projets dans les autres modules
 */

/** \file       htdocs/includes/modules/modProjet.class.php
 \ingroup    projet
 \brief      Fichier de description et activation du module Projet
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modProjet
 \brief      Classe de description et activation du module Projet
 */

class modProjet extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modProjet($DB)
	{
		$this->db = $DB ;
		$this->numero = 400 ;

		$this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des projets";
		$this->revision = explode(" ","$Revision$");
		$this->version = $this->revision[1];
		$this->const_name = 'MAIN_MODULE_PROJET';
		$this->special = 0;
		$this->picto='email';

		// D�pendances
		$this->depends = array();
		$this->requiredby = array();

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'projet';

		$this->rights[1][0] = 41; // id de la permission
		$this->rights[1][1] = 'Lire les projets'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par d�faut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 42; // id de la permission
		$this->rights[2][1] = 'Cr�er modifier les projets'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 44; // id de la permission
		$this->rights[3][1] = 'Supprimer les projets'; // libelle de la permission
		$this->rights[3][2] = 'd'; // type de la permission (d�pr�ci� � ce jour)
		$this->rights[3][3] = 0; // La permission est-elle une permission par d�faut
		$this->rights[3][4] = 'supprimer';
	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		// Permissions
		$this->remove();

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
