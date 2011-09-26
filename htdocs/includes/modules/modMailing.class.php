<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   mailing  Module emailing
 *	\brief      Module to manage EMailings
 *	\file       htdocs/includes/modules/modMailing.class.php
 *	\ingroup    mailing
 *	\brief      Fichier de description et activation du module Mailing
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *	\class      modMailing
 *	\brief      Classe de description et activation du module Mailing
 */
class modMailing extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
	 */
	function modMailing($DB)
	{
		$this->db = $DB ;
		$this->numero = 22 ;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des EMailings";
		$this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='email';

		// Data directories to create when module is enabled
		$this->dirs = array("/mailing/temp");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("mails");

		// Config pages
		$this->config_page_url = array("mailing.php");

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'mailing';

		$this->rights[1][0] = 221; // id de la permission
		$this->rights[1][1] = 'Consulter les mailings'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 222;
		$this->rights[2][1] = 'Creer/modifier les mailings (sujet, destinataires...)';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 223;
		$this->rights[3][1] = 'Valider les mailings (permet leur envoi)';
		$this->rights[3][2] = 'w';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'valider';

		$this->rights[4][0] = 229;
		$this->rights[4][1] = 'Supprimer les mailings)';
		$this->rights[4][2] = 'd';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'supprimer';

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		// Permissions
		$this->remove();

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
}
?>
