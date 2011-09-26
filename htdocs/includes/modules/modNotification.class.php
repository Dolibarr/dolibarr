<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\defgroup   notification	Module email notification
 *	\brief      Module pour gerer les notifications (par mail ou autre)
 *	\file       htdocs/includes/modules/modNotification.class.php
 *	\ingroup    notification
 *	\brief      Fichier de description et activation du module Notification
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *	\class      modNotification
 *	\brief      Classe de description et activation du module Mailing
 */
class modNotification extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
	 */
	function modNotification($DB)
	{
		$this->db = $DB ;
		$this->numero = 600;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des notifications (par mail) sur evenement Dolibarr";
		$this->version = 'dolibarr';	// 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;
		$this->picto='email';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("mails");

		// Config pages
		$this->config_page_url = array("notification.php");

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'notification';
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
