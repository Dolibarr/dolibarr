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
 * Synchronise les id dans la tables des comm
 * scrip temporaire le temps de ré-écrire la facturation
 *
 */
require ("../../master.inc.php");

$error = 0;

for ($i = 0 ; $i < 50 ; $i++)
{

  $sql = "SELECT fk_ligne, ligne";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " WHERE fk_ligne is null";
  $sql .= " LIMIT 1";

  if ($db->query($sql))
    {
      $num = $db->num_rows();
  
      if ($num)
	{
	  $row = $db->fetch_row();	
	  $ligne = $row[1];
	}
      $db->free(); 
    }

  $sql = "SELECT rowid, ligne";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
  $sql .= " WHERE ligne =".$ligne;

  if ($db->query($sql))
    {
      $num = $db->num_rows();
  
      if ($num)
	{
	  $row = $db->fetch_row();
	  $id = $row[0];
	}
      $db->free();
    }

  $sql = "UPDATE ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " SET fk_ligne = ".$id;
  $sql .= " WHERE ligne =".$ligne;

  if ($db->query($sql))
    {
      print "$ligne -> $id -> "; 
      print $db->affected_rows()."\n";
    }
}
?>
