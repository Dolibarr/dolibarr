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

class modFacture
{

  /*
   * Initialisation
   *
   */

  Function modFacture($DB)
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
    $const[0][0] = "FAC_PDF_INTITULE";
    $const[0][1] = "chaine";
    $const[1][0] = "FAC_PDF_ADRESSE";
    $const[1][1] = "texte";
    $const[2][0] = "FAC_PDF_TEL";
    $const[2][1] = "chaine";
    $const[3][0] = "FAC_PDF_FAX";
    $const[3][1] = "chaine";
    $const[4][0] = "FAC_PDF_SIREN";
    $const[4][1] = "chaine";
    $const[5][0] = "FAC_PDF_SIRET";
    $const[5][1] = "chaine";
    $const[6][0] = "FAC_PDF_INTITULE2";
    $const[6][1] = "chaine";
    $const[7][0] = "FACTURE_ADDON_PDF";
    $const[7][1] = "chaine";
    $const[7][2] = "bulot";

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
     * Permissions
     */
    $sql = array(
		 "insert into llx_rights_def values (10,'Tous les droits sur les factures','facture','a',0);",
		 "insert into llx_rights_def values (11,'Lire les factures','facture','r',1);",
		 "insert into llx_rights_def values (12,'Créer modifier les factures','facture','w',0);",
		 //"insert into llx_rights_def values (13,'Modifier les factures d\'autrui','facture','m',0);",
		 "insert into llx_rights_def values (14,'Valider les factures','facture','d',0);",
		 "insert into llx_rights_def values (15,'Envoyer les factures aux clients','facture','d',0);",
		 "insert into llx_rights_def values (16,'Emettre des paiements sur les factures','facture','d',0);",
		 "insert into llx_rights_def values (19,'Supprimer les factures','facture','d',0);"
		 );
    
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
    $sql = "DELETE FROM llx_rights_def WHERE module = 'facture';";
    $this->db->query($sql);
  }
}
?>
