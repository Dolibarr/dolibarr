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

class modPropale extends DolibarrModules
{

  /*
   * Initialisation
   *
   */

  Function modPropale($DB)
  {
    $this->db = $DB ;
    $this->numero = 20 ;
    $this->name = "Propositions commerciales";
    $this->description = "Gestion des propositions commerciales";
    $this->const_name = "MAIN_MODULE_PROPALE";
    $this->const_config = MAIN_MODULE_PROPALE;

    $this->depends = array("modSociete","modCommercial");
    $this->config_page_url = "propale.php";

    $this->const = array();
    $this->boxes = array();
    /*
     *  Constantes
     */
    $this->const[0][0] = "PROPALE_ADDON_PDF";
    $this->const[0][1] = "chaine";
    $this->const[0][2] = "rouge";

    $this->const[1][0] = "PROPALE_ADDON";
    $this->const[1][1] = "chaine";
    $this->const[1][2] = "mod_propale_ivoire";
    /*
     * Boites
     */
    $this->boxes[0][0] = "Proposition commerciales";
    $this->boxes[0][1] = "box_propales.php";
  }
  /*
   *
   *
   *
   */

  Function init()
  {       
    /*
     * Permissions et valeurs par défaut
     */
    $sql = array(
		 "insert into ".MAIN_DB_PREFIX."rights_def values (20,'Tous les droits sur les propositions commerciales','propale','a',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (21,'Lire les propositions commerciales','propale','r',1);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (22,'Créer modifier les propositions commerciales','propale','w',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (24,'Valider les propositions commerciales','propale','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (25,'Envoyer les propositions commerciales aux clients','propale','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (26,'Clôturer les propositions commerciales','propale','d',0);",
		 "insert into ".MAIN_DB_PREFIX."rights_def values (27,'Supprimer les propositions commerciales','propale','d',0);",
		 "REPLACE INTO ".MAIN_DB_PREFIX."propal_model_pdf SET nom = '".$this->const[0][2]."'",
		 );
    //"insert into ".MAIN_DB_PREFIX."rights_def values (23,'Modifier les propositions commerciales d\'autrui','propale','m',0);",
    
    return $this->_init($sql);

  }
  /*
   *
   *
   */
  Function remove()
  {
    $sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'propale';"
		 );

    return $this->_remove($sql);

  }
}
?>
