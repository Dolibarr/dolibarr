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
 * Generation des graphiques sur les lignes
 *
 *
 */
require ("../../master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/stats/ProcessGraphLignes.class.php");

$graph_all = 0;

//loop through our arguments and see what the user selected
for ($i = 1; $i < sizeof($GLOBALS["argv"]); $i++)
{
  switch($GLOBALS["argv"][$i])
    {
    case "-v":
    case "--version":
      echo  $GLOBALS['argv'][0]." $Revision$\n";
      exit;
      break;
    case "--all":
      $graph_all = 1;
      break;
    }
}

print strftime("%H:%M:%S",time())."\n";

$datetime = time();
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

$sql = "SELECT distinct(fk_ligne)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
if ($graph_all == 0)
{
  $sql .= " WHERE date_format(date,'%m%Y') = '".$month.$year."'";
}

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print "$num lignes\n";

  while ($i < $num)
    {
      print substr("0000".($i+1), -4) . "/".substr("0000".$num, -4)."\n";
      $row = $db->fetch_row($resql);

      $gr = new ProcessGraphLignes($db);
      $gr->go($row[0]);

      $i++;
    }
}
?>
