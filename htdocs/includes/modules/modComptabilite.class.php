<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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

/*!     \defgroup   comptabilite     Module comptabilite
        \brief      Module pour inclure des fonctions de comptabilité (gestion de comptes comptables et rapports)
*/

/*!
        \file       htdocs/includes/modules/modComptabilite.class.php
        \ingroup    comptabilite
        \brief      Fichier de description et activation du module Comptabilite
*/

include_once "DolibarrModules.class.php";

/*! \class modComptabilite
        \brief      Classe de description et activation du module Comptabilite
*/

class modComptabilite extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
   */

  function modComptabilite($DB)
  {
    $this->db = $DB ;
    $this->numero = 10 ;
    
    $this->family = "financial";
    $this->name = "Comptabilite";
    $this->description = "Gestion sommaire de comptabilité";
    $this->const_name = "MAIN_MODULE_COMPTABILITE";
    $this->const_config = MAIN_MODULE_COMPTABILITE;

    // Config pages
	$this->config_page_url = "compta.php";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modFacture");

    $this->const = array();
    $this->boxes = array();
  }
  /** initialisation du module
   *
   *
   *
   */

  function init()
  {
    /*
     * Permissions
     */    
		 $this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (90,'Tous les droits sur la compta','compta','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (91,'Lire les charges','compta','r',1);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (92,'Créer modifier les charges','compta','w',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (93,'Supprimer les charges','compta','d',0);",

		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (95,'Lire CA, bilans, résultats','compta','r',1);",
		 );
    
    return $this->_init($sql);
  }
  /** suppression du module
   *
   *
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'compta';");

    return $this->_remove($sql);
  }
}
?>
