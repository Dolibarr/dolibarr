<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
		\defgroup   mailing  Module mailing
		\brief      Module pour gérer les mailings
*/

/**
		\file       htdocs/includes/modules/modMailing.class.php
		\ingroup    mailing
		\brief      Fichier de description et activation du module Mailing
*/

include_once "DolibarrModules.class.php";

/**
		\class      modMailing
		\brief      Classe de description et activation du module Mailing
*/

class modMailing extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modMailing($DB)
  {
    $this->db = $DB ;
    $this->id = 'mailing';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 22 ;

    $this->family = "technic";
    $this->name = "EMailings";
    $this->description = "Gestion des EMailings";
    $this->version = 'dolibarr';    // 'experimental' or 'dolibarr' or version
    $this->const_name = 'MAIN_MODULE_MAILING';
    $this->special = 1;
    $this->picto='email';

    // Dir
    $this->dirs = array();

    // Dépendances
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
    $this->rights[1][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[1][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 222;
    $this->rights[2][1] = 'Créer/modifier les mailings (sujet, destinataires...)';
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
