<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   webcalendar     Module webcalendar
        \brief      Module pour inclure webcalendar dans Dolibarr et
                    intégrer les évênement Dolibarr directement dans le calendrier
*/

/*!
        \file       htdocs/includes/modules/modWebcalendar.class.php
        \ingroup    webcalendar
        \brief      Fichier de description et activation du module Webcalendar
*/

include_once "DolibarrModules.class.php";

/*! \class modWebcalendar
    \brief      Classe de description et activation du module Webcalendar
*/

class modWebcalendar extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modWebcalendar($DB)
  {
    $this->db = $DB ;
    $this->numero = 410 ;

    $this->family = "projects";
    $this->name = "Webcalendar";
    $this->description = "Gestion de l'outil Webcalendar";
    $this->const_name = "MAIN_MODULE_WEBCALENDAR";
    $this->const_config = MAIN_MODULE_WEBCALENDAR;

    // Config pages
    $this->config_page_url = "webcalendar.php";

    // Dépendences
    $this->depends = array();
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

    $sql = array(
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
