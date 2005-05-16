<?php
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
 * Generation des graphiques clients
 *
 *
 */
require "../../master.inc.php";


$sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_stats_destination";

if ($db->query($sql))
{

}

$sql = "SELECT dest, count(cout_vente), sum(cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " GROUP BY dest";

$resql = $db->query($sql);

$dest = array();

if ($resql)
{
  $i = 0;
  $num = $db->num_rows($resql);

  while ($i < $num)
    {

      $row = $db->fetch_row();
      $dest[$i] = $row;
      $i++;
    }
  $db->free($resql);
}
else
{
  print $db->error();
}

for ($i = 0 ; $i < $num ; $i++)
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_stats_destination";
  $sql .= " ( destination, nbappels, ca)";
  $sql .= " VALUES ('".$dest[$i][0]."'";
  $sql .= ",".$dest[$i][1];
  $sql .= ",".$dest[$i][2].")";

  $resql = $db->query($sql);
}

?>
