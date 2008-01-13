<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.org>
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
        \defgroup   bookmark4u      Module bookmark4u
        \brief      Module pour se synchroniser avec bookmark4u
*/

/**
        \file       htdocs/includes/modules/modBookmark4u.class.php
        \ingroup    bookmark4u
        \brief      Fichier de description et activation du module Bookmark4u
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** 
        \class      modBookmark4u
        \brief      Classe de description et activation du module Bookmark4u
*/

class modBookmark4u extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'acc�s base
    */
  function modBookmark4u($DB)
  {
    $this->db = $DB ;
    $this->numero = 59 ;

    $this->family = "technic";
    $this->name = "Bookmark4u";
    $this->description = "Ajoute fonction pour g�n�rer un compte Bookmark4u depuis un compte Dolibarr";

    $this->version = 'development';		// 'development' or 'experimental' or 'dolibarr' or version

    $this->const_name = 'MAIN_MODULE_BOOKMARK4U';
    $this->special = 1;
    $this->picto='user';

    // Dir
    $this->dirs = array();

    // D�pendances
    $this->depends = array();
    $this->requiredby = array();

    // Config pages
    $this->config_page_url = array();

    // Constantes
    $this->const = array();

    // Boites
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'bookmark4u';
  }

   /**
    *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
    *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
    */
  function init()
  {
    global $conf;

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
