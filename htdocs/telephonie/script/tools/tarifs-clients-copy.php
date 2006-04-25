<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Ce script se veut plus un squelette pour effectuer des opérations sur la base
 * qu'un réel scrip de production.
 *
 * Recalcul le montant d'une facture lors d'une erreur de tarif
 *
 */

require ("../../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");

$from = 0;
$to = 0;

//loop through our arguments and see what the user selected
for ($i = 1; $i < sizeof($GLOBALS["argv"]); $i++)
{
  switch($GLOBALS["argv"][$i])
    {
    case "--from":
      $from = $GLOBALS["argv"][($i+1)];
      break;
    case "--to":
      $to = $GLOBALS["argv"][($i+1)];
      break;
    }
}

if (($from * $to) == 0)
{
  print "usage --from FROM --to TO\n";
  exit;
}


$sql = "SELECT fk_tarif,temporel,fixe,fk_user FROM ".MAIN_DB_PREFIX."telephonie_tarif_client";
$sql .= " WHERE fk_client = ".$from;

$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  print "$num tarifs trouvés\n";

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $error = 0;

      $db->begin();
  
      $sqlr = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_client";
      $sqlr .= " (fk_tarif, fk_client, temporel, fixe, fk_user) VALUES ";
      $sqlr .= " (".$row[0].",".$to.",'".$row[1]."','".$row[2]."','".$row[3]."')";
      
      if (! $db->query($sqlr) )
	{
	  $error++;
	  print $db->error();
	}
      
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_client_log";
      $sqli .= " (fk_tarif, fk_client, temporel, fixe, fk_user, datec) VALUES ";
      $sqli .= " (".$row[0].",".$to.",'".$row[1]."','".$row[2]."','".$row[3]."',now())";
      
      if (! $db->query($sqli) )
	{
	  $error++;
	  print $db->error();
	}
      
      if ( $error == 0 )
	{
	  $db->commit();
	}
      else
	{
	  $db->rollback();
	  print $db->error();
	}     
      
      $i++;
    }
  $db->free($resql);
}
else
{
  $error++;
}








$db->close();
?>
