<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
require("../contact.class.php3");

llxHeader();

?>
<style type="text/css">
p.code { background: #cfcfcf }
</style>

<?PHP


print_titre("Liste des fiches d'intervention");



$file = $conf->fichinter->outputdir . "/$fichinter_ref/$fichinter_ref.tex";

print $file .'<p class="code">';

$fcontents = file ($file);

if (file_exists($file)) {    


  while (list ($line_num, $line) = each ($fcontents)) {
    echo htmlspecialchars ($line), "<br>\n";
  }

  

// readfile($file);
}
print "</p>";


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
