<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Envois la liste des factures impayées aux commerciaux
 *
 */

require ("../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php");

$error = 0;

$sql = "SELECT f.facnumber, f.total_ttc, s.nom, u.name, u.firstname, u.email";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE f.paye = 0";
$sql .= " AND f.fk_soc = s.idp";
$sql .= " AND sc.fk_soc = s.idp";
$sql .= " AND sc.fk_user = u.rowid";
$sql .= " ORDER BY u.email ASC, s.idp ASC";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  $i = 0;
  $oldemail = '';
  $message = '';
  $total = '';
  dolibarr_syslog("factures-impayees-commerciaux: ");
  
  while ($i < $num)
    {
      $obj = $db->fetch_object();

      if ($obj->email <> $oldemail)
	{

	  if (strlen($oldemail))
	    {
	      dolibarr_syslog("factures-impayees-commerciaux: send mail to $oldemail");
	      $subject = "[Dolibarr] Liste des factures impayées";
	      $sendto = $oldemail;
	      $from = $oldemail;

	      $allmessage = "Liste des factures impayées à ce jour\n";
	      $allmessage .= "Cette liste ne comporte que les factures des sociétés dont vous êtes référencés comme commercial.\n";
	      $allmessage .= "\n";
	      $allmessage .= $message;
	      $allmessage .= "\n";
	      $allmessage .= "Total = ".price($total)."\n";

	      $mail = new DolibarrMail($subject,
				       $sendto,
				       $from,
				       $allmessage);
	  
	      $mail->errors_to = $errorsto;                 
	      
	      if ( $mail->sendfile() )
		{
		  
		}
	      

	      
	    }
	  $oldemail = $obj->email;
	  $message = '';
	  $total = 0;
	}


      $message .= "Facture ".$obj->facnumber." : ".price($obj->total_ttc)." : ".$obj->nom."\n";
      $total += $obj->total_ttc;

      dolibarr_syslog("factures-impayees-commerciaux: ".$obj->email);
      $i++;      
    }

  /* On répète le code c'est mal */
  dolibarr_syslog("factures-impayees-commerciaux: send mail to $oldemail");
  $subject = "[Dolibarr] Liste des factures impayées";
  $sendto = $oldemail;
  $from = $oldemail;
  
  $allmessage = "Liste des factures impayées à ce jour\n";
  $allmessage .= "Cette liste ne comporte que les factures des sociétés dont vous êtes référencés comme commercial.\n";
  $allmessage .= "\n";
  $allmessage .= $message;
  $allmessage .= "\n";
  $allmessage .= "Total = ".price($total)."\n";
  
  $mail = new DolibarrMail($subject,
			   $sendto,
			   $from,
			   $allmessage);
  
  $mail->errors_to = $errorsto;                 
  
  if ( $mail->sendfile() )
    {
      
    }
}
else
{
  dolibarr_syslog("factures-impayees-commerciaux: Error");
}

?>
