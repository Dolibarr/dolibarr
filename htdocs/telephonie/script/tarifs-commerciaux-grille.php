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
 * Affecte aux contrats la grille de tarif spécifiaque a un
 * commercial
 *
 */
require ("../../master.inc.php");
$error = 0;

/*
 * Lecture des lignes
 *
 */
$comms = array();

$sql = "SELECT fk_grille, fk_commercial";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille_commerciaux";
  
if ($db->query($sql))
{
  while($row = $db->fetch_row($resql))
    {
      $comms[$row[0]] = $row[1];
    }
  $db->free($resql);
}

foreach ($comms as $key => $value)
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_contrat";
  $sql .= " SET grille_tarif = '".$key."'";
  $sql .= " WHERE fk_commercial_sign = '".$value."';";

  $resql = $db->query($sql);
  
  if (!$resql)
    {
      print "Erreur";
    }
}
?>
