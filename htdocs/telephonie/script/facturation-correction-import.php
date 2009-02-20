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
 * Script de vérification avant facturation
 */

require ("../../master.inc.php");

$error = 0;

$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$sql .= " WHERE num in (1014,1015,1013,1016)";
$db->query($sql);
/*
 * Mauvais formatage de Bretagne Telecom
 *
 */
$sql = "SELECT num ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
$sql .= " WHERE num like '8%';";

$resql = $db->query($sql) ;
  
if ( $resql )
{
  while ($row = $db->fetch_row($resql))
    {
      $sqlu = "UPDATE ".MAIN_DB_PREFIX."telephonie_import_cdr";
      $sqlu .= " SET num = '0".$row[0]."' WHERE num = '".$row[0]."';";

      $resqlu = $db->query($sqlu) ;
    }            
  $db->free($resql);
} 
else
{
  dol_syslog("Erreur SQL");
}

?>
