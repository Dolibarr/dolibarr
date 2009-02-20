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
 * Recherche les lignes sans traffic
 *
 */
print "Mem : ".memory_get_usage() ."\n";
require ("../../master.inc.php");

require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");

$error = 0;

$datetime = time();
$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);

$month = strftime("%m", $datetime);
$year = strftime("%Y", $datetime);

if ($month == 1)
{
  $month = "12";
  $year = $year - 1;
}
else
{
  $month = substr("00".($month - 1), -2) ;
}


/*
 * Lecture des lignes
 *
 */
$lignes = array();
$lignes_traffic = array();

$sql = "SELECT rowid, ligne FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
$sql .= " WHERE statut = 3";
  
$resql = $db->query($sql);

if ($resql)
{
  $nums = $db->num_rows($resql);
  $i = 0;
  while($i < $nums)
    {
      $row = $db->fetch_row($resql);
      $lignes[$i] = $row;
      $i++;
    }
  $db->free($resql);
}
dol_syslog(sizeof($lignes)." lignes actives");
/*
 * Lecture des comms
 *
 */
$sql = "SELECT distinct(ligne) FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
$sql .= " WHERE date_format(date,'%Y%m') = ".$year.substr("00".$month, -2);
$resql = $db->query($sql);

if ($resql)
{
  $nums = $db->num_rows($resql);
  $i = 0;
  while($i < $nums)
    {
      $row = $db->fetch_row($resql);
      array_push($lignes_traffic, $row[0]);
      $i++;
    }
  $db->free($resql);
}
dol_syslog(sizeof($lignes_traffic)." lignes avec traffic");

/*
 * Croisement des données
 *
 */
$j = 0;
for ($i = 0 ; $i < sizeof($lignes) ; $i++)
{
  if (in_array($lignes[$i][1], $lignes_traffic))
    {

    }
  else
    {
      $j++;
      print "$j Pas de traffic en $month/$year sur ".$lignes[$i][1]."\n";
    }
}
?>
