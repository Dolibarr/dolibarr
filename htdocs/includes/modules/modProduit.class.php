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

class modProduit
{

  /*
   * Initialisation
   *
   */

  Function modProduit($DB)
  {
    $this->db = $DB ;
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
		 "insert into llx_rights_def values (30,'Tous les droits sur les produits','produit','a',0);",
		 "insert into llx_rights_def values (31,'Lire les produits','produit','r',1);",
		 "insert into llx_rights_def values (32,'Créer modifier les produits','produit','w',0);",
		 "INSERT INTO llx_rights_def values (34,'Supprimer les produits','produit','d',0);",
		 "REPLACE INTO llx_boxes_def (name,file) VALUES('Services vendus', 'box_services_vendus.php');",
		 "REPLACE INTO llx_boxes_def (name,file) VALUES('Derniers produits', 'box_produits.php');"
		 );
    //"insert into llx_rights_def values (33,'Modifier les produits d\'autrui','produit','m',0);",
    
    for ($i = 0 ; $i < sizeof($sql) ; $i++)
      {
	$this->db->query($sql[$i]);
      }
  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array(
		 "DELETE FROM llx_rights_def WHERE module = 'produit';",
		 "DELETE FROM llx_boxes_def WHERE file = 'box_services_vendus.php';",
		 "DELETE FROM llx_boxes_def WHERE file = 'box_produits.php';"
		 );
		 

    for ($i = 0 ; $i < sizeof($sql) ; $i++)
      {
	$this->db->query($sql[$i]);
      }


  }
}
?>
