<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php");

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

llxHeader();

/*
 * Interface de configuration de certaines variables de la partie adherent
 */

print_titre("Migration paiement multiple facture");


$sql = "SELECT p.rowid, p.fk_facture";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";

$result = $db->query($sql);

if ($result) 
{
  $i = 0;
  $row = array();
  $num = $db->num_rows();
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($result , $i);
      $row[$obj->rowid] = $obj->fk_facture;
      $i++;
    }

}

foreach($row as $key => $value)
{

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement)";
  $sql .= " VALUES ($value, $key)";

  print $db->query($sql);
} 



$db->close();
llxFooter();

?>
