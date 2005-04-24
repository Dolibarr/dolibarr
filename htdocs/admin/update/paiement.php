<?php
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
    access_forbidden();


llxHeader();


print_titre("Vérifications et réparation des données de la base");


// Mise a jour des paiements (lien n-n paiements factures)
print '<br>';
print "<b>Mise a jour des paiments (lien n-n paiements-factures)</b><br>\n";

$sql = "SELECT p.rowid, p.fk_facture, p.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
$sql .= " WHERE p.fk_facture > 0";
$result = $db->query($sql);

if ($result) 
{
  $i = 0;
  $row = array();
  $num = $db->num_rows();
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($result);
      $row[$i][0] = $obj->rowid ;
      $row[$i][1] = $obj->fk_facture;
      $row[$i][2] = $obj->amount;
      $i++;
    }
}

if ($num)
{
    print "$num paiement(s) à mettre à jour<br>\n";
    if ($db->begin())
    {
      $res = 0;
      for ($i = 0 ; $i < sizeof($row) ; $i++)
        {
          $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
          $sql .= " VALUES (".$row[$i][1].",".$row[$i][0].",".$row[$i][2].")";
          
          $res += $db->query($sql);
          
          $sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_facture = 0 WHERE rowid = ".$row[$i][0];
          
          $res += $db->query($sql);
    
          print "Mise a jour paiement(s) ".$row[$i]."<br>\n";
        } 
    }
    
    if ($res == (2 * sizeof($row)))
    {
      $db->commit();
      print "Mise à jour réussie";
    }
    else
    {
      $db->rollback();
      print "La mise à jour à échouée";
    }
}
else
{
    print "Pas de paiements orhpelins à mettre à jour.<br>\n";
}    




$db->close();

llxFooter();
?>
