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
 *
 * Ce script se veut plus un squelette pour effectuer des opérations sur la base
 * qu'un réel scrip de production.
 *
 * Recalcul le montant d'une facture lors d'une erreur de tarif
 *
 */

require ("../../../master.inc.php");

$af = 0;

$source = 5;
$dest = 6;

$sql = "SELECT  fk_tarif, temporel, fixe";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_montant";
$sql .= " WHERE fk_tarif_desc=$source";

$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  $sqlu = "DELETE FROM ."MAIN_DB_PREFIX."telephonie_tarif_montant";
  $sqlu .= " WHERE fk_tarif_desc = $dest;";
  
  $resqlu = $db->query($sqlu);

  print "$num tarifs trouvés\n";

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_montant";
      $sqli .= " ( fk_tarif_desc, fk_tarif, temporel, fixe, fk_user,tms)";
      $sqli .= " VALUES ('$dest','$row[0]','$row[1]','$row[2]','1',now())";

      $resqli = $db->query($sqli);

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
