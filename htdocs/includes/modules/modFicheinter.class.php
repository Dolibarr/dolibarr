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

class modFicheinter
{

  /*
   * Initialisation
   *
   */

  Function modFicheinter($DB)
  {
    $this->db = $DB ;
    $this->depends = array("MAIN_MODULE_SOCIETE");
    $this->config_page_url = "fichinter.php";
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
		 "INSERT INTO llx_rights_def VALUES (60,'Tous les droits sur les fiches d\'intervention','ficheinter','a',0);",
		 "INSERT INTO llx_rights_def VALUES (61,'Lire les fiches d\'intervention','ficheinter','r',1);",
		 "INSERT INTO llx_rights_def VALUES (62,'Créer modifier les fiches d\'intervention','ficheinter','w',0);",
		 "INSERT INTO llx_rights_def VALUES (64,'Supprimer les fiches d\'intervention','ficheinter','d',0);"
		 );
    //"INSERT INTO llx_rights_def VALUES (63,'Modifier les fiches d\'intervention d\'autrui','ficheinter','m',0);",
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
    $sql = "DELETE FROM llx_rights_def WHERE module = 'ficheinter';";
    $this->db->query($sql);

  }
}
?>
