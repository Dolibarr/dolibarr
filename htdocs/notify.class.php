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

class Notify 
{
  var $id;
  var $db;
  var $socidp;
  var $author;
  var $ref;
  var $date;
  var $duree;
  var $note;
  var $projet_id;

  Function Notify($DB)
    {
      $this->db = $DB ;
      include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php3");
    }

  /*
   *
   *
   *
   */
  Function send($action, $socid, $texte, $file="")
    {
      $sql = "SELECT s.nom, c.email, c.idp, c.name, c.firstname, a.titre,n.rowid";
      $sql .= " FROM llx_socpeople as c, llx_action_def as a, llx_notify_def as n, llx_societe as s";
      $sql .= " WHERE n.fk_contact = c.idp AND a.rowid = n.fk_action";
      $sql .= " AND n.fk_soc = s.idp AND n.fk_action = ".$action;
      $sql .= " AND s.idp = ".$socid;

      $result = $this->db->query($sql);
      if ($result)
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object( $i);

	      $sendto = $obj->firstname . " " . $obj->name . " <".$obj->email.">";

	      if (strlen($sendto))
		{	  
		  $subject = "Notification Dolibarr";
		  $message = $texte;
		  $filename = $file;
		  $replyto = MAIN_MAIL_FROM;
	  
		  $mailfile = new CMailFile($subject,
					    $sendto,
					    $replyto,
					    $message,
					    $file, "application/pdf", $filename);
	  
		  if ( $mailfile->sendfile() )
		    {
		      $sendto = htmlentities($sendto);
	      
		      $sql = "INSERT INTO llx_actioncomm (datea,fk_action,fk_soc,note,fk_facture, fk_contact,fk_user_author, label) VALUES (now(), 9 ,$fac->socidp ,'Envoyée à $sendto',$fac->id, $sendtoid, $user->id, 'Envoi Facture par mail');";
		      /*
		      if (! $this->db->query($sql) )
			{
			  print $this->db->error();
			  print "<p>$sql</p>";
			}     
		      */
		    }
		  else
		    {
		      print "envoi failed";
		    }
		}
	      $i++;
	    }
	}
      else
	{
	  print $this->db->error();
	}
      /*
       *  Insertion dans la base de la trace
       */

    }

}    
?>
