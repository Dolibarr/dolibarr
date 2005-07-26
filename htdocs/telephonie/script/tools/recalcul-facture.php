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

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie-tarif.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/communication.class.php");


$lines = array ('0297754500','0297740033','0297753052','0297754791','0297754790','0297754767','0297754766','0297753788');

$facture = 1687;

$factels = array();

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."telephonie_facture";
$sql .= " WHERE fk_facture =".$facture;

$resql = $db->query($sql);
  
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  $row = $db->fetch_row($resql);
  print "Factures téléphoniques : $row[0]";
  array_push($factels, $row[0]);
  $i++;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      print ", $row[0]";
      array_push($factels, $row[0]);
      $i++;
    }
  $db->free($resql);
}
else
{
  $error++;
}
print "\n";

$total = 0;

foreach ($factels as $factel)
{

  $sql = "SELECT sum(cout_vente) FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " WHERE fk_telephonie_facture =".$factel;
  
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $row = $db->fetch_row($resql);
      $total += $row[0];
      print "Facture $factel - $row[0]\n";
      $db->free($resql);
    }
  else
    {
      $error++;
    } 
}

print "Total : $total\n";

// Analyse

reset($factels);
foreach ($factels as $factel)
{

  $sql = "SELECT distinct(dest) FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " WHERE fk_telephonie_facture =".$factel;
  $sql .= " AND tarif_vente_temp = 0.18";
  
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $row = $db->fetch_row($resql);
      print "Facture $factel - $row[0]\n";
      $db->free($resql);
    }
  else
    {
      $error++;
    } 
}

//

$totale = 0;
$totald = 0;
reset($factels);
foreach ($factels as $factel)
{

  $sql = "SELECT count(*), sum(cout_vente), sum(duree) FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  $sql .= " WHERE fk_telephonie_facture =".$factel;
  $sql .= " AND tarif_vente_temp = 0.18";
  
  $resql = $db->query($sql);
  
  if ( $resql )
    {
      $row = $db->fetch_row($resql);
      $totale += $row[1];
      $totald += $row[2];
      print "Facture $factel - $row[0] - $row[1] - $row[2]\n";
      $db->free($resql);
    }
  else
    {
      $error++;
    } 
}
print "Total : $totale duree $totald\n";

$coutreel = $totald * 0.015 / 60;

print "Cout reel à 0.015 : $coutreel\n";

$reel = $total - $totale + $coutreel;

print "Nouvelle facture = $reel\n";

$db->close();


?>
