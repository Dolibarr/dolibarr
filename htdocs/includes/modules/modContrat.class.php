<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!     \defgroup   contrat     Module contrat
        \brief      Module pour gérer la tenue de contrat de services
*/

/*!
        \file       htdocs/includes/modules/modContrat.class.php
        \ingroup    contrat
        \brief      Fichier de description et activation du module Contrat
*/

include_once "DolibarrModules.class.php";

/*! \class modContrat
        \brief      Classe de description et activation du module Contrat
*/

class modContrat extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  function modcontrat($DB)
  {
    $this->db = $DB ;
    $this->numero = 54 ;
    
    $this->family = "crm";
    $this->name = "Contrats";
    $this->description = "Gestion des contrats de services";
    $this->const_name = "MAIN_MODULE_CONTRAT";
    $this->const_config = MAIN_MODULE_CONTRAT;

    // Dépendances
    $this->depends = array("modService");
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
     * Permissions
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
    $sql = array(

		 );

    return $this->_remove($sql);

  }
}
?>
