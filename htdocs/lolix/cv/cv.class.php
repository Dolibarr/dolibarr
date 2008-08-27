<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Cv {
  var $db;
  var $id;
 
  Function Cv($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;

    return 1;
  }

  /*
   *
   *
   */
  Function fetch()
    {
      $sql = "SELECT c.idp, c.nom, c.prenom, c.email";
      $sql .= " FROM lolixfr.candidat as c";
      $sql .= " WHERE c.idp = ".$this->id;

      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);

	      $this->id = $obj->idp;
	      $this->active = $obj->active;
	      $this->date_activation = $obj->da;
	      $this->nom    = stripslashes($obj->nom);
	      $this->prenom = stripslashes($obj->prenom);
	      $this->email  = stripslashes($obj->email);

	      return 1;
	      
	    }
	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	}
  }
  

  Function deactivate()
  {
    $sql = "UPDATE lolixfr.candidat SET active=0,deacmeth='b',pubkey='$digest' WHERE idp=" . $this->id;    
    $result = $this->db->query($sql);
    
    $sql = "INSERT INTO lolixfr.res_statutlog (datel, fk_cand, fk_statut,author)";
    $sql .= " VALUES (".$this->db->idate(mktime()).",$this->id,0,'bots')";
    $result = $this->db->query($sql);
  
    $header = "From: webmaster@lolix.org\r\nReply-To: webmaster@lolix.org\r\nX-Mailer: Dolibarr";

    $email = $this->email;

    $message = '  Bonjour,

   Le CV que vous avez déposé sur http://fr.lolix.org est arrivé à expiration, 
celui-ci n\'est plus consultable en ligne, nous vous invitons à le réactiver si
vous êtes toujours à la recherche d\'un emploi.


Cordialement,

---
L\'équipe Lolix - http://fr.lolix.org/
Le guide des prestataires logiciels libres - http://www.support-libre.com
';


    mail($email, "Desactivation de votre CV sur Lolix", $message, $header);

    print "mail sent to : $email";

  }  
}

?>
