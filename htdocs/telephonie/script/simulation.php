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
 */

require ("../../master.inc.php");

$error = 0;

$datetime = time();

$date = strftime("%d %h %Y %Hh %Mm %S",$datetime);


$sql = " INSERT INTO ".MAIN_DB_PREFIX."telephonie_simul";
$sql .= " (description) VALUES (";
$sql .= " 'Simulation du $date')";

if ( $db->query($sql) )
{
  $simid = $db->last_insert_id();
}
print "Simulation : $simid\n";
/*******************************************************************************
 *
 *
 */
$sql = "SELECT ligne, date, numero, duree ";
$sql .= " ,fourn_montant";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
  
if ( $db->query($sql) )
{
  $row = array();
  $nums = $db->num_rows();
  $i = 0;

  while($i < $nums)
    {
      $row[$i] = $db->fetch_row();

      print ".";
      $i++;
    }
  $db->free();
}
print "\n";

for ($i = 0 ; $i < sizeof($row) ; $i++)
{
  $numero = $row[$i][2];
  $duree = $row[$i][3];
  $cout_achat = $row[$i][4];

  if (substr($numero,0,2) == '00') /* International */
    {
      $cout_vente = $cout_achat * 2;
    }     
  elseif (substr($numero,0,2) == '06') /* Telephones Mobiles */
    {	
      $cout_vente = ereg_replace(",",".",$cout_achat + 0.04);
    }
  else
    {
      $cout_vente = ereg_replace(",",".",($duree * 0.01)/60 + 0.09);
    }	  
  
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_simul_comm";
  $sql .= "(fk_simulation, ligne, date, numero, duree, cout_achat, cout_vente)";
  $sql .=" VALUES ('$simid','".$row[$i][0]."'";
  $sql .=" ,'".$row[$i][1]."'";
  $sql .=" ,'".$row[$i][2]."'";
  $sql .=" ,'".$row[$i][3]."'";
  $sql .=" ,'".$row[$i][4]."'";
  $sql .=" ,'".$cout_vente."'";
  $sql .= " )";

  if (! $db->query($sql) )
    {
      print "Error";
      exit ;
    }
}

/*
 *
 *
 */

$sql = "SELECT fk_simulation, sum(cout_achat), sum(cout_vente),count(*)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_simul_comm";
$sql .= " GROUP BY  fk_simulation";
if ( $db->query($sql) )
{
  $nums = $db->num_rows();
  $i = 0;

  while($i < $nums)
    {
      $row = $db->fetch_row();

      print $row[0]." ".round($row[1],2);
      print "\t".round($row[2],2);
      print "\t".round(($row[2]-$row[1])/$row[1]*100,2)." %";
      print "\t".round($row[3],2);
      print "\t".round($row[1]/$row[3],2);
      print "\t".round($row[2]/$row[3],2)."\n";

      $i++;
    }
  $db->free();
}
print "\n";


?>
