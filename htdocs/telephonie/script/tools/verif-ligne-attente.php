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
 * Vérifie les lignes ayant le statut d'attente
 *
 */
require ("../../../master.inc.php");
/*
 *
 */
$users = array();
$sqll  = "SELECT rowid,name,firstname,email";
$sqll .= " FROM ".MAIN_DB_PREFIX."user";

$resqll = $db->query($sqll) ;
if ( $resqll )
{
  while ($row = $db->fetch_row($resqll))
    {
      $users[$row[0]] = $row;
    }
}

else
{
  print $db->error();
}

$sqll  = "SELECT l.datec, l.ligne, l.fk_commercial_sign, l.rowid";
$sqll .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sqll .= " WHERE statut = -1";
$sqll .= " AND l.datec + INTERVAL 1 MONTH < now();";

$resqll = $db->query($sqll);
if ( $resqll )
{
  while ($row = $db->fetch_row($resqll))
    {

      $sqlc  = "SELECT counter";
      $sqlc .= " FROM ".MAIN_DB_PREFIX."telephonie_alertecounter";
      $sqlc .= " WHERE fk_ligne = ".$row[3];
      $sqlc .= " AND fk_user = ".$row[2];
      
      $resqlc = $db->query($sqlc);
      if ( $resqlc )
	{
	  if ($rowc = $db->fetch_row($resqlc))
	    {
	      $count = $rowc[0];
	      $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_alertecounter";
	      $sql .= " SET counter=counter+1 WHERE fk_ligne=".$row[3];
	      $sql .= " AND fk_user=".$row[2].";";
	      $db->query($sql);
	    }
	  else
	    {
	      $count = 1;
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_alertecounter";
	      $sql .= " (fk_ligne,fk_user,counter)";
	      $sql .= " VALUES (".$row[3].",".$row[2].",1);";
	      $db->query($sql);
	    }
	}

      $mesg = "Bonjour,\n\n";
      $mesg .= "Alerte Ligne ".$row[1]." en attente\n";
      $mesg .= "Depuis le ".$row[0]."\n";
      $mesg .= "Envoi # ".$count."\n\n";
      $mesg .= "--\nmessage automatique en provenance de dolibarr";

      $headers = 'From: metac@NE_PAS_REPONDRE.com' . "\r\n" .
	'Reply-To: '.$users[$row[2]][3]. "\r\n" .
	'X-Mailer: Dolibarr';
      
      $to = $users[$row[2]][3];
      $subject = "Alerte ligne en attente";
      
      mail($to,$subject,$mesg, $headers);
    }
}

else
{
  print $db->error();
}



$db->close();
?>
