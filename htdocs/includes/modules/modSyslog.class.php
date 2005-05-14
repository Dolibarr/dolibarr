<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
   \defgroup   syslog  Module syslog
   \brief      Module pour gérer les messages d'erreur dans syslog
*/

/**
   \file       htdocs/includes/modules/modSyslog.class.php
   \ingroup    syslog
   \brief      Fichier de description et activation du module de syslog
*/

include_once "DolibarrModules.class.php";

/**
   \class      modSyslog
   \brief      Classe de description et activation du module Syslog
*/

class modSyslog extends DolibarrModules
{
  
  /**
   *   \brief      Constructeur. Definit les noms, constantes et boites
   *   \param      DB      handler d'accès base
   */
  function modSyslog($DB)
  {
    $this->db = $DB ;
    $this->numero = 42 ;
    
    $this->family = "technic";
    $this->name = "Syslog";
    $this->description = "Activation des traces debug (syslog)";
    $this->version = '2.0.0';    // 'experimental' or 'dolibarr' or version
    $this->const_name = "MAIN_MODULE_SYSLOG";
    $this->const_config = MAIN_MODULE_SYSLOG;
    $this->special = 0;
    //$this->picto='phoning';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'syslog';

    $this->rights[0][0] = 1001;
    $this->rights[0][1] = 'Lire les stocks';
    $this->rights[0][2] = 'r';
    $this->rights[0][3] = 1;
    $this->rights[0][4] = 'lire';
    $this->rights[0][5] = '';

    $this->rights[1][0] = 1002;
    $this->rights[1][1] = 'Créer/Modifier les stocks';
    $this->rights[1][2] = 'w';
    $this->rights[1][3] = 0;
    $this->rights[1][4] = 'creer';
    $this->rights[1][5] = '';

    $this->rights[2][0] = 1003;
    $this->rights[2][1] = 'Supprimer les stocks';
    $this->rights[2][2] = 'd';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'supprimer';
    $this->rights[2][5] = '';

    $this->rights[3][0] = 1004;
    $this->rights[3][1] = 'Lire mouvements de stocks';
    $this->rights[3][2] = 'r';
    $this->rights[3][3] = 1;
    $this->rights[3][4] = 'mouvement';
    $this->rights[3][5] = 'lire';

    $this->rights[4][0] = 1005;
    $this->rights[4][1] = 'Créer/modifier mouvements de stocks';
    $this->rights[4][2] = 'w';
    $this->rights[4][3] = 0;
    $this->rights[4][4] = 'mouvement';
    $this->rights[4][5] = 'creer';

  }


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    $sql = array();
    
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
