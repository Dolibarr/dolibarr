<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Retourbplc
{
  var $conf;
  var $db;

  var $ipclient;
  var $num_transaction;
  var $date_transaction;
  var $heure_transaction;
  var $num_autorisation;
  var $cle_acceptation;
  var $code_retour;

  var $ref_commande;
  /*
   *   Initialisation des valeurs par défaut
   */
  Function Retourbplc($db, $conf) 
  {
    $this->db = $db;
  }
  /*
   *
   *
   *
   */
  Function insertdb()
  {

    $sql = "INSERT INTO transaction_bplc";
    $sql .= " (  ipclient, num_transaction, date_transaction, heure_transaction, num_autorisation, cle_acceptation, code_retour, ref_commande)";
    $sql .= " VALUES ('$this->ipclient','$this->num_transaction','$this->date_transaction','$this->heure_transaction','$this->num_autorisation','$this->cle_acceptation','$this->code_retour','$this->ref_commande')";

    $result = $this->db->query($sql);
      
    if ($result) 
      {
	return 1;
      }
    else
      {
	print $this->db->error();
	print "<h2><br>$sql<br></h2>";
	return 0;
      }         
  }


  /*
   *
   *
   *
   */

}
?>
