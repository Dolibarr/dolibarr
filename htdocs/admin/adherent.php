<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}

$db = new Db();

// positionne la variable pour le test d'affichage de l'icone

$adh_use_mailman = ADH_USE_MAILMAN;


if ($action == 'setmailman')
{
  $sql = "REPLACE INTO llx_const SET name = 'ADH_USE_MAILMAN', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      $adh_use_mailman = 1;
    }
}

if ($action == 'unsetmailman')
{
  $sql = "DELETE FROM llx_const WHERE name = 'ADH_USE_MAILMAN'";

  if ($db->query($sql))
    {
      $adh_use_mailman = 0;
    }
}


/*
 * PDF
 */

print_titre("Gestion des adhérents");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre"><td colspan="4">Modules externes</td></tr>';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Info</td>';
print '<td align="center">Activé</td>';
print '<td>&nbsp;</td>';
print "</TR>\n";


print '<tr><td>Mailman</td><td>Système de mailing listes';

print '</td><td align="center">';

if ($adh_use_mailman == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if ($adh_use_mailman == 0)
{
  print '<a href="'.$PHP_SELF.'?action=setmailman&value=1">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unsetmailman&value=0">désactiver</a>';
}
print '</td></tr>';


print '</table>';



$db->close();
llxFooter();
?>
