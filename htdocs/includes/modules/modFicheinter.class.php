<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier			 <benoit.mortier@opensides.be>
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

/*!     \defgroup   ficheinter     Module fiche interventions
        \brief      Module pour gérer la tenue de fiches d'interventions
*/

/*!
        \file       htdocs/includes/modules/modFicheinter.class.php
        \ingroup    ficheinter
        \brief      Fichier de description et activation du module Ficheinter
*/

include_once "DolibarrModules.class.php";

/*! \class modFicheinter
		\brief      Classe de description et activation du module Ficheinter
*/

class modFicheinter  extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modFicheinter($DB)
  {
    $this->db = $DB ;
    $this->numero = 70 ;
    
    $this->family = "crm";
    $this->name = "Fiche d'intervention";
    $this->description = "Gestion des fiches d'intervention";
    $this->const_name = "MAIN_MODULE_FICHEINTER";
    $this->const_config = MAIN_MODULE_FICHEINTER;

    // Config pages
    $this->config_page_url = "fichinter.php";

    // Dépendances
    $this->depends = array("modSociete");
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
  }
  /*
   *
   *
   *
   */

  function init()
  {
    /*
     *  Activation du module
     */

    /*
     * Permissions
     */
		 $this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (60,'Tous les droits sur les fiches d\'intervention','ficheinter','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (61,'Lire les fiches d\'intervention','ficheinter','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (62,'Créer modifier les fiches d\'intervention','ficheinter','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (64,'Supprimer les fiches d\'intervention','ficheinter','d',0);"
		 );

    return $this->_init($sql);
  }
  /*
   *
   *
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'ficheinter';");

    return $this->_remove($sql);
  }
}
?>
