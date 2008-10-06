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

/**
 \defgroup   ficheinter     Module intervention cards
 \brief      Module to manage intervention cards
 \version	$Id$
 */

/**
 \file       htdocs/includes/modules/modFicheinter.class.php
 \ingroup    ficheinter
 \brief      Fichier de description et activation du module Ficheinter
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modFicheinter
 \brief      Classe de description et activation du module Ficheinter
 */

class modFicheinter  extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modFicheinter($DB)
	{
		$this->db = $DB ;
		$this->numero = 70 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des fiches d'intervention";

		$this->revision = explode(" ","$Revision$");
		$this->version = $this->revision[1];

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "intervention";

		// Dir
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array("fichinter.php");

		// D�pendances
		$this->depends = array("modSociete","modCommercial");
		$this->requiredby = array();

		// Constantes
		$this->const = array();
		$r=0;
		 
		$this->const[$r][0] = "FICHEINTER_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "soleil";
		$r++;

		$this->const[$r][0] = "FICHEINTER_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "pacific";
		$r++;

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'ficheinter';

		$this->rights[1][0] = 61;
		$this->rights[1][1] = 'Lire les fiches d\'intervention';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 62;
		$this->rights[2][1] = 'Creer/modifier les fiches d\'intervention';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 64;
		$this->rights[3][1] = 'Supprimer les fiches d\'intervention';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';

	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		// Dir
		$this->dirs[0] = $conf->facture->dir_output;

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."'",
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES('".$this->const[0][2]."','ficheinter')",
		);

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
