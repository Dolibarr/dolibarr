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

include_once "modDolibarrModules.class.php";

class modCommande extends modDolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modCommande($DB)
  {
    $this->db = $DB ;
    $this->const = array();
    $this->boxes = array();

    $this->boxes[0][0] = "Commandes";
    $this->boxes[0][1] = "box_commandes.php";
  }
  /*
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
		 "insert into llx_rights_def values (80,'Tous les droits sur les commandes','commande','a',0);",
		 "insert into llx_rights_def values (81,'Lire les commandes','commande','r',1);",
		 "insert into llx_rights_def values (82,'Créer modifier les commandes','commande','w',0);",
		 //"insert into llx_rights_def values (83,'Modifier les commandes d\'autrui','commande','m',0);",
		 "insert into llx_rights_def values (84,'Valider les commandes','commande','d',0);",
		 "insert into llx_rights_def values (85,'Envoyer les commandes aux clients','commande','d',0);",
		 "insert into llx_rights_def values (86,'Emettre des paiements sur les commandes','commande','d',0);",
		 "insert into llx_rights_def values (89,'Supprimer les commandes','commande','d',0);",
		 );
    
    return $this->_init($sql);
  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array(

		 );

    return $this->_remove($sql);

  }
}
?>
