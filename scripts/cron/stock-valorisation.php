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
 * Calcul la valorisation du stock
 *
 */
require ("../../htdocs/master.inc.php");

$verbose = 0;

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
  if ($argv[$i] == "-v")
    {
      $verbose = 1;
    }
  if ($argv[$i] == "-vv")
    {
      $verbose = 2;
    }
  if ($argv[$i] == "-vvv")
    {
      $verbose = 3;
    }
}
/*
 *
 *
 */
$sql  = "SELECT e.rowid as ref, sum(ps.reel * p.price) as valo";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,".MAIN_DB_PREFIX."product_stock as ps,".MAIN_DB_PREFIX."product as p";
$sql .= " WHERE ps.fk_entrepot = e.rowid AND ps.fk_product = p.rowid";
$sql .= " GROUP BY e.rowid";

$resql = $db->query($sql) ;

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."entrepot_valorisation";
      $sqli .= " VALUES (now(),$row[0],$row[1])";
     
      $resqli = $db->query($sqli);
    }
  $db->free($resql);
}
else
{
  print $sql;
}

?>
