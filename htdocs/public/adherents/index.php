<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

print_titre("Gestion des adhesions a l'association");

print '<table class="border" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td colspan=2>Les menus ci-contre correspondent a:</td></tr>';
print '<tr><td>-Inscription :</td><td> Formulaires d\'inscription pour les non-adherents</td></tr>';
print '<tr><td>-Edition de sa fiche :</td><td> Permet d\'editer sa fiche d\'adherent</td></tr>';
print '<tr><td>-Liste des adherents :</td><td> Permet de voir la liste des adherents (reserve aux adherents)</td></tr>';



print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
