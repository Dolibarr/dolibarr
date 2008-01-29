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
 *
 */
require("./pre.inc.php");

if ($user->societe_id > 0)
{
  $socid = $user->societe_id;
}



llxHeader("","","Lolix");

print_titre("Espace Lolix");

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td valign="top" width="50%">';
/*
 *
 *
 */
$sql = "SELECT count(*) as cc, o.active";
$sql .= " FROM lolixfr.offre as o ";
$sql .= " GROUP BY o.active DESC";

$active[1] = "Active";
$active[0] = "Inactive";
$active[-2] = "Inactive";
$active[-3] = "Inactive (désactivée robots)";
$active[-4] = "Inactive (???)";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0 )
    {
      print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">Offres</td></tr>';
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print "<tr $bc[$var]><td><a href=\"liste.php?catid=".$obj->rowid."\">".$active[$obj->active]."</a></td>";
	  print '<td align="center">'.$obj->cc."</td></tr>";
	  $i++;
	}
      print "</table><br>";
    }
}

/*
 *
 *
 */
print '</td><td valign="top" width="50%">';
/*
 *
 *
 */
$sql = "SELECT count(*) as cc, o.active";
$sql .= " FROM lolixfr.candidat as o ";
$sql .= " GROUP BY o.active DESC";

$active[1] = "Active";
$active[0] = "Inactive";
$active[-2] = "Inactive";
$active[-3] = "Inactive (désactivée robots)";
$active[-4] = "Inactive (???)";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0 )
    {
      print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">Candidats</td></tr>';
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print "<tr $bc[$var]><td><a href=\"liste.php?catid=".$obj->rowid."\">".$active[$obj->active]."</a></td>";
	  print '<td align="center">'.$obj->cc."</td></tr>";
	  $i++;
	}
      print "</table><br>";
    }
}


print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
