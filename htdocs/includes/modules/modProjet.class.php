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

class modProjet extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modProjet($DB)
  {
    $this->db = $DB ;
    $this->numero = 60 ;
    $this->name = "Projets";
    $this->description = "Gestion des projets";
    $this->const_name = "MAIN_MODULE_PROJET";
    $this->const_config = MAIN_MODULE_PROJET;

    $this->depends = array();
    $this->const = array();
    $this->boxes = array();
  }
  /*
   *
   *
   *
   */

  Function init()
  {
    /*
     *  Activation du module
     */
    /*
     * Permissions
     */
    $sql = array(
		 "insert into ".MAIN_DB_PREFIX."rights_def values (40,'Tous les droits sur les projets','projet','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (41,'Lire les projets','projet','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (42,'Créer modifier les projets','projet','w',0);",
		 //"insert into ".MAIN_DB_PREFIX."rights_def values (43,'Modifier les projets d\'autrui','projet','m',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (44,'Supprimer les projets','projet','d',0);"
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'projet';");

    return $this->_remove($sql);
  }
}
?>
