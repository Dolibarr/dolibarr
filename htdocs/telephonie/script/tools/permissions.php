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
 * Positionne les permissions sur les societes
 */

/****************************************************************
 * ATTENTION ce script est un script personnel à NE PAS UTILISER
 * sur vos bases sans modification, il est distribué à titre
 * d'exemple uniquement !
 ****************************************************************/
require ("../../../master.inc.php");

$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe";
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      
      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].", 1,1,1,1)";
      $resqlu = $db->query($sqlu);

      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].", 9,1,1,1)";
      $resqlu = $db->query($sqlu);

      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].", 5,1,1,1)";
      $resqlu = $db->query($sqlu);

      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",16,1,1,1)";
      $resqlu = $db->query($sqlu);

      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",18,1,0,0)";
      $resqlu = $db->query($sqlu);

      $sqlu = "REPLACE INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",29,1,0,0)";
      $resqlu = $db->query($sqlu);
    }
  $db->free($resql);
}
else
{
  $error++;
}

/* Speciaux */
$sql = "SELECT fk_client_comm, fk_commercial_sign, fk_commercial_suiv FROM ".MAIN_DB_PREFIX."telephonie_contrat";
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {      
      $sqlu = "INSERT INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",".$row[1].",1,0,0)";
      $resqlu = $db->query($sqlu);

      $sqlu = "INSERT INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",".$row[2].",1,0,0)";
      $resqlu = $db->query($sqlu);
    }
  $db->free($resql);
}
else
{
  $error++;
}

/* Speciaux */
$sql = "SELECT fk_client_comm, fk_commercial_sign ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat ";
$sql .= ", llx_telephonie_distributeur_commerciaux as c";
$sql .= " WHERE fk_commercial_sign = c.fk_user";
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {      
      $sqlu = "INSERT INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",18,1,1,1)";
      $resqlu = $db->query($sqlu);
    }
  $db->free($resql);
}
else
{
  print $db->error();
}

/* Speciaux */
$sql = "SELECT fk_client_comm, fk_commercial_suiv ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat ";
$sql .= ", llx_telephonie_distributeur_commerciaux as c";
$sql .= " WHERE fk_commercial_suiv = c.fk_user";
$resql = $db->query($sql);
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {      
      $sqlu = "INSERT INTO llx_societe_perms";
      $sqlu .= " (fk_soc, fk_user, pread, pwrite, pperms) ";
      $sqlu .= " VALUES (".$row[0].",18,1,1,1)";
      $resqlu = $db->query($sqlu);
    }
  $db->free($resql);
}
else
{
  print $db->error();
}

$db->close();
?>
