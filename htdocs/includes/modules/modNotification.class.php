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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
		\defgroup   notification	Module notification
		\brief      Module pour gérer les notifications (par mail ou autre)
*/

/**
		\file       htdocs/includes/modules/modNotification.class.php
		\ingroup    notification
		\brief      Fichier de description et activation du module Notification
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
		\class      modMailing
		\brief      Classe de description et activation du module Mailing
*/

class modNotification extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modNotification($DB)
  {
    $this->db = $DB ;
    $this->id = 'notification';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 600;

    $this->family = "technic";
    $this->name = "Notifications";
    $this->description = "Gestion des notifications (par mail) sur évênement Dolibarr";
    $this->version = 'dolibarr';	// 'experimental' or 'dolibarr' or version
    $this->const_name = 'MAIN_MODULE_NOTIFICATION';
    $this->special = 1;
    $this->picto='email';

    // Dir
    $this->dirs = array();

    // Dépendances
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
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();
   
    return $this->_init($sql);
  }

  /**
    \brief      Fonction appelée lors de la désactivation d'un module.
    Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
