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

class modPropale
{

  /*
   * Initialisation
   *
   */

  Function modPropale($DB)
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
    $const[0][0] = "PROPALE_ADDON_PDF";
    $const[0][1] = "chaine";
    $const[0][2] = "rouge";

    foreach ($const as $key => $value)
      {
	$name = $const[$key][0];
	$type = $const[$key][1];
	$val  = $const[$key][2];

	$sql = "SELECT count(*) FROM llx_const WHERE name ='".$name."'";

	if ( $this->db->query($sql) )
	  {
	    $row = $this->db->fetch_row($sql);
	    
	    if ($row[0] == 0)
	      {
		if (strlen($val))
		  {
		    $sql = "INSERT INTO llx_const (name,type,value) VALUES ('".$name."','".$type."','".$val."')";
		  }
		else
		  {
		    $sql = "INSERT INTO llx_const (name,type) VALUES ('".$name."','".$type."')";
		  }

		if ( $this->db->query($sql) )
		  {

		  }
	      }
	  }
      }

       
    /*
     * Permissions et valeurs par défaut
     */
    $sql = array(
		 "insert into llx_rights_def values (20,'Tous les droits sur les propositions commerciales','propale','a',0);",
		 "insert into llx_rights_def values (21,'Lire les propositions commerciales','propale','r',1);",
		 "insert into llx_rights_def values (22,'Créer modifier les propositions commerciales','propale','w',0);",
		 "insert into llx_rights_def values (24,'Valider les propositions commerciales','propale','d',0);",
		 "insert into llx_rights_def values (25,'Envoyer les propositions commerciales aux clients','propale','d',0);",
		 "insert into llx_rights_def values (26,'Clôturer les propositions commerciales','propale','d',0);",
		 "insert into llx_rights_def values (27,'Supprimer les propositions commerciales','propale','d',0);",
		 "INSERT INTO llx_boxes_def (name,file) VALUES('Proposition commerciales', 'box_propales.php');",
		 "REPLACE INTO llx_propal_model_pdf SET nom = '".$const[0][2]."'"
		 );
    //"insert into llx_rights_def values (23,'Modifier les propositions commerciales d\'autrui','propale','m',0);",
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
		 "DELETE FROM llx_boxes_def WHERE file = 'box_propales.php';",
		 "DELETE FROM llx_rights_def WHERE module = 'propale';"
		 );

    for ($i = 0 ; $i < sizeof($sql) ; $i++)
      {
	$this->db->query($sql[$i]);
      }

  }
}
?>
