<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \file       htdocs/comm/mailing/index.php
        \ingroup    commercial
        \brief      Page accueil de la zone mailing
        \version    $Revision$
*/
 
require("./pre.inc.php");

if ($user->societe_id > 0)
{
  accessforbidden();
}
	  
$langs->load("commercial");
$langs->load("orders");

llxHeader('','Mailing');

/*
 *
 */

print_titre("Espace mailing");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Dernières actions commerciales effectuées
 *
 */

$sql = "SELECT count(*), client";
$sql .= " FROM ".MAIN_DB_PREFIX."societe";
$sql .= " WHERE client in (1,2)";
$sql .= " GROUP BY client";



if ( $db->query($sql) ) 
{
  $num = $db->num_rows();

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Statistics").'</td></tr>';
  $i = 0;

  while ($i < $num ) 
    {
      $row = $db->fetch_row();
 
      $st[$row[1]] = $row[0];

      $i++;
    }
  
  $var = true;
  print "<tr $bc[$var]>".'<td>'.$langs->trans("Customers").'</td><td align="center">'.$st[1]."</td></tr>";
  $var=!$var;
  print "<tr $bc[$var]>".'<td>'.$langs->trans("Prospects").'</td><td align="center">'.$st[2]."</td></tr>";

  print "</table><br>";

  $db->free();
} 
else
{
  dolibarr_print_error($db);
}

/*
 *
 *
 */

/*
 *
 *
 */
print '</td><td valign="top" width="70%">';




/*
 * 
 *
 */

$sql = "SELECT m.rowid, m.titre, m.nbemail";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " LIMIT 10";
if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num > 0)
    { 
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">10 derniers mailings</td></tr>';
      $var = true;
      $i = 0;
      
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?id='.$obj->rowid.'">'.$obj->titre.'</a></td>';
	  print '<td>'.$obj->nbemail.'</td>';

	  $i++;
	}

      print "</table><br>";
    }
  $db->free();
} 
else
{
  dolibarr_print_error($db);
}



print '</td></tr>';
print '</table>';

$db->close();


// Affiche note legislation dans la langue
$htmlfile="../../langs/".$langs->defaultlang."/html/spam.html";
if (! is_readable($htmlfile)) {
    $htmlfile="../../langs/fr_FR/html/spam.html";
}
if (is_readable($htmlfile)) {
    print "<br>".$langs->trans("Note").":<br><hr>";
    include($htmlfile);
}
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
