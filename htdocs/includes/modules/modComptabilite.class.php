<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

include_once "DolibarrModules.class.php";

class modComptabilite extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
   */

  Function modComptabilite($DB)
  {
    $this->nom = "Module comptabilité";
    $this->numero = 10 ;
    $this->db = $DB ;
    $this->depends = array();

    $this->name = "Comptabilite";
    $this->description = "Gestion sommaire de comptabilité";
    $this->const_name = "MAIN_MODULE_COMPTABILITE";
    $this->const_config = MAIN_MODULE_COMPTABILITE;

    $this->const = array();
    $this->boxes = array();
  }
  /** initialisation du module
   *
   *
   *
   */

  Function init()
  {
    /*
     * Permissions
     */    
    $sql = array(
		 "insert into llx_rights_def values (92,'Gestion charges','compta','a',1);",
		 "insert into llx_rights_def values (93,'Gestion resultat','compta','a',1);",		 
		 );
    
    return $this->_init($sql);
  }
  /** suppression du module
   *
   *
   */
  Function remove()
  {
    $sql = array("DELETE FROM llx_rights_def WHERE module = 'compta';");

    return $this->_remove($sql);
  }
}
?>
