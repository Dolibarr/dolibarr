<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

// positionne la variable pour le test d'affichage de l'icone

$main_use_mailman = MAIN_USE_MAILMAN;
$main_use_glasnost = MAIN_USE_GLASNOST;
$main_use_glasnost_auto = MAIN_USE_GLASNOST_AUTO;
$main_use_spip = MAIN_USE_SPIP;
$main_use_spip_auto = MAIN_USE_SPIP_AUTO;


if ($action == 'set')
{
  $sql = "REPLACE INTO llx_const SET name = '$name', value='".$value."', visible=0";

  if ($db->query($sql))
    {
      Header("Location: adherent.php");
    }
}

if ($action == 'unset')
{
  $sql = "DELETE FROM llx_const WHERE name = '$name'";

  if ($db->query($sql))
    {
      Header("Location: adherent.php");
    }
}

llxHeader();

/*
 * PDF
 */

print_titre("Gestion des adhérents : Configurations de parametres");

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

if ($main_use_mailman == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if ($main_use_mailman == 0)
{
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_MAILMAN">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_MAILMAN">désactiver</a>';
}
print '</td></tr>';

print '<tr><td>Glasnost</td><td>Système de vote en ligne';
print '</td><td align="center">';

if ($main_use_glasnost == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if ($main_use_glasnost == 0)
{
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_GLASNOST">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_GLASNOST">désactiver</a>';
}
print '</td></tr>';


print '<tr><td>Glasnost Auto</td><td>Inscription automatique dans Glasnost';
print '</td><td align="center">';

if (MAIN_USE_GLASNOST_AUTO == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if (MAIN_USE_GLASNOST == 0)
{
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_GLASNOST_AUTO">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_GLASNOST_AUTO">désactiver</a>';
}
print '</td></tr>';




print '<tr><td>Spip</td><td>Système de publication';
print '</td><td align="center">';

if ($main_use_spip == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if ($main_use_spip == 0)
{
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_SPIP">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_SPIP">désactiver</a>';
}
print '</td></tr>';


print '<tr><td>Spip Auto</td><td>Inscription automatique dans SPIP';
print '</td><td align="center">';

if ($main_use_spip_auto == 1)
{
  print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tick.png" border="0"></a>';
}
else
{
  print "&nbsp;";
}

print "</td><td>\n";

if ($main_use_spip_auto == 0)
{
  print '<a href="'.$PHP_SELF.'?action=set&value=1&name=MAIN_USE_SPIP_AUTO">activer</a>';
}
else
{
  print '<a href="'.$PHP_SELF.'?action=unset&value=0&name=MAIN_USE_SPIP_AUTO">désactiver</a>';
}
print '</td></tr>';



print '</table>';



$db->close();
llxFooter();
?>
