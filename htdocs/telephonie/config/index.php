<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
    \file       htdocs/telephonie/config/index.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

if ($_GET["action"] == "set")
{
  dolibarr_set_const($db, $_GET["name"], $_GET["value"], $type='chaine');
  Header("Location: index.php");
}

/*
 *
 *
 *
 */
llxHeader('','Téléphonie -  Configuration');
print_titre("Configuration du module de Téléphonie");

print "<br>";

/*
 *
 *
 */
print_titre("Emails");
print '<form method="post" action="propale.php?action=nbprod">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td>';
print "</tr>\n";
print '<tr class="pair"><td>';
print 'Nombre de ligne produits</td><td align="center">';
print '<input size="3" type="text" name="value" value="'.TELEPHONIE_EMAIL_FACTURATION_EMAIL.'">';

print '</td><td><input type="submit" value="changer"></td></tr>';


print '<tr class="pair"><td>';
print 'Email facturation FROM</td><td align="center">';
print TELEPHONIE_EMAIL_FACTURATION_EMAIL;
print '</td><td>TELEPHONIE_EMAIL_FACTURATION_EMAIL</td></tr>';


print '<tr class="impair"><td>';
print 'Email facturation BCC</td><td align="center">';
print TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;
print '</td><td>TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC</td></tr>';


print '<tr class="pair"><td>Module ADSL</td>';
print '<td align="center">';
if (TELEPHONIE_MODULE_ADSL == 1)
{
  print 'oui</td><td><a href="index.php?action=set&amp;name=TELEPHONIE_MODULE_ADSL&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=set&amp;name=TELEPHONIE_MODULE_ADSL&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_ADSL</td></tr>';


print '<tr class="pair"><td>Module SIMULATION</td>';
print '<td align="center">';
if (TELEPHONIE_MODULE_SIMULATION == 1)
{
  print 'oui</td><td><a href="index.php?action=set&amp;name=TELEPHONIE_MODULE_SIMULATION&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=set&amp;name=TELEPHONIE_MODULE_SIMULATION&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_SIMULATION</td></tr>';


print '</table></form>';


$db->close();

llxFooter();
?>
